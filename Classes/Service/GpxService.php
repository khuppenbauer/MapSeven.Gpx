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
 * Gpx Service
 *
 * @Flow\Scope("singleton")
 */
class GpxService
{

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
     * @var MapboxService
     */
    protected $mapboxService;

    /**
     * @Flow\Inject
     * @var GeoFunctionsService
     */
    protected $geoFunctionsService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


     /**
     * Generate GeoJson
     *
     * @param Strava|File $object
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function generateGeoJson($object)
    {
        $geoJson = $this->geoFunctionsService->gpsbabel($object->getGpxFile());
        $object->setGeoJson($geoJson);
    }

    /**
     * Generate GeoJson
     *
     * @param Strava|File $object
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function createGeoJsonCompressed($object)
    {
        if (empty($object->getGeoJson())) {
            $this->generateGeoJson($object);
        }
        $geoJsonCompressed = $this->geoFunctionsService->geobuf($object->getGeoJson());
        $object->setGeoJsonCompressed($geoJsonCompressed);
        if ($object instanceof Strava) {
            $this->stravaRepository->update($object);
        } elseif ($object instanceof File) {
            $this->fileRepository->update($object);
        }
    }

    /**
     * Create Static Image
     *
     * @param Strava|File $object
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function createStaticImage($object)
    {
        if (empty($object->getGeoJson())) {
            $this->generateGeoJson($object);
        }
        $staticImage = $this->mapboxService->createStaticImage($object->getGeoJson(), $object->getGpxFile()->getTitle());
        $object->setStaticImage($staticImage);
        if ($object instanceof Strava) {
            $this->stravaRepository->update($object);
        } elseif ($object instanceof File) {
            $this->fileRepository->update($object);
        }
    }
}
