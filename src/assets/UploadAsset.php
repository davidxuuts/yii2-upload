<?php

namespace davidxu\upload\assets;

use yii\web\AssetBundle;

class UploadAsset extends AssetBundle
{
    public $sourcePath = "@davidxu/upload/";

    public $js = [
    ];
    
    public $css = [
        'css/style' . (YII_ENV_PROD ? '' : '.min') . '.css',
    ];
    
    public $depends = [
        PluploadAsset::class,
    ];
}
