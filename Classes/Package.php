<?php
namespace MapSeven\Gpx;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                       *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;

/**
 * The Gpx Package
 */
class Package extends BasePackage
{

    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     *
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect('MapSeven\Gpx\Controller\StravaController', 'activityCreated', 'MapSeven\Gpx\Service\WebhookService', 'sendMessage');
        $dispatcher->connect('MapSeven\Gpx\Controller\StravaController', 'activityUpdated', 'MapSeven\Gpx\Service\WebhookService', 'sendMessage');
        $dispatcher->connect('MapSeven\Gpx\Controller\StravaController', 'activityDeleted', 'MapSeven\Gpx\Service\WebhookService', 'sendMessage');

        $dispatcher->connect('MapSeven\Gpx\Controller\StravaController', 'activityCreated', 'MapSeven\Gpx\Service\FileService', 'createFile');
        $dispatcher->connect('MapSeven\Gpx\Controller\StravaController', 'activityUpdated', 'MapSeven\Gpx\Service\FileService', 'createFile');
        $dispatcher->connect('MapSeven\Gpx\Controller\StravaController', 'activityDeleted', 'MapSeven\Gpx\Service\FileService', 'deleteFile');
    }
}