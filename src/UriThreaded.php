<?php
namespace Zodream\Spider;

use Threaded;
use Zodream\Http\Uri;

class UriThreaded extends Threaded {

    const STATUS_NONE = 0;

    const STATUS_DEALING = 1;
    const STATUS_COMPLETE = 2;
    const STATUS_FAILURE = 3;

    /**
     * @var Uri
     */
    protected $baseUri;

    protected $uriList = [];

    public function addUri($url) {
        $uri = $this->getRealUrl();
        if (!$this->isMatch($url)) {
            return $this;
        }
        $this->uriList[] = [
            'url' => $url,
            'status' => self::STATUS_NONE
        ];
        return $this;
    }

    public function getUri() {

    }


    public function getRealUrl($url) {
        return $this->baseUri->decode($url)->encode();
    }

    public function isMatch($url) {
        return true;
    }


}