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
use MapSeven\Gpx\Domain\Model\File;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Domain\Repository\FileRepository;

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
     * @var FileRepository
     */
    protected $fileRepository;

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
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;


    /**
     * Create Visualization
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
        $headers = [
            'headers' => $this->utilityService->getHeaders($this->apiSettings)
        ];
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
            } elseif ($object instanceof File) {
                $this->fileRepository->update($object);
            }
            $this->persistenceManager->persistAll();            
        }
    }
}