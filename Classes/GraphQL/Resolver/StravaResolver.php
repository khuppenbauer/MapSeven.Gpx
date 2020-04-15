<?php

namespace MapSeven\Gpx\GraphQL\Resolver;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\Arrays;
use t3n\GraphQL\ResolverInterface;
use MapSeven\Gpx\Domain\Model\Strava;
use MapSeven\Gpx\Service\UtilityService;

/**
 * StravaResolver for the MapSeven.Gpx package
 *
 */
class StravaResolver implements ResolverInterface
{
    /**
     * @Flow\InjectConfiguration("domain")
     * @var array
     */
    protected $domain;

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
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    public function identifier(Strava $strava)
    {
        return $this->persistenceManager->getIdentifierByObject($strava);
    }

    public function date(Strava $strava)
    {
        return $strava->getDate()->format('Y-m-d');
    }

    public function slug(Strava $strava)
    {
        return $strava->getDate()->format('Y-m-d') . '-' . UtilityService::sanitizeFilename($strava->getName());
    }

    public function geoJson(Strava $strava, $variables)
    {
        $distance = Arrays::getValueByPath($variables, 'distance');
        $points = Arrays::getValueByPath($variables, 'points');
        return $strava->getGeoJson($distance, $points);
    }

    public function staticImage(Strava $strava)
    {
        $staticImage = $this->resourceManager->getPublicPersistentResourceUri($strava->getStaticImage()->getResource());
        return str_replace(FLOW_PATH_WEB, $this->domain, $staticImage);
    }

    public function gpxFile(Strava $strava)
    {
        $gpxFileUrl = $this->resourceManager->getPublicPersistentResourceUri($strava->getGpxFile()->getResource());
        return str_replace(FLOW_PATH_WEB, $this->domain, $gpxFileUrl);
    }
}
