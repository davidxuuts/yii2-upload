<?php


namespace davidxu\upload;


use yii\base\BaseObject;
use Yii;
use yii\helpers\Url;

class UploadEvents extends BaseObject
{
    
    public $previewContainer = 'previewContainer';
    public $errorContainer = 'errorContainer';
    public $multiSelection;
    public $inputElement;
    public $fileBaseUrl;
    
    public $errorImage;
    public $videoDefaultUrl;
    public $audioDefaultUrl;
    public $fileDefaultUrl;
    public $minHeight = 0;
    public $minWidth = 0;
    
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
        $message = Yii::t('uploadtr', 'The image size is too small, please check');
        $js = /** @lang JavaScript */ <<<JS_BIND
function(up) {
    let params = up.getOption('multipart_params')
    let elementBrowse = $(up.settings.container)
    if (params.max_file_nums !== undefined && params.max_file_nums > 0) {
        let uploaded_nums = $('#{$this->previewContainer}').children().length
        if (uploaded_nums >= params.max_file_nums) {
            elementBrowse.hide();
        }
    }

    const minWidth = {$this->minWidth}
    const minHeight = {$this->minHeight}
    if (minHeight > 0 || minWidth > 0 ) {
        plupload.addFileFilter('min_img_resolution', function (minImgSize, file, callback) {
           const minWidth = minImgSize[0]
           const minHeight = minImgSize[1]
            let self = this
            let img = new window.moxie.image.Image()
            function finalize(result) {
                img.destroy()
                img = null
                if (!result) {
                    self.trigger('Error', {
                        response: {
                            error: true,
                            code: plupload.IMAGE_DIMENSIONS_ERROR,
                            message: '{$message}',
                            file: file
                        }
                    })
                }
                callback(result)
            }
            img.onload = function() {
                let result = true
                if (minWidth > 0 && minHeight > 0) {
                    result = img.width >= minWidth && img.height >= minHeight
                } else if (minWidth > 0) {
                    result = img.width >= minWidth
                } else if (minHeight > 0) {
                    result = img.height >= minHeight
                } else {
                    result = true
                }
                finalize(result)
            }
            img.onerror = function() {
                finalize(false)
            }
            img.load(file.getSource())
        })
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
    $(document).on('click', '.upload_file_action', function () {
        $(this).parent().remove()
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
    $('#{$this->errorContainer}').hide()
    let upfiles = ''
    let prepareUploadText = '{$prepareUploadText}'
    let removeFileText = '{$removeFileText}'
    plupload.each(files, function (file) {
        let params = up.getOption('multipart_params')
        let key = '{$this->generateKey()}'
        let ext = file.name.substr(file.name.lastIndexOf('.'))
        const mimeType = file.type.split('/', 1)[0]
        let fileType = 'files'
        if (mimeType === 'image') {
          fileType = 'images'
        } else if (mimeType === 'video') {
          fileType = 'videos'
        } else if (mimeType === 'audio') {
          fileType = 'audios'
        }
        params.key = params['x:uploadPath'] + key + ext
        params['x:upload_type'] = fileType
        up.setOption('multipart_params', params)
        upfiles += UploadItem.tplUploadItem(up, file, prepareUploadText, removeFileText)
    })
    $('#{$this->previewContainer}').{$this->appendHtmlType}(upfiles)
    up.refresh()
    up.disableBrowse({$disableBrowse})
    up.start()
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
    $('#' + file.id).find('.upload_file_mark').addClass('upload_file_uploading').html('{$uploadInProgressText}')
}
JS_BIND;
        return $js;
    }
    
    protected function bindUploadProgress() {
        $js = /** @lang JavaScript */ <<<JS_BIND
(function (up, file) {
    let percent = file.percent + '%'
    let elementFile = $('#' + file.id)
    elementFile.find('.upload_file_percent').html(percent);
    elementFile.find('.progress-bar').width(percent);
})
JS_BIND;
        return $js;
    }
    
    protected function bindFileUploaded() {
        $fileBaseUrl = Url::to($this->fileBaseUrl, true);
        $inputElement = $this->multiSelection
            ? 'elementFile.find(".upload_file_input")'
            : '$(\'#' . $this->inputElement . '\')';
    
        $js = /** @lang JavaScript */ <<<JS_BIND
function (up, file, res) {
    if (typeof res !== 'undefined' && res.status === 200) {
        let response = JSON.parse(res.response)
        let url = response.path
        if (response.upload_type === 'videos') {
            url = `{$this->videoDefaultUrl}`
        }
        if (response.upload_type === 'audios') {
            url = `{$this->audioDefaultUrl}`
        }
        if (response.upload_type === 'files') {
            url = `{$this->fileDefaultUrl}`
        }
        let elementFile = $('#' + file.id)
        let responseElement = {$inputElement}
        let params = up.getOption('multipart_params')
        if (params['x:store_in_db'] === true || params['x:store_in_db'] === 'true') {
            responseElement.val(response.id)
        } else {
            responseElement.val(url)
        }
        elementFile.find('.upload_file_thumb img').attr('src', '{$fileBaseUrl}' + url)
        elementFile.removeClass('upload_file_loading');
        elementFile.find('.upload_file_status').remove();
    }
}
JS_BIND;
        return $js;
    }
    
    protected function bindUploadComplete() {
        $js = /** @lang JavaScript */ <<<JS_BIND
(function (up, files) {
    up.disableBrowse(false)
})
JS_BIND;
        return $js;
    }
    
    protected function bindRefresh() {
        $js = /** @lang JavaScript */ <<<JS_BIND
function(up) {
    let params = up.getOption('multipart_params')
    let elementBrowse = $(up.settings.container)
    if (params.max_file_nums !== undefined && params.max_file_nums > 0) {
        let uploaded_nums = $('#{$this->previewContainer}').children().length;
        if (uploaded_nums < params.max_file_nums) {
            elementBrowse.show();
        } else {
            elementBrowse.hide();
        }
    }
}
JS_BIND;
        return $js;
    }
    
    protected function bindError() {
        $js = /** @lang JavaScript */ <<<JS_BIND
(function (up, err) {
    let errorElement = $('#' + up.settings.error_container)
    let errMsg = ''
    if ((typeof err.response) == 'object') {
        let error = err.response
        errorElement.html('Error #:' + error.code + ' ' + error.message).show()
    } else {
         error= JSON.parse(err.response)
         errMsg = error.error
         errorElement.html('Error #:' + err.code + ' ' + err.message + errMsg).show()
    }
})
JS_BIND;
        return $js;
    }

    protected function generateKey($len = 10) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $string = time();
        for(; $len >= 1; $len--) {
            $position = rand() % strlen($chars);
            $position2 = rand() % strlen($string);
            $string = substr_replace($string, substr($chars, $position, 1), $position2, 0);
        }
        return date('His') . '_' . $string;
    }
    
}
