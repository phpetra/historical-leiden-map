<?php

namespace AppBundle\Controller;


use Pdx\DatasetBundle\Entity\DataSet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig', [

        ]);
    }

    /**
     * @Route("/panden", name="panden")
     */
    public function allAction()
    {
        return $this->render('default/panden.html.twig', [

        ]);
    }

    /**
     * Lists all the available datsets for a certain HG Type option.
     * Defaults to all
     *
     * @Route("/kaarten", name="maps")
     */
    public function listMapsAction($option = 'all')
    {
        return $this->render('default/maps.html.twig', [
            'datasets' => $this->getDoctrine()
                ->getRepository($this->getEntityName())
                ->findViewableDatasets('d.title', 'ASC')
        ]);
    }

    /**
     * Display the maps that are available at the HGBuildings level
     *
     * @Route("/panden/{set}", name="map-buildings", requirements={"set": "\d+"})
     * @param int $set
     * @return Response
     */
    public function mapBuildingsAction($set)
    {
        return $this->render('default/map-buildings.html.twig', [
            'id' => $set
        ]);
    }

    /**
     * Display the map for a dataset at the HGBuildings level
     *
     * @Route("/straten/{set}", name="map-streets", requirements={"set": "\d+"})
     */
    public function mapStreetsAction($set)
    {
        return $this->render('default/map-streets.html.twig', [
            'id' => $set
        ]);
    }

    /**
     * Display a map at the Borough level
     *
     * @Route("/buurten/{set}", name="map-boroughs", requirements={"set": "\d+"})
     */
    public function mapBoroughsAction($set)
    {
        return $this->render('default/map-boroughs.html.twig', [
            'id' => $set
        ]);
    }

    /**
     * Display the maps that are available at the HGBuildings level
     *
     * @Route("/wijken/{set}", name="map-neighbourhoods", requirements={"set": "\d+"})
     */
    public function mapNeighbourhoodsAction($set)
    {
        return $this->render('default/map-neighbourhoods.html.twig', [
                'id' => $set
            ]
        );
    }

    /**
     * Returns the entityName, need for the data grid
     */
    protected function getEntityName()
    {
        return 'Pdx\DatasetBundle\Entity\DataSet';
    }

    /**
     * Donwload / view a pdf file
     *
     * @Route("/pdf/{file}", name="pdf-download")
     *
     * @param string $file
     * @return Response
     */
    public function pdfAction($file)
    {
        $filepath = '/' . $file;
        $fileContent = $this->get('elo_filesystem')->read($filepath);

        $response = new Response($fileContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Let's users download the geojson
     *
     * @Route("/download/{set}", name="download-geojson", requirements={"set": "\d+"})
     * @param $file
     * @param $type
     * @return Response
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function downloadBuildingGeoJSONAction($set)
    {
        $dataset = $this->getDoctrine()->getRepository(DataSet::class)->find($set);
        if (!$dataset) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'Sorry, die bestaat niet!'
            );

            return $this->redirect($this->generateUrl('maps'));
        }

        $filepath = '/geojson/building.' . $dataset->getCsvName();
        $filename = str_replace('.csv', '.json', $dataset->getCsvName());

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
