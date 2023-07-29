<?php
/*
 * Copyright (c) 2023.
 * @author David Xu <david.xu.uts@163.com>
 * All rights reserved.
 */

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
