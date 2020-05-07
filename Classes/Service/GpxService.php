<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use MapSeven\Gpx\Domain\Model\Strava;
use MapSeven\Gpx\Domain\Model\File;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Domain\Repository\FileRepository;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Arrays;

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
     * @var GpsBabelFunctionsService
     */
    protected $gpsBabelFunctionsService;

    /**
     * @Flow\Inject
     * @var GeoLibFunctionsService
     */
    protected $geoLibFunctionsService;

    /**
     * @Flow\Inject
     * @var LocationService
     */
    protected $locationService;

    /**
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;

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
     * Create GeoJson File
     *
     * @param Strava|File $object
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function createGeoJsonFile($object)
    {
        $geoJson = $this->gpsBabelFunctionsService->gpsbabel($object->getGpxFile(), '8m', 500, '0.001k');
        $object->setGeoJson($geoJson);
        $source = $object instanceof Strava ? 'strava' : 'file';
        $filename = $object->getGpxFile()->getTitle();
        $asset = $this->utilityService->importAsset(json_encode($geoJson), $filename, 'json', $source);
        $object->setGeoJsonFile($asset);
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
        $geoJson = $this->gpsBabelFunctionsService->gpsbabel($object->getGpxFile(), null, 100);
        $staticImage = $this->mapboxService->createStaticImage($geoJson, $object->getGpxFile()->getTitle());
        $object->setStaticImage($staticImage);
    }

    /**
     * Adds Meta Data to Gpx Object
     *
     * @param Strava|File $object
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function addMetaData($object)
    {
        $geoJson = $object->getGeoJson();
        $points = Arrays::getValueByPath($geoJson, 'features.0.geometry.coordinates');
        $bounds = $this->geoLibFunctionsService->getBounds($points);
        $distance = $this->geoLibFunctionsService->getPathLength($points);
        $elevation = $this->calculateElevation($points);
        $start = $points[0];
        $end = $points[count($points) - 1];
        $object->setStartCoords(['lat' => round($start[1], 2), 'lon' => round($start[0], 2)]);
        $object->setEndCoords(['lat' => round($end[1], 2), 'lon' => round($end[0], 2)]);
        $startLocation = $this->locationService->getReverseGeoCoding($object->getStartCoords());
        $endLocation = $this->locationService->getReverseGeoCoding($object->getEndCoords());
        $object->setDistance($distance);
        $object->setTotalElevationGain($elevation['totalElevationGain']);
        $object->setTotalElevationLoss($elevation['totalElevationLoss']);
        $object->setElevLow($elevation['elevLow']);
        $object->setElevHigh($elevation['elevHigh']);
        $object->setMinCoords(['lat' => round($bounds['minLat'], 2), 'lon' => round($bounds['minLng'], 2)]);
        $object->setMaxCoords(['lat' => round($bounds['maxLat'], 2), 'lon' => round($bounds['maxLng'],2)]);
        $object->setStartCity(Arrays::getValueByPath($startLocation, 'address.city'));
        $object->setStartCountry(Arrays::getValueByPath($startLocation, 'address.country'));
        $object->setStartState(Arrays::getValueByPath($startLocation, 'address.state'));
        $object->setEndCity(Arrays::getValueByPath($endLocation, 'address.city'));
        $object->setEndCountry(Arrays::getValueByPath($endLocation, 'address.country'));
        $object->setEndState(Arrays::getValueByPath($endLocation, 'address.state'));
    }

    /**
     * Saves Gpx Object
     *
     * @param Strava|File $object
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function save($object)
    {
        if ($object instanceof Strava) {
            $this->stravaRepository->update($object);
        } else {
            $this->fileRepository->update($object);
        }
    }

    /**
     * Return calculated Elevation
     *
     * @param $points
     * @return array
     */
    protected function calculateElevation($points)
    {
        $totalElevationGain = 0;
        $totalElevationLoss = 0;
        foreach ($points as $point) {
            if (isset($point[2])) {
                $ele2 = $point[2];
                if (!empty($ele1) && $ele2 > $ele1) {
                    $diff = $ele2 - $ele1;
                    $totalElevationGain = $totalElevationGain + $diff;
                }
                if (!empty($ele1) && $ele2 < $ele1) {
                    $diff = $ele1 - $ele2;
                    $totalElevationLoss = $totalElevationLoss + $diff;
                }
                $ele1 = $point[2];
                $ele[] = $point[2];
            }
        }
        return [
            'totalElevationGain' => round($totalElevationGain, 2),
            'totalElevationLoss' => round($totalElevationLoss, 2),
            'elevLow' => round(min($ele), 2),
            'elevHigh' => round(max($ele), 2)
        ];
    }
}
