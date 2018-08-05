<?php
namespace Zodream\Spider;

use Zodream\Disk\File;
use Zodream\Http\Http;
use Zodream\Service\Factory;
use Zodream\Spider\Support\Html;
use Zodream\Spider\Support\Uri;
use Exception;

class Spider {

    public static $agent_list = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/17.17134',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0',
        'Mozilla/5.0 (Linux; Android 8.0; Pixel 2 Build/OPD3.170816.012) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Mobile Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1'
    ];
    /**
     * @var ProxyPool
     */
    public static $proxy_pool;

    public static function getProxyPool() {
        if (empty(self::$proxy_pool)) {
            self::$proxy_pool = new ProxyPool();
            self::$proxy_pool->load()->valid();
        }
        return self::$proxy_pool;
    }

    public static function url($url) {
        $uri = $url instanceof Uri ? $url : new Uri($url);
        return static::http($uri->asHttp());
    }

    public static function http($http) {
        $http = self::getHttp($http);
        try {
            $html = $http
                ->setOption(CURLOPT_TIMEOUT, 60)
                ->text();
        } catch (Exception $ex) {
            $html = '';
            Factory::log()->error($ex->getMessage());
        }
        return new Html($html);
    }

    public static function download($url, $file) {
        $http = self::getHttp($url);
        return $http->save($file);
    }

    public static function getHttp($url) {
        if ($url instanceof Uri) {
            $http = $url->asHttp();
        } elseif ($url instanceof Http) {
            $http = $url;
        } else {
            $http = (new Uri($url))->asHttp();
        }
        $http->setUserAgent(self::$agent_list[rand(0, 2)]);
        static::getProxyPool()->apply($http);
        return $http;
    }

    public static function file($file) {
        $file = $file instanceof File ? $file : new File($file);
        return new Html($file->read());
    }
}