<?php
namespace MapSeven\Gpx\Command;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use MapSeven\Gpx\Service\StravaService;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Domain\Model\Strava;

/**
 * Strava Command controller for the MapSeven.Gpx package
 * 
 * @Flow\Scope("singleton")
 */
class StravaCommandController extends CommandController
{

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
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * Import Strava Activities
     * 
     * @param integer $page
     * @param integer $perPage
     */
    public function importCommand($page = 1, $perPage = 30) 
    {
        $athlete = $this->stravaService->requestUri($this->apiSettings['base_uri'], ['athlete']);
        $this->addAthleteActivities($page, $perPage, $athlete['username']);
    }
    
    /**
     * Update Strava Activities
     */
    public function updateCommand($requestData = false) 
    {
        $athlete = $this->stravaService->requestUri($this->apiSettings['base_uri'], ['athlete']);
        $stravaActivities = $this->stravaRepository->findAll();
        foreach ($stravaActivities as $stravaActivity) {
            if ($requestData === true) {
                $strava = $this->stravaService->addActivity($stravaActivity->getId(), $athlete['username']);
                sleep(1);                
            } else {
                $this->stravaRepository->update($stravaActivity);
            }
        }
    }

    /**
     * Add Strava Activities 
     * 
     * @param integer $page
     * @param integer $perPage
     * @param string $athlete
     */
    private function addAthleteActivities($page = 1, $perPage = 30, $athlete)
    {
        $uriSegments = [
            'athlete',
            'activities'
        ];
        $queryParams = [
            'page' => $page,
            'per_page' => $perPage
        ];

        $activities = $this->stravaService->requestUri($this->apiSettings['base_uri'], $uriSegments, $queryParams, true);
        if (!empty($activities) && $page > 0) {
            foreach ($activities as $activity) {
                $strava = $this->stravaService->addActivity($activity['id'], $athlete);
                if (!empty($strava)) {
                    $this->persistenceManager->persistAll();
                    $this->outputLine('Add ' . $activity['name']);
                    sleep(1);
                }
            }
            $page = $page + 1;
            $this->addAthleteActivities($page, $perPage, $athlete);
        }
    }
}