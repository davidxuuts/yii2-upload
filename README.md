UNDER REDESIGN
==========

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

< TO BE COMPLETED >

For qiniu, on view side
```php
<?= \davidxu\upload\Upload::widget(
    Upload::class, [
        'uploadDrive' => 'qiniu',
        'uploadUrl' => 'https://up.qiniup.com',
); ?>```

For wechat, on view side
```php
<?= \davidxu\upload\UploadWechat::widget(
    Upload::class, [
        'uploadUrl' => \yii\helpers\Url::to('path/to/upload'),
        'type' => 'video',
); ?>```

TO BE CONTINUED
