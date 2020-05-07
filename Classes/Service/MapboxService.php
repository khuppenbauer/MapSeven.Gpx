<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Arrays;

/**
 * Mapbox Service
 *
 * @Flow\Scope("singleton")
 */
class MapboxService
{

    /**
     * @Flow\InjectConfiguration("mapbox")
     * @var array
     */
    protected $mapboxSettings;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

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
     * Create Static Image
     *
     * @param array $geoJson
     * @param string $fileName
     * @param string $mapboxStyle
     * @param string $imageSize
     * @param string $stroke
     * @param string $strokeWidth
     * @return Image
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     */
    public function createStaticImage(
        $geoJson,
        $fileName,
        $mapboxStyle = null,
        $imageSize = null,
        $stroke = null,
        $strokeWidth = null
    ) {
        $mapboxStyle = $mapboxStyle ?: $this->mapboxSettings['staticImage']['style'];
        $imageSize = $imageSize ?: $this->mapboxSettings['staticImage']['size'];
        $stroke = $stroke ?: $this->mapboxSettings['staticImage']['stroke'];
        $strokeWidth = $strokeWidth ?: $this->mapboxSettings['staticImage']['strokeWidth'];
        $fileName .= '-' . str_replace('/', '_', $mapboxStyle) . '-' . $imageSize . '-' . $stroke . '-' . $strokeWidth;
        $asset = $this->assetRepository->findOneByTitle($fileName);
        if (empty($asset)) {
            $coordinates = Arrays::getValueByPath($geoJson, 'features.0.geometry.coordinates');
            if (!empty($coordinates)) {
                $geoJsonLineString = [
                    'type' => 'Feature',
                    'properties' => [
                        'stroke' => $stroke,
                        'stroke-width' => $strokeWidth
                    ],
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => $coordinates
                    ]
                ];
                $res = $this->utilityService->requestUri(
                    ['base_uri' => $this->mapboxSettings['api']['base_uri']],
                    [
                        $mapboxStyle,
                        'static',
                        'geojson(' . urlencode(json_encode($geoJsonLineString)) . ')',
                        'auto',
                        $imageSize
                    ],
                    ['access_token' => $this->mapboxSettings['api']['key']],
                    false
                );
                $asset = $this->utilityService->importAsset($res, $fileName,  'png', 'mapbox');
            }
        }
        return $asset;
    }

}
