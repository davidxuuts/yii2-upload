<?php
/*
 * Copyright (c) 2023.
 * @author David Xu <david.xu.uts@163.com>
 * All rights reserved.
 */

namespace davidxu\upload\assets;

use davidxu\base\assets\BaseAppAsset;
use yii\web\AssetBundle;

class PluploadAsset extends AssetBundle
{
    public $sourcePath = "@npm/plupload/js/";
    public string $sourceUrl = "@npm/plupload/js/";

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
