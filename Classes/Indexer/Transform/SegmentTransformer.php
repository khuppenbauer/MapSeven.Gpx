<?php
namespace MapSeven\Gpx\Indexer\Transform;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;
use Flowpack\ElasticSearch\Indexer\Object\Transform\TransformerInterface;
use Flowpack\ElasticSearch\Annotations\Transform;

/**
 * Segment Transformer
 * 
 * @Flow\Scope("singleton")
 */
class SegmentTransformer implements TransformerInterface
{

    /**
     * Returns the Elasticsearch type this transform() method returns
     *
     * @return string
     */
    public function getTargetMappingType()
    {
        return 'string';
    }

    /**
     * @param mixed $source
     * @param Transform $annotation
     * @return array
     */
    public function transformByAnnotation($source, Transform $annotation)
    {
        $segments = [];
        foreach ($source as $item) {
            $segments[] = $item['segment'];
        }
        return $segments;
    }
}