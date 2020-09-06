<?php
namespace Zodream\Spider;

use Zodream\Disk\File;
use Zodream\Http\Http;
use Zodream\Http\HttpBatch;
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
        return static::http($url);
    }

    /**
     * 多线程批量获取数据
     * @param array $urls
     * @return Html[]
     * @throws Exception
     */
    public static function manyUrl(array $urls) {
        return self::manyHttp($urls);
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
        return new Html(Http::tryGzipDecode($html));
    }

    /**
     * 批量处理
     * @param array $https
     * @return Html[]
     * @throws Exception
     */
    public static function manyHttp(array $https) {
        if (empty($https)) {
            return [];
        }
        $box = new HttpBatch();
        foreach ($https as $http) {
            $box->addHttp(self::getHttp($http)->setOption(CURLOPT_TIMEOUT, 60));
        }
        $box->execute();
        return $box->map(function (Http $http) {
            if (empty($http->getResponseHeader('errorNo'))) {
                return null;
            }
            return new Html($http->getResponseText());
        });
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
            $http = new Http($url);
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