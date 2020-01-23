<?php
namespace MapSeven\Gpx\Command;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Service\UtilityService;
use MapSeven\Gpx\Service\StravaService;
use MapSeven\Gpx\Service\ExportService;

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
     * @var UtilityService
     */
    protected $utilityService;

    /**
     * @Flow\Inject
     * @var StravaService
     */
    protected $stravaService;

    /**
     * @Flow\Inject
     * @var ExportService
     */
    protected $exportService;

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
        $athlete = $this->utilityService->requestUri($this->apiSettings, ['athlete']);
        $this->addAthleteActivities($page, $perPage, $athlete['username']);
    }
    
    /**
     * Update Strava Activities
     * 
     * @param boolean $requestData
     */
    public function updateCommand($requestData = false) 
    {
        $athlete = $this->utilityService->requestUri($this->apiSettings, ['athlete']);
        $stravaActivities = $this->stravaRepository->findAll();
        foreach ($stravaActivities as $stravaActivity) {
            if ($requestData === true) {
                $this->stravaService->addActivity($stravaActivity->getId(), $athlete['username']);
                sleep(1);                
            } else {
                $stravaActivity->setUpdated(new \DateTime());
                $this->stravaRepository->update($stravaActivity);
            }
            $this->outputLine('Update ' . $stravaActivity->getName());
            $this->utilityService->emitActivityUpdated($stravaActivity);
        }
    }

    /**
     * Write StaticFile
     */
    public function createStaticFilesCommand()
    {
        $stravaActivities = $this->stravaRepository->findAll();
        foreach ($stravaActivities as $stravaActivity) {
            $filename = $this->exportService->createFile($stravaActivity);
            $this->outputLine('File ' . $filename . ' created');
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

        $activities = $this->utilityService->requestUri($this->apiSettings, $uriSegments, $queryParams, true);
        if (!empty($activities) && $page > 0) {
            foreach ($activities as $activity) {
                $strava = $this->stravaService->addActivity($activity['id'], $athlete);
                if (!empty($strava)) {
                    $this->persistenceManager->persistAll();
                    $this->outputLine('Add ' . $activity['name']);
                    $this->utilityService->emitActivityCreated($strava);
                    sleep(1);
                }
            }
            $page = $page + 1;
            $this->addAthleteActivities($page, $perPage, $athlete);
        }
    }
}