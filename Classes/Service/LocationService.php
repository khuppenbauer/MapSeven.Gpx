<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;

/**
 * Location Service
 *
 * @Flow\Scope("singleton")
 */
class LocationService
{

    /**
     * @Flow\InjectConfiguration("locationService")
     * @var array
     */
    protected $locationServiceSettings;

    /**
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;


    /**
     * Returns Location by Coordinates
     *
     * @param array $point
     * @return array
     */
    public function getReverseGeoCoding($point)
    {
        return $this->utilityService->requestUri(
            $this->locationServiceSettings,
            ['reverse.php'],
            [
                'key' => $this->locationServiceSettings['key'],
                'format' => 'json',
                'lat' => $point['lat'],
                'lon' => $point['lon'],
                'normalizecity' => 1,
                'accept-language' => 'de'
            ],
            false
        );
    }

    /**
     * Returns Timezone by Coordinates
     *
     * @param array $point
     * @return array
     */
    public function getTimezone($point)
    {
        return $this->utilityService->requestUri(
            $this->locationServiceSettings,
            ['timezone.php'],
            [
                'key' => $this->locationServiceSettings['key'],
                'lat' => $point['lat'],
                'lon' => $point['lon']
            ],
            false
        );
    }

}
