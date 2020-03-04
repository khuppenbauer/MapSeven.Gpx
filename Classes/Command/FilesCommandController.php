<?php

namespace MapSeven\Gpx\Command;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Utility\Files;
use MapSeven\Gpx\Domain\Repository\FileRepository;
use MapSeven\Gpx\Service\FileService;
use MapSeven\Gpx\Service\ExportService;
use MapSeven\Gpx\Service\UtilityService;

/**
 * File Command controller for the MapSeven.Gpx package
 *
 * @Flow\Scope("singleton")
 */
class FilesCommandController extends CommandController
{

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
     * @var ExportService
     */
    protected $exportService;

    /**
     * @Flow\Inject
     * @var UtilityService
     */
    protected $utilityService;


    /**
     * Import GPX Files from directory
     *
     * @param string $author
     * @param string $type
     */
    public function importCommand($author = 'John Doe', $type = 'Ride')
    {
        libxml_use_internal_errors(true);
        $path = FLOW_PATH_DATA . 'Import';
        $files = Files::readDirectoryRecursively($path);
        foreach ($files as $item) {
            $xml = file_get_contents($item);
            $file = $this->fileService->import($item, $xml, $author, $type);
            if (!empty($file)) {
                $this->outputLine('Add ' . $file->getName());
                $this->utilityService->emitActivityCreated($file);
            }
            sleep(1);
        }
    }

    /**
     * Write StaticFile
     */
    public function createStaticFilesCommand()
    {
        $gpxActivities = $this->fileRepository->findAll();
        foreach ($gpxActivities as $gpx) {
            $filename = $this->exportService->createFile($gpx);
            $this->outputLine('File ' . $filename . ' created');
        }
    }
}
