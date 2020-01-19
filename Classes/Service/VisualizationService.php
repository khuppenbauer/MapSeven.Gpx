<?php
namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use MapSeven\Gpx\Domain\Model\Strava;
use MapSeven\Gpx\Domain\Model\Gpx;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Domain\Repository\GpxRepository;

/**
 * Visualization Service
 * 
 * @Flow\Scope("singleton")
 */
class VisualizationService 
{

    /**
     * @Flow\InjectConfiguration("visualization")
     * @var array
     */
    protected $settings;

    /**
     * @Flow\InjectConfiguration("visualization.api")
     * @var array
     */
    protected $apiSettings;

    /**
     * @Flow\Inject
     * @var GpxRepository
     */
    protected $gpxRepository;

    /**
     * @Flow\Inject
     * @var StravaRepository
     */
    protected $stravaRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;


    /**
     * Create Visualization
     * 
     * 
     * @param object $object
     */
    public function createVisualization($object)
    {
        if ($this->settings['enabled'] === false) {
            return;
        }

        $client = new \GuzzleHttp\Client();
        $requestOptions = [
            'http_errors' => false
        ];
        $headers = $this->getHeaders();
        $identifier = $this->persistenceManager->getIdentifierByObject($object);

        $activityOptions = [
            'activityId' => $identifier,
            'title' => $object->getName(),
            'activityType' => 'Mountain Bike'
        ];
        $activityResponse = $client->request('POST', $this->apiSettings['base_uri'] . 'activity', array_merge($headers, ['json' => $activityOptions], $requestOptions));
        if ($activityResponse->getStatusCode() === 200) {
            $uploadUrl = json_decode($activityResponse->getBody()->getContents(), true)['uploadUrl'];               
            $url = $this->resourceManager->getPublicPersistentResourceUri($object->getGpxFile()->getResource());
            $xml = file_get_contents($url);
            $client->request('PUT', $uploadUrl, array_merge(['body' => $xml], $requestOptions));            
        }

        $sceneOptions = [
            'title' => $object->getName(),
            'activities' => [
                0 => [
                    'activityId' => $identifier                        
                ]
            ],
            'defaultSpeed' => 100,
            'autoplay' => false
        ];
        $sceneResponse = $client->request('POST', $this->apiSettings['base_uri'] . 'scene', array_merge($headers, ['json' => $sceneOptions], $requestOptions));
        if ($sceneResponse->getStatusCode() === 200) {
            $visualizationUrl = json_decode($sceneResponse->getBody()->getContents(), true)['sceneUrl'];
            $object->setVisualizationUrl($visualizationUrl);
            if ($object instanceof Strava) {
                $this->stravaRepository->update($object);
            } elseif ($object instanceof Gpx) {
                $this->gpxRepository->update($object);
            }
            $this->persistenceManager->persistAll();            
        }
    }

    /**
     * Returns Headers
     * 
     * @return array
     */
    private function getHeaders()
    {
        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $this->getToken()   
            ]
        ];
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
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiSettings['basicAuth'])
            ],
            'form_params' => $this->apiSettings['auth']
        ]);
        if ($response->getStatusCode() === 200) {
            $content = json_decode($response->getBody()->getContents(), true);
            $accessToken = $content['access_token'];
            if (isset($content['refresh_token'])) {
                $refreshToken = $content['refresh_token'];
                $settings = $this->configurationSource->load(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
                $settings = Arrays::setValueByPath($settings, 'MapSeven.Gpx.visualization.api.auth.refresh_token', $refreshToken);
                $this->configurationSource->save(FLOW_PATH_CONFIGURATION . ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settings);
                $this->configurationManager->refreshConfiguration();                
            }
            return $accessToken;
        }
    }
}