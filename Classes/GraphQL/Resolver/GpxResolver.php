<?php
namespace MapSeven\Gpx\GraphQL\Resolver;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use t3n\GraphQL\ResolverInterface;
use MapSeven\Gpx\Domain\Model\Gpx;
use MapSeven\Gpx\Service\StravaService;

/**
 * GpxResolver for the MapSeven.Gpx package
 *
 */
class GpxResolver implements ResolverInterface
{
    
    /**
     * @Flow\Inject
     * @var StravaService
     */
    protected $stravaService;
    
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    public function identifier(Gpx $gpx)
    {
        return $this->persistenceManager->getIdentifierByObject($gpx);
    }

    public function slug(Gpx $gpx)
    {
        return $gpx->getDate()->format('Y-m-d') . '-' . StravaService::sanitizeFilename($gpx->getName());
    }

    public function coords(Gpx $gpx)
    {
        $gpxFile = $gpx->getGpxFile();
        $coords = $this->stravaService->convertGpx($gpxFile);
        return $coords;
    }
}