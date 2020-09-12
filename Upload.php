<?php


namespace davidxu\upload;

use davidxu\upload\actions\QiniuUploadAction;
use Qiniu\Auth;
use yii\base\InvalidArgumentException;
use yii\bootstrap\InputWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use Yii;
use yii\i18n\PhpMessageSource;

class Upload extends InputWidget
{
    /**
     * @var string
     */
    public $uploadUrl = 'https://up.qiniup.com';
    public $storeInDB = true;
    public $uploadDrive = 'qiniu';
    public $uploadPath = '';
    public $uploadBaseUrl = '';
    
    public $maxFileNumber = 1;
    public $template = 'create';
    public $attachmentProperty = 'attachment';
    public $multiSelection = false;
    public $maxFileSize = 100 * 1024 * 1024;
    
    public $showUploadProgress = true;
    
    public $qiniuBucket;
    public $qiniuAccessKey;
    public $qiniuSecretKey;
    public $qiniuCallbackUrl;
    public $qiniuCallbackBody = [
        'drive' => 'qiniu',
        'specific_type' => '$(mimeType)',
        'path' => '$(key)',
        'hash' => '$(etag)',
        'size' => '$(fsize)',
        'name' => '$(fname)',
        'extension' => '$(ext)',
        'member_id' => '$(x:member_id)',
        'width' => '$(imageInfo.width)',
        'height' => '$(imageInfo.height)',
        'year' => '$(x:year)',
        'month' => '$(x:month)',
        'day' => '$(x:day)',
        'store_in_db' => '$(x:store_in_db)',
    ];
    
    public $id;
    public $events = [];
    public $options;
    public $resizeOptions = [
        'quality' => 90,
        'crop' => false,
    ];
    protected $auth;
    
    protected $htmlOptions = ['class' => 'upload_wrapper'];
    
    protected $previewContainer;
    protected $previewOptions = ['class' => 'upload_preview'];
    protected $containerOptions = ['class' => 'upload_container'];
    
    protected $browseIcon = 'ionicons';
    protected $browseLabel = '<span class="add-file glyphicon glyphicon-plus"></span>';
    protected $browseOptions;
    
    protected $uploadLabel;
    protected $uploadOptions = ['class' => 'upload_label'];
    
    protected $errorContainer;
    
    protected $inputElement;
    
    protected $bundle;
    
    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        $this->bundle = $this->registerAssetBundle();
        $this->registerTranslations();
        if (empty($this->id)) {
            $this->id = $this->getId();
        }
        
        if (!isset($this->htmlOptions['id'])) {
            $this->htmlOptions['id'] = $this->id;
        }
        // Make sure the upload URL is provided
        if (!$this->uploadUrl || $this->uploadUrl === '') {
            throw new InvalidArgumentException(
                Yii::t(
                    'uploadtr',
                    '{class} must specify "url" property value.',
                    ['class' => get_class($this)]
                )
            );
        }
        
        if ($this->hasModel()) {
            if (!preg_match(Html::$attributeRegex, $this->attribute, $matchs)) {
                throw new InvalidArgumentException(
                    Yii::t('uploadtr', 'Attribute name must contain word charactes only')
                );
            }
            $this->attribute = $matchs[2];
            
            $model = $this->model;
            $attribute = $this->attribute;
            $this->name = Html::getInputName($this->model, $this->attribute);
            $value = $model->$attribute;
            if ($value && $this->multiSelection && is_array($value)) {
                $model->$attribute = [$value];
            }
            $this->inputElement = Html::getInputId($this->model, $this->attribute);
            $this->options['input_name'] = $this->name;
        } else {
            if (!$this->inputElement) {
                $this->inputElement = $this->id . '_input';
            }
        }
        
        if (!isset($this->browseOptions['id'])) {
            $this->browseOptions['id'] = $this->id . '_browse';
        }
        if (!isset($this->browseOptions['class'])) {
            $this->browseOptions['class'] = 'upload_btn-browse';
        }
        
        if ($this->multiSelection) {
            Html::addCssClass($this->htmlOptions, 'upload_many');
        } else {
            Html::addCssClass($this->htmlOptions, 'upload_one');
            $this->maxFileNumber = 1;
        }
        
        $this->options['resize'] = $this->resizeOptions;
        
        // For preview
        if (!isset($this->previewOptions['id'])) {
            $this->previewOptions['id'] = $this->id . "_preview";
        }
        $this->previewContainer = $this->id . "_preview";

        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = $this->id . "_container";
        }
        
        if (!isset($this->errorContainer)) {
            $this->errorContainer = $this->id . '_error';
        }
        
        if (empty($this->uploadBaseUrl)) {
            if (
                isset(Yii::$app->params['uploadBaseUrl'])
                && (Yii::$app->params['uploadBaseUrl'] !== '' || !empty(Yii::$app->params['uploadBaseUrl']))
            ) {
                $this->uploadBaseUrl = Yii::$app->params['uploadBaseUrl'];
            } else {
                $this->uploadBaseUrl = Yii::getAlias('@web/');
            }
        }
        
        $this->registerAssets();
    }
    
    /**
     * @inheritDoc
     */
    public function run()
    {
        parent::run();
        return $this->renderHtml();
    }
    
    protected function renderHtml()
    {
        if (empty($this->uploadLabel)) {
            $this->uploadLabel = Yii::t('uploadtr', 'Upload files');
        }

        $options = [
            'htmlOptions' => $this->htmlOptions,
            'containerOptions' => $this->containerOptions,
            'previewOptions' => $this->previewOptions,
            'errorContainer' => $this->errorContainer,
            'browseOptions' => $this->browseOptions,
            'browseLabel' => $this->browseLabel,
            'uploadLabel' => $this->uploadLabel,
            'uploadOptions' => $this->uploadOptions,
            'multiSelection' => $this->multiSelection,
            'maxFileNumber' => $this->maxFileNumber,
            'storeInDB' => $this->storeInDB,
            'uploadBaseUrl' => $this->uploadBaseUrl,
            'attachmentProperty' => $this->attachmentProperty,
        ];
        if ($this->hasModel()) {
            $options['model'] = $this->model;
            $options['attribute'] = $this->attribute;
        }
        return $this->render($this->template, $options);
    }
    
    protected function registerAssets()
    {
        $bundle = $this->registerAssetBundle();
        $view = $this->getView();
        
        $defaultJsOptions = [
            'runtimes' => 'html5,flash,silverlight,html4',
            'url' => $this->uploadUrl,
            'container' => $this->containerOptions['id'],
            'browse_button' => $this->browseOptions['id'],
            'multi_selection' => $this->multiSelection && ($this->maxFileNumber > 1),
            'max_file_size' => $this->maxFileSize,
            'max_retries' => 3,
            'chunk_size' => 4 * 1024 * 1024,
            'flash_swf_url' => $bundle->baseUrl . "/Moxie.swf",
            'silverlight_xap_url' => $bundle->baseUrl . "/Moxie.xap",
            'error_container' => $this->errorContainer,
        ];
        $this->options['error_image_url'] = $bundle->baseUrl . '/images/error.png';
        $options = ArrayHelper::merge($defaultJsOptions, $this->options);
        
        $externalOptions = [
            'multipart_params' => [
                'x:member_id' => Yii::$app->getUser()->getIsGuest() ? 0: Yii::$app->user->id,
                Yii::$app->request->csrfParam => Yii::$app->request->csrfToken,
                'max_file_nums' => $this->maxFileNumber,
                'x:store_in_db' => $this->storeInDB,
                'x:year' => (int)(date('Y')),
                'x:month' => (int)(date('m')),
                'x:day' => (int)(date('d')),
                'x:uploadPath' => $this->uploadPath !== '' || !empty($this->uploadPath)
                    ? $this->uploadPath
                    : 'uploads'. DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR,
            ],
        ];
        if ($this->uploadDrive === 'qiniu') {
            $externalOptions['multipart_params']['token'] = $this->getQiniuToken();
        }
        $options = Json::encode(ArrayHelper::merge($options, $externalOptions));
        
        $id = $this->id;
        $js = /** @lang Javascript */ <<<UPLOAD_JS
$(function() {
    let uploader_{$id} = new plupload.Uploader({$options})
    uploader_{$id}.init()
    {$this->buildBinds()}
})
UPLOAD_JS;
        $view->registerJs($js);
    }
    
    protected function registerAssetBundle()
    {
        return UploadAsset::register($this->view);
    }

    protected function buildBinds()
    {
        $events = ArrayHelper::merge(self::buildEvents(), $this->events);
        if (empty($events)) {
            return;
        }
        $script = '';
        foreach ($events as $event => $bindScript) {
            $script .= 'uploader_' . $this->id . ".bind('$event', $bindScript);\n";
        }
        return $script;
    }
    
    protected function buildEvents()
    {
        
        $registerEvents = [
            'Init',
            'PostInit',
            'FilesAdded',
            'FilesRemoved',
            'BeforeUpload',
            'FileUploaded',
            'UploadComplete',
            'Refresh',
            'Error'
        ];
        if ($this->showUploadProgress) {
            $registerEvents[] = 'UploadProgress';
        }
        //register script of plupload evnets
        $configs = [
            'errorContainer' => $this->errorContainer,
            'previewContainer' => $this->previewContainer,
            'multiSelection' => $this->multiSelection,
            'inputElement' => $this->inputElement,
            'fileBaseUrl' => $this->uploadBaseUrl,
        ];
        $event = new UploadEvents($configs);
        return $event->getScripts($registerEvents);
    }
    
    protected function registerTranslations()
    {
        $i18n = Yii::$app->i18n;
        $i18n->translations['upload*'] = [
                'class' => PhpMessageSource::class,
                'sourceLanguage' => 'en-US',
                'basePath' => '@davidxu/upload/messages',
                'fileMap' => [
                    '*' => 'uploadtr.php',
                ],
        ];
    }
    
    protected function getQiniuToken() {
        if (empty($this->qiniuCallbackUrl) || $this->qiniuCallbackUrl === '') {
            if (!isset(Yii::$app->params['qiniu.callbackUrl']) || Yii::$app->params['qiniu.callbackUrl'] === '') {
                throw new InvalidArgumentException(Yii::t('uploadtr', 'Invalid configuration'));
            }
            $this->qiniuCallbackUrl = Yii::$app->params['qiniu.callbackUrl'];
        }
    
        if (empty($this->qiniuBucket) || $this->qiniuBucket === '') {
            if (!isset(Yii::$app->params['qiniu.bucket']) || Yii::$app->params['qiniu.bucket'] === '') {
                throw new InvalidArgumentException(Yii::t('uploadtr', 'Invalid configuration'));
            }
            $this->qiniuBucket = Yii::$app->params['qiniu.bucket'];
        }
    
        if (empty($this->qiniuAccessKey) || $this->qiniuAccessKey === '') {
            if (!isset(Yii::$app->params['qiniu.accessKey']) || Yii::$app->params['qiniu.accessKey'] === '') {
                throw new InvalidArgumentException(Yii::t('uploadtr', 'Invalid configuration'));
            }
            $this->qiniuAccessKey = Yii::$app->params['qiniu.accessKey'];
        }
        
        if (empty($this->qiniuSecretKey) || $this->qiniuSecretKey === '') {
            if (!isset(Yii::$app->params['qiniu.secretKey']) || Yii::$app->params['qiniu.secretKey'] === '') {
                throw new InvalidArgumentException(Yii::t('uploadtr', 'Invalid configuration'));
            }
            $this->qiniuSecretKey = Yii::$app->params['qiniu.secretKey'];
        }
    
//        if (empty($this->qiniuBucket) || empty($this->qiniuAccessKey)
//            || empty($this->qiniuSecretKey) || empty($this->qiniuCallbackUrl)
//        ) {
//            throw new InvalidArgumentException(Yii::t('uploadtr', 'Invalid configuration'));
//        }
        $this->auth = new Auth($this->qiniuAccessKey, $this->qiniuSecretKey);

        $policy = [];
        if (count($this->qiniuCallbackBody) > 0 && (!empty($this->qiniuCallbackUrl) || $this->qiniuCallbackUrl !== '')) {
            $policy = [
                'callbackUrl' => $this->qiniuCallbackUrl,
                'callbackBody' => Json::encode($this->qiniuCallbackBody),
                'callbackBodyType' => 'application/json',
            ];
        }
        return $this->auth->uploadToken($this->qiniuBucket, null, 3600, $policy);
    }
}
