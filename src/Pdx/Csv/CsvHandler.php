<?php

namespace Pdx\Csv;


use League\Csv\Reader;
use League\Csv\Writer;
use SplTempFileObject;

/**
 * Class CsvHandler
 * Reads the originally uploaded CSV files
 * Uses Flysystem to load the files and league CSV Reader to read them
 *
 */
class CsvHandler
{
    /** @var  \League\Flysystem\Filesystem $filesystem */
    protected $filesystem;

    /** @var array default delimiters to check */
    private $delimiters = [',', ';', "\t"];

    public function __construct(\League\Flysystem\Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function isUTF8($filename)
    {
        $file = $this->filesystem->read($filename);
        if (mb_check_encoding($file, 'UTF-8')) {
            // yup, all UTF-8
            return true;
        }

        return false;
    }

    /**
     *
     * @param string $filename
     * @return \League\Csv\Reader
     */
    public function read($filename)
    {
        $file = $this->filesystem->read($filename);

        // fix for weird CR characters that some people seem to use:
        // hope it's no longer needed
        /*
         * $fileContent = file_get_contents($file);
         *
         */
        if (strpos($file, "\r")) {
            $converted = preg_replace('~\r\n?~', "\n", $file);
            $reader = Reader::createFromString($converted);
        } else {
            $reader = Reader::createFromString($file);
        }
        //$reader = Reader::createFromString($file);

        $foundDelimiters = $reader->fetchDelimitersOccurrence($this->delimiters, 6);
        $reader->setDelimiter(key($foundDelimiters));

        return $reader;
    }

    /**
     * Get the header row
     *
     * @param $filename
     * @return mixed
     */
    public function getColumns($filename)
    {
        $reader = $this->read($filename);

        return $reader->fetchOne();
    }

    /**
     *
     * @param string $filename
     * @param bool $stripHeader
     * @param null $limit
     * @return mixed
     */
    public function getRows($filename, $stripHeader = true, $limit = null)
    {
        $reader = $this->read($filename);

        if (null === $limit) {
            $readerRows = $reader->setOffset(0)->fetchAll();
        } else {
            $readerRows = $reader->setOffset(0)->setLimit($limit)->fetchAll();
        }

        if (true === $stripHeader) {
            array_shift($readerRows);
        }

        return $readerRows;
    }

    /**
     * Converts the csv file to html table
     *
     * @param string $filename
     * @param int $limit
     * @return string
     */
    public function convertCsv2Html($filename, $limit = 100)
    {
        $reader = $this->read($filename);

        $reader->setInputEncoding('utf-8');
        $reader->setLimit($limit);

        return $reader->toHTML('table table-striped table-bordered table-hover');
    }

    /**
     * Save a csv file, with separate (manipulated) headers
     *
     * @param $filePath
     * @param array $data
     * @param array $headers
     * @return bool
     */
    public function saveFile($filePath, $data, $headers = [])
    {
        $writer = $this->writeData($data, $headers);
        if ($this->filesystem->has($filePath)) {
            return $this->filesystem->update($filePath, $writer);
        }

        return $this->filesystem->write($filePath, $writer);
    }

    /**
     * Create a new csv for
     *
     * @param $data
     * @param array $headers
     * @return mixed
     */
    public function writeData($data, $headers = [])
    {
        $writer = Writer::createFromFileObject(new SplTempFileObject());
        //$writer->setDelimiter(';');

        //$writer->setInputEncoding("utf-8");
        //$writer->setEnclosure('"');

        if (count($headers) > 0) {
            $writer->insertOne($headers);
        }
        $writer->insertAll($data);

        return $writer;
    }

    public function writeAndDownloadFile($filename, $data, $headers = [])
    {
        $writer = $this->writeData($data, $headers);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->output($filename);
    }

    /**
     * @return \League\Flysystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Return the geocoded file, there is still one
     *
     * @param string $originalFilename
     * @return string
     */
    public function getGeoCodedFilePath($originalFilename)
    {
        return '/geocoded/' . $originalFilename;
    }

    /**
     * Return (one of the) geoJSON file paths)
     *
     * @param string $originalFilename
     * @param string $prefix
     * @return string
     */
    public function getGeoJSONFilePath($originalFilename, $prefix)
    {
        return '/geojson/' . $prefix . '.' . $originalFilename;
    }

    /**
     * Removes the original file and all offspring (geocoded etc) that was created
     *
     * @param string $originalFilename
     * @return bool
     */
    public function removeFile($originalFilename)
    {
        $this->filesystem->delete($originalFilename);
        $this->filesystem->delete($this->getGeoCodedFilePath($originalFilename));

        if ($this->filesystem->has($this->getGeoJSONFilePath($originalFilename, 'panden'))) {
            $this->filesystem->delete($this->getGeoJSONFilePath($originalFilename, 'panden'));
        }
        if ($this->filesystem->has($this->getGeoJSONFilePath($originalFilename, 'straten'))) {
            $this->filesystem->delete($this->getGeoJSONFilePath($originalFilename, 'straten'));
        }
        if ($this->filesystem->has($this->getGeoJSONFilePath($originalFilename, 'gebuurten'))) {
            $this->filesystem->delete($this->getGeoJSONFilePath($originalFilename, 'gebuurten'));
        }
        if ($this->filesystem->has($this->getGeoJSONFilePath($originalFilename, 'wijken'))) {
            $this->filesystem->delete($this->getGeoJSONFilePath($originalFilename, 'wijken'));
        }

        return true;
    }

    /**
     * List a bunch of files in a dir
     *
     * @return mixed
     */
    public function listAvailableFiles($dir)
    {
        $files = [];
        $all = $this->filesystem->listContents($dir);
        foreach ($all as $file) {
            if ($file->type === 'file') {
                $files[] = $file;
            }
        }

        return $files;
    }

}
