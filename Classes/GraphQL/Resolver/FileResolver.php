<?php

namespace MapSeven\Gpx\GraphQL\Resolver;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Utility\Arrays;
use t3n\GraphQL\ResolverInterface;
use MapSeven\Gpx\Domain\Model\File;
use MapSeven\Gpx\Service\UtilityService;

/**
 * FileResolver for the MapSeven.Gpx package
 *
 */
class FileResolver implements ResolverInterface
{

    /**
     * @Flow\InjectConfiguration("domain")
     * @var array
     */
    protected $domain;

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
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    public function identifier(File $file)
    {
        return $this->persistenceManager->getIdentifierByObject($file);
    }

    public function date(File $file)
    {
        return $file->getDate()->format('Y-m-d');
    }

    public function slug(File $file)
    {
        return $file->getDate()->format('Y-m-d') . '-' . UtilityService::sanitizeFilename($file->getName());
    }

    public function geoJson(File $file, $variables)
    {
        $distance = Arrays::getValueByPath($variables, 'distance');
        $points = Arrays::getValueByPath($variables, 'points');
        return $file->getGeoJson($distance, $points);
    }

    public function staticImage(File $file)
    {
        $staticImage = $this->resourceManager->getPublicPersistentResourceUri($file->getStaticImage()->getResource());
        return str_replace(FLOW_PATH_WEB, $this->domain, $staticImage);
    }

    public function gpxFile(File $file)
    {
        $gpxFileUrl = $this->resourceManager->getPublicPersistentResourceUri($file->getGpxFile()->getResource());
        return str_replace(FLOW_PATH_WEB, $this->domain, $gpxFileUrl);
    }
}
