<?php


namespace davidxu\upload;

use yii\base\InvalidArgumentException;
use yii\bootstrap\InputWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use Yii;
use yii\i18n\PhpMessageSource;

class UploadWechat extends InputWidget
{
    /**
     * @var string
     */
    public $uploadUrl;
    public $uploadBaseUrl;
    public $uploadId;
    
    // $type: image|voice|video|thumb|news
    public $type = 'image';
    // $materialType: perm|temp
    public $materialType = 'perm';
    public $maxFileNumber = 1;
    public $template = 'wechat-image';
    public $multiSelection = false;
    public $descriptionAttribute = 'description';
    
    public $showUploadProgress = true;
    
    public $id;
    public $events = [];
    public $options;
    public $resizeOptions = [
        'quality' => 90,
        'crop' => false,
    ];
//    protected $auth;
    
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
    
    private $audioDuration = 60;
    private $maxSizePermImage = '10mb';
    private $maxSizePermVideo = '10mb';
    private $maxSizePermVoice = '2mb';
    private $maxSizePermThumb = '64kb';
    
    
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
        
        if (!$this->uploadUrl || $this->uploadUrl === '') {
            throw new InvalidArgumentException(
                Yii::t(
                    'uploadtr',
                    '{class} must specify "url" property value.',
                    ['class' => get_class($this)]
                )
            );
        }
        
        if (empty($this->uploadId)) {
            $this->uploadId = 'uploader_' . $this->id;
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
        
        if ($this->type === 'image' || $this->type === 'thumb') {
            $this->options['resize'] = $this->resizeOptions;
        }
        
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
        
        if (empty($this->uploadBaseUrl) || $this->uploadBaseUrl === '') {
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
            'uploadBaseUrl' => $this->uploadBaseUrl,
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
        
        switch ($this->type) {
            case 'image':
                $filters = [
                    'mime_types' => [
                        [
                            'title' => 'Image files',
                            'extensions' => 'bmp,png,jpeg,jpg,gif',
                        ],
                    ],
                    'max_file_size' => $this->maxSizePermImage ?? '10mb',
                ];
                break;
            case 'voice':
                $filters = [
                    'mime_types' => [
                        [
                            'title' => 'Voice files',
                            'extensions' => 'mp3,wma,wav,amr',
                        ],
                    ],
                    'max_file_size' => $this->maxSizePermVoice ?? '2mb',
                    'max_duration' => $this->audioDuration,
                ];
                break;
            case 'video':
                $filters = [
                    'mime_types' => [
                        [
                            'title' => 'Video files',
                            'extensions' => 'mp4',
                        ],
                    ],
                    'max_file_size' => $this->maxSizePermVideo ?? '10mb',
                ];
                break;
            case 'thumb':
                $filters = [
                    'mime_types' => [
                        [
                            'title' => 'Thumbnail files',
                            'extensions' => 'jpg',
                        ],
                    ],
                    'max_file_size' => $this->maxSizePermThumb ?? '64kb',
                ];
                break;
            default:
                $filters = [];
        }

        $defaultJsOptions = [
            'runtimes' => 'html5,flash,silverlight,html4',
            'url' => $this->uploadUrl,
            'container' => $this->containerOptions['id'],
            'browse_button' => $this->browseOptions['id'],
            'multi_selection' => $this->multiSelection && ($this->maxFileNumber > 1),
            'filters' => $filters,
            'max_retries' => 3,
//            'chunk_size' => 4 * 1024 * 1024,
            'flash_swf_url' => $bundle->baseUrl . "/Moxie.swf",
            'silverlight_xap_url' => $bundle->baseUrl . "/Moxie.xap",
            'error_container' => $this->errorContainer,
        ];
        $this->options['error_image_url'] = $bundle->baseUrl . '/images/error.png';
    
        $options = ArrayHelper::merge($defaultJsOptions, $this->options);
        
        $externalOptions = [
            'multipart_params' => [
//                Yii::$app->request->csrfParam => Yii::$app->request->csrfToken,
                'merchant_id' => 0,
                'max_file_nums' => $this->maxFileNumber,
                'media_type' => $this->type,
                'is_temporary' => $this->materialType,
                'year' => (int)(date('Y')),
                'month' => (int)(date('m')),
                'day' => (int)(date('d')),
                'link_type' => 1,
                'status' => 1,
            ],
        ];
        $options = Json::encode(ArrayHelper::merge($options, $externalOptions));
        
        $id = $this->id;
        $langMaxAudioDurationLimit = Yii::t('uploadtr', 'Max duration should be {duration}', [
            'duration' => $this->audioDuration ?? 60,
        ]);
        $jsMaxDuration = /** @lang JavaScript */ <<<MAX_DURATION_JS

    plupload.addFileFilter('max_duration', function(maxDuration, file, cb) {
        let self = this
        let audioUrl = URL.createObjectURL(file.getNative())
        let audio = new Audio(audioUrl)
        let duration = 0
        audio.addEventListener('loadedmetadata', function(_event) {
            duration = Math.floor(audio.duration)
            if (typeof duration !== 'undefined' && maxDuration && duration > maxDuration) {
                self.trigger('Error', {
                    code : -800,
                    message : '{$langMaxAudioDurationLimit}',
                    file : file
                })
                cb(false)
            } else {
                cb(true)
            }
        })
    })
MAX_DURATION_JS;
        $jsMaxSize = /** @lang JavaScript */ <<<MAX_SIZE_JS

    plupload.addFileFilter('max_file_size', function(maxSize, file, cb) {
        let undef
        maxSize = plupload.parseSize(maxSize)
        const reader = new FileReader()
        if (file.size !== undef && maxSize && file.size > maxSize) {
		    this.trigger('Error', {
			    code : plupload.FILE_SIZE_ERROR,
			    message : plupload.translate('File size error.'),
			    file : file
		    })
		    cb(false)
	    } else {
		    cb(true)
	    }
    })
MAX_SIZE_JS;
        $jsUpload = /** @lang JavaScript */ <<<UPLOAD_JS

$(function() {
    {$this->buildFileFilters($jsMaxSize)}
    {$this->buildFileFilters($jsMaxDuration)}
    const {$this->uploadId} = new plupload.Uploader({$options})
    {$this->uploadId}.init()
    {$this->buildBinds()}
    {$this->uploadId}.start()
})
UPLOAD_JS;
        $view->registerJs($jsUpload);
    }
    
    protected function registerAssetBundle()
    {
        return UploadAsset::register($this->view);
    }

    protected function buildFileFilters($fileFilter) {
        return $fileFilter;
    }
    
    protected function buildBinds()
    {
        $events = ArrayHelper::merge(self::buildEvents(), $this->events);
        if (empty($events)) {
            return;
        }
        $script = '';
        foreach ($events as $event => $bindScript) {
            $script .= $this->uploadId .".bind('$event', $bindScript)\n";
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
            'Error',
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
            'errorImage' => $this->bundle->baseUrl . "/images/fail.png",
            'voiceDefaultUrl' => $this->bundle->baseUrl . '/images/music.png',
            'videoDefaultUrl' => $this->bundle->baseUrl . '/images/play.png',
            'fileDefaultUrl' => $this->bundle->baseUrl . '/images/file.png',
        ];
        if ($this->type === 'video') {
            $configs['videoDescriptionAttribute'] = $this->descriptionAttribute;
        }
        $event = new UploadWechatEvents($configs);
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
}
