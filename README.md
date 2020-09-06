# spider
web spider

## 网页爬虫软件

### 基本配置

```PHP

[
    'url' => '开始网址',
    'filter' => '正则限制',
]

```

### 代理配置

config.php

这是使用内部模块代理
```php
[
    'spider.proxy' => 'http://zodream.localhost/proxy?format=json'
];
```

### 使用方法

```PHP

$spider = new Spider($configs | $uri);

```