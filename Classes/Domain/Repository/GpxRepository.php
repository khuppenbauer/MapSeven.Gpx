<?php

namespace MapSeven\Gpx\Domain\Repository;

/*                                                                           *
 * This script belongs to the MapSeven.Gpx package.                          *
 *                                                                           *
 *                                                                           */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;

/**
 * Gpx Repository
 *
 * @Flow\Scope("singleton")
 */
class GpxRepository extends Repository
{

    /**
     * @var array
     */
    protected $defaultOrderings = ['date' => QueryInterface::ORDER_DESCENDING];


    /**
     * @param array $properties
     * @return QueryResultInterface
     */
    public function findByUniqueProperties($properties)
    {
        $query = $this->createQuery();
        foreach ($properties as $property => $value) {
            $constraints[] = $query->equals($property, $value);
        }
        return $query->matching($query->logicalAnd($constraints))->execute();
    }
}
