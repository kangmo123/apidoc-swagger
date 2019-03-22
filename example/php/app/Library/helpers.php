<?php
/**
 * 助手函数定义
 *
 * 该文件中定义了一些非常常用的简单函数，因为它们不是以Class的方式定义的，因此全部放在这里进行注册。
 *
 */
if (!function_exists('bcrypt')) {
    /**
     * Hash the given value.
     *
     * @param  string $value
     * @param  array $options
     * @return string
     */
    function bcrypt($value, $options = [])
    {
        return app('hash')->make($value, $options);
    }
}

/**
 * 解析sort字段格式
 */
if (!function_exists('parseSortString')) {
    /**
     * @param $value
     * @return array
     */
    function parseSortString($value)
    {
        $result = [];
        if ($value) {
            $explode = array_filter(array_map('trim', explode(',', $value)));
            foreach ($explode as $item) {
                $firstChar = substr($item, 0, 1);
                $remainPart = substr($item, 1);
                if ($firstChar == '+') {
                    $result[$remainPart] = 'asc';
                }
                if ($firstChar == '-') {
                    $result[$remainPart] = 'desc';
                }
            }
        }

        return $result;
    }
}

@include_once '/usr/local/ons_agent/names/nameapi.php';

if (!class_exists('ZkHost')) {
    class ZkHost
    {
        public $ip;
        public $port;
    }
}

if (!function_exists('getStatelessHostByKey')) {
    /**
     * 获取无状态名字服务信息
     * @param $key
     * @param int $timeout
     * @return \ZkHost
     */
    function getStatelessHostByKey($key, $timeout = 500)
    {
        $host = new \ZkHost;
        getHostByKey($key, $host, $timeout);
        if (empty($host->ip) || empty($host->port)) {
            throw new \RuntimeException("resolving ip and port for {$key} failed.");
        }
        return $host;
    }
}

if (!function_exists('getStatefulHostByKey')) {
    /**
     * 获取有状态名字服务信息
     * @param $key
     * @param $route_key
     * @param int $timeout
     * @return \ZkHost
     */
    function getStatefulHostByKey($key, $route_key, $timeout = 500)
    {
        $host = new \ZkHost;
        getHostByKeyEx($key, $route_key, $host, $timeout);
        if (empty($host->ip) || empty($host->port)) {
            throw new \RuntimeException("resolving ip and port for {$key} failed.");
        }
        return $host;
    }
}

if (!function_exists('getDictValueByKey')) {
    /**
     * 获取字段服务信息
     * @param $key
     * @param int $timeout
     * @return string
     */
    function getDictValueByKey($key, $timeout = 500)
    {
        $value = null;
        getValueByKey($key, $value, $timeout);
        if ($value === null) {
            throw new \RuntimeException("resolving value for {$key} failed.");
        }
        return $value;
    }
}

if (!function_exists('CacheTags')) {
    /**
     * @return \Illuminate\Cache\RedisTaggedCache
     */
    function CacheTags()
    {
        $prefix = env('REDIS_TAG_PREFIX');
        return \Illuminate\Support\Facades\Cache::tags($prefix);
    }
}