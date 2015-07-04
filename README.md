fbapi
=====
Facebook api for yii2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist thirsight/yii2-ts-fbapi "*"
```

or add

```
"thirsight/yii2-ts-fbapi": "*"
```

to the require section of your `composer.json` file.


Setting
-------

Settings in config/bootstrap.php

```php
\Yii::$container->set('ts\fbapi\Facebook', [
    'appId' => '9166967xxxxxxxx',
    'appSecret' => 'ac4035a6183d50xxxxxxxxxxxxxxxx',
    'redirectRoute' => ['site/facebook-after-auth'],
]);
```

Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= Yii::$container->get(Facebook::className())->getLoginUrl(); ?>
```
