<?php
/*
 * Copyright (c) 2023.
 * @author David Xu <david.xu.uts@163.com>
 * All rights reserved.
 */

namespace davidxu\upload;

use davidxu\base\assets\FancyUIAsset;
use davidxu\base\assets\JqueryCropperJsAsset;
use davidxu\base\assets\QETagAsset;
use davidxu\base\assets\QiniuJsAsset;
use davidxu\base\helpers\StringHelper;
use davidxu\base\widgets\InputWidget;
use davidxu\upload\assets\UploadAsset;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use Yii;
use yii\i18n\PhpMessageSource;
use yii\web\View;

class Upload extends InputWidget
{
    /** @var array|int[]  */
    public array $resizeOptions = [
        'quality' => 100,
    ];
    /** @var bool */
    public bool $crop = false;
    public int|float $aspectRatio = 1;
    public bool $useFancyUI = false;
    public array $filters = [];

    protected ?string $containerId = null;

    protected View|string|null $_view = null;
    private ?string $_encodedClientOptions = null;
    private ?string $_crop = null;
    private ?string $_useFancyUI = null;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->_view = $this->getView();
        $this->registerTranslations();
        if ($this->acceptedFiles) {
            $this->clientOptions = ArrayHelper::merge($this->clientOptions, [
                'filters' => [
                    'mime_types' => [
                        [
                            'extensions' => $this->acceptedFiles,
                        ]
                    ],
                ],
            ]);
        }
        if ($this->crop) {
            $this->maxFiles = 1;
            $this->secondUpload = false;
            $this->clientOptions = ArrayHelper::merge($this->clientOptions, [
                'filters' => [
                    'mime_types' => [
                        [
                            'extensions' => 'jpg,gif,png,jpeg',
                        ]
                    ],
                ],
            ]);
        }
        $this->containerId = 'plup' . StringHelper::generatePureString(14);
        $this->options['id'] = StringHelper::getInputId($this->name);
        $this->clientOptions = ArrayHelper::merge($this->clientOptions, [
            'url' => $this->url,
            'container' => $this->containerId,
            'browse_button' => 'fileinput_' . $this->containerId,
            'multi_selection' => false,
            'max_retries' => 1,
            'chunk_size' => $this->isLocalDrive() ? $this->chunkSize : 0,
            'flash_swf_url' => $this->registerAssets($this->_view)->baseUrl . '/Moxie.swf',
            'silverlight_xap_url' => $this->registerAssets($this->_view)->baseUrl . '/js/Moxie.xap',
            'multipart_params' => $this->metaData,
        ]);
        $this->_encodedClientOptions = Json::encode($this->clientOptions);
        $this->_crop = $this->crop ? 'true' : 'false';
        $this->_useFancyUI = $this->useFancyUI ? 'true' : 'false';
        $this->registerAssets($this->_view);
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        parent::run();
        $html = [];
        $invalidFeedbackContainer = Html::tag('div', '', ['class' => 'invalid-feedback ']);
//        $html[] = ($this->hasModel())
//            ? Html::activeHiddenInput($this->model, $this->attribute, $this->options)
//            : Html::hiddenInput($this->name, $this->value, $this->options);
        $inputButton = Html::tag('div', '<i class="fas fa-upload"></i>', [
            'class' => 'fileinput-button',
            'id' => 'fileinput_' . $this->containerId,
        ]);
        $html[] = Html::tag('div', $inputButton, ['class' => 'col text-center']);
        $html[] = ($this->hasModel())
            ? (Html::activeHiddenInput($this->model, $this->attribute, $this->options) . $invalidFeedbackContainer)
            : (Html::hiddenInput($this->name, $this->value, $this->options) . $invalidFeedbackContainer);
        echo Html::tag('div', implode("\n", $html), [
            'class' => 'row upload-previews',
            'id' => $this->containerId,
        ]);
        if ($this->crop) {
            echo '<div class="modal fade" id="modalUp" aria-hidden="true" data-backdrop="static" style="display: none;" tabindex="-1">'
                . '    <div class="modal-dialog">'
                . '    <div class="modal-content">'
                . '        <div class="modal-header">'
                . '           <h4 class="modal-title">' . Yii::t('app', 'Basic information') . '</h4>'
                . '            <button type="button" class="close" data-dismiss="modal" aria-label="Close">'
                . '                <span aria-hidden="true">×</span>'
                . '            </button>'
                . '        </div>'
                . '        <div class="modal-body">'
                . '            <p>' . Yii::t('app', 'Loading ...') . '</p>'
                . '        </div>'
                . '        <div class="modal-footer">'
                . '            <button type="button" class="btn btn-secondary" data-dismiss="modal">' . Yii::t('app', 'Close') . '</button>'
                . '           ' . Html::tag('span', Yii::t('app', 'Save'), [
                    'type' => 'button',
                    'class' => 'btn btn-primary',
                    'id' => 'save-crop',
                    'data-option' => '{"width":160, "height": 160}',
                    'data-method' => 'getCroppedCanvas'
                ])
                . '        </div>'
                . '    </div>'
                . '</div>'
                . '</div>';
        }
        $this->registerScripts();
    }

    /**
     * @param View|string $_view
     * @return UploadAsset
     */
    protected function registerAssets(View|string $_view): UploadAsset
    {
        if ($this->crop) {
            JqueryCropperJsAsset::register($_view);
        }
        if ($this->useFancyUI) {
            FancyUIAsset::register($_view);
        }
        if ($this->isQiniuDrive()) {
            QiniuJsAsset::register($_view);
        }
        if ($this->secondUpload) {
            QETagAsset::register($_view);
        }
        return UploadAsset::register($_view);
    }

    protected function registerScripts()
    {
        $js = /** @lang JavaScript */ <<<UPLOAD_JS
function uploadFileTemplate(progress, file) {
    return '<div class="col">'
        + '    <div class="preview" id="preview_' + file.id +'">'
        + '    </div>'
        + '    <div class="info">'
        + '        <p class="size text-right">' + plupload.formatSize(file.size) + '</p>'
        + '        <p class="name text-center text-middle">' + file.name + '</p>'
        + '    </div>'
        + '    <div class="progress active" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="progress_' + file.id + '">'
        + '        <div class="progress-bar progress-bar-striped progress-bar-animated progress-bar-success" style="width: 0;" role="progressbar">'
        + '        ' + progress + '%'
        + '        </div>'
        + '    </div>'
        + '    <div class="uploaded bg-success d-none" id="uploaded_' + file.id + '">'
        + '    </div>'
        + '    <div class="icon-remove" id="remove_' + file.id + '" data-target="'+ file.id +'" data-id="' + file.id + '">'
		+ '        <i class="fas fa-times"></i>'
	    + '    </div>'
        + '</div>'
}
$(function() {
    function getUploadedIcon(fileType, file) {
        file = file || false
        let uploadedIcon, path, html
        if (fileType.toLowerCase().indexOf('image') >= 0) {
            uploadedIcon = '<i class="fas fa-image"></i>'
        } else if (fileType.toLowerCase().indexOf('video') >= 0) {
            uploadedIcon = '<i class="fas fa-video"></i>'
        } else if (fileType.toLowerCase().indexOf('audio') >= 0) {
            uploadedIcon = '<i class="fas fa-volume-up"></i>'
        } else {
            uploadedIcon = '<i class="fas fa-file-alt"></i>'
        }
        if ({$this->_useFancyUI} && file) {
            path = file.path ?? file.poster
            if (path) {
                html = '<a href="' + path +'" data-fancybox="fancyboxGallery"' +
                ' data-caption="' + file.name + '">' + uploadedIcon + '</a>'
            }
       } else {
            html = uploadedIcon
       }
        return html
    }
    let uploader_{$this->containerId} = new plupload.Uploader({$this->_encodedClientOptions})
    uploader_{$this->containerId}.init()
    uploader_{$this->containerId}.bind('Init', function(up) {
        let files = []
        if (Object.prototype.toString.call({$this->_encodedExistFiles}) === '[object Object]') {
            files.push({$this->_encodedExistFiles})
        } else if (Object.prototype.toString.call({$this->_encodedExistFiles}) === '[object Array]') {
            files = {$this->_encodedExistFiles}
        }
        $.each(files, function (key, file) {
            let img = new window.moxie.image.Image()
            let image = new Image()  
            img.onload = function() {
                img.server_id = file.id
                let previewTemplate = uploadFileTemplate(0, file)
                $('#{$this->containerId}').append(previewTemplate)
                $('#preview_' + file.id).html('<img src="' + img.getAsDataURL() + '" id="image-cropper">')
                $('#progress_' + file.id).removeClass('d-done').addClass('d-none')
                $('#uploaded_' + file.id).removeClass('d-none').addClass('d-done').html(getUploadedIcon(file.file_type, file))
                up.refresh()
            }
            img.load(file.file_type === 'image' ? file.path : file.poster)
            img.onembedded = function() {
                img.destroy()
            }
            img.onerror = function(e) {
                img.destroy()
            }
        })
        if (files.length >= {$this->maxFiles}) {
            up.disableBrowse()
            $('#fileinput_{$this->containerId}').parent().addClass('d-none')
        } else {
            up.disableBrowse(false)
            $('#fileinput_{$this->containerId}').parent().removeClass('d-none')
        }
    })
    uploader_{$this->containerId}.bind('QueueChanged', function(up) {
        up.refresh()
        if (up.files.length >= {$this->maxFiles}) {
            up.disableBrowse()
            $('#fileinput_{$this->containerId}').parent().addClass('d-none')
        } else {
            up.disableBrowse(false)
            $('#fileinput_{$this->containerId}').parent().removeClass('d-none')
        }
    })
    uploader_{$this->containerId}.bind('FilesAdded', function (up, files) {
        const file = files[0]
        const fileInfo = getFileInfo(file, '{$this->uploadBasePath}')
        up.setOption('multipart_params', $.extend(up.getOption('multipart_params'), fileInfo))
        let img = new window.moxie.image.Image()
        let image = new Image()
        if (!!{$this->_crop}) {
            modalCropper(file).then(originalImage => {
                cropImg(up, originalImage, {$this->aspectRatio}, {$this->resizeOptions['quality']}).then(imgSrc => {                     
                    if (imgSrc) {
                        image.src = imgSrc
                    }
                    img.onload = function() {
                        img.id = file.id
                        img.name = file.name
                        let previewTemplate = uploadFileTemplate(0, img)
                        $('#{$this->containerId}').append(previewTemplate)
                        img.resize({width: 160, height: 160})
                        if (!imgSrc) {
                            image.src = img.getAsDataURL()
                        }
                        $('#preview_' + img.id).html(image)
                        files.push(img)
                        files.splice(0, 1)
                        up.refresh()
                        handleUploadDrive(up, img, fileInfo)
                    }
                    img.onembedded = function() {
                        img.destroy()
                    }
                    img.onerror = function(e) {
                        img.destroy()
                    }
                    img.load(imgSrc ? image : file.getSource())
                })
            })
        } else {
            if ({$this->_secondUpload}) {
                getHash(file.getNative()).then(hash => {
                    let multipart_params = up.getOption('multipart_params')
                    multipart_params.hash = hash
                    const uploaded = new Promise((resolve, reject) => {
                        $.ajax({
                            data: multipart_params,
                            url: '{$this->getHashUrl}',
                            type: 'POST',
                            success: function (response) {
                                fileInfo.hash = hash
                                if (('success' in response) && (response.success === 'true' || response.success === true)) {
                                    resolve(response)
                                    // up.trigger('FileUploaded', file, response)
                                } else {
                                    console.log('will upload')
                                    
                                    console.log('file info with hash', fileInfo, hash)
                                    reject()
                                    // handleUploadDrive(up, file, fileInfo)
                                }
                            }
                        })
                    })
                    uploaded.then((response) => {
                        console.log( 'no need upload, thiere is a hash in database')
                        up.trigger('FileUploaded', file, response)
                    }).catch(() => {
                        handleUploadDrive(up, file, fileInfo)
                    })
                })
            }
            if (file.type.indexOf('image') >= 0) {
                img.onload = function() {
                    let previewTemplate = uploadFileTemplate(0, file)
                    $('#{$this->containerId}').append(previewTemplate)
                    $('#preview_' + file.id).html('<img src="' + img.getAsDataURL() + '" id="image-cropper">')
                    up.refresh()
                }
                img.load(file.getSource())
                img.onembedded = function() {
                    img.destroy()
                }
                img.onerror = function(e) {
                    img.destroy()
                }
            } else {
                let previewTemplate = uploadFileTemplate(0, file)
                $('#{$this->containerId}').append(previewTemplate)
                $('#preview_' + file.id).html(getUploadedIcon(file.type))
                up.refresh()
            }
            handleUploadDrive(up, file, fileInfo)
        }
    })
    uploader_{$this->containerId}.bind('UploadProgress', function(up, file) {
        $('#progress_' + file.id).attr('aria-valuenow', up.total.percent)
        $('#progress_' + file.id).find('.progress-bar').html(up.total.percent + '%')
    })
    uploader_{$this->containerId}.bind('BeforeChunkUpload', function(up, file, params) {
        let multipartParams = up.getOption('multipart_params')
        params.total_chunks = params.chunks
        params.chunk_index = params.chunk
        up.setOption('multipart_params', $.extend(multipartParams, params))
    })
    uploader_{$this->containerId}.bind('FileUploaded', function(up, file, response) {
        console.log('FileUploaded', response)
        response = (typeof response) === 'string' ? JSON.parse(response) : response
        response = (typeof response.response) === 'string' ? JSON.parse(response.response) : response.response
        if (response.completed) {
            let params = response.response
            params = $.extend(up.getOption('multipart_params'), params, {eof: true})
            $.ajax({
                data: params,
                url: '{$this->url}',
                type: 'POST',
                success: function (response) {
                    if (response.success) {
                        const result = response.data
                        let fileEl = $('#{$this->options["id"]}').val().split(',')
                            .filter(value => value !=="" && value !== null && value !== "0" && value !== 0)
                        if ({$this->_storeInDB}) {
                            if ({$this->maxFiles} <= 1) {
                                $('#{$this->options["id"]}').val(result.id)
                            } else {
                                fileEl.push(result.id)
                                $('#{$this->options["id"]}').val(fileEl.toString())
                            }
                        } else {
                            if ({$this->maxFiles} <= 1) {
                                $('#{$this->options["id"]}').val(result.path)
                            } else {
                                fileEl.push(result.path)
                                $('#{$this->options["id"]}').val(fileEl.toString())
                            }
                        }
                        $('#progress_' + file.id).removeClass('d-done').addClass('d-none')
                        $('#remove_' + file.id).attr('data-target', {$this->_storeInDB} ? result.id : result.path)
                        $('#uploaded_' + file.id).removeClass('d-none').addClass('d-done')
                            .html(getUploadedIcon(result.file_type, result))
                    }
                },
            })
        } else {
            let fileEl = $('#{$this->options["id"]}').val().split(',')
                .filter(value => value !=="" && value !== null && value !== "0" && value !== 0)
            if ({$this->_storeInDB}) {
                if ({$this->maxFiles} <= 1) {
                    $('#{$this->options["id"]}').val(response.id)
                } else {
                    fileEl.push(response.id)
                    $('#{$this->options["id"]}').val(fileEl.toString())
                }
            } else {
                if ({$this->maxFiles} <= 1) {
                    $('#{$this->options["id"]}').val(response.path)
                } else {
                    fileEl.push(response.path)
                    $('#{$this->options["id"]}').val(fileEl.toString())
                }
            }
            $('#remove_' + file.id).attr('data-target', {$this->_storeInDB} ? response.id : response.path)
            $('#progress_' + file.id).removeClass('d-done').addClass('d-none')
            $('#uploaded_' + file.id).removeClass('d-none').addClass('d-done')
                .html(getUploadedIcon(response.file_type, response))
        }
    })   
    $('#{$this->containerId}').on('click', '.icon-remove', function (e) {
        e.preventDefault()
        uploader_{$this->containerId}.splice($(this).attr('data-id'), 1)
        if ({$this->maxFiles} <= 1) {
            $('#{$this->options["id"]}').val()
        } else {
            let fileEl = $('#{$this->options["id"]}').val().split(',')
                .filter(value => value !=="" && value !== null && value !== "0" && value !== 0)
            fileEl.splice($.inArray($(this).attr('data-target'), fileEl), 1)
            $('#{$this->options["id"]}').val(fileEl.toString())
        }
        $(this).parent().remove()
        uploader_{$this->containerId}.refresh()
    })
    function handleUploadDrive(up, file, fileInfo) {
        console.log('handle upload', fileInfo)
        if ({$this->isLocalDrive()}) {
            up.start()
        } else if ({$this->isQiniuDrive()}) {
            console.log('fileInfo by handleUploadDrive', fileInfo)
            handleQiniuUpload(up, file, fileInfo)
        }
    }
    function handleQiniuUpload(up, file, fileInfo) {
        console.log('qiniu upload', 'fileinfo', fileInfo)
        const config = {
            useCdnDomain: true,
            chunkSize: Math.floor({$this->chunkSize},  1024 * 1024)
        }
        let customVars = {$this->_encodedMetaData}
        customVars['x:file_type'] = fileInfo.file_type
        const putExtra = {
            fname: fileInfo.name,
            mimeType: fileInfo.mime_type,
            customVars: customVars
        }
        const observable = qiniu.upload(file.getNative(), fileInfo.key, '{$this->getQiniuToken()}', putExtra, config)
        const observer = {
            next(res) {
                const percent = res.total.percent.toFixed(2)
                console.log('upload percent', percent)
                $('#progress_' + file.id).attr('aria-valuenow', percent)
                $('#progress_' + file.id).find('.progress-bar').html(percent + '%')
            }, 
            error(err) {
                sweetAlertToast.update({
                    toast: true,
                    position: 'top-end',
                    html: '',
                    title: err.data.error,
                    icon: 'error'
                })
            }, 
            complete(res) {
                console.log('qiniu uploaded', file, res)
                up.trigger('FileUploaded', file, res)
            }
        }
        const subscription = observable.subscribe(observer)
    }
})

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
