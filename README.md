# php anti crawl
require redis
This package use https://github.com/nicolasff/phpredis PHP extension, please make sure this is installed before continuing.
##Installation:
```javascript
composer require megaads-vn/anti-crawl
```
###OR
**add in file composer.json**
```javascript
"require": {
	"megaads-vn/anti-crawl":"dev-master"
}
```
config for yii1
```javascript
    'components' => array(
        "antiCrawl"=>array(
            "class" => "application.vendor.megaads-vn.anti-crawl.src.RequestLimits",
        )
    )
```
use for yii1
```javascript
$isOk = Yii::app()->antiCrawl->check();
```