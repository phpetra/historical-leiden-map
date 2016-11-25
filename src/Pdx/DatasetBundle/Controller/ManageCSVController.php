<?php

namespace Pdx\DatasetBundle\Controller;


use Pdx\Csv\GeoJSONConverter;
use Pdx\DatasetBundle\Entity\DataSet;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ManageCsvController
 * @Route("/manage/csv")
 */
class ManageCSVController extends Controller
{

    /**
     * Find geometries with external source and
     *
     * @Route("/geocode/{file}/{datasetId}", name="manage-csv-geocode")
     * @Method("GET")
     *
     * @param $file
     * @param $datasetId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function geocodeAction($file, $datasetId)
    {
        $logger = $this->get('logger');
        $logger->addInfo('Offload to cli: geocoding for file ' . $file);


        if ($this->get('kernel')->getEnvironment() === 'prod') {
            $root = $this->getParameter('kernel.root_dir') . '/../bin/console';
            exec('SYMFONY_ENV=prod php ' . $root . ' pdx:geocode ' . $file . ' > /dev/null &');
        } else {
            exec('php ../bin/console pdx:geocode ' . $file . ' > /dev/null &');
        }

        $this->get('session')->getFlashBag()->add(
            'notice',
            'Geometrieen worden nu toegevoegd. Dit kan een paar minuten duren. Je krijgt een mailtje als het is afgerond.'
        );

        return $this->redirectToRoute('manage-dataset-index', ['id' => $datasetId]);
    }

    /**
     * Convert to geoJSON
     *
     * @Route("/geojson/{file}", name="manage-csv-geojson")
     * @Method("GET")
     *
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function generateGeoJSONAction($file)
    {
        /** @var GeoJSONConverter $coder */
        $coder = $this->get('pdx_csv.geojson');

        $dataSet = $this->getDoctrine()
            ->getRepository('PdxDatasetBundle:DataSet')
            ->findOneBy(['csvName' => $file]
            );
        $coder->setMapping($dataSet->getMapping());
        $coder->createGeoJSONFiles($file, false);

        $dataSet->setStatus(DataSet::STATUS_GEOJSONED);
        $em = $this->getDoctrine()->getManager();
        $em->persist($dataSet);
        $em->flush();


        $this->get('session')->getFlashBag()->set('notice', 'GeoJSON bestanden gemaakt!');

        return $this->redirectToRoute('manage-dataset-index', ['id' => $dataSet->getId()]);

    }

    /**
     * Let's users download the CSV
     *
     * @Route("/download/{file}/{type}", name="manage-csv-download")
     * @Method("GET")
     *
     * @param $file
     * @param $type
     * @return Response
     */
    public function dowloadAction($file, $type = 'csv')
    {
        switch ($type) {
            case 'building':
                $filepath = '/geojson/building.' . $file;
                $filename = str_replace('.csv', '.json', $file);
                break;
            case 'geocoded':
                $filepath = '/geocoded/' . $file;
                $filename = 'geocoded_' . $file;
                break;
            case 'csv':
            default:
                $filepath = '/' . $file;
                $filename = $file;
                break;
        }
        $fileContent = $this->get('elo_filesystem')->read($filepath);

        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
}
