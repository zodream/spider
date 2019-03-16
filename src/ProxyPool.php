<?php
namespace Zodream\Spider;

use Zodream\Http\Http;
use Zodream\Service\Factory;

class ProxyPool {
    protected $data = [];

    protected $index = -1;

    public function next() {
        $length = count($this->data);
        if ($length < 1) {
            return false;
        }
        $this->index ++;
        if ($length <= $this->index) {
            $this->index = 0;
        }
        return $this->data[$this->index];
    }

    /**
     * @param Http $http
     * @return static
     */
    public function apply(Http $http) {
        $url = $this->next();
        if (!empty($url)) {
            $http->setProxy($url);
        }
        return $this;
    }

    /**
     * @return static
     * @throws \Exception
     */
    public function load() {
        $cache_key = 'spider_ip_proxy';
        $data = cache()->getOrSet($cache_key, function () {
            $content = file_get_contents('http://zodream.localhost/proxy?format=json');
            return empty($content) ? json_decode($content) : [];
        }, 3600);
        foreach ($data as $url) {
            if (is_array($url)) {
                $url = sprintf('%s://%s:%s',
                    strtolower($url['http']), $url['ip'], $url['port']);
            }
            $this->add($url);
        }
        return $this;
    }

    /**
     * @param $url
     * @return static
     */
    public function add($url) {
        $this->data[] = trim($url);
        return $this;
    }

    /**
     * @return static
     * @throws \Exception
     */
    public function valid() {
        if (empty($this->data)) {
            return $this;
        }
        $data = [];
        $http = new Http();
        foreach ($this->data as $url) {
            if (empty($url)) {
                continue;
            }
            $http->url('https://www.baidu.com/')
                ->setProxy($url)
                ->text();
            if ($http->getStatusCode() == 200) {
                $data[] = $url;
            }
        }
        $this->data = $data;
        return $this;
    }
}