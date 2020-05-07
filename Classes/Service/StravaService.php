<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Document;
use Neos\Utility\Arrays;
use Neos\Utility\ObjectAccess;
use MapSeven\Gpx\Domain\Model\Strava;
use MapSeven\Gpx\Domain\Repository\StravaRepository;
use MapSeven\Gpx\Service\UtilityService;

/**
 * Strava Service
 *
 * @Flow\Scope("singleton")
 */
class StravaService
{

    /**
     * @Flow\InjectConfiguration("strava.api")
     * @var array
     */
    protected $apiSettings;

    /**
     * @Flow\InjectConfiguration("strava.mappingKeys")
     * @var array
     */
    protected $mappingKeys;

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
     * @var float
     */
    protected $totalElevationGain = 0;

    /**
     * @var float
     */
    protected $totalElevationLoss = 0;

    /**
     * @var Asset
     */
    protected $gpxFile;


    /**
     * Returns strava activity
     *
     * @param integer $activityId
     * @param string $athlete
     * @return Strava
     */
    public function addActivity($activityId, $athlete)
    {
        $activity = $this->getActivityData($activityId);
        if (empty($activity)) {
            return;
        }
        $activity['author'] = $athlete;
        $strava = $this->stravaRepository->findOneById($activityId);
        if (!empty($strava)) {
            $strava = $this->transformArray($activity, $strava);
            $strava->setUpdated(new \DateTime());
            $this->stravaRepository->update($strava);
            return $strava;
        } else {
            $strava = $this->transformArray($activity, new Strava());
            $this->stravaRepository->add($strava);
            return $strava;
        }
    }

    /**
     * Returns transformed activity object
     *
     * @param array $activity
     * @param Strava $strava
     * @return Strava
     */
    private function transformArray($activity, $strava)
    {
        $strava->setDate(new \DateTime($activity['start_date_local']));
        $strava->setActive(true);
        foreach ($this->mappingKeys['activity'] as $property) {
            $propertyValue = Arrays::getValueByPath($activity, $property['arrayAccess']);
            ObjectAccess::setProperty($strava, $property['objectAccess'], $propertyValue);
        }
        $activityData = $this->getActivityStreamData($activity['id'], 'high', 'latlng,altitude,time',
            $activity['start_date_local']);
        $gpxFile = $this->writeGpx($activity['name'], $activity['start_date_local'], $activityData);
        $strava->setGpxFile($gpxFile);
        return $strava;
    }

    /**
     * Returns activity data
     *
     * @param integer $activityId
     * @return array
     */
    private function getActivityData($activityId)
    {
        $uriSegments = [
            'activities',
            $activityId,
        ];
        $activity = $this->utilityService->requestUri($this->apiSettings, $uriSegments);
        if ($activity['visibility'] !== 'everyone') {
            return;
        }
        $photoItems = [];
        $photos = $this->utilityService->requestUri($this->apiSettings, array_merge($uriSegments, ['photos']),
            ['size' => 600]);
        foreach ($photos as $key => $photo) {
            $photoItems[] = $photo['urls'][600];
        }
        $activity['photos'] = $photoItems;
        return $activity;
    }

    /**
     * Returns Activity Sream Data
     *
     * @param integer $activityId
     * @param string $resolution
     * @param string $type
     * @param string $startDate
     * @return array
     */
    private function getActivityStreamData($activityId, $resolution, $type, $startDate)
    {
        $time = new \DateTime($startDate);
        $uriSegments = [
            'activities',
            $activityId,
            'streams',
            $type
        ];
        $streams = $this->utilityService->requestUri($this->apiSettings, $uriSegments, ['resolution' => $resolution]);
        $data = [];
        foreach ($streams as $item) {
            foreach ($item['data'] as $key => $dataItem) {
                if ($item['type'] === 'time') {
                    $time->add(new \DateInterval('PT' . $dataItem . 'S'));
                    $data[$key][$item['type']] = $time->format('Y-m-d\TH:i:s\Z');
                    $time = new \DateTime($startDate);
                } else {
                    $data[$key][$item['type']] = $dataItem;
                }
            }
        }
        return $data;
    }

    /**
     * Write GPX Data to File System
     *
     * @param string $name
     * @param string $date
     * @param array $data
     * @return Document
     */
    private function writeGpx($name, $date, $data)
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><gpx creator="StravaGPX Android" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" version="1.1" xmlns="http://www.topografix.com/GPX/1/1"></gpx>');
        $metadata = $xml->addChild('metadata');
        $metadata->addChild('time', $date);
        $trk = $xml->addChild('trk');
        $trk->addChild('name', htmlspecialchars($name));
        $trk->addChild('type', 1);
        $trkseg = $trk->addChild('trkseg');
        foreach ($data as $item) {
            $trkpnt = $trkseg->addChild('trkpt');
            $trkpnt->addAttribute('lat', round($item['latlng'][0], 6));
            $trkpnt->addAttribute('lon', round($item['latlng'][1], 6));
            $trkpnt->addChild('ele', $item['altitude']);
            $trkpnt->addChild('time', $item['time']);
        }
        $filename = substr($date, 0, 10) . '-' . UtilityService::sanitizeFilename($name) . '-' . substr(md5($date), 0,
                6);
        return $this->utilityService->saveXMLDocument($filename, $xml->asXML(), 'strava');
    }
}
