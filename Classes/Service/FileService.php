<?php

namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Repository\AssetRepository;
use MapSeven\Gpx\Domain\Model\File;
use MapSeven\Gpx\Domain\Repository\FileRepository;

/**
 * File Service
 *
 * @Flow\Scope("singleton")
 */
class FileService
{

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @Flow\Inject
     * @var LocationService
     */
    protected $locationService;

    /**
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;


    /**
     * @param string $file
     * @param string $xml
     * @param string $author
     * @param string $type
     * @return array
     */
    public function import($file, $xml, $author = 'John Doe', $type = 'Ride')
    {
        //combine segments
        $xml = preg_replace('/<wpt\\s+lat="[^"]*"\\s+lon="[^"]*">(.*?)<\/wpt>/sU', '', $xml);
        $content = self::parseXML($xml);
        $date = $this->getDate($content);
        $files = [];
        foreach ($content->trk as $trk) {
            $name = $this->getName($trk, $file);
            $filename = $date->format('Y-m-d') . '-' . UtilityService::sanitizeFilename($name) . '-' . substr(md5($date->getTimestamp()), 0,
                    6);
            $existingDocument = $this->assetRepository->findOneByTitle($filename);
            if (!empty($existingDocument)) {
                continue;
            }
            $xml = preg_replace('/<trk>(.*?)<\/trk>/sU', $trk->asXML(), $xml);
            $gpxFile = $this->utilityService->saveXMLDocument($filename, $xml, 'file');
            $owner = $content->metadata->author ? (string)$content->metadata->author->name : null;
            $author = !empty($owner) ? $owner : $author;
            $file = new File();
            $file->setName($name);
            $file->setDate($date);
            $file->setAuthor($author);
            $file->setType($type);
            $file->setGpxFile($gpxFile);
            $this->fileRepository->add($file);
            $files[] = $file;
        }
        return $files;
    }

    /**
     * @param object $trk
     * @param string $file
     * @return mixed|string
     */
    protected function getName($trk, $file)
    {
        $name = (string)$trk->name;
        if (empty($name)) {
            $pathinfo = pathinfo($file);
            $name = $pathinfo['filename'];
        }
        return $name;
    }

    /**
     * @param $content
     * @throws \Exception
     */
    /**
     * @param \SimpleXMLElement $content
     * @return \DateTime|string
     * @throws \Exception
     */
    protected function getDate(\SimpleXMLElement $content)
    {
        $time = $content->metadata->time;
        $date = new \DateTime($time);
        $start = $content->trk->trkseg->trkpt;
        if (!empty($start)) {
            $lat = (string)$start->attributes()->lat;
            $lon = (string)$start->attributes()->lon;
            $timezone = $this->locationService->getTimezone(['lat' => $lat, 'lon' => $lon]);
        }
        if (!empty($timezone) && $timezone['status'] === 'ok') {
            $date->setTimezone(new \DateTimeZone($timezone['timezone']['name']));
        }
        return $date;
    }

    /**
     * @param string $xml
     * @return \SimpleXMLElement
     */
    static protected function parseXML($xml)
    {
        $content = simplexml_load_string($xml);
        if (!$content instanceof \SimpleXMLElement) {
            //try to fix corrupted files
            $xml = substr($xml, 0, strrpos($xml, '</trkpt>')) . '</trkpt></trkseg></trk></gpx>';
            $content = simplexml_load_string($xml);
            if (!$content instanceof \SimpleXMLElement) {
                return;
            }
        }
        for ($i = 0; $i < 2; $i++) {
            self::removeFirstPoint($content);
        }
        if ($content->trk->trkseg[0]->trkpt->count() === 0) {
            unset($content->trk->trkseg[0]);
        }
        return $content;
    }

    /**
     * @param \SimpleXMLElement $content
     */
    static protected function removeFirstPoint(\SimpleXMLElement $content)
    {
        unset($content->trk->trkseg[0]->trkpt[0]);
    }
}
