<?php


namespace davidxu\upload;

use davidxu\base\assets\QETagAsset;
use davidxu\base\assets\QiniuJsAsset;
use davidxu\base\helpers\StringHelper;
use davidxu\base\widgets\InputWidget;
use davidxu\upload\assets\UploadAsset;
use yii\base\InvalidArgumentException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use Yii;
use yii\i18n\PhpMessageSource;

class Upload extends InputWidget
{
    /**
     * @var string
     */
    public $resizeOptions = [
        'quality' => 90,
        'crop' => false,
    ];
    public $filters = [];

    protected $containerId;

    private $_view;
    private $_encodedClientOptions;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        $this->_view = $this->getView();
        $this->registerTranslations();
        $this->containerId = 'plup' . StringHelper::generatePureString(14);
        $this->options['id'] = StringHelper::getInputId($this->name);

//        // PreviewTemplate start
//        $previewTemplatePreview = Html::tag('div', '<img data-dz-thumbnail />', ['class' => 'preview']);
//        $previewTemplateInfo = Html::tag('div',
//            "<p class=\"size text-right\" data-dz-size></p>\n"
//            . "<p class=\"name text-center text-middle\" data-dz-name></p>", [
//                'class' => 'info'
//            ]
//        );
//        $previewTemplateProgressBar = Html::tag('div', '', [
//            'class' => 'progress-bar progress-bar-striped progress-bar-animated progress-bar-success',
//            'style' => 'width: 0;',
//            'role' => 'progressbar',
//            'data-dz-uploadprogress' => null,
//        ]);
//        $previewTemplateProgress = Html::tag('div', $previewTemplateProgressBar, [
//            'class' => 'progress active',
//            'aria-valuemin' => '0',
//            'aria-valuemax' => '100',
//            'aria-valuenow' => '0',
//        ]);
//        $previewTemplate = Html::tag('div',
//            $previewTemplatePreview . "\n"
//            . $previewTemplateInfo . "\n"
//            . $previewTemplateProgress . "\n",
//            [
//                'class' => 'col',
//            ]
//        );

        $this->clientOptions = ArrayHelper::merge($this->clientOptions, [
            'url' => $this->url,
            'container' => $this->containerId,
            'browse_button' => 'fileinput_' . $this->containerId,
            'multi_selection' => false,
            'max_retries' => 3,
            'chunk_size' => $this->chunkSize,
            'flash_swf_url' => Yii::getAlias('@npm/plupload/js/Moxie.swf'),
            'silverlight_xap_url' => Yii::getAlias('@npm/plupload/js/Moxie.xap'),
        ]);
        $this->_encodedClientOptions = Json::encode($this->clientOptions);
        $this->registerAssets($this->_view);
    }
    
    /**
     * @inheritDoc
     */
    public function run()
    {
        parent::run();
        $html = [];
        $html[] = ($this->hasModel())
            ? Html::activeHiddenInput($this->model, $this->attribute, $this->options)
            : Html::hiddenInput($this->name, $this->value, $this->options);
        $inputButton = Html::tag('div', '<i class="fas fa-upload"></i>', [
            'class' => 'fileinput-button',
            'id' => 'fileinput_' . $this->containerId,
        ]);
        $html[] = Html::tag('div', $inputButton, ['class' => 'col text-center']);
        echo Html::tag('div', implode("\n", $html), [
            'class' => 'row upload-previews',
            'id' => $this->containerId,
        ]);
        $this->registerScripts();
    }

    protected function registerAssets($_view)
    {
        UploadAsset::register($_view);
        if ((bool)$this->isQiniuDrive()) {
            QiniuJsAsset::register($_view);
        }
        if ($this->secondUpload) {
            QETagAsset::register($_view);
        }
    }

    protected function registerScripts()
    {
        $js = /** @lang Javascript */ <<<UPLOAD_JS
$(function() {
    let uploader_{$id} = new plupload.Uploader({$this->_encodedClientOptions})
    uploader_{$id}.init()
UPLOAD_JS;
        $this->_view->registerJs($js);
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
