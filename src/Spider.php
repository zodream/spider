<?php
namespace Zodream\Spider;

use Zodream\Disk\File;
use Zodream\Http\Http;
use Zodream\Service\Factory;
use Zodream\Spider\Support\Html;
use Zodream\Spider\Support\Uri;
use Exception;

class Spider {

    public static function url($url) {
        $uri = $url instanceof Uri ? $url : new Uri($url);
        return static::http($uri->asHttp());
    }

    public static function http(Http $http) {
        try {
            $html = $http->text();
        } catch (Exception $ex) {
            $html = '';
            Factory::log()->error($ex->getMessage());
        }
        return new Html($html);
    }

    public static function file($file) {
        $file = $file instanceof File ? $file : new File($file);
        return new Html($file->read());
    }
}