<?php

namespace davidxu\upload\assets;

use davidxu\base\assets\BaseAppAsset;
use yii\web\AssetBundle;

class PluploadAsset extends AssetBundle
{
    public $sourcePath = "@npm/plupload/js/";
    public $sourceUrl = "@npm/plupload/js/";

    public $js = [
        'plupload.full.min.js',
    ];
    
    public $css = [
    ];
    
    public $depends = [
        BaseAppAsset::class,
        CompatibilityIEAsset::class,
    ];
}
