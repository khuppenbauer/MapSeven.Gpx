<?php
namespace MapSeven\Gpx\Service;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Document;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Files;
use MapSeven\Gpx\Service\UtilityService;

/**
 * Export Service
 * 
 * @Flow\Scope("singleton")
 */
class ExportService 
{

    /**
     * @Flow\InjectConfiguration("staticFile")
     * @var array
     */
    protected $settings;

    /**
     * @Flow\InjectConfiguration("domain")
     * @var array
     */
    protected $domain;
    
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * Creates file with Activity Data
     * 
     * @param object $object
     * @return string
     */
    public function createFile($object)
    {
        $filename = $this->writeFile($this->transformObject($object), $object->getDate());
        return $filename;
    }
    
    /**
     * Deletes file
     * 
     * @param object $object
     */
    public function deleteFile($object)
    {
        $this->removeFile($object, $object->getDate());
    }
    
    /**
     * Returns transformed object
     * 
     * @param object $object
     * @return array
     */
    protected function transformObject($object)
    {
        $propertyNames = ObjectAccess::getGettablePropertyNames($object);
        $class = new \ReflectionClass($object);
        $array = [
            'identifier' => $this->persistenceManager->getIdentifierByObject($object),
            'source' => $class->getShortName(),
            'slug' => $object->getDate()->format('Y-m-d') . '-' . UtilityService::sanitizeFilename($object->getName())
        ];
        foreach ($propertyNames as $propertyName) {
            $propertyValue = ObjectAccess::getProperty($object, $propertyName);
            if (!is_object($propertyValue)) {
                if (in_array($propertyName, ['startCoords', 'endCoords', 'minCoords', 'maxCoords'])) {
                    $geojson = [
                        'type' => 'Point',
                        'coordinates' => [
                            $propertyValue['lon'],
                            $propertyValue['lat']
                        ]
                    ];
                    $array[$propertyName] = json_encode($geojson);
                } else {
                    $array[$propertyName] = $propertyValue;                    
                }
            } elseif ($propertyValue instanceof \DateTime) {
                $array[$propertyName] = $propertyValue->format('Y-m-d');
            } elseif ($propertyValue instanceof Document) {
                $uri = $this->resourceManager->getPublicPersistentResourceUri($propertyValue->getResource());
                $array[$propertyName] = str_replace(FLOW_PATH_WEB, $this->domain, $uri);
            }
        }
        return $array;
    }
    
    /**
     * Write File
     * 
     * @param array $data
     * @param \DateTime $date
     * @return string
     */
    protected function writeFile($data, \DateTime $date)
    {
        if ($this->settings['create']['enabled'] === false) {
            return;
        }
        $content = json_encode($data, JSON_PRETTY_PRINT);
        if (empty($content)) {
            return;
        }
        $path = $this->settings['create']['path'];
        if (!is_dir($path)) {
            Files::createDirectoryRecursively($path);
        }
        $fileName =  $path . '/' . $data['slug'] . '.' . $this->settings['create']['extension'];
        file_put_contents($fileName, $content);
        if ($this->settings['commit']['enabled'] === true) {
            $this->commit('Create Activitiy "' . $data['slug'] . '"');            
        }
        return $fileName;
    }
    
    /**
     * Remove File
     * 
     * @param object $data
     * @param \DateTime $date
     */
    protected function removeFile($data, $date)
    {
        if ($this->settings['create']['enabled'] === false) {
            return;
        }
        $slug = $data->getDate()->format('Y-m-d') . '-' . UtilityService::sanitizeFilename($data->getName());
        $fileName = $this->settings['create']['path'] . '/' . $slug . '.' . $this->settings['create']['extension'];
        unlink($fileName);
        if ($this->settings['commit']['enabled'] === true) {
            $this->commit('Delete Activity "' . $slug . '"');            
        }
    }
    
    /**
     * Commit File
     * 
     * @param string $message
     */
    protected function commit($message)
    {
        chdir($this->settings['commit']['path']);
        exec('git pull --rebase', $output, $return);
        exec('git add .', $output, $return);
        exec('git commit -m "' . $message . '"', $output, $return);
        //exec('git push origin master', $output, $return);
    }
}