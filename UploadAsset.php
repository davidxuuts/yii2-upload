<?php

namespace davidxu\upload;

use davidxu\upload\assets\CompatibilityIEAsset;
use yii\bootstrap\BootstrapAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

class UploadAsset extends AssetBundle
{
    public $sourcePath = "@davidxu/upload/assets";
    public $baseUrl = "@davidxu/upload/assets";
    
    public $js = [
//        'moxie.min.js',
        'plupload.full.min.js',
//        'plupload.dev.js',
        'i18n/zh_CN.js',
        'plupload.uploadItem.js',
        'js/sha1.min.js',
        'js/qetag.js',
    ];
    
    public $css = [
        'https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css',
        'css/upload.css',
    ];
    
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        CompatibilityIEAsset::class,
    ];
}
