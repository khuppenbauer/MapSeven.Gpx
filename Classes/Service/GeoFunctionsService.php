<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Document;

/**
 * GeoFunctions Service
 * Use Netlify Functions
 *
 * @Flow\Scope("singleton")
 */
class GeoFunctionsService
{

    /**
     * @Flow\InjectConfiguration("domain")
     * @var array
     */
    protected $domain;

    /**
     * @Flow\InjectConfiguration("geoFunctions")
     * @var array
     */
    protected $geoFunctionsSettings;

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
     * Returns geojson from gpx
     *
     * @param Document $gpxFile
     * @return array
     */
    public function togeojson($gpxFile)
    {
        $gpxFileUrl = $this->resourceManager->getPublicPersistentResourceUri($gpxFile->getResource());
        $gpxFileUrl = str_replace(FLOW_PATH_WEB, $this->domain, $gpxFileUrl);
        return $this->utilityService->requestUri(
            ['base_uri' => $this->geoFunctionsSettings['api']['base_uri']],
            ['togeojson'],
            ['url' => $gpxFileUrl],
            false
        );
    }

    /**
     * Returns cleaned geoJson
     *
     * @param array $geoJson
     * @param integer $time
     * @param integer $distance
     * @param integer $points
     * @return array
     */
    public function geoJsonTidy($geoJson, $time = 2, $distance = 5, $points = 1000)
    {
        return $this->utilityService->requestUri(
            ['base_uri' => $this->geoFunctionsSettings['api']['base_uri']],
            ['geojson-tidy'],
            ['distance' => $distance, 'time' => $time, 'points' => $points],
            false,
            'POST',
            $geoJson
        );
    }

    /**
     * Returns compressed or decompressed geoJson
     *
     * @param mixed $data
     * @param string $type
     * @return mixed
     */
    public function geobuf($data, $type = 'encode')
    {
        return $this->utilityService->requestUri(
            ['base_uri' => $this->geoFunctionsSettings['api']['base_uri']],
            ['geobuf'],
            ['type' => $type],
            false,
            'POST',
            $data
        );
    }

    /**
     * Returns compressed or decompressed geoJson
     *
     * @param mixed $data
     * @param string $type
     * @return mixed
     */
    public function polyline($data, $type)
    {
        return $this->utilityService->requestUri(
            ['base_uri' => $this->geoFunctionsSettings['api']['base_uri']],
            ['polyline'],
            ['type' => $type],
            false,
            'POST',
            $data
        );
    }

}
