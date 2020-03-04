<?php

namespace MapSeven\Gpx\GraphQL\Resolver;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use t3n\GraphQL\ResolverInterface;
use MapSeven\Gpx\Domain\Repository\FileRepository;
use MapSeven\Gpx\Domain\Repository\GpxRepository;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Domain\Model\File;
use MapSeven\Gpx\Domain\Model\Gpx;
use MapSeven\Gpx\Domain\Model\Strava;

/**
 * QueryResolver for the MapSeven.Gpx package
 *
 */
class QueryResolver implements ResolverInterface
{

    /**
     * @Flow\Inject
     * @var FileRepository
     */
    protected $fileRepository;

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
     *
     * @param type $_
     * @param array $variables
     * @return array
     */
    public function allStravaActivities($_, $variables)
    {
        return $this->stravaRepository->findAll();
    }

    /**
     *
     * @param type $_
     * @param array $variables
     * @return Strava
     */
    public function stravaActivity($_, $variables)
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
    public function allFileActivities($_, $variables)
    {
        return $this->fileRepository->findAll();
    }

    /**
     *
     * @param type $_
     * @param array $variables
     * @return File
     */
    public function fileActivity($_, $variables)
    {
        return $this->fileRepository->findByIdentifier($variables['identifier']);
    }
}
