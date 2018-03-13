<?php
namespace Zodream\Spider;

use Zodream\Disk\File;
use Zodream\Http\Http;
use Zodream\Spider\Support\Html;
use Zodream\Spider\Support\Uri;

class Spider {

    public static function url($url) {
        $uri = $url instanceof Uri ? $url : new Uri($url);
        return static::http($uri->asHttp());
    }

    public static function http(Http $http) {
        return new Html($http->text());
    }

    public static function file($file) {
        $file = $file instanceof File ? $file : new File($file);
        return new Html($file->read());
    }
}