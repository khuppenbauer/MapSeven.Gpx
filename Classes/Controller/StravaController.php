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
use MapSeven\Gpx\Service\StravaService;
use MapSeven\Gpx\Domain\Model\Strava;
use MapSeven\Gpx\Domain\Repository\StravaRepository;

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
     * Create Action
     * 
     * @SkipCsrfProtection
     * @param Strava $strava
     * @return void
     */
    public function createAction(Strava $strava)
    {
        $athlete = $this->stravaService->requestUri($this->apiSettings['base_uri'], ['athlete']);
        $strava = $this->stravaService->addActivity($strava->getId(), $athlete['username']);
        $this->emitActivityCreated($strava);
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
        $this->emitActivityUpdated($strava);
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
        $this->emitActivityDeleted($strava);
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

    /**
     * Signal that a strava activity was created
     *
     * @Flow\Signal
     * @param Strava $strava
     * @return void
     */
    protected function emitActivityCreated(Strava $strava)
    {
    }

    /**
     * Signals that a strava activity was updated
     *
     * @Flow\Signal
     * @param Strava $strava
     * @return void
     */
    protected function emitActivityUpdated(Strava $strava)
    {
    }

    /**
     * Signals that a strava activity was deleted
     *
     * @Flow\Signal
     * @param Strava $strava
     * @return void
     */
    protected function emitActivityDeleted(Strava $strava)
    {
    }
}