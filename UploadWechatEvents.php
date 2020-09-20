<?php


namespace davidxu\upload;

use yii\base\BaseObject;
use Yii;
use yii\helpers\Url;

class UploadWechatEvents extends BaseObject
{
    public $previewContainer = 'previewContainer';
    public $errorContainer = 'errorContainer';
    public $multiSelection;
    public $inputElement;
    public $errorImage;
    public $videoDefaultUrl;
    public $voiceDefaultUrl;
    public $fileDefaultUrl;
    public $videoDescriptionAttribute = 'description';
    
    private $appendHtmlType = 'html';
    
    public function getScripts($events)
    {
        $registerEvents = [];
        foreach ($events as $methodName) {
            $methodName = ucfirst($methodName);
            $method = 'bind' . $methodName;
            if (!method_exists($this, $method)) {
                continue;
            }
            $registerEvents[$methodName] = $this->$method();
        }
        return $registerEvents;
    }
    
    /**
     * Init
     */
    protected function bindInit() {
        $js = /** @lang JavaScript */ <<<JS_BIND
function(up) {
    console.log('bindInit')
    let params = up.getOption('multipart_params')
    let elementBrowse = $(up.settings.container)
    if (typeof params.max_file_nums !== 'undefined' && params.max_file_nums > 0) {
        let uploaded_nums = $('#{$this->previewContainer}').children().length
        if (uploaded_nums >= params.max_file_nums) {
            elementBrowse.hide()
        }
    }
}
JS_BIND;
        return $js;
    }
    
    /**
     * PostInit
     */
    protected function bindPostInit() {

        $js = /** @lang JavaScript */ <<<JS_BIND
function(up) {
    // console.log('bindPostInit')
    $(document).on('click', '.upload_file_action', function () {
        console.log('click')
        let fileId = $(this).parent().attr('id')
        // console.log(fileId)
        if (typeof fileId !== 'undefined') {
            up.removeFile(up.getFile(fileId))
        } else {
           $(this).parent().remove()
        }
        up.refresh()
    })
    $('#{$this->errorContainer}').hide()
}
JS_BIND;
        return $js;
    }
    
    protected function bindFilesAdded() {
        $prepareUploadText = Yii::t('uploadtr', 'Prepare to upload');
        $removeFileText = Yii::t('uploadtr', 'Remove');
        $disableBrowse = 'true';
        if ($this->multiSelection) {
            $this->appendHtmlType = 'append';
            $disableBrowse = 'false';
        }
        
        $js = /** @lang JavaScript */ <<<JS_BIND
function (up, files) {
    // console.log('bindFilesAdded')
    // console.log(up, files)
    const self = this
    $('#{$this->errorContainer}').hide()
    let upfiles = ''
    let prepareUploadText = '{$prepareUploadText}'
    let removeFileText = '{$removeFileText}'
    let params
    plupload.each(files, function (file) {
        console.log(file, up)
        params = up.getOption('multipart_params')
        params.file_name = file.name
        console.log(params)
        if (file.type.split('/')[0] === 'image') {
            const reader = new FileReader()
            reader.readAsDataURL(file.getNative())
            reader.onload = function(e) {
                const image = new Image()
                image.src = e.target.result
                image.onload = function() {
                    params.width = this.width
                    params.height = this.height
                }
            }
        }
        if (file.type.split('/')[0] === 'video') {
            console.log('isVideo')
            const video = document.createElement('video')
            // video.oncanplay = function() {
            video.onloadeddata = function() {
                console.log(Math.floor(video.videoWidth), Math.floor(video.videoHeight))
                // console.log(video.duration, video.videoHeight, video.videoWidth)
                params['width'] = Math.floor(video.videoWidth)
                params['height'] = Math.floor(video.videoHeight)
            }
            video.setAttribute('preload', 'auto')
            video.src = URL.createObjectURL(file.getNative())
        }
        console.log(params)
        // up.setOption('multipart_params', params)
        // console.log(up.settings)
        upfiles += UploadItem.tplUploadItem(up, file, prepareUploadText, removeFileText)
    })
    $('#{$this->previewContainer}').{$this->appendHtmlType}(upfiles)
    
    const pluploadId = up.getOption('id')
    const inputName = up.getOption('input_name').split(/\[|\]/)[1]
    const descriptionAttr = pluploadId.replace(new RegExp(inputName, 'g'), '{$this->videoDescriptionAttribute}')
    const inputDescription = $('#' + descriptionAttr)
    params['{$this->videoDescriptionAttribute}'] = $('#' + descriptionAttr).val()
    up.setOption('multipart_params', params)
    if (params['{$this->videoDescriptionAttribute}'].trim() === '') {
        inputDescription.attr('aria-required', 'true').attr('aria-invalid', 'true')
        inputDescription.parent().parent().addClass('required has-error')
        inputDescription.parent().find('.help-block').html('视频详情不能为空。')
        plupload.each(files, function (file) {
            up.removeFile(file)
        })
        self.trigger('Error', {
            code: -800,
            message: '视频详情不能为空'
        })
       up.refresh()
    } else {
        up.refresh()
        up.disableBrowse({$disableBrowse})
        console.log(up.getOption('multipart_params'))
        // up.start()
    }
}
JS_BIND;
        return $js;
    }
    
    protected function bindFilesRemoved() {
        $inputElement = $this->multiSelection
            ? 'elementFile.find(".upload_file_input")'
            : '$(\'#' . $this->inputElement . '\')';
        $js = /** @lang JavaScript */ <<<JS_BIND
function (up, files) {
        console.log('bindFilesRemoved')
    let responseElement = {$inputElement}
    $.each(files, function(index, file) {
        responseElement.val('')
        $('#' + file.id).remove()
    })
}
JS_BIND;
        return $js;
    }
    
    protected function bindBeforeUpload() {
        $uploadInProgressText = Yii::t('uploadtr', 'Upload in progress');
        $js = /** @lang JavaScript */ <<<JS_BIND
function (up, file) {
    console.log('bindBeforeUpload')
    console.log(up, file)
    $('#' + file.id).find('.upload_file_mark').addClass('upload_file_uploading').html('{$uploadInProgressText}')
}
JS_BIND;
        return $js;
    }
    
    protected function bindUploadProgress() {
        $js = /** @lang JavaScript */ <<<JS_BIND_UPLOAD_PROGRESS
(function (up, file) {
    let percent = file.percent + '%'
    let elementFile = $('#' + file.id)
    elementFile.find('.upload_file_percent').html(percent)
    elementFile.find('.progress-bar').width(percent)
})
JS_BIND_UPLOAD_PROGRESS;
        return $js;
    }
    
    protected function bindFileUploaded() {
        $inputElement = $this->multiSelection
            ? 'elementFile.find(".upload_file_input")'
            : '$(\'#' . $this->inputElement . '\')';
    
        $js = /** @lang JavaScript */ <<<JS_BIND

function (up, file, res) {
    // console.log('bindFileUploaded')
    console.log(up)
    const date = new Date()
    console.log(date.toLocaleTimeString())
    if (typeof res !== 'undefined' && res.status === 200) {
        const response = JSON.parse(res.response)
        console.log(response)
        let url = response.url
        switch (response.media_type) {
            case 'video':
                url = '{$this->videoDefaultUrl}'
                break
            case 'voice':
                url = '{$this->voiceDefaultUrl}'
                break
            default:
        }
        if (url === 'null' || typeof url === 'undefined' || url === null) {
            url = '{$this->errorImage}'
        }
        let elementFile = $('#' + file.id)
        let responseElement = {$inputElement}
        let params = up.getOption('multipart_params')
        responseElement.val(url)
        elementFile.find('.upload_file_thumb img').attr('src', url)
        elementFile.removeClass('upload_file_loading')
        elementFile.find('.upload_file_status').remove()
    }
}
JS_BIND;
        return $js;
    }
    
    protected function bindUploadComplete() {
        $js = /** @lang JavaScript */ <<<JS_BIND
(function (up, files) {
    console.log('bindUploadComplete')
    up.disableBrowse(false)
})
JS_BIND;
        return $js;
    }
    
    protected function bindRefresh() {
        $js = /** @lang JavaScript */ <<<JS_BIND
function (up) {
    console.log('bindRefresh')
    let params = up.getOption('multipart_params')
    console.log(params)
    let elementBrowse = $(up.settings.container)
    if ((typeof params.max_file_nums !== 'undefined') && params.max_file_nums > 0) {
        let uploaded_nums = $('#{$this->previewContainer}').children().length
        if (uploaded_nums < params.max_file_nums) {
            elementBrowse.show()
        } else {
            elementBrowse.hide()
        }
    }
}
JS_BIND;
        return $js;
    }
    
    protected function bindError() {
        $js = /** @lang JavaScript */ <<<JS_BIND
(function (up, err) {
    console.log('bindError')
    const errorElement = $('#' + up.settings.error_container)
    errorElement.html('Error #:' + err.code + ' ' + err.message).show()
})
JS_BIND;
        return $js;
    }
}
