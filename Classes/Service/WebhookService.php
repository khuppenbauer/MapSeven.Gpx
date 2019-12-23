<?php
namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use MapSeven\Gpx\Domain\Model\Strava;

/**
 * Webhook Service
 * 
 * @Flow\Scope("singleton")
 */
class WebhookService 
{

    /**
     * @Flow\InjectConfiguration("webhook.urls")
     * @var array
     */
    protected $urls;
    
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * Sends message with Strava Activity Data to configured webhook
     * 
     * 
     * @param Strava $strava
     */
    public function sendMessage(Strava $strava)
    {
        if (!empty($this->urls)) {
            foreach ($this->urls as $url) {
                $client = new \GuzzleHttp\Client();
                $client->request('POST', $url, [
                    'json' => $this->transformObject($strava)
                ]);
            }
        }
    }
    
    /**
     * Returns transformed object
     * 
     * @param object $object
     * @return array
     */
    protected function transformObject($object)
    {
        $propertyNames = ObjectAccess::getGettablePropertyNames($object);
        $array = [
            '__identity' => $this->persistenceManager->getIdentifierByObject($object)
        ];
        foreach ($propertyNames as $propertyName) {
            $propertyValue = ObjectAccess::getProperty($object, $propertyName);
            if (!is_object($propertyValue)) {
                $array[$propertyName] = $propertyValue;
            }
        }
        return $array;
    }
}