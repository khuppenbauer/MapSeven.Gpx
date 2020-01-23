<?php
namespace MapSeven\Gpx\Domain\Model;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Flowpack\ElasticSearch\Annotations as ElasticSearch;

/**
 * Strava Model
 * 
 * @Flow\Entity
 * @ElasticSearch\Indexable("gpx", typeName="_doc")
 */
class Strava extends Gpx
{

    /**
     * @var integer
     * @ORM\Column(type="bigint")
     */
    protected $id;
    
    /**
     * @var float
     * @ElasticSearch\Indexable
     */
    protected $movingTime;
   
    /**
     * @var float
     * @ElasticSearch\Indexable
     */
    protected $averageSpeed;
    
    /**
     * @var float
     * @ElasticSearch\Indexable
     */
    protected $maxSpeed;
    
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $polyline;
    
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $summaryPolyline;
    
    /**
     * @var array
     * @ORM\Column(type="json_array")
     * @ElasticSearch\Indexable
     * @ElasticSearch\Transform(type="\MapSeven\Gpx\Indexer\Transform\SegmentTransformer")
     */
    protected $segmentEfforts;
    
    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    protected $photos;

    
    /**
     * @return integer
     */
    public function getId() 
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id) 
    {
        $this->id = $id;
    }
    
    /**
     * @return float
     */
    public function getMovingTime() 
    {
        return $this->movingTime;
    }

    /**
     * @param float $movingTime
     */
    public function setMovingTime($movingTime) 
    {
        $this->movingTime = $movingTime;
    }

    /**
     * @return float
     */
    public function getAverageSpeed() 
    {
        return $this->averageSpeed;
    }

    /**
     * @param float $averageSpeed
     */
    public function setAverageSpeed($averageSpeed) 
    {
        $this->averageSpeed = $averageSpeed;
    }

    /**
     * @return float
     */
    public function getMaxSpeed() 
    {
        return $this->maxSpeed;
    }

    /**
     * @param float $maxSpeed
     */
    public function setMaxSpeed($maxSpeed) 
    {
        $this->maxSpeed = $maxSpeed;
    }

    /**
     * @return string
     */
    public function getPolyline() 
    {
        return $this->polyline;
    }

    /**
     * @param string $polyline
     */
    public function setPolyline($polyline) 
    {
        $this->polyline = $polyline;
    }

    /**
     * @return string
     */
    public function getSummaryPolyline() 
    {
        return $this->summaryPolyline;
    }

    /**
     * @param string $summaryPolyline
     */
    public function setSummaryPolyline($summaryPolyline) 
    {
        $this->summaryPolyline = $summaryPolyline;
    }

    /**
     * @return array
     */
    public function getSegmentEfforts() 
    {
        return $this->segmentEfforts;
    }

    /**
     * @param array $segmentEfforts
     */
    public function setSegmentEfforts($segmentEfforts) 
    {
        $this->segmentEfforts = $segmentEfforts;
    }

    /**
     * @return array
     */
    public function getPhotos() 
    {
        return $this->photos;
    }

    /**
     * @param array $photos
     */
    public function setPhotos($photos) 
    {
        $this->photos = $photos;
    }
}