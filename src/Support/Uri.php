<?php
namespace Zodream\Spider\Support;

use Zodream\Http\Http;
use Zodream\Http\Uri as BaseUri;

class Uri extends BaseUri {

    public function asHttp() {
        return new Http($this);
    }
}