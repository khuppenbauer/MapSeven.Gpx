<?php
namespace MapSeven\Gpx\Domain\Model;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Flowpack\ElasticSearch\Annotations as ElasticSearch;

/**
 * File Model
 * 
 * @Flow\Entity
 * @ElasticSearch\Indexable("gpx", typeName="_doc")
 */
class File extends Gpx
{

}