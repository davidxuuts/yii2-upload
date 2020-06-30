A Yii2 Upload
=====================================
This is a Yii2 upload widget with plupload, includes 'local' upload and 'qiniu' upload

Status
------
By using this widget, you can upload files to local server or remote qiniu bucket directly

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist davidxu/yii2-upload "*"
```

or add

```
"davidxu/yii2-upload": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \davidxu\upload\AutoloadExample::widget(); ?>```