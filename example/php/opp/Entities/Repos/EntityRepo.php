<?php

namespace App\Entities\Repos;

use Illuminate\Support\Collection;
use App\Entities\Opportunity;
use App\Entities\Forecast;
use App\Entities\Detail;

class EntityRepo
{
    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var SearchService
     */
    protected $searchService;

    /**
     * EntityRepo constructor.
     * @param EntityProvider $entityProvider
     * @param SearchService $searchService
     */
    public function __construct(
        EntityProvider $entityProvider,
        SearchService $searchService
    ) {
        $this->entityProvider    = $entityProvider;
        $this->searchService     = $searchService;
    }

    /**
     * Base query for searching entities
     * @param string $type
     * @param bool $allowDrafts
     * @return \Illuminate\Database\Query\Builder
     */
    protected function entityQuery($type, $allowDrafts = false)
    {
    }

    /**
     * Check if an entity with the given value exists.
     * @param $type
     * @param $field
     * @param $value
     * @return bool
     */
    public function exists($type = 'opportunity', $field = 'opp_name', $value)
    {
        return $this->entityQuery($type)->where($field, '=', $value)->exists();
    }

    /**
     * Get an entity by ID
     * @param string $type
     * @param integer $id
     * @param bool $allowDrafts
     * @return \App\Entities\Entity
     */
    public function getById($type, $id, $allowDrafts = false)
    {
        $query = $this->entityQuery($type, $allowDrafts);

        return $query->find($id);
    }

    /**
     * @param string $type
     * @param []int $ids
     * @param bool $allowDrafts
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Collection
     */
    public function getManyById($type, $ids, $allowDrafts = false)
    {
        $query = $this->entityQuery($type, $allowDrafts);

        return $query->whereIn('id', $ids)->get();
    }

    /**
     * Get all entities of a type, limited by count unless count is false.
     * @param string $type
     * @param integer|bool $count
     * @return Collection
     */
    public function getAll($type, $count = 20)
    {
        $q = $this->entityQuery($type, false)->orderBy('id', 'desc');
        if ($count !== false) {
            $q = $q->take($count);
        }
        return $q->get();
    }

    /**
     * Get all entities in a paginated format
     * @param $type
     * @param int $count
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllPaginated($type, $count = 10)
    {
        return $this->entityQuery($type)->orderBy('id', 'desc')->paginate($count);
    }

    /**
     * Get the latest opportunities added to the system with pagination.
     * @param string $type
     * @param int $count
     * @return mixed
     */
    public function getRecentlyCreatedPaginated($type, $count = 20)
    {
        return $this->entityQuery($type)->orderBy('created_at', 'desc')->paginate($count);
    }

    /**
     * Get the latest opportunities added to the system with pagination.
     * @param string $type
     * @param int $count
     * @return mixed
     */
    public function getRecentlyUpdatedPaginated($type, $count = 20)
    {
        return $this->entityQuery($type)->orderBy('updated_at', 'desc')->paginate($count);
    }

    /**
     * Get all child objects of a opportunity.
     * Returns a sorted collection of forecasts and details.
     * @param bool $filterDrafts
     * @return mixed
     */
    public function getOpportunityChildren(Opportunity $opportunity, $filterDrafts = false)
    {
        $q        = $this->opportunityChildrenQuery($opportunity->id, $filterDrafts)->get();
        $entities = [];
        $parents  = [];
        $tree     = [];

        return collect($tree);
    }

    /**
     * Get the children of a opportunity in an efficient single query.
     * @param string $opportunity_id
     * @param bool $filterDrafts
     * @return QueryBuilder
     */
    public function opportunityChildrenQuery($opportunity_id, $filterDrafts = false)
    {
    }

    /**
     * Check if a opportunity name already exists in the database.
     * @param string $name
     * @param bool|integer $currentId
     * @return bool
     */
    protected function nameExists($name, $currentId = false)
    {
        $query = $this->entityProvider->get('opportunity')->where('opp_name', '=', $name);
        if ($currentId) {
            $query = $query->where('id', '!=', $currentId);
        }
        return $query->count() > 0;
    }

    /**
     * Destroy the provided opportunity and all its child entities.
     * @param \App\Entities\Opportunity $opportunity
     * @throws NotifyException
     * @throws \Throwable
     */
    public function destroyOpportunity(Opportunity $opportunity)
    {
        foreach ($opportunity->forecasts as $forecast) {
            $this->destroyForecast($forecast);
        }
        $this->destroyEntityCommonRelations($opportunity);
        $opportunity->delete();
    }

    /**
     * Destroy a given forcast.
     */
    public function destroyForecast(Forecast $forecast)
    {
    }

    /**
     * Destroy a given detail.
     */
    public function destroyDetail(Detail $detail)
    {
    }
}
