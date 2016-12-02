<?php

namespace Pdx\DatasetBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Pdx\DatasetBundle\Utils;
use Pdx\UserBundle\Entity\User;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * DataSet
 *
 * @ORM\Table(name="dataset")
 * @ORM\Entity()
 * @Vich\Uploadable
 */
class DataSet implements \JsonSerializable
{

    const STATUS_MAPPED            = 'mapped';
    const STATUS_GEOCODED          = 'geocoded';
    const STATUS_GEOCODING_STARTED = 'started geocoding';
    const STATUS_GEOJSONED         = 'geojson';
    const STATUS_QUEUED            = 'queued';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="period", type="string", length=63, nullable=true)
     */
    private $period;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=127, nullable=true)
     */
    private $version;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="dataset_csv", fileNameProperty="csvName")
     *
     * @var File
     * @Assert\File(
     *     maxSize = "2048k",
     *     mimeTypes= {"text/plain", "text/csv", "application/csv", "text/excel", "application/excel"},
     *     mimeTypesMessage = "Upload een geldig CSV bestand"
     * )
     */
    private $csvFile;

    /**
     *
     * Assert\Regex(
     *     pattern="/^[a-zA-Z0-9\-\_\.]+$/",
     *     match=true,
     *     message="Spaces are not allowed!"
     * )
     * @ORM\Column(type="string", unique=true, length=255, nullable=true)
     * @var string
     */
    private $csvName;

    /**
     * @Vich\UploadableField(mapping="dataset_pdf", fileNameProperty="pdfName")
     *
     * @var File
     * @Assert\File(
     *     mimeTypes = {"application/pdf", "application/x-pdf"},
     *     mimeTypesMessage = "Upload een geldig PDF bestand"
     * )
     */
    private $pdfFile;

    /**
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $pdfName;

    /**
     * @var string
     *
     * @ORM\Column(name="credits", type="text", nullable=true)
     */
    private $credits;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=123, nullable=true)
     */
    private $website;

    /**
     * @var User $creator
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="Pdx\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     * })
     *
     **/
    private $creator;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @var string JSON blob of the mapping
     * @ORM\Column(type="text", nullable=true)
     */
    private $mapping;

    /**
     * @var string JSON blob of the mapping
     * @ORM\Column(type="text", nullable=true)
     */
    private $columns;

    /**
     * @var string The state of the workflow. Can be mapped|geocoded|geojson
     * @ORM\Column(type="string", length=31, nullable=true)
     */
    private $status;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $visible;

    /**
     * @return boolean
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param boolean $visible
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return DataSet
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set period
     *
     * @param string $period
     *
     * @return DataSet
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * Get period
     *
     * @return string
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return DataSet
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return DataSet
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set credits
     *
     * @param string $credits
     *
     * @return DataSet
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Get credits
     *
     * @return string
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * Set website
     *
     * @param string $website
     *
     * @return DataSet
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param User $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return File
     */
    public function getCsvFile()
    {
        return $this->csvFile;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $csvFile
     * @return $this
     */
    public function setCsvFile(File $csvFile = null)
    {
        $this->csvFile = $csvFile;

        if ($csvFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCsvName()
    {
        return $this->csvName;
    }

    /**
     * @param mixed $csvName
     */
    public function setCsvName($csvName)
    {
        $this->csvName = $csvName;
    }

    /**
     * @return string
     */
    public function getMapping()
    {
        return json_decode($this->mapping);
    }

    /**
     * @param string $mapping
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @return string
     */
    public function getColumns()
    {
        return json_decode($this->columns);
    }

    /**
     * @param array $columns
     */
    public function setColumns($columns)
    {
        foreach ($columns as &$column) {
            $column = Utils::slugify($column);
        }

        $this->columns = \GuzzleHttp\json_encode($columns);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function jsonSerialize()
    {
        $fields = ['id', 'title', 'csvName', 'mapping', 'columns'];
        $data = [];
        foreach ($fields as $field) {
            $method = 'get' . ucfirst($field);
            $data[$field] = $this->$method();
        }
        $data['numeric-filters'] = $this->getFilters('numeric');
        $data['string-filters'] = $this->getFilters('strings');

        return $data;
    }

    /**
     * Get the colimn names of the filter thingies
     *
     * @return mixed
     */
    public function getFilters($type)
    {
        if ($this->getMapping()) {
            $filters = $this->getMapping()->$type;

            $names = [];
            $columns = (array)$this->getColumns();
            foreach ($filters as $filter) {
                $names[] = $columns[$filter];
            }

            return $names;
        }
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getPdfFile()
    {
        return $this->pdfFile;
    }

    /**
     * @param mixed $pdfFile
     */
    public function setPdfFile($pdfFile)
    {
        $this->pdfFile = $pdfFile;
    }

    /**
     * @return string
     */
    public function getPdfName()
    {
        return $this->pdfName;
    }

    /**
     * @param string $pdfName
     */
    public function setPdfName($pdfName)
    {
        $this->pdfName = $pdfName;
    }


}

