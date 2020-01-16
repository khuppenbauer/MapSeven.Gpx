<?php
namespace MapSeven\Gpx\Command;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use MapSeven\Gpx\Domain\Model\Gpx;
use MapSeven\Gpx\Domain\Repository\GpxRepository;
use MapSeven\Gpx\Service\StravaService;
use MapSeven\Gpx\Service\FileService;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * Gpx Command controller for the MapSeven.Gpx package
 * 
 * @Flow\Scope("singleton")
 */
class GpxCommandController extends CommandController
{

    /**
     * @Flow\InjectConfiguration("geocoding")
     * @var array
     */
    protected $geocodingSettings;

    /**
     * @Flow\InjectConfiguration("timezone")
     * @var array
     */
    protected $timezoneSettings;
       
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var GpxRepository
     */
    protected $gpxRepository;
    
    /**
     * @Flow\Inject
     * @var StravaService
     */
    protected $stravaService;

    /**
     * @Flow\Inject
     * @var FileService
     */
    protected $fileService;
    
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * Import GPX Files from directory
     * 
     * @param string $author
     * @param string $type
     */
    public function importCommand($author = '', $type = 'Ride') 
    {
        libxml_use_internal_errors(true);
        $path = FLOW_PATH_DATA . 'Import';
        $files = Files::readDirectoryRecursively($path);
        foreach ($files as $key => $file) {
            $xml = file_get_contents($file);
            //combine segments
            $xml = str_replace('</trkseg><trkseg>', '', $xml);
            $content = simplexml_load_string($xml);
            if (!$content instanceof \SimpleXMLElement) {
                //try to fix corrupted files
                $xml = substr($xml, 0, strrpos($xml, '</trkpt>')). '</trkpt></trkseg></trk></gpx>';
                $content = simplexml_load_string($xml);
                if (!$content instanceof \SimpleXMLElement) {
                    $this->outputLine('Error while importing ' . $file);
                    continue;   
                }
            }
            $array = json_decode(json_encode($content), true);
            $name = Arrays::getValueByPath($array, 'trk.name');
            if (empty($name)) {
                $name = Arrays::getValueByPath($array, 'trk.0.name');                
            }
            if (empty($name)) {
                $pathinfo = pathinfo($file);
                $name = $pathinfo['filename'];
            }
            $date = Arrays::getValueByPath($array, 'metadata.time');
            if (empty($date)) {
                $date = new \DateTime();
                $date->setTimestamp(filectime($file));
            } else {
                $date = new \DateTime($date);
            }
            $filename = $date->format('Y-m-d') . '-' . StravaService::sanitizeFilename($name) . '-' . substr(md5($file), 0, 6);
            $existingDocument = $this->assetRepository->findOneByTitle($filename);
            if (!empty($existingDocument)) {
                continue;
            }
            $gpxFile = $this->stravaService->saveXMLDocument($filename, $xml, 'gpx');
            $owner = Arrays::getValueByPath($array, 'metadata.author.name'); 
            $author = !empty($owner) ? $owner : $author;
            $gpx = $this->convertGpx($name, $date, $array, $author, $type);
            $gpx->setGpxFile($gpxFile);
            if (!empty($gpx)) {
                $this->gpxRepository->add($gpx);
                $this->persistenceManager->persistAll();
                $this->outputLine('Add ' . $gpx->getName());
                sleep(1);
            }
        }        
    }

    /**
     * Write StaticFile
     */
    public function createStaticFilesCommand()
    {
        $gpxActivities = $this->gpxRepository->findAll();
        foreach ($gpxActivities as $gpx) {
            $filename = $this->fileService->createFile($gpx);
            $this->outputLine('File ' . $filename . ' created');
        }
    }

    /**
     * Returns gpx object
     * 
     * @param string $name
     * @param \DateTime $date
     * @param array $array
     * @param string $author
     * @param string $type
     * @return Gpx
     */
    protected function convertGpx($name, $date, $array, $author, $type) 
    {
        $points = Arrays::getValueByPath($array, 'trk.trkseg.trkpt');
        if (empty($points)) {
            $points = Arrays::getValueByPath($array, 'trk.0.trkseg.trkpt');                
        }
        if (empty($points)) {
            $points = Arrays::getValueByPath($array, 'trk.trkseg.0.trkpt');
        }
        if (!empty($points)) {
            $startPoint = $points[0];
            $endPoint = $points[count($points)-1];
            $geocoding = $this->stravaService->requestUri($this->geocodingSettings['base_uri'], ['reverse.php'], ['key' => $this->geocodingSettings['key'], 'format' => 'json', 'lat' => $startPoint['@attributes']['lat'], 'lon' => $startPoint['@attributes']['lon'], 'normalizecity' => 1, 'accept-language' => 'de'], false);
            $timezone = $this->stravaService->requestUri($this->timezoneSettings['base_uri'], ['get-time-zone'], ['key' => $this->timezoneSettings['key'], 'format' => 'json', 'lat' => $startPoint['@attributes']['lat'], 'lng' => $startPoint['@attributes']['lon'], 'by' => 'position']);
            $date->setTimezone(new \DateTimeZone($timezone['zoneName']));
            $gpx = new Gpx();
            $gpx->setName($name);
            $gpx->setDate($date);
            $gpx->setAuthor($author);
            $gpx->setType($type);
            $data = static::calculateFromPoints($points);
            $gpx->setStartCoords([round($startPoint['@attributes']['lat'], 2), round($startPoint['@attributes']['lon'], 2)]);
            $gpx->setEndCoords([round($endPoint['@attributes']['lat'], 2), round($endPoint['@attributes']['lon'], 2)]);
            $gpx->setElapsedTime($data['elapsedTime']);
            $gpx->setMinCoords($data['minCoords']);
            $gpx->setMaxCoords($data['maxCoords']);
            $gpx->setElevLow($data['elevLow']);
            $gpx->setElevHigh($data['elevHigh']);
            $gpx->setTotalElevationGain($data['totalElevationGain']);
            $gpx->setDistance($data['distance']);
            $gpx->setStartCity(Arrays::getValueByPath($geocoding, 'address.city'));
            $gpx->setStartCountry(Arrays::getValueByPath($geocoding, 'address.country'));
            $gpx->setStartState(Arrays::getValueByPath($geocoding, 'address.state'));
            return $gpx;
        }
    }

    /**
     * Returns calculated metadata from points
     * 
     * @param array $points
     * @return array
     */
    protected static function calculateFromPoints($points)
    {
        $totalElevationGain = 0;
        $distance = 0;
        $elapsedTime = 0;
        foreach ($points as $point) {
            $time2 = isset($point['time']) ? new \DateTime($point['time']) : null;
            if (empty($time2)) {
                $elapsedTime = null;
            }
            if (!empty($time1)) {
                $timeDiff = $time2->getTimestamp() - $time1->getTimestamp();
                if ($timeDiff < 0) {
                    $elapsedTime = null;
                }
                if ($elapsedTime !== null) {
                    $elapsedTime = $elapsedTime + $timeDiff;
                }
            }
            $lat2 = $point['@attributes']['lat'];
            $lng2 = $point['@attributes']['lon'];
            if (!empty($lat1) && !empty($lng1)) {
                $distance = $distance + static::getDistance($lat1, $lng1, $lat2, $lng2);
            }
            $lat[] = round($lat2, 2);
            $lng[] = round($lng2, 2);
            $time1 = isset($point['time']) ? new \DateTime($point['time']) : null;
            $lat1 = $point['@attributes']['lat'];
            $lng1 = $point['@attributes']['lon'];
            if (isset($point['ele'])) {
                $ele2 = $point['ele'];
                if (!empty($ele1) && $ele2 > $ele1) {
                    $diff = $ele2 - $ele1;
                    $totalElevationGain = $totalElevationGain + $diff;
                }                
                $ele[] = $point['ele'];
                $ele1 = $point['ele'];
            }
        }
        return [
            'minCoords' => ['lat' => min($lat), 'lon' => min($lng)],
            'maxCoords' => ['lat' => max($lat), 'lon' => max($lng)],
            'elevLow' => round(min($ele), 2),
            'elevHigh' => round(max($ele), 2),
            'totalElevationGain' => round($totalElevationGain, 2),
            'distance' => round($distance, 2),
            'elapsedTime' => $elapsedTime
        ];
    }

    /**
     * Returns distance between two points
     * 
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    protected static function getDistance($lat1, $lon1, $lat2, $lon2) 
    {
        $earthRadiusM = 6371000;

        $dLat = static::degreesToRadians($lat2-$lat1);
        $dLon = static::degreesToRadians($lon2-$lon1);

        $lat1 = static::degreesToRadians($lat1);
        $lat2 = static::degreesToRadians($lat2);

        $a = sin($dLat/2) * sin($dLat/2) + sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2); 
        $c = 2 * atan2(sqrt($a), sqrt(1-$a)); 
        return $earthRadiusM * $c;
    }
    
    /**
     * @param float $degrees
     * @return float
     */
    protected static function degreesToRadians($degrees) 
    {
        return $degrees * M_PI / 180;
    }
}