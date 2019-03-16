<?php
namespace Zodream\Spider\Support;

use DOMDocument;
use DOMNode;
use DOMElement;
use Zodream\Service\Factory;
use Exception;

class Html {

    /**
     * @var string
     */
    protected $html;

    /**
     * @var DOMNode
     */
    protected $document;

    private $_lFound = [];

    public function __construct($html) {
        $this->setHtml($html);
    }

    /**
     * @param string $html
     * @return Html
     */
    public function setHtml($html) {
        if ($html instanceof DOMNode) {
            $this->document = $html;
            return $this;
        }
        $this->html = $html;
        return $this;
    }

    public function isEmpty() {
        return empty($this->html);
    }

    /**
     * @return string
     */
    public function getHtml() {
        if (is_null($this->html) && !empty($this->document)) {
            $this->html = $this->outerHtml();
        }
        return $this->html;
    }

    /**
     * @param $begin
     * @param null $end
     * @return Html
     */
    public function sub($begin, $end = null) {
        return new static($this->subValue($begin, $end));
    }

    /**
     * @param $begin
     * @param null $end
     * @return string
     */
    public function subValue($begin, $end = null) {
        if (!empty($begin) && !is_integer($begin)) {
            $begin = stripos($this->getHtml(), $begin);
        }
        $begin = intval($begin);
        if (!empty($end) && !is_integer($end)) {
            $end = stripos($this->getHtml(), $end, $begin);
        }
        $end = intval($end);
        if ($end > 0 && $begin >= $end) {
            return '';
        }
        if ($end <= 0) {
            return substr($this->getHtml(), $begin);
        }
        return substr($this->getHtml(), $begin, $end - $begin);
    }

    /**
     * @param $pattern
     * @param callable|null $func
     * @return $this|Collection
     */
    public function matches($pattern, callable $func = null) {
        if (!preg_match_all($pattern, $this->getHtml(), $matches, PREG_SET_ORDER)) {
            $matches = [];
        }
        if (empty($func)) {
            return new Collection($matches);
        }
        foreach ($matches as $match) {
            call_user_func($func, $match);
        }
        return $this;
    }

    /**
     * 截取
     * @param $tag
     * @return array
     */
    public function split($tag) {
        return explode($tag, $this->html);
    }

    /**
     * @param $pattern
     * @param callable|null $func
     * @return $this|Collection
     */
    public function match($pattern, callable $func = null) {
        if (!preg_match($pattern, $this->getHtml(), $match)) {
            $match = [];
        }
        if (empty($func)) {
            return new Collection($match);
        }
        call_user_func($func, $match);
        return $this;
    }

    /**
     * @param $pattern
     * @param int $index
     * @return string
     */
    public function matchValue($pattern, $index = 0) {
        if (!preg_match($pattern, $this->getHtml(), $match)) {
            return '';
        }
        if (isset($match[$index])) {
            return $match[$index];
        }
        return '';
    }

    /**
     * @return DOMNode
     */
    public function getDocument() {
        if (!empty($this->document)) {
            return $this->document;
        }
        if ($this->html instanceof DOMNode) {
            return $this->document = $this->html;
        }
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->strictErrorChecking = false;
        try {
            $dom->loadHTML($this->html, LIBXML_NOERROR);
        } catch (\ErrorException $exception) {
            $this->html = mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8');
            $dom->loadHTML($this->html, LIBXML_NOERROR);
        }
        // 不显示所有错误
        return $this->document = $dom;
    }

    public function xPath($path) {

    }

    /**
     * 深度优先查询
     *
     * @param string $selector
     * @param number $idx 找第几个,从0开始计算，null 表示都返回, 负数表示倒数第几个
     * @return Html|Html[]|boolean
     */
    public function find($selector, $idx = null) {
        if (empty($this->getDocument()->childNodes)) {
            return false;
        }
        $selectors = $this->parseSelector($selector);
        if (($count = count($selectors)) === 0) {
            return false;
        }
        for ($c = 0; $c < $count; $c++) {
            if (($level = count($selectors[$c])) === 0) {
                return false;
            }
            $this->docSearch($this->document, $idx, $selectors[$c], $level);
        }
        $found = $this->_lFound;
        $this->_lFound = [];
        if ($idx !== null) {
            if ($idx < 0) {
                $idx = count($found) + $idx;
            }
            if (isset($found[$idx])) {
                return $found[$idx];
            } else {
                return false;
            }
        }
        return $found;
    }

    /**
     * @codeCoverageIgnore
     * @param string $name
     * @return mixed
     */
    function __get($name) {
        switch ($name) {
            case 'outerText':
                return $this->outerHtml();
            case 'innerText':
            case 'html':
                return $this->innerHtml();
            case 'plainText':
            case 'text':
                return $this->plainText();
            case 'href':
                return $this->getAttr('href');
            case 'src':
                return $this->getAttr('src');
            default:
                return null;
        }
    }

    /**
     * 返回文本信息
     *
     * @return string
     */
    public function plainText() {
        return $this->docText($this->getDocument());
    }
    /**
     * 获取innerHtml
     * @return string
     */
    public function innerHtml() {
        $innerHTML = "";
        $children = $this->getDocument()->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $this->document->ownerDocument->saveHTML($child) ?: '';
        }
        return $innerHTML;
    }
    /**
     * 获取outerHtml
     * @return string|bool
     */
    public function outerHtml() {
        $doc = new DOMDocument();
        $doc->appendChild($doc->importNode($this->getDocument(), true));
        return $doc->saveHTML($doc);
    }
    /**
     * 获取html的元属值
     *
     * @param string $name
     * @return string|null
     */
    public function getAttr($name) {
        $oAttr = $this->getDocument()->attributes->getNamedItem($name);
        if (isset($oAttr)) {
            return $oAttr->nodeValue;
        }
        return null;
    }

    /**
     * 匹配
     *
     * @param string $exp
     * @param string $pattern
     * @param string $value
     * @return boolean|number
     */
    protected function docMatch($exp, $pattern, $value) {
        $pattern = strtolower($pattern);
        $value = strtolower($value);
        switch ($exp) {
            case '=' :
                return ($value === $pattern);
            case '!=' :
                return ($value !== $pattern);
            case '^=' :
                return preg_match("/^" . preg_quote($pattern, '/') . "/", $value);
            case '$=' :
                return preg_match("/" . preg_quote($pattern, '/') . "$/", $value);
            case '*=' :
                if ($pattern [0] == '/') {
                    return preg_match($pattern, $value);
                }
                return preg_match("/" . $pattern . "/i", $value);
        }
        return FALSE;
    }
    /**
     * 分析查询语句
     *
     * @param string $selector_string
     * @return array
     */
    protected function parseSelector($selector_string) {
        $pattern = '/([\w\-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w\-:]+)(?:([!*^$]?=)["\']?(.*?)["\']?)?\])?([\/, ]+)/is';
        preg_match_all($pattern, trim($selector_string) . ' ', $matches, PREG_SET_ORDER);
        $selectors = [];
        $result = [];
        foreach ($matches as $m) {
            $m [0] = trim($m [0]);
            if ($m [0] === '' || $m [0] === '/' || $m [0] === '//')
                continue;
            if ($m [1] === 'tbody')
                continue;
            list ($tag, $key, $val, $exp, $no_key) = [$m [1], null, null, '=', false];
            if (!empty ($m [2])) {
                $key = 'id';
                $val = $m [2];
            }
            if (!empty ($m [3])) {
                $key = 'class';
                $val = $m [3];
            }
            if (!empty ($m [4])) {
                $key = $m [4];
            }
            if (!empty ($m [5])) {
                $exp = $m [5];
            }
            if (!empty ($m [6])) {
                $val = $m [6];
            }
            // convert to lowercase
            $tag = strtolower($tag);
            $key = strtolower($key);
            // elements that do NOT have the specified attribute
            if (isset ($key [0]) && $key [0] === '!') {
                $key = substr($key, 1);
                $no_key = true;
            }
            $result [] = [$tag, $key, $val, $exp, $no_key];
            if (trim($m [7]) === ',') {
                $selectors [] = $result;
                $result = [];
            }
        }
        if (count($result) > 0) {
            $selectors [] = $result;
        }
        return $selectors;
    }
    /**
     * 深度查询
     *
     * @param DOMNode $search
     * @param          $idx
     * @param          $selectors
     * @param          $level
     * @param int $search_level
     * @return bool
     */
    protected function docSearch(DOMNode $search, $idx, $selectors, $level, $search_level = 0) {
        if ($search_level >= $level) {
            $rs = $this->docSeek($search, $selectors, $level - 1);
            if ($rs !== false && $idx !== null) {
                if ($idx == count($this->_lFound)) {
                    $this->_lFound[] = new self($rs);
                    return true;
                } else {
                    $this->_lFound[] = new self($rs);
                }
            } elseif ($rs !== false) {
                $this->_lFound[] = new self($rs);
            }
        }
        if (!empty($search->childNodes)) {
            foreach ($search->childNodes as $val) {
                if ($this->docSearch($val, $idx, $selectors, $level, $search_level + 1)) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * 获取tidy_node文本
     *
     * @param DOMNode $node
     * @return string
     */
    protected function docText(DOMNode $node) {
        return $node->textContent;
    }
    /**
     * 匹配节点,由于采取的倒序查找，所以时间复杂度为n+m*l n为总节点数，m为匹配最后一个规则的个数，l为规则的深度,
     * @codeCoverageIgnore
     * @param DOMNode $search
     * @param array $selectors
     * @param int $current
     * @return boolean|DOMNode
     */
    protected function docSeek(DOMNode $search, $selectors, $current) {
        if (!($search instanceof DOMElement)) {
            return false;
        }
        list ($tag, $key, $val, $exp, $no_key) = $selectors [$current];
        $pass = true;
        if ($tag === '*' && !$key) {
            exit('tag为*时，key不能为空');
        }
        if ($tag && $tag != $search->tagName && $tag !== '*') {
            $pass = false;
        }
        if ($pass && $key) {
            if ($no_key) {
                if ($search->hasAttribute($key)) {
                    $pass = false;
                }
            } else {
                if ($key != 'plaintext' && !$search->hasAttribute($key)) {
                    $pass = false;
                }
            }
        }
        if ($pass && $key && $val && $val !== '*') {
            if ($key == 'plaintext') {
                $nodeKeyValue = $this->docText($search);
            } else {
                $nodeKeyValue = $search->getAttribute($key);
            }
            $check = $this->docMatch($exp, $val, $nodeKeyValue);
            if (!$check && strcasecmp($key, 'class') === 0) {
                foreach (explode(' ', $search->getAttribute($key)) as $k) {
                    if (!empty ($k)) {
                        $check = $this->docMatch($exp, $val, $k);
                        if ($check) {
                            break;
                        }
                    }
                }
            }
            if (!$check) {
                $pass = false;
            }
        }
        if ($pass) {
            $current--;
            if ($current < 0) {
                return $search;
            } elseif ($this->docSeek($this->docGetParent($search), $selectors, $current)) {
                return $search;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * 获取父亲节点
     *
     * @param DOMNode $node
     * @return DOMNode
     */
    protected function docGetParent(DOMNode $node) {
        return $node->parentNode;
    }

    public function __toString() {
        return $this->html;
    }

    public static function toText($html) {
        $html = preg_replace('/<!--[\s\S]*?-->/is', '', $html);
        $html = preg_replace('/\s+/is', ' ', $html);
        $html = preg_replace('/\<style .*?\<\\/style\>/is', '', $html);
        $html = preg_replace('/\<script.*?\<\/script>/is', '', $html);
        $html = preg_replace('/\<br\s*\/?\>/i', PHP_EOL, $html);
        $html = preg_replace('/\<\/p\>/i', PHP_EOL, $html);
        $html = preg_replace('/&(quot|#34)/i', '/', $html);
        $html = preg_replace('/&(nbsp|#160);/i', ' ', $html);
        $html = preg_replace('/&(amp|#38);/i', '&', $html);
        $html = preg_replace('/&(lt|#60);/i', '<', $html);
        $html = preg_replace('/“/i', '"', $html);
        $html = preg_replace('/&ldquo;/i', '"', $html);
        $html = preg_replace('/‘/i', '\'', $html);
        $html = preg_replace('/&lsquo;/i', '\'', $html);
        $html = preg_replace('/\'/i', '\'', $html);
        $html = preg_replace('/&rsquo;/i', '\'', $html);
        $html = preg_replace('/&(gt|#62);/i', '>', $html);
        $html = preg_replace('/”/i', '"', $html);
        $html = preg_replace('/&rdquo;/i', '"', $html);
        $html = preg_replace('/&(iexcl|#161);/i', '\xa1', $html);
        $html = preg_replace('/&(cent|#162);/i', '\xa2', $html);
        $html = preg_replace('/&(pound|#163);/i', '\xa3', $html);
        $html = preg_replace('/&(copy|#169);/i', '\xa9', $html);
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_QUOTES, 'utf-8');
        return preg_replace('/&#.*?;/i', '', $html);
    }
}