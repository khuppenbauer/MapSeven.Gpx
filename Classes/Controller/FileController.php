<?php

namespace MapSeven\Gpx\Controller;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\SkipCsrfProtection;
use Neos\Flow\Mvc\Controller\RestController;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\ResourceManagement\ResourceManager;
use MapSeven\Gpx\Domain\Model\File;
use MapSeven\Gpx\Domain\Repository\FileRepository;
use MapSeven\Gpx\Service\FileService;
use MapSeven\Gpx\Service\UtilityService;

/**
 * File controller for the MapSeven.Gpx package
 *
 * @Flow\Scope("singleton")
 */
class FileController extends RestController
{

    const JSON_VIEW = 'Neos\\Flow\\Mvc\\View\JsonView';

    /**
     * @var string
     */
    protected $resourceArgumentName = 'file';

    /**
     * @Flow\Inject
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @Flow\Inject
     * @var FileService
     */
    protected $fileService;

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
     * List Action
     *
     * @return void
     */
    public function listAction()
    {
        $activities = $this->fileRepository->findAll();
        $this->response->setComponentParameter(SetHeaderComponent::class, 'X-Total-Count', $activities->count());
        $this->view->assign('value', $activities);
    }

    /**
     * Show Action
     *
     * @param File $file
     * @return void
     */
    public function showAction(File $file)
    {
        $this->view->assign('value', $file);
    }

    /**
     * Show GeoJson Action
     *
     * @param File $file
     * @param integer $distance
     * @param integer $points
     * @return void
     */
    public function showGeoJsonAction(File $file, $distance = null, $points = null)
    {
        $this->view->assign('value', $file->getGeoJson($distance, $points));
    }

    /**
     * Show Gpx Action
     *
     * @param File $file
     * @return void
     */
    public function showGpxAction(File $file)
    {
        $asset = $file->getGpxFile();
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Content-Type', $asset->getMediaType());
        $streamResource = $this->resourceManager->getStreamByResource($asset->getResource());
        return stream_get_contents($streamResource);
    }

    /**
     * Create Action
     *
     * @SkipCsrfProtection
     * @param string $filename
     * @return void
     */
    public function createAction($filename)
    {
        $xml = file_get_contents($filename);
        $files = $this->fileService->import($filename, $xml);
        foreach ($files as $file) {
            $this->utilityService->emitActivityUpdated($file);
        }
        $this->view->assign('value', $files);
    }

    /**
     * Update Action
     *
     * @SkipCsrfProtection
     * @param File $file
     * @return void
     */
    public function updateAction(File $file)
    {
        $this->fileRepository->update($file);
        $this->utilityService->emitActivityUpdated($file);
        $this->view->assign('value', $file);
    }

    /**
     * Delete Action
     *
     * @SkipCsrfProtection
     * @param File $file
     * @return void
     */
    public function deleteAction(File $file)
    {
        $this->fileRepository->remove($file);
        $this->utilityService->emitActivityDeleted($file);
        $this->response->setStatusCode(204);
    }

    /**
     * Overrides the standard resolveView method
     *
     * @return ViewInterface $view The view
     */
    protected function resolveView()
    {
        $viewObjectName = self::JSON_VIEW;
        $view = $this->objectManager->get($viewObjectName);
        return $view;
    }
}
