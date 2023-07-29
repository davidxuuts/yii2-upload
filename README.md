A Yii2 Upload Widget
=====================================
This is a Yii2 upload widget with plupload, includes 'local' upload and 'qiniu' upload


Functions
------------
By using this widget, you can upload files to local server or remote qiniu bucket directly

It also supports second upload function by used Qiniu [QETag](https://github.com/qiniu/qetag).

Chunk upload is also enabled. If custom `chunkSize` more than system size (`get_cfg_var('upload_max_filesize')`), system upload max filesize will be used

You can upload files to local server or [Qiniu Kodo](https://developer.qiniu.com/kodo) currently.

For more Qiniu Kodo policy please refer to Qiniu website.

We use custom function for video/image upload and insert.

If cache set, you can get uploaded file information by `Yii::$app->cache->get($info['path'])` and invalidate this key by using `yii\caching\TagDependency::invalidate($cache, $info['path'] . $info['hash'])`

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

to the requirement section of your `composer.json` file.


Usage
-----
If you want to store files information in DB (MySQL/MariaDB), please execute migration file by
```
yii migrate/up @davidxu/base/migrations
```
and then simply use it in your code by:

### for Local upload

------

##### In View
```php
<?php
use davidxu\upload\Upload;
use yii\helpers\Url;
use davidxu\base\enums\AttachmentTypeEnum;

// without ActiveForm
echo Upload::widget([
    'model' => $model,
    'attribute' => 'image_src',
    'name' => 'image_src', // If no model and attribute pointed
    'url' => Url::to('@web/upload/local'),
    'clientOptions' => [
        'foo' => 'bar',
    ],
    'useFancyUI' => true, // default true
    'maxFiles' => 3,
    'acceptedFiles' => 'image/*',
    'uploadBasePath' => 'uploads/',
    // for single file,
    'existFiles' => [
        'id' => 1,
        'name' => 'some_name.jpg',
        'path' => 'some_path_for_file',
        'poster' => 'some_poster', // for video or audio placeholder
        'file_type' => AttachmentTypeEnum::TYPE_IMAGES,
        'size' => 1111,
    ],
    // for multiple files
//    'existFiles' => [
//        [
//            'id' => 1,
//            'name' => 'some_name.jpg',
//            'path' => 'some_path_for_file',
//            'poster' => 'some_poster',
//            'file_type' => AttachmentTypeEnum::TYPE_IMAGES,
//            'size' => 1111,
//        ], [
//            'id' => 2,
//            'name' => 'some_other_name.jpg',
//            'path' => 'some_path_for_other_file',
//            'poster' => 'some_poster', // for video or audio placeholder
//            'file_type' => AttachmentTypeEnum::TYPE_IMAGES,
//            'size' => 2222,
//        ],
//    ],
    'storeInDB' => true, // return file id in DB to image url instead of file url if true, migrate model db first. default false
    'metaData' => ['foo' => 'bar',],
    'crop' => true, // default false, if true, the 'maxFiles' will be forced to 1
    'aspectRatio' => 16 / 9, // default 1 /1   
    'chunkSize' => 4 * 1024 * 1024, // If `chunkSize` more than system size (`get_cfg_var('upload_max_filesize')`), system upload max filesize will be used
    'secondUpload' => true, // if true, `getHashUrl` should be set
    'getHashUrl' => Url::to('@web/upload/get-hash'),
]); ?>

<?php
// with ActiveForm
echo $form->field($model, 'image_src')
    ->widget(Upload::class, [
        'url' => Url::to('@web/upload/local'),
        'maxFiles' => 3,
        'acceptedFiles' => 'image/*',
        'uploadBasePath' => 'uploads/',
        // for single file,
        'existFiles' => [
            'id' => 1,
            'name' => 'some_name.jpg',
            'path' => 'some_path_for_file',
            'poster' => 'some_poster', // for video or audio placeholder
            'file_type' => AttachmentTypeEnum::TYPE_IMAGES,
            'size' => 1111,
        ],
        // for multiple files
//        'existFiles' => [
//            [
//                'id' => 1,
//                'name' => 'some_name.jpg',
//                'path' => 'some_path_for_file',
//                'poster' => 'some_poster',
//                'file_type' => AttachmentTypeEnum::TYPE_IMAGES,
//                'size' => 1111,
//            ], [
//                'id' => 2,
//                'name' => 'some_other_name.jpg',
//                'path' => 'some_path_for_other_file',
//                'poster' => 'some_poster', // for video or audio placeholder
//                'file_type' => AttachmentTypeEnum::TYPE_IMAGES,
//                'size' => 2222,
//            ],
//        ],
// ....
]);?>

```

##### In Upload Controller:
```php
use davidxu\dropzone\actions\LocalAction;
use davidxu\dropzone\models\Attachment;
use yii\web\Controller;

class UploadController extends Controller
{
    public function actions(): array
    {
        $actions = parent::actions();
        return ArrayHelper::merge([
            'local' => [
                'class' => LocalAction::class,
                'url' => Yii::getAlias('@web/uploads'), // default: ''. stored file base url,
                'fileDir' => Yii::getAlias('@webroot/uploads'), // default: '@webroot/uploads'. file store in this directory,
                'allowAnony' => true, // default false
                'attachmentModel' => Attachment::class,
            ],
        ], $actions);
    }
}
```

### for Qiniu upload

------

##### In View
```php
<?php
use davidxu\upload\Upload;
use yii\helpers\Url;

echo Upload::widget([
    'model' => $model,
    'attribute' => 'image_src',
    'name' => 'image_src', // If no model and attribute pointed
    'drive' => UploadTypeEnum::DRIVE_QINIU,
    // ...... (refer to local config in view)
]); ?>

<?php
// with ActiveForm
echo $form->field($model, 'image_src')
    ->widget(Upload::class, [
    'drive' => UploadTypeEnum::DRIVE_QINIU,
    'qiniuBucket' => Yii::$app->params['qiniu.bucket'],
    'qiniuAccessKey' => Yii::$app->params['qiniu.bucket'],
    'qiniuSecretKey' => Yii::$app->params['qiniu.bucket'],
    'qiniuCallbackUrl' => Yii::$app->params['qiniu.bucket'],
    // default 'qiniuCallbackBody' here, you can modify them.
//    'qiniuCallbackBody' => [
//        'drive' => UploadTypeEnum::DRIVE_QINIU,
//        'specific_type' => '$(mimeType)',
//        'file_type' => '$(x:file_type)',
//        'path' => '$(key)',
//        'hash' => '$(etag)',
//        'size' => '$(fsize)',
//        'name' => '$(fname)',
//        'extension' => '$(ext)',
//        'member_id' => '$(x:member_id)',
//        'width' => '$(imageInfo.width)',
//        'height' => '$(imageInfo.height)',
//        'duration' => '$(avinfo.format.duration)',
//        'store_in_db' => '$(x:store_in_db)',
//        'upload_ip' => '$(x:upload_ip)',
//    ];
    // ...... (refer to local config in view)
]);?>

```

##### In Upload Controller:
```php
use davidxu\dropzone\actions\QiniuAction;
use davidxu\dropzone\models\Attachment;
use yii\web\Controller;
use yii\web\BadRequestHttpException;

class UploadController extends Controller
{
     /**
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        $currentAction = $action->id;
        $novalidateActions = ['qiniu'];
        if(in_array($currentAction, $novalidateActions)) {
            // disable CSRF validation
            $action->controller->enableCsrfValidation = false;
        }
        parent::beforeAction($action);
        return true;
    }
    public function actions(): array
    {
        $actions = parent::actions();
        return ArrayHelper::merge([
            'qiniu' => [
                'class' => QiniuAction::class,
                'url' => Yii::getAlias('@web/uploads'), // default: ''. stored file base url,
                'allowAnony' => true, // default false
                'attachmentModel' => Attachment::class,
            ],
        ], $actions);
    }
}
```

Have fun!
