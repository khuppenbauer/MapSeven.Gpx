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
use MapSeven\Gpx\Domain\Model\Strava;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Service\StravaService;
use MapSeven\Gpx\Service\UtilityService;

/**
 * Strava controller for the MapSeven.Gpx package
 *
 * @Flow\Scope("singleton")
 */
class StravaController extends RestController
{
    const JSON_VIEW = 'Neos\\Flow\\Mvc\\View\JsonView';

    /**
     * @var string
     */
    protected $resourceArgumentName = 'strava';

    /**
     * @Flow\InjectConfiguration("strava.api")
     * @var array
     */
    protected $apiSettings;

    /**
     * @Flow\Inject
     * @var StravaRepository
     */
    protected $stravaRepository;

    /**
     * @Flow\Inject
     * @var StravaService
     */
    protected $stravaService;

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
        $activities = $this->stravaRepository->findAll();
        $this->response->setComponentParameter(SetHeaderComponent::class, 'X-Total-Count', $activities->count());
        $this->view->assign('value', $activities);
    }

    /**
     * Show Action
     *
     * @param Strava $strava
     * @return void
     */
    public function showAction(Strava $strava)
    {
        $this->view->assign('value', $strava);
    }

    /**
     * Show GeoJson Action
     *
     * @param Strava $strava
     * @param integer $distance
     * @param integer $points
     * @return void
     */
    public function showGeoJsonAction(Strava $strava, $distance = null, $points = null)
    {
        $this->view->assign('value', $strava->getGeoJson($distance, $points));
    }

    /**
     * Show Gpx Action
     *
     * @param Strava $strava
     * @return void
     */
    public function showGpxAction(Strava $strava)
    {
        $asset = $strava->getGpxFile();
        $this->response->setComponentParameter(SetHeaderComponent::class, 'Content-Type', $asset->getMediaType());
        $streamResource = $this->resourceManager->getStreamByResource($asset->getResource());
        return stream_get_contents($streamResource);
    }

    /**
     * Create Action
     *
     * @SkipCsrfProtection
     * @param Strava $strava
     * @return void
     */
    public function createAction(Strava $strava)
    {
        $athlete = $this->utilityService->requestUri($this->apiSettings, ['athlete']);
        $strava = $this->stravaService->addActivity($strava->getId(), $athlete['username']);
        $this->persistenceManager->persistAll();
        $this->utilityService->emitActivityCreated($strava);
        $this->view->assign('value', $strava);
    }

    /**
     * Update Action
     *
     * @SkipCsrfProtection
     * @param Strava $strava
     * @return void
     */
    public function updateAction(Strava $strava)
    {
        $this->stravaRepository->update($strava);
        $this->persistenceManager->persistAll();
        $this->utilityService->emitActivityUpdated($strava);
        $this->view->assign('value', $strava);
    }

    /**
     * Delete Action
     *
     * @SkipCsrfProtection
     * @param Strava $strava
     * @return void
     */
    public function deleteAction(Strava $strava)
    {
        $this->stravaRepository->remove($strava);
        $this->utilityService->emitActivityDeleted($strava);
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
