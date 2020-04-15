<?php

namespace MapSeven\Gpx\Domain\Model;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Flowpack\ElasticSearch\Annotations as ElasticSearch;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Asset;
use Neos\Utility\Arrays;
use MapSeven\Gpx\Service\GeoFunctionsService;
use MapSeven\Gpx\Service\MapboxService;
use MapSeven\Gpx\Service\UtilityService;

/**
 * Gpx Model
 *
 * @Flow\Entity
 * @ORM\InheritanceType("JOINED")
 */
class Gpx
{

    /**
     * @var string
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $name;

    /**
     * @var \DateTime
     * @ElasticSearch\Indexable
     * @ElasticSearch\Transform("Date", options={"format"="c"})
     */
    protected $created;

    /**
     * @var \DateTime
     * @ElasticSearch\Indexable
     * @ElasticSearch\Transform("Date", options={"format"="c"})
     */
    protected $updated;

    /**
     * @var \DateTime
     * @ElasticSearch\Indexable
     * @ElasticSearch\Transform("Date", options={"format"="c"})
     */
    protected $date;

    /**
     * @var string
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $type;

    /**
     * @var boolean
     * @ElasticSearch\Indexable
     */
    protected $active = true;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $author;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(type="geo_point")
     */
    protected $startCoords;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(type="geo_point")
     */
    protected $endCoords;

    /**
     * @var float
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     */
    protected $distance;

    /**
     * @var float
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     */
    protected $elapsedTime;

    /**
     * @var float
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     */
    protected $totalElevationGain;

    /**
     * @var float
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     */
    protected $totalElevationLoss;

    /**
     * @var float
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     */
    protected $elevHigh;

    /**
     * @var float
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     */
    protected $elevLow;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(type="geo_point")
     */
    protected $minCoords;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(type="geo_point")
     */
    protected $maxCoords;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $startCity;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $startState;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $startCountry;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $endCity;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $endState;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     * @ElasticSearch\Indexable
     * @ElasticSearch\Mapping(fields={@Elasticsearch\Mapping(index_name="keyword", type="keyword", ignore_above=256)})
     */
    protected $endCountry;

    /**
     * @var Asset
     * @ORM\OneToOne(cascade={"remove"})
     */
    protected $gpxFile;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $visualizationUrl;

    /**
     * @var Asset
     * @ORM\OneToOne(cascade={"remove"})
     */
    protected $staticImage;

    /**
     * @var string
     * @ORM\Column(nullable=true)
     */
    protected $geoJsonCompressed;

    /**
     * @var array
     * @Flow\Transient
     */
    protected $geoJson;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var GeoFunctionsService
     */
    protected $geoFunctionsService;

    /**
     * @Flow\Inject
     * @var MapboxService
     */
    protected $mapboxService;

    /**
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;


    /**
     * Constructs this gpx object
     */
    public function __construct()
    {
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return array
     */
    public function getStartCoords()
    {
        return $this->startCoords;
    }

    /**
     * @param array $startCoords
     */
    public function setStartCoords($startCoords)
    {
        $this->startCoords = [
            'lat' => $startCoords[0],
            'lon' => $startCoords[1]
        ];
    }

    /**
     * @return array
     */
    public function getEndCoords()
    {
        return $this->endCoords;
    }

    /**
     * @param array $endCoords
     */
    public function setEndCoords($endCoords)
    {
        $this->endCoords = [
            'lat' => $endCoords[0],
            'lon' => $endCoords[1]
        ];
    }

    /**
     * @return float
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * @param float $distance
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
    }

    /**
     * @return float
     */
    public function getElapsedTime()
    {
        return $this->elapsedTime;
    }

    /**
     * @param float $elapsedTime
     */
    public function setElapsedTime($elapsedTime)
    {
        $this->elapsedTime = $elapsedTime;
    }

    /**
     * @return float
     */
    public function getTotalElevationGain()
    {
        return $this->totalElevationGain;
    }

    /**
     * @param float $totalElevationGain
     */
    public function setTotalElevationGain($totalElevationGain)
    {
        $this->totalElevationGain = $totalElevationGain;
    }

    /**
     * @return float
     */
    public function getTotalElevationLoss()
    {
        return $this->totalElevationLoss;
    }

    /**
     * @param float $totalElevationLoss
     */
    public function setTotalElevationLoss($totalElevationLoss)
    {
        $this->totalElevationLoss = $totalElevationLoss;
    }

    /**
     * @return float
     */
    public function getElevHigh()
    {
        return $this->elevHigh;
    }

    /**
     * @param float $elevHigh
     */
    public function setElevHigh($elevHigh)
    {
        $this->elevHigh = $elevHigh;
    }

    /**
     * @return float
     */
    public function getElevLow()
    {
        return $this->elevLow;
    }

    /**
     * @param float $elevLow
     */
    public function setElevLow($elevLow)
    {
        $this->elevLow = $elevLow;
    }

    /**
     * @return array
     */
    public function getMinCoords()
    {
        return $this->minCoords;
    }

    /**
     * @param array $minCoords
     */
    public function setMinCoords($minCoords)
    {
        $this->minCoords = $minCoords;
    }

    /**
     * @return array
     */
    public function getMaxCoords()
    {
        return $this->maxCoords;
    }

    /**
     * @param array $maxCoords
     */
    public function setMaxCoords($maxCoords)
    {
        $this->maxCoords = $maxCoords;
    }

    /**
     * @return string
     */
    public function getStartCity()
    {
        return $this->startCity;
    }

    /**
     * @param string $startCity
     */
    public function setStartCity($startCity)
    {
        $this->startCity = $startCity;
    }

    /**
     * @return string
     */
    public function getStartState()
    {
        return $this->startState;
    }

    /**
     * @param string $startState
     */
    public function setStartState($startState)
    {
        $this->startState = $startState;
    }

    /**
     * @return string
     */
    public function getStartCountry()
    {
        return $this->startCountry;
    }

    /**
     * @param string $startCountry
     */
    public function setStartCountry($startCountry)
    {
        $this->startCountry = $startCountry;
    }

    /**
     * @return string
     */
    public function getEndCity()
    {
        return $this->endCity;
    }

    /**
     * @param string $endCity
     */
    public function setEndCity($endCity)
    {
        $this->endCity = $endCity;
    }

    /**
     * @return string
     */
    public function getEndState()
    {
        return $this->endState;
    }

    /**
     * @param string $endState
     */
    public function setEndState($endState)
    {
        $this->endState = $endState;
    }

    /**
     * @return string
     */
    public function getEndCountry()
    {
        return $this->endCountry;
    }

    /**
     * @param string $endCountry
     */
    public function setEndCountry($endCountry)
    {
        $this->endCountry = $endCountry;
    }

    /**
     * @param Asset $gpxFile
     */
    public function setGpxFile($gpxFile)
    {
        $this->gpxFile = $gpxFile;
    }

    /**
     * @return Asset
     */
    public function getGpxFile()
    {
        return $this->gpxFile;
    }

    /**
     * @return string
     */
    public function getVisualizationUrl()
    {
        return $this->visualizationUrl;
    }

    /**
     * @param string $visualizationUrl
     */
    public function setVisualizationUrl($visualizationUrl)
    {
        $this->visualizationUrl = $visualizationUrl;
    }

    /**
     * @return Asset
     */
    public function getStaticImage()
    {
        return $this->staticImage;
    }

    /**
     * @param string $style
     * @param string $size
     * @param string $stroke
     * @param string $strokeWidth
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function generateStaticImage($style = null, $size = null, $stroke = null, $strokeWidth = null)
    {
        $staticImage = $this->mapboxService->createStaticImage($this->geoJson, $this->gpxFile->getTitle(), $style,
            $size, $stroke, $strokeWidth);
        $this->setStaticImage($staticImage);
    }

    /**
     * @param Asset $staticImage
     */
    public function setStaticImage($staticImage)
    {
        $this->staticImage = $staticImage;
    }

    /**
     * Returns geoJson with optional tidy params
     *
     * @param integer $distance
     * @param integer $points
     * @return array
     */
    public function getGeoJson($distance = null, $points = null)
    {
        if (!empty($this->gpxFile)) {
            $this->generateGeoJson($distance, $points);
        }

        return $this->geoJson;
    }

    /**
     * Generate GeoJson with optional tidy params
     *
     * @param integer $distance
     * @param integer $points
     */
    public function generateGeoJson($distance = null, $points = null)
    {
        $geoJson = $this->geoFunctionsService->gpsbabel($this->gpxFile, $distance, $points);
        $this->setGeoJson($geoJson);
    }

    /**
     * @param array $geoJson
     */
    public function setGeoJson($geoJson)
    {
        $this->geoJson = $geoJson;
    }

    /**
     * @return string
     */
    public function getGeoJsonCompressed()
    {
        return $this->geoJsonCompressed;
    }

    /**
     * Generate GeoJson Compressed
     */
    public function generateGeoJsonCompressed()
    {
        $geoJsonCompressed = $this->geoFunctionsService->geobuf($this->geoJson);
        $this->setGeoJsonCompressed($geoJsonCompressed);
    }

    /**
     * @param string $geoJsonCompressed
     */
    public function setGeoJsonCompressed($geoJsonCompressed)
    {
        $this->geoJsonCompressed = $geoJsonCompressed;
    }
}
