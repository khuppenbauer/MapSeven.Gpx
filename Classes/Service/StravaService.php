<?php
namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use MapSeven\Gpx\Domain\Model\Strava;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * Strava Service
 * 
 * @Flow\Scope("singleton")
 */
class StravaService 
{

    /**
     * @Flow\InjectConfiguration("strava.api")
     * @var array
     */
    protected $apiSettings;
    
    /**
     * @Flow\InjectConfiguration("strava.mappingKeys")
     * @var array 
     */
    protected $mappingKeys;

    /**
     * @Flow\InjectConfiguration("geocoding")
     * @var array
     */
    protected $geocodingSettings;
    
    /**
     * @var YamlSource
     * @Flow\Inject
     */
    protected $configurationSource;
    
    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;
    
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;
    
    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var StravaRepository
     */
    protected $stravaRepository;
    
    /**
     * @var array
     */
    protected $bounds;
    
    /**
     * @var Asset
     */
    protected $gpxFile;


    /**
     * Returns strava activity
     * 
     * @param integer $activityId
     * @param string $athlete
     * @return Strava
     */
    public function addActivity($activityId, $athlete)
    {
        $activity = $this->getActivityData($activityId);
        if (empty($activity)) {
            return;
        }
        $activity['author'] = $athlete;
        $strava = $this->stravaRepository->findOneById($activityId);
        if (!empty($strava)) {
            $strava = $this->transformArray($activity, $strava);
            $strava->setUpdated(new \DateTime());
            $this->stravaRepository->update($strava);
            return $strava;
        } else {
            $strava = $this->transformArray($activity, new Strava());
            $this->stravaRepository->add($strava);
            return $strava;
        }
    }
    
    /**
     * Returns activity data
     * 
     * @param integer $activityId
     * @return array
     */
    private function getActivityData($activityId)
    {
        $uriSegments = [
            'activities',
            $activityId,
        ];
        $activity = $this->requestUri($this->apiSettings['base_uri'], $uriSegments);
        if ($activity['visibility'] !== 'everyone') {
            return;
        }
        $photoItems = [];
        $photos = $this->requestUri($this->apiSettings['base_uri'], array_merge($uriSegments, ['photos']), ['size' => 600]);
        foreach ($photos as $key => $photo) {
            $photoItems[] = $photo['urls'][600];
        }
        $activityData = $this->getActivityStreamData($activity['id'], 'high', 'latlng,altitude');
        $this->bounds = $this->generateBounds($activityData);
        $this->gpxFile = $this->writeGpx($activity['name'], $activity['start_date_local'], $activityData);
        $activity['photos'] = $photoItems;                   
        $activity['geocoding'] = $this->requestUri($this->geocodingSettings['base_uri'], ['reverse.php'], ['key' => $this->geocodingSettings['key'], 'format' => 'json', 'lat' => $activity['start_latitude'], 'lon' => $activity['start_longitude'], 'normalizecity' => 1, 'accept-language' => 'de'], false);
        $activity['bounds'] = $this->bounds;
        return $activity;
    }
    
    /**
     * Returns Activity Sream Data
     * 
     * @param integer $activityId
     * @param string $resolution
     * @param string $type
     * @return array
     */
    private function getActivityStreamData($activityId, $resolution, $type)
    {
        $uriSegments = [
            'activities',
            $activityId,
            'streams',
            $type
        ];
        $streams = $this->requestUri($this->apiSettings['base_uri'], $uriSegments, ['resolution' => $resolution]);
        $data = [];
        foreach ($streams as $item) {
            foreach ($item['data'] as $key => $dataItem) {
                $data[$key][$item['type']] = $dataItem;                    
            }
        }
        return $data;
    }

    /**
     * Returns transformed activity object
     * 
     * @param array $activity
     * @param Strava $strava
     * @return Strava
     */
    private function transformArray($activity, $strava)
    {
        $strava->setDate(new \DateTime($activity['start_date_local']));
        $strava->setActive(true);
        foreach ($this->mappingKeys['activity'] as $property) {
            $propertyValue = Arrays::getValueByPath($activity, $property['arrayAccess']);
            ObjectAccess::setProperty($strava, $property['objectAccess'], $propertyValue);
        }
        $strava->setGpxFile($this->gpxFile);
        return $strava;
    }
    
    /**
     * Write GPX Data to File System
     * 
     * @param string $name
     * @param string $date
     * @param array $data
     * @return Asset
     */
    private function writeGpx($name, $date, $data) 
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><gpx creator="StravaGPX Android" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" version="1.1" xmlns="http://www.topografix.com/GPX/1/1"></gpx>');
        $metadata = $xml->addChild('metadata');
        $metadata->addChild('time', $date);
        $bounds = $metadata->addChild('bounds');
        $bounds->addAttribute('minlat', $this->bounds['minLat']);
        $bounds->addAttribute('minlon', $this->bounds['minLng']);
        $bounds->addAttribute('maxlat', $this->bounds['maxLat']);
        $bounds->addAttribute('maxlon', $this->bounds['maxLng']);
        $trk = $xml->addChild('trk');
        $trk->addChild('name', htmlspecialchars($name));
        $trk->addChild('type', 1);
        $trkseg = $trk->addChild('trkseg');
        foreach ($data as $item) {
            $trkpnt = $trkseg->addChild('trkpt');
            $trkpnt->addAttribute('lat', $item['latlng'][0]);
            $trkpnt->addAttribute('lon', $item['latlng'][1]);
            $trkpnt->addChild('ele', $item['altitude']);
        }
        $filename = substr($date, 0,10) . '-' . static::sanitizeFilename($name) . '-' . substr(md5($date), 0, 6);
        return $this->saveXMLDocument($filename, $xml->asXML(), 'strava');
    }
    
    /**
     * Returns saved GPX File
     * 
     * @param string $filename
     * @param string $content
     * @param string $source
     * @return Asset
     */
    public function saveXMLDocument($filename, $content, $source) 
    {
        $existingDocument = $this->assetRepository->findOneByTitle($filename);
        if (!empty($existingDocument)) {
            return $existingDocument;
        }
        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($content);
        $resource = $this->resourceManager->importResourceFromContent($dom->saveXML(), $filename . '.xml');
        $asset = new Document($resource);
        $asset->setTitle($filename);
        $asset->setAssetSourceIdentifier($source);
        $this->assetRepository->add($asset);
        $this->gpxFile = $asset;
        return $asset;
    }

    /**
     * Returns Bounds
     * 
     * @param array $data
     * @return array
     */
    private function generateBounds($data)
    {
        foreach ($data as $item) {
            $lat[] = round($item['latlng'][0], 2);
            $lng[] = round($item['latlng'][1], 2);   
        }
        return [
            'minLat' => min($lat),
            'minLng' => min($lng),
            'maxLat' => max($lat),
            'maxLng' => max($lng),
            'minLatLon' => ['lat' => min($lat), 'lon' => min($lng)],
            'maxLatLon' => ['lat' => max($lat), 'lon' => max($lng)]            
        ];
    }

    /**
     * Returns sanitized filename
     * 
     * @param string $title
     * @return string
     */
    static public function sanitizeFilename($title) 
    {
        $title = str_replace(['ä','ö','ü','ß', ' '], ['ae','oe','ue','ss', '-'], strtolower($title));
        $title = preg_replace("/[^a-z0-9\-_]/", "", $title);
        return $title;        
    }
    
    /**
     * Returns result from Api Request
     * 
     * @param string $baseUri
     * @param array $uriSegments
     * @param array $queryParams
     * @param boolean $includeToken
     * @return array
     */
    public function requestUri($baseUri, $uriSegments, $queryParams = [], $includeToken = true)
    {
        $uri = implode('/', $uriSegments);
        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }
        $data = [];
        $client = new \GuzzleHttp\Client(['base_uri' => $baseUri]);
        $response = $client->request('GET', $uri, ['headers' => $this->getHeaders($includeToken)]);
        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents(), true);
        }
        return $data;
    }
    
    /**
     * Returns Headers
     * 
     * @param boolean $includeToken
     * @return array
     */
    private function getHeaders($includeToken = true)
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];
        if ($includeToken === true) {
            $headers['Authorization'] = 'Bearer ' . $this->getToken();
        }
        return $headers;
    }
    
    /**
     * Returns Access Token
     * 
     * @return string
     */
    private function getToken()
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $this->apiSettings['oauth_uri'], [
            'form_params' => $this->apiSettings['auth']
        ]);
        if ($response->getStatusCode() === 200) {
            $content = json_decode($response->getBody()->getContents(), true);
            $accessToken = $content['access_token'];
            $refreshToken = $content['refresh_token'];
            $settings = $this->configurationSource->load(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
            $settings = Arrays::setValueByPath($settings, 'MapSeven.Gpx.strava.api.auth.refresh_token', $refreshToken);
            $this->configurationSource->save(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settings);
            $this->configurationManager->refreshConfiguration();                
            return $accessToken;
        }
    }
}