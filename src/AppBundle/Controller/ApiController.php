<?php

namespace AppBundle\Controller;


use Pdx\DatasetBundle\Entity\DataSet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ApiController
 * Quick controller for internal API calls
 * @Route("/api")
 */
class ApiController extends Controller
{

    /**
     * Lists all the available geoJSON files for a certain mapping option.
     * Defaults to returning all datasets
     *
     * @Route("/geojson/{option}", name="api-geojson", requirements={"option":"building|street|borough|neighbourhood|all"}, defaults={"option":"all"})
     */
    public function listAction(Request $request, $option)
    {
        if ($option === 'all') {
            return new JsonResponse($this->getDataSets());
        }

        return new JsonResponse($this->filterDataSets($this->getDataSets(), $option));
    }

    /**
     * Serve up the geoJSON file for a dataSet AND a particular HGType
     *
     * @Route("/geojson/{option}/{id}", name="api-geojson-id", requirements={"option":
     *     "building|street|borough|neighbourhood", "id":"\d+"})
     */
    public function getGeoJSONForDatasetAndHGTypeAction(Request $request, $id, $option)
    {
        /** @var DataSet $set */
        $set = $this->getDoctrine()
            ->getRepository($this->getEntityName())
            ->find($id);

        if ($set) {
            $flySystem = $this->get('elo_filesystem');
            $filePath = '/geojson/' . $option . '.' . $set->getCsvName();

            if ($flySystem->has($filePath)) {
                $stream = $flySystem->readStream($filePath);

                return new StreamedResponse(function () use ($stream) {
                    fpassthru($stream);
                    exit();
                }, 200, [
                    'Content-Type'        => 'application/json',
                    'Content-Disposition' => 'inline; filename=' . $filePath,
                    'Content-Length'      => fstat($stream)['size'],
                ]);
            }

            return new Response('Not a proper file', 404);
        }
    }

    /**
     * Return data for one set
     *
     * @Route("/dataset/{id}", name="api-dataset-id")
     */
    public function getDataSetDetailsAction(Request $request, $id)
    {
        $set = $this->getDoctrine()
            ->getRepository($this->getEntityName())
            ->find($id);
        if (! $set) {
            return new JsonResponse('Set not found', 401);
        }

        return new JsonResponse($set);
    }

    /**
     * Fetch all data sets
     *
     * @return array
     */
    private function getDataSets()
    {
        return $this->getDoctrine()
            ->getRepository($this->getEntityName())
            //->findBy(['visible' => true])
            ->findAll();
    }

    /**
     * Check the mapping to see if the dataset has an geojson for the specified filter
     *
     * @param $dataSets
     * @param $type
     * @return mixed
     */
    private function filterDataSets($dataSets, $type)
    {
        foreach ($dataSets as $key => $set) {

            if ($set->getMapping() === null || $set->getMapping()->$type === null) {
                unset($dataSets[$key]);
            }
        }

        return $dataSets;
    }

    /**
     * Returns the entityName, need for the data grid
     */
    protected function getEntityName()
    {
        return 'Pdx\DatasetBundle\Entity\DataSet';
    }

}
