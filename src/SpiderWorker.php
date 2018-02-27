<?php
namespace Zodream\Spider;

use Worker;

class SpiderWorker extends Worker {

    protected $uri;

    public function __construct(UriThreaded $uri) {
        $this->uri = $uri;
    }

    public function getUri() {
        return $this->uri;
    }
}