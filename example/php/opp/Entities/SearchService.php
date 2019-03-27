<?php

namespace App\Entities;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * @var SearchTerm
     */
    protected $searchTerm;

    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * Acceptable operators to be used in a query
     * @var array
     */
    protected $queryOperators = ['<=', '>=', '=', '<', '>', 'like', '!='];

    /**
     * SearchService constructor.
     * @param EntityProvider $entityProvider
     * @param Connection $db
     */
    public function __construct(EntityProvider $entityProvider, Connection $db)
    {
        $this->entityProvider    = $entityProvider;
        $this->db                = $db;
    }

    /**
     * Set the database connection
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->db = $connection;
    }

    /**
     * Search all entities in the system.
     * @param string $searchString
     * @param string $entityType
     * @param int $page
     * @param int $count
     * @param string $action
     * @return array[int, Collection];
     */
    public function searchEntities($searchString, $entityType = 'all', $page = 1, $count = 20, $action = 'view')
    {
        $terms               = $this->parseSearchString($searchString);
        $entityTypes         = array_keys($this->entityProvider->all());
        $entityTypesToSearch = $entityTypes;

        if ($entityType !== 'all') {
            $entityTypesToSearch = $entityType;
        } elseif (isset($terms['filters']['type'])) {
            $entityTypesToSearch = explode('|', $terms['filters']['type']);
        }

        $results = collect();
        $total   = 0;
        $hasMore = false;

        foreach ($entityTypesToSearch as $entityType) {
            if (!in_array($entityType, $entityTypes)) {
                continue;
            }
            $search      = $this->searchEntityTable($terms, $entityType, $page, $count, $action);
            $entityTotal = $this->searchEntityTable($terms, $entityType, $page, $count, $action, true);
            if ($entityTotal > $page * $count) {
                $hasMore = true;
            }
            $total += $entityTotal;
            $results = $results->merge($search);
        }

        return [
            'total'    => $total,
            'count'    => count($results),
            'has_more' => $hasMore,
            'results'  => $results->sortByDesc('score')->values()
        ];
    }
}
