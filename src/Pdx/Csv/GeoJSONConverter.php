<?php

namespace Pdx\Csv;


use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use Pdx\DatasetBundle\Utils;


/**
 * Class GeoJSONConverter
 * Converts a geocoded csv file to the GeoJSON files necessary for displaying
 */
class GeoJSONConverter
{
    /**
     * @var CsvHandler
     */
    private $csvHandler;

    /**
     * @var \stdClass That holds all the important mapping info
     */
    private $mapping;

    public function __construct(CsvHandler $csvHandler)
    {
        $this->csvHandler = $csvHandler;
    }

    /**
     * @param \stdClass $mapping
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     *
     * Converts the panden column
     *
     * @param string $filename The original filename
     * @param int $geometryKey
     * @param bool $useCachedVersion
     * @return int
     */
    public function createBuildingGeoJson($filename, $geometryKey, $useCachedVersion = true)
    {
        return $this->convertToGeoJson($filename, 'building', $geometryKey, $useCachedVersion);
    }

    /**
     *
     * Converts the straten column
     *
     * @param string $filename The original filename
     * @param int $geometryKey
     * @param bool $useCachedVersion
     * @return int
     */
    public function createStreetGeoJson($filename, $geometryKey, $useCachedVersion = true)
    {
        return $this->convertToGeoJson($filename, 'straten', $geometryKey, $useCachedVersion);
    }

    /**
     *
     * Converts the wijken column
     *
     * @param string $filename
     * @param int $geometryKey
     * @param bool $useCachedVersion
     * @return int
     */
    public function createNeighbourhoodGeoJson($filename, $geometryKey, $useCachedVersion = true)
    {
        return $this->convertToGeoJson($filename, 'wijken', $geometryKey, $useCachedVersion);
    }

    /**
     *
     * Converts the gebuurten column
     *
     * @param int $geometryKey
     * @param string $filename
     * @param bool $useCachedVersion
     * @return int
     */
    public function createBoroughGeoJson($filename, $geometryKey, $useCachedVersion = true)
    {
        return $this->convertToGeoJson($filename, 'gebuurten', $geometryKey, $useCachedVersion);
    }

    /**
     * Creates all the geoJSON Files based on the set mappings
     *
     * @param string $originalFile
     * @param bool $useCachedVersion
     * @return bool
     */
    public function createGeoJSONFiles($originalFile, $useCachedVersion = true)
    {
        // get columns form the GeoCoded file, to find the positions of the _geo columns
        $geocodedCopy = $this->csvHandler->getGeoCodedFilePath($originalFile);
        $columns = array_flip($this->csvHandler->getColumns($geocodedCopy));

        if ($this->mapping->building !== null) {
            $this->createBuildingGeoJson($originalFile, $columns['building_geometry'], $useCachedVersion);
        }

        //if ($this->mapping->street !== null) {
            //$this->convertColumnsToGeoJSONPerHgType($originalFile, 'street');
            //$this->createStreetGeoJson($originalFile, $columns['street_geometry'], $useCachedVersion);
        //}
        if ($this->mapping->borough !== null) {
            $this->convertColumnsToGeoJSONPerHgType($originalFile, 'borough');
        }
        if ($this->mapping->neighbourhood !== null) {
            $this->convertColumnsToGeoJSONPerHgType($originalFile, 'neighbourhood');
        }

        return true;
    }

    /**
     * From all the columns, keep just the one that hold properties
     *
     * @param $headers
     * @return array
     */
    private function getPropertiesKeys($headers)
    {
        // remove at least the geomerty ones
        foreach ($headers as $key => $value) {
            if (strstr($value, '_geometry')) {
                unset($headers[$key]);
            }
        }
//        if ($this->mapping->building !== null) {
//            unset($headers[$this->mapping->building]);
//        }
//        if ($this->mapping->street !== null) {
//            unset($headers[$this->mapping->street]);
//        }
//        if ($this->mapping->borough !== null) {
//            unset($headers[$this->mapping->borough]);
//        }
//        if ($this->mapping->neighbourhood !== null) {
//            unset($headers[$this->mapping->neighbourhood]);
//        }

        return array_keys($headers);
    }

    /**
     * Converts the CSV to geoJSON based on the supplied mapping
     *
     * @param string $originalFile
     * @param string $filePrefix
     * @param integer $geometryKey
     * @param bool $useCachedVersion
     * @return string Name of the created geojson file
     * @throws \Exception
     */
    private function convertToGeoJson($originalFile, $filePrefix, $geometryKey, $useCachedVersion = true)
    {
        $geocodedCopy = $this->csvHandler->getGeoCodedFilePath($originalFile);

        $geoJSONCopy = $this->csvHandler->getGeoJSONFilePath($originalFile, $filePrefix);
        if (true === $useCachedVersion && $this->csvHandler->getFilesystem()->has($geoJSONCopy)) {
            return $geoJSONCopy;
        }

        $headers = $this->csvHandler->getColumns($geocodedCopy);
        foreach ($headers as &$header) {
            $header = Utils::slugify($header);
        }
        $rows = $this->csvHandler->getRows($geocodedCopy, true);

        $propKeys = $this->getPropertiesKeys($headers);

        $features = [];
        foreach ($rows as $row) {
            if ($row[$geometryKey] != 'null' && ! empty($row[$geometryKey])) {
                $features[] = $this->addGeoJsonFeature($headers, $row, $geometryKey, $propKeys);
            }
        }

        $this->saveGeoJSONFile($geoJSONCopy, $features);

        return $geoJSONCopy;
    }

    /**
     * Write the geoJOSN to a File (if it can be encoded properly)
     *
     * @param string $geoJSONCopy The filename to write
     * @param array $features Array of GeoJSON Features
     * @return bool
     * @throws \Exception
     */
    private function saveGeoJSONFile($geoJSONCopy, $features)
    {
        $collection = new \GeoJson\Feature\FeatureCollection($features);

        $encodedJSON = json_encode($collection, JSON_UNESCAPED_UNICODE);
        if (! $encodedJSON) {
            throw new \Exception('Could not convert to geoJSON. Probably beacuse the file is not proper UTF-8');
        }

        if ($this->csvHandler->getFilesystem()->has($geoJSONCopy)) {
            $this->csvHandler->getFilesystem()->update($geoJSONCopy, $encodedJSON);
        } else {
            $this->csvHandler->getFilesystem()->write($geoJSONCopy, $encodedJSON);
        }

        return $geoJSONCopy;
    }

    /**
     * Reads the geocoded CSV file and counts the unique values for a given column IN a specific HGType
     * and creates a new GeoJSON file for that HGType
     * For instance: count of the "bakers" in a neighbourhood, count of the "brewers" etc.
     *
     * @param string $originalFile
     * @param string $HGType
     * @return bool
     */
    public function convertColumnsToGeoJSONPerHgType($originalFile, $HGType)
    {
        $geocodedCopy = $this->csvHandler->getGeoCodedFilePath($originalFile);

        $headers = $this->csvHandler->getColumns($geocodedCopy);
        foreach ($headers as &$header) {
            $header = Utils::slugify($header);
        }
        $csvRows = $this->csvHandler->getRows($geocodedCopy);

        // get the columnKey that holds the geometry
        $geometryKey = array_flip($headers)[$HGType . '_geometry'];

        $HGTypeColumnKey = $this->mapping->$HGType;

        $stringFields = $this->mapping->strings;
        /// HIERONDER IS PER gekozen KOLOM, voor de aantallen tellen optie:

        $features = [];
        foreach ($stringFields as $columnKey) {
            $columnName = $headers[$columnKey];

            $features = $this->addAggregatesForStringFields(
                $features, $csvRows, $columnKey, $columnName, $HGTypeColumnKey, $geometryKey
            );
        }

        $newFile = $this->csvHandler->getGeoJSONFilePath($originalFile, $HGType);

        return $this->saveGeoJSONFile($newFile, $features);
    }

    private function addAggregatesForStringFields(
        $features,
        $csvRows,
        $columnKeyToFilter,
        $columnName,
        $HGTypeColumnKey,
        $geometryKey
    ) {
        $counts = [];
        $geometries = [];
        // count all individual values of the csv
        foreach ($csvRows as $row) {
            // skipping everything where there is no geometry (we can't map that)
            if ($row[$geometryKey] != 'null' && ! empty($row[$geometryKey])) {
                if (! empty(trim($row[$columnKeyToFilter]))) { // strip empty values and spaces

                    // $counts['gebuurte1']['bakker']
                    if (isset($counts[$row[$HGTypeColumnKey]][$row[$columnKeyToFilter]])) {
                        $counts[$row[$HGTypeColumnKey]][$row[$columnKeyToFilter]] += 1;
                    } else {
                        $counts[$row[$HGTypeColumnKey]][$row[$columnKeyToFilter]] = 1;
                        // at the same time store the geometry for easy fetching later
                        $geometries[$row[$HGTypeColumnKey]] = $row[$geometryKey];
                    }
                }
            }
        }

        // and convert those to proper geoJSON Features
        foreach ($counts as $key => $count) { // values should be an array of individual "beroepen" names with their counts
            $geometry = \GeoJson\GeoJson::jsonUnserialize(json_decode($geometries[$key]));
            foreach ($count as $label => $number) {
                $props = [
                    'HGID' => $key,
                    $columnName => $label,
                    'aantal' => $number,
                ];
                $features[] = new Feature($geometry, $props);
            }
        }

        return $features;
    }

    /**
     * Read the geoJSOn for a specific HG Type
     *
     * @param string $originalFile
     * @param string $filePrefix
     * @return bool|false|null|string
     */
    public function readGeoJSONFile($originalFile, $filePrefix)
    {
        $geoJSONCopy = $this->csvHandler->getGeoJSONFilePath($originalFile, $filePrefix);
        if ($this->csvHandler->getFilesystem()->has($geoJSONCopy)) {
            return $this->csvHandler->getFilesystem()->read($geoJSONCopy);
        }

        return null;
    }

    /**
     * Adds the data of one row in the form of a GeoJSON Feature
     *
     * @param $headers
     * @param $row
     * @param $geometryKey
     * @param $propKeys array of indexed of the columns that have all the property info
     * @return Feature
     */
    private function addGeoJsonFeature($headers, $row, $geometryKey, $propKeys)
    {
        $geometry = \GeoJson\GeoJson::jsonUnserialize(json_decode($row[$geometryKey]));

        $properties = [];
        foreach ($propKeys as $prop) {
            $properties[$headers[$prop]] = $row[$prop];
        }

        return new Feature($geometry, $properties);
    }

}
