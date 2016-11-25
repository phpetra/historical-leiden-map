<?php

namespace Pdx\Csv;


use GuzzleHttp\Exception\RequestException;
use Pdx\Histograph\ApiClient;

/**
 * Class GeoCoder
 * Fetches Geometries from Histograph and creates a new geocoded.csv file bases on the uploaded csv
 */
class GeoCoder
{
    /**
     * @var CsvHandler
     */
    private $csvHandler;

    /** @var  ApiClient The Histograph client */
    private $apiClient;

    /**
     * @var \stdClass That holds all the important mapping info
     */
    private $mapping;

    public function __construct(CsvHandler $csvHandler, ApiClient $apiClient)
    {
        $this->csvHandler = $csvHandler;
        $this->apiClient = $apiClient;
    }

    /**
     * @param \stdClass $mapping
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * For each of the columns listed as containing HG identifiers, try to fetch the geometry from the API and add the
     * columns to the CSV.
     * Save a copy of the CSV file
     *
     * @param string $filename CSV file to handle
     * @param bool $useCachedVersion If a geocoded file already exists we can use it, or not
     * @return bool
     */
    public function addGeometriesToCsv($filename, $useCachedVersion = true)
    {
        $newFilename = $this->csvHandler->getGeoCodedFilePath($filename);

        if (true === $useCachedVersion && $this->csvHandler->getFilesystem()->has($newFilename)) {
            return $newFilename;
        }

        $headers = $this->csvHandler->getColumns($filename);
        $rows = $this->csvHandler->getRows($filename, true);


        // first add the column names
        $columnsWithHgIdentifiers = [];
        if ($this->mapping->building !== null) {
            $headers[] = 'building_geometry';
            $columnsWithHgIdentifiers[] = $this->mapping->building;
        }
        if ($this->mapping->street !== null) {
            $headers[] = 'street_geometry';
            $columnsWithHgIdentifiers[] = $this->mapping->street;
        }
        if ($this->mapping->borough !== null) {
            $headers[] = 'borough_geometry';
            $columnsWithHgIdentifiers[] = $this->mapping->borough;
        }
        if ($this->mapping->neighbourhood !== null) {
            $headers[] = 'neighbourhood_geometry';
            $columnsWithHgIdentifiers[] = $this->mapping->neighbourhood;
        }

        // fetch geometry per existing rows
        foreach ($rows as &$row) {
            foreach ($columnsWithHgIdentifiers as $columnNr) {
                $row[] = $this->getGeometryByIdFromHistograph($row[$columnNr]);
            }
        }

        $this->csvHandler->saveFile($newFilename, $rows, $headers);

        return true;
    }

    /**
     * Fetch the latest known geometry from the API
     *
     * @param integer $id
     * @return string
     */
    public function getGeometryByIdFromHistograph($id)
    {
        // fail silently when the id is empty (happens quite a few times
        if (strlen($id) < 3){
            return '';
        }

        // todo add some try catch for when API is not there, response is not an object
        $response = $this->apiClient->findByHgId($id);
        if ($response) {
            return \GuzzleHttp\json_encode($response->getGeometryForHgId($id));
        }

        return ''; // empty string to keep the geoJSON importer happy
    }

}
