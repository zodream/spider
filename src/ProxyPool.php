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
        $cache_key = 'ip_proxy';
        if (!Factory::cache()->has($cache_key)) {
            $data = Spider::url('http://api.xicidaili.com/free2016.txt')->split("\n");
            Factory::cache()->set($cache_key, $data, 3600);
        } else {
            $data = Factory::cache($cache_key);
        }
        foreach ($data as $url) {
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