<?php

namespace Pdx\Histograph;


use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use Monolog\Logger;


/**
 * Class ApiClient
 * For quering the Histograph API
 */
class ApiClient
{

    const API_TIMEOUT         = 10;
    const API_CONNECT_TIMEOUT = 10;
    const SEARCH_ENDPOINT     = '/search';
    const DATASET_ENDPOINT    = '/datasets';

    private $baseUri = 'https://api.histograph.io';

    /** @var  Client */
    private $guzzle;

    /**
     * @var \Monolog\Logger
     */
    private $logger = null;

    /** @var \Doctrine\Common\Cache\CacheProvider|null */
    private $cacheDriver;

    public function __construct(Logger $logger = null, CacheProvider $cacheDriver = null)
    {
        $this->guzzle = new Client([
            'base_uri'        => $this->baseUri,
            'timeout'         => self::API_TIMEOUT,
            'connect_timeout' => self::API_CONNECT_TIMEOUT,
            'allow_redirects' => false,
            'verify'          => false,
            'headers'         => [
                'User-Agent'     => 'testing/1.0',
                'Accept'         => 'application/json',
                'Accept-Charset' => 'utf-8',
                'Content-Type'   => 'application/json',
            ]
        ]);

        $this->logger = $logger;
        $this->cacheDriver = $cacheDriver;
    }

    public function isUp()
    {
        return $this->callApi($this->baseUri);
    }

    /**
     * Searches the API by uri
     *
     * @param string $uri
     * @return bool|ApiResponse
     */
    public function findByUri($uri)
    {
        $query = self::SEARCH_ENDPOINT . '?uri=' . urlencode($uri);

        return $this->getGeoJSONResponse($query);
    }

    /**
     * Searches the API by id
     *
     * @param string $id
     * @return bool|ApiResponse
     */
    public function findByHgId($id)
    {
        $query = self::SEARCH_ENDPOINT . '?id=' . $id;

        if ($this->cacheDriver->contains($query)) {
            if ($this->logger) {
                $this->logger->addDebug('Fetched from cache: "' . $query . '"');
            }

            return new ApiResponse($this->cacheDriver->fetch($query));
        }



        return $this->getGeoJSONResponse($query);
    }

    /**
     * Call the API to perform the search but check the cache first
     *
     * @param string $name
     * @param bool $quoted
     * @param bool $exact
     * @param null $pitType
     * @param bool $geometry
     * @return mixed
     */
    public function search($name, $quoted = true, $exact = true, $pitType = null, $geometry = false)
    {
        $name = $this->escapeChars($name);
        $query = $this->composeSearchQuery($name, $quoted, $exact, $pitType, $geometry);

        if ($this->cacheDriver->contains($query)) {
            if ($this->logger) {
                $this->logger->addDebug('Fetched from cache: "' . $name . '"');
            }

            return new ApiResponse($this->cacheDriver->fetch($query));
        }

        return $this->getGeoJSONResponse($query);
    }


    /**
     * Compose the search query
     *
     * @param string $name
     * @param bool $quoted
     * @param bool $exact
     * @param string $type
     * @param bool $geometry
     * @return string
     */
    private function composeSearchQuery($name, $quoted, $exact, $type, $geometry)
    {
        $query = self::SEARCH_ENDPOINT . '?q=';

        // name first
        if (true === $quoted) {
            $name = '"' . $name . '"';
        }
        $query .= $name;

        if (true === $exact) {
            $query .= '&exact=true';
        } else {
            $query .= '&exact=false';
        }

        // specific pit type
        if (null !== $type) {
            $query .= '&type=' . $type;
        }

        if (false === $geometry) {
            $query .= '&geometry=false';
        }

        return $query;
    }


    /**
     * Escape characters before they're send to the API
     *
     * @param $name
     * @return string
     */
    private function escapeChars($name)
    {
        $bad = ':/?#[]@!$&()*,+;=';

        return preg_replace('!\s+!', ' ', str_ireplace(str_split($bad), '', $name));
    }

    // todo implement liesIn
    private function addLiesIn($query)
    {

//        if ($this->liesIn && ! strlen($this->escapeChars($this->liesIn) > 0)) {
//            $query .= ', ' . $this->escapeChars($this->liesIn);
//            //$query .= '&related=hg:liesIn&related.q=' . $this->escapeChars($this->liesIn);
//        }
    }

    /**
     * List all available datasets from the API.
     * Returns a plain response
     *
     * @return mixed
     */
    public function listDatasets()
    {
        return $this->callApi(self::DATASET_ENDPOINT);
    }

    /**
     * Actually calls the API but only for calls returning a GeoJSON Response
     *
     * @param $uri
     * @return bool|ApiResponse
     */
    public function callApi($uri)
    {
        if ($this->logger) {
            $this->logger->addDebug('Calling histograph API with: "' . $uri . '"');
        }

        try {
            $response = $this->guzzle->get($uri);
            if ($response->getStatusCode() === 200) {
                return \GuzzleHttp\json_decode($response->getBody());
            } else {
                if ($this->logger) {
                    $this->logger->addError('Histograph API reported ' . $response->getReasonPhrase());
                }
                throw new \RuntimeException('Histograph API could not be searched.');
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($this->logger) {
                $this->logger->addError('Histograph API returned with the following error: ' . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Actually calls the API but only for calls returning a GeoJSON Response
     *
     * @param $uri
     * @return bool|ApiResponse
     */
    public function getGeoJSONResponse($uri)
    {
        if ($this->logger) {
            $this->logger->addDebug('Calling histograph API with: "' . $uri . '"');
        }

        try {
            $response = $this->guzzle->get($uri);
            if ($response->getStatusCode() === 200) {
                $geoJson = \GuzzleHttp\json_decode($response->getBody());
                $this->cacheDriver->save($uri, $geoJson);

                return new ApiResponse($geoJson);
            } else {
                if ($this->logger) {
                    $this->logger->addError('Histograph API reported ' . $response->getReasonPhrase());
                }
                throw new \RuntimeException('Histograph API could not be searched.');
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($this->logger) {
                $this->logger->addError('Histograph API returned with the following error: ' . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }


}
