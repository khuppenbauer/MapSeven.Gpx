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
 * GpsBabel Functions Service
 * Use Netlify Functions
 *
 * @Flow\Scope("singleton")
 */
class GpsBabelFunctionsService
{

    /**
     * @Flow\InjectConfiguration("domain")
     * @var array
     */
    protected $domain;

    /**
     * @Flow\InjectConfiguration("gpsBabelFunctions")
     * @var array
     */
    protected $gpsBabelFunctionsSettings;

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
     * Returns gpsbabel result
     *
     * @param Document $gpxFile
     * @param string $distance
     * @param integer $points
     * @param string $error
     * @param string $intype
     * @param string $outtype
     * @return array
     */
    public function gpsbabel($gpxFile, $distance = null, $points = null, $error = null, $intype = 'gpx', $outtype = 'geojson')
    {
        $gpxFileUrl = $this->resourceManager->getPublicPersistentResourceUri($gpxFile->getResource());
        $gpxFileUrl = str_replace(FLOW_PATH_WEB, $this->domain, $gpxFileUrl);
        $params = [
            'infile'=> urldecode($gpxFileUrl),
            'intype' => $intype,
            'outtype' => $outtype
        ];
        if (!empty($distance)) {
            $params['distance'] = $distance;
        }
        if (!empty($points)) {
            $params['count'] = $points;
        }
        if (!empty($error)) {
            $params['error'] = $error;
        }
        return $this->utilityService->requestUri(
            ['base_uri' => $this->gpsBabelFunctionsSettings['api']['base_uri']],
            ['gpsbabel'],
            $params,
            false
        );
    }

}
