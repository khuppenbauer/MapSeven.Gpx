<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\Arrays;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * Utiliy Service
 *
 * @Flow\Scope("singleton")
 */
class UtilityService
{

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
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

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
        $resource = $this->resourceManager->importResourceFromContent($dom->saveXML(), $filename . '.gpx');
        $asset = new Document($resource);
        $asset->setTitle($filename);
        $asset->setAssetSourceIdentifier($source);
        $this->assetRepository->add($asset);
        $this->persistenceManager->persistAll();
        return $asset;
    }

    /**
     * Returns coords from gpx file
     *
     * @param Document $gpxFile
     * @return array
     */
    public function convertGpx(Document $gpxFile)
    {
        $url = $this->resourceManager->getPublicPersistentResourceUri($gpxFile->getResource());
        $xml = file_get_contents($url);
        $content = simplexml_load_string($xml);
        $array = json_decode(json_encode($content), true);
        $points = Arrays::getValueByPath($array, 'trk.trkseg.trkpt');
        foreach ($points as $point) {
            $gpx[] = [
                'lat' => (float)$point['@attributes']['lat'],
                'lon' => (float)$point['@attributes']['lon'],
                'ele' => (float)$point['ele']
            ];
            $geojson[] = [
                (float)$point['@attributes']['lon'],
                (float)$point['@attributes']['lat'],
                (float)$point['ele']
            ];
        }
        $coords = [
            'gpx' => $gpx,
            'geojson' => $geojson
        ];
        return $coords;
    }

    /**
     * Returns sanitized filename
     *
     * @param string $title
     * @return string
     */
    public static function sanitizeFilename($title)
    {
        $title = str_replace(['ä', 'ö', 'ü', 'ß', ' '], ['ae', 'oe', 'ue', 'ss', '-'], strtolower($title));
        $title = preg_replace("/[^a-z0-9\-_]/", "", $title);
        return $title;
    }

    /**
     * Returns result from Api Request
     *
     * @param array $apiSettings
     * @param array $uriSegments
     * @param array $queryParams
     * @param boolean $includeToken
     * @param string $method
     * @param mixed $body
     * @return mixed
     */
    public function requestUri(
        $apiSettings,
        $uriSegments,
        $queryParams = [],
        $includeToken = true,
        $method = 'GET',
        $body = null
    ) {
        $uri = implode('/', $uriSegments);
        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }
        $client = new \GuzzleHttp\Client(['base_uri' => $apiSettings['base_uri']]);
        $options = [
            'headers' => $this->getHeaders($apiSettings, $includeToken, 'Bearer ')
        ];
        if (!empty($body)) {
            if (is_array($body)) {
                $options['json'] = $body;
            } else {
                $options['body'] = $body;
            }
        }
        $response = $client->request($method, $uri, $options);
        if ($response->getStatusCode() === 200) {
            $contents = $response->getBody()->getContents();
            $contentType = explode(';', $response->getHeader('Content-Type')[0]);
            if (in_array('application/json', $contentType)) {
                return json_decode($contents, true);
            } else {
                return $contents;
            }
        }
    }

    /**
     * Returns Headers
     *
     * @param array $apiSettings
     * @param boolean $includeToken
     * @param string $tokenType
     * @return array
     */
    public function getHeaders($apiSettings, $includeToken = true, $tokenType = '')
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];
        if ($includeToken === true) {
            $headers['Authorization'] = $tokenType . $this->getToken($apiSettings);
        }
        return $headers;
    }

    /**
     * Returns Access Token
     *
     * @param array $apiSettings
     * @return string
     */
    public function getToken($apiSettings)
    {
        $client = new \GuzzleHttp\Client();
        $options = [
            'form_params' => $apiSettings['auth']
        ];
        if (isset($apiSettings['basicAuth'])) {
            $options['headers'] = ['Authorization' => 'Basic ' . base64_encode($apiSettings['basicAuth'])];
        }
        $response = $client->request('POST', $apiSettings['oauth_uri'], $options);
        if ($response->getStatusCode() === 200) {
            $content = json_decode($response->getBody()->getContents(), true);
            $accessToken = $content['access_token'];
            if (isset($content['refresh_token'])) {
                $refreshToken = $content['refresh_token'];
                $settings = $this->configurationSource->load(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
                $settings = Arrays::setValueByPath($settings, $apiSettings['auth']['refresh_token'], $refreshToken);
                $this->configurationSource->save(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                    $settings);
                $this->configurationManager->refreshConfiguration();
            }
            return $accessToken;
        }
    }

    /**
     * Returns simplified GeoJson
     *
     * @param array $coordinates
     * @param type $amount
     * @return array
     */
    public function simplifyGeoJsonLineString($coordinates, $amount = 100)
    {
        $points = [];
        $count = count($coordinates);
        if ($count < $amount) {
            return $coordinates;
        }
        $factor = round($count / $amount);
        foreach ($coordinates as $key => $coordinate) {
            if ($key % $factor === 0) {
                $points[] = [
                    0 => round($coordinate[0], 6),
                    1 => round($coordinate[1], 6)
                ];
            }
        }
        return $points;
    }

    /**
     * Signal that an activity was created
     *
     * @Flow\Signal
     * @param object $activity
     * @return void
     */
    public function emitActivityCreated($activity)
    {
    }

    /**
     * Signal that an activity was updated
     *
     * @Flow\Signal
     * @param object $activity
     * @return void
     */
    public function emitActivityUpdated($activity)
    {
    }

    /**
     * Signal that an activity was deleted
     *
     * @Flow\Signal
     * @param object $activity
     * @return void
     */
    public function emitActivityDeleted($activity)
    {
    }
}
