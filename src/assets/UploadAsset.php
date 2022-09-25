<?php

namespace davidxu\upload\assets;

use yii\web\AssetBundle;

class UploadAsset extends AssetBundle
{
    public $sourcePath = "@davidxu/upload/";

    public $js = [
    ];
    
    public $css = [
        'css/style.scss',
    ];
    
    public $depends = [
        PluploadAsset::class,
    ];
}
