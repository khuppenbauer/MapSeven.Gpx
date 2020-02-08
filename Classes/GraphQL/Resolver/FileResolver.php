<?php
namespace MapSeven\Gpx\GraphQL\Resolver;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
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

    public function coords(File $file)
    {
        $gpxFile = $file->getGpxFile();
        $coords = $this->utilityService->convertGpx($gpxFile);
        return $coords['gpx'];
    }

    public function geoJson(File $file)
    {
        $gpxFile = $file->getGpxFile();
        $coords = $this->utilityService->convertGpx($gpxFile);
        $geojson = [
            'type' => 'LineString',
            'coordinates' => $coords['geojson']
        ];
        return $geojson;
    }
}