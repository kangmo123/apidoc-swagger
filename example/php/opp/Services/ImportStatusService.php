<?php

namespace App\Services;

use Illuminate\Cache\CacheManager;
use Psr\SimpleCache\InvalidArgumentException;

class ImportStatusService
{
    /**
     * @var string
     */
    protected $taskId;

    /**
     * @var CacheManager
     */
    protected $cache;

    /**
     * @var array
     */
    protected $cacheData = [];

    const CACHE_PREFIX = 'import-status:';
    const CACHE_EXPIRE = 60;

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';
    const STATUS_PROCESSING = 'processing';

    /**
     * ImportStatusService constructor.
     * @param $taskId
     */
    public function __construct($taskId)
    {
        $this->taskId = $taskId;
        $this->cache = app('cache');
    }

    /**
     * @param $count int
     * @return bool
     */
    public function succeed($count)
    {
        return $this->setCache([
            'status' => self::STATUS_SUCCESS,
            'count' => $count,
        ]);
    }

    /**
     * @param $count
     * @param $file
     * @return bool
     */
    public function fail($count, $file)
    {
        return $this->setCache([
            'status' => self::STATUS_FAILURE,
            'count' => $count,
            'file' => $file,
        ]);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        if (empty($this->cacheData)) {
            $this->cacheData = $this->cache->get($this->getCacheKey()) ?? [];
        }
        if (empty($this->cacheData) or !is_array($this->cacheData)) {
            return self::STATUS_PROCESSING;
        }
        return $this->cacheData['status'];
    }

    /**
     * @return int
     */
    public function getCount()
    {
        if (empty($this->cacheData)) {
            $this->cacheData = $this->cache->get($this->getCacheKey()) ?? [];
        }
        if (empty($this->cacheData) or !is_array($this->cacheData)) {
            return 0;
        }
        return $this->cacheData['count'];
    }

    /**
     * @return string|null
     */
    public function getFile()
    {
        if (empty($this->cacheData)) {
            $this->cacheData = $this->cache->get($this->getCacheKey()) ?? [];
        }
        if (empty($this->cacheData) or !is_array($this->cacheData)) {
            return null;
        }
        return $this->cacheData['file'];
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return self::CACHE_PREFIX . $this->taskId;
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function setCache(array $data)
    {
        try {
            $success = $this->cache->set($this->getCacheKey(), $data, self::CACHE_EXPIRE);
        } catch (InvalidArgumentException $exception) {
            return false;
        }
        return $success;
    }
}
