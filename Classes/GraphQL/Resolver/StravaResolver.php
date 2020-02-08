<?php
namespace MapSeven\Gpx\GraphQL\Resolver;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
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

    public function coords(Strava $strava)
    {
        $gpxFile = $strava->getGpxFile();
        $coords = $this->utilityService->convertGpx($gpxFile);
        return $coords['gpx'];
    }

    public function geoJson(Strava $strava)
    {
        $gpxFile = $strava->getGpxFile();
        $coords = $this->utilityService->convertGpx($gpxFile);
        $geojson = [
            'type' => 'LineString',
            'coordinates' => $coords['geojson']
        ];
        return $geojson;
    }
}