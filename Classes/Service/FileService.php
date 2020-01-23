<?php
namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use MapSeven\Gpx\Domain\Model\File;
use MapSeven\Gpx\Domain\Repository\FileRepository;
use MapSeven\Gpx\Service\UtilityService;

/**
 * File Service
 * 
 * @Flow\Scope("singleton")
 */
class FileService 
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
     * @var FileRepository
     */
    protected $fileRepository;
    
    /**
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;
    
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * @param string $file
     * @param string $xml
     * @param string $author
     * @param string $type
     * @return File
     */
    public function import($file, $xml, $author = 'John Doe', $type = 'Ride')
    {
        //combine segments
        $xml = str_replace('</trkseg><trkseg>', '', $xml);
        $content = simplexml_load_string($xml);
        if (!$content instanceof \SimpleXMLElement) {
            //try to fix corrupted files
            $xml = substr($xml, 0, strrpos($xml, '</trkpt>')). '</trkpt></trkseg></trk></gpx>';
            $content = simplexml_load_string($xml);
            if (!$content instanceof \SimpleXMLElement) {
                return;   
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
        $filename = $date->format('Y-m-d') . '-' . UtilityService::sanitizeFilename($name) . '-' . substr(md5($file), 0, 6);
        $existingDocument = $this->assetRepository->findOneByTitle($filename);
        if (!empty($existingDocument)) {
            return $this->fileRepository->findOneByGpxFile($existingDocument);
        } 
        $gpxFile = $this->utilityService->saveXMLDocument($filename, $xml, 'file');
        $owner = Arrays::getValueByPath($array, 'metadata.author.name'); 
        $author = !empty($owner) ? $owner : $author;
        $fileObject = $this->convertObject($name, $date, $array, $author, $type);
        $fileObject->setGpxFile($gpxFile);
        if (!empty($fileObject)) {
            $this->fileRepository->add($fileObject);
            $this->persistenceManager->persistAll();
            return $fileObject;
        }
    }

    /**
     * Returns file object
     * 
     * @param string $name
     * @param \DateTime $date
     * @param array $array
     * @param string $author
     * @param string $type
     * @return File
     */
    protected function convertObject($name, $date, $array, $author, $type) 
    {
        $points = Arrays::getValueByPath($array, 'trk.trkseg.trkpt');
        if (empty($points)) {
            $points = Arrays::getValueByPath($array, 'trk.0.trkseg.trkpt');                
        }
        if (empty($points)) {
            $points = Arrays::getValueByPath($array, 'trk.trkseg.0.trkpt');
        }
        array_shift($points);
        if (!empty($points)) {
            $startPoint = $points[0];
            $endPoint = $points[count($points)-1];
            $startLocation = $this->utilityService->requestUri($this->geocodingSettings, ['reverse.php'], ['key' => $this->geocodingSettings['key'], 'format' => 'json', 'lat' => $startPoint['@attributes']['lat'], 'lon' => $startPoint['@attributes']['lon'], 'normalizecity' => 1, 'accept-language' => 'de'], false);
            $endLocation = $this->utilityService->requestUri($this->geocodingSettings, ['reverse.php'], ['key' => $this->geocodingSettings['key'], 'format' => 'json', 'lat' => $endPoint['@attributes']['lat'], 'lon' => $endPoint['@attributes']['lon'], 'normalizecity' => 1, 'accept-language' => 'de'], false);
            $timezone = $this->utilityService->requestUri($this->timezoneSettings, ['get-time-zone'], ['key' => $this->timezoneSettings['key'], 'format' => 'json', 'lat' => $startPoint['@attributes']['lat'], 'lng' => $startPoint['@attributes']['lon'], 'by' => 'position'], false);
            $date->setTimezone(new \DateTimeZone($timezone['zoneName']));
            $file = new File();
            $file->setName($name);
            $file->setDate($date);
            $file->setAuthor($author);
            $file->setType($type);
            $data = static::calculateFromPoints($points);
            $file->setStartCoords([round($startPoint['@attributes']['lat'], 2), round($startPoint['@attributes']['lon'], 2)]);
            $file->setEndCoords([round($endPoint['@attributes']['lat'], 2), round($endPoint['@attributes']['lon'], 2)]);
            $file->setElapsedTime($data['elapsedTime']);
            $file->setMinCoords($data['minCoords']);
            $file->setMaxCoords($data['maxCoords']);
            $file->setElevLow($data['elevLow']);
            $file->setElevHigh($data['elevHigh']);
            $file->setTotalElevationGain($data['totalElevationGain']);
            $file->setTotalElevationLoss($data['totalElevationLoss']);
            $file->setDistance($data['distance']);
            $file->setStartCity(Arrays::getValueByPath($startLocation, 'address.city'));
            $file->setStartCountry(Arrays::getValueByPath($startLocation, 'address.country'));
            $file->setStartState(Arrays::getValueByPath($startLocation, 'address.state'));
            $file->setEndCity(Arrays::getValueByPath($endLocation, 'address.city'));
            $file->setEndCountry(Arrays::getValueByPath($endLocation, 'address.country'));
            $file->setEndState(Arrays::getValueByPath($endLocation, 'address.state'));
            return $file;
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
        $totalElevationLoss = 0;
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
                if (!empty($ele1) && $ele2 < $ele1) {
                    $diff = $ele1 - $ele2;
                    $totalElevationLoss = $totalElevationLoss + $diff;
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
            'totalElevationLoss' => round($totalElevationLoss, 2),
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