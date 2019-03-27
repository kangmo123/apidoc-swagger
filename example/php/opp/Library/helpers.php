<?php
/**
 * 助手函数定义
 *
 * 该文件中定义了一些非常常用的简单函数，因为它们不是以Class的方式定义的，因此全部放在这里进行注册。
 *
 */

use Illuminate\Contracts\Bus\Dispatcher;

if (! function_exists('bcrypt')) {
    /**
     * Hash the given value.
     *
     * @param  string  $value
     * @param  array   $options
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
                $firstChar  = substr($item, 0, 1);
                $remainPart = substr($item, 1);
                if ($firstChar == '+') {
                    $result[$remainPart] = 'asc';
                } elseif ($firstChar == '-') {
                    $result[$remainPart] = 'desc';
                } else {
                    $result[$item] = 'asc';
                }
            }
        }
        return $result;
    }
}

/**
 * 解析逗号隔开字符串
 */
if (!function_exists('parseCommaString')) {
    /**
     * @param string $value
     * @param string $separator
     * @return array
     */
    function parseCommaString($value, $separator = ',')
    {
        return array_values(array_unique(array_filter(array_map('trim', explode($separator, $value)))));
    }
}

/**
 * parse_url的反方法
 */
if (!function_exists('build_url'))
{
    /**
     * @param array $parts
     * @return string
     */
    function build_url(array $parts)
    {
        $scheme   = isset($parts['scheme']) ? ($parts['scheme'] . '://') : '';
        $host     = ($parts['host'] ?? '');
        $port     = isset($parts['port']) ? (':' . $parts['port']) : '';
        $user     = ($parts['user'] ?? '');
        $pass     = isset($parts['pass']) ? (':' . $parts['pass'])  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = ($parts['path'] ?? '');
        $query    = isset($parts['query']) ? ('?' . $parts['query']) : '';
        $fragment = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';
        return implode('', [$scheme, $user, $pass, $host, $port, $path, $query, $fragment]);
    }
}

/*
* 递归替换数组的key
* @param $array arr 数组
*/
if (!function_exists('addF')) {
    function addF($arr)
    {
        $func = function ($key) {
            if (strpos($key, 'F') !== 0 && preg_match("/[^\d-., ]/", $key)) {//数字下标不加'F'
                $key = 'F' . $key;
            }
            return $key;
        };

        $_newArr = [];
        if (!is_array($arr) || empty($arr)) {
            return $_newArr;
        }
        foreach ($arr as $k => $v) {
            $_key           = call_user_func($func, $k);
            $_newArr[$_key] = is_array($v) ? addF($v) : $v;
        }
        return $_newArr;
    }
}

if (!function_exists('is_base64')) {
    /**
     * 判断是否是base64编码的字符串
     * @param $str
     * @return bool
     */
    function is_base64($str)
    {
        if (empty($str)) {
            return false;
        }
        $compareStr = base64_encode(base64_decode($str, true));
        return $str === $compareStr ? true : false;
    }
}

if (! function_exists('dispatch_now')) {
    /**
     * Dispatch a command to its appropriate handler in the current process.
     *
     * @param  mixed  $job
     * @param  mixed  $handler
     * @return mixed
     */
    function dispatch_now($job, $handler = null)
    {
        return app(Dispatcher::class)->dispatchNow($job, $handler);
    }
}

@include_once '/usr/local/ons_agent/names/nameapi.php';

if (!class_exists('ZkHost')) {
    class ZkHost {
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

if (!function_exists('merge_config')) {
    /**
     * 合并配置项
     * @param array $original
     * @param array $merging
     * @return array
     */
    function merge_config(array $original, array $merging)
    {
        $array = array_merge($original, $merging);
        foreach ($original as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (!array_key_exists($key, $merging)) {
                continue;
            }
            if (is_numeric($key)) {
                continue;
            }
            $array[$key] = merge_config($value, $merging[$key]);
        }
        return $array;
    }
}

if (!function_exists('filter_str')) {
    function filter_str($str)
    {
        $str = trim($str);
        $arr = ['（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']', '，' => ',', '；' => ';',
            '｛'     => '{', '｝' => '}', ];
        return strtr($str, $arr);
    }
}

if (!function_exists('guid')) {
    function guid()
    {
        if (function_exists('com_create_guid')) {
            return trim(com_create_guid(), '{}');
        } else {
            mt_srand((double) microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(time() . uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $guid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            return $guid;
        }
    }
}

if (!function_exists('code')) {
    function code($prefix = 'OPP', $length = 4)
    {
        $subfix = explode(' ', microtime());

        // 取毫秒部分，再放大1000倍、再取整数部分、再用0在右侧填充，凑足3位，使最终结果保证在20位
        $micro_sec = str_pad(intval($subfix[0] * 1000), 3, 0, STR_PAD_RIGHT);

        $code = $prefix . date('YmdHis') . $micro_sec;

        return $code;
    }
}
