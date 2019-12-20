<?php
namespace MapSeven\Gpx\GraphQL\Resolver;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use t3n\GraphQL\ResolverInterface;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Domain\Repository\GpxRepository;

/**
 * QueryResolver for the MapSeven.Gpx package
 *
 */
class QueryResolver implements ResolverInterface
{

    /**
     * @Flow\Inject
     * @var StravaRepository
     */
    protected $stravaRepository;

    /**
     * @Flow\Inject
     * @var GpxRepository
     */
    protected $gpxRepository;


    /**
     * 
     * @param type $_
     * @param array $variables
     * @return array
     */
    public function stravaActivities($_, $variables)
    {
        return $this->stravaRepository->findAll();
    }

    /**
     * 
     * @param type $_
     * @param array $variables
     * @return Strava
     */
    public function strava($_, $variables)
    {
        if (isset($variables['id'])) {
            return $this->stravaRepository->findOneById($variables['id']);
        } elseif (isset($variables['identifier'])) {
            return $this->stravaRepository->findByIdentifier($variables['identifier']);
        }
    }

    /**
     * 
     * @param type $_
     * @param array $variables
     * @return array
     */
    public function gpxActivities($_, $variables)
    {
        return $this->gpxRepository->findAll();
    }

    /**
     * 
     * @param type $_
     * @param array $variables
     * @return Strava
     */
    public function gpx($_, $variables)
    {
        return $this->gpxRepository->findByIdentifier($variables['identifier']);
    }
}