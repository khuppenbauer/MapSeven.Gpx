<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;

/**
 * GeoLibFunctions Service
 * Use Netlify Functions
 *
 * @Flow\Scope("singleton")
 */
class GeoLibFunctionsService
{

    /**
     * @Flow\InjectConfiguration("geoLibFunctions")
     * @var array
     */
    protected $geoLibFunctionsSettings;

    /**
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;


    /**
     * Returns Bounds of LineString
     *
     * @param array $points
     * @return array
     */
    public function getBounds($points)
    {
        return $this->utilityService->requestUri(
            ['base_uri' => $this->geoLibFunctionsSettings['api']['base_uri']],
            ['getBounds'],
            [],
            false,
            'POST',
            json_encode(['points' => $points])
        );
    }

    /**
     * Returns Path Length of LineString
     *
     * @param array $points
     * @return array
     */
    public function getPathLength($points)
    {
        return $this->utilityService->requestUri(
            ['base_uri' => $this->geoLibFunctionsSettings['api']['base_uri']],
            ['getPathLength'],
            [],
            false,
            'POST',
            json_encode(['points' => $points])
        );
    }

}
