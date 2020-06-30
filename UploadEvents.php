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
    // console.log('Init')
    let params = up.getOption('multipart_params')
    let elementBrowse = $(up.settings.container)
    if (params.max_file_nums !== undefined && params.max_file_nums > 0) {
        let uploaded_nums = $('#{$this->previewContainer}').children().length
        if (uploaded_nums >= params.max_file_nums) {
            elementBrowse.hide();
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
    // console.log('PostInit')
    $(document).on('click', '.upload_file_action', function () {
        let fileId = $(this).parent().attr('id')
        // console.log(fileId)
        if (fileId !== undefined) {
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
    // console.log('FilesAdded')
    $('#{$this->errorContainer}').hide()
    let upfiles = ''
    let prepareUploadText = '{$prepareUploadText}'
    let removeFileText = '{$removeFileText}'
     plupload.each(files, function (file) {
        // console.log(file)
        let params = up.getOption('multipart_params')
        let key = '{$this->generateKey()}'
        let ext = file.name.substr(file.name.lastIndexOf('.'))
        params.key = params['x:uploadPath'] + key + ext
        // console.log(params)
        up.setOption('multipart_params', params)
        // console.log(up)
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
    // console.log('FilesRemoved')
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
    // console.log('Beforeupload')
    $('#' + file.id).find('.upload_file_mark').addClass('upload_file_uploading').html('{$uploadInProgressText}')
}
JS_BIND;
        return $js;
    }
    
    protected function bindUploadProgress() {
        $js = /** @lang JavaScript */ <<<JS_BIND
function (up, file) {
    // console.log('UploadProgress')
    let percent = file.percent + '%'
    let elementFile = $('#' + file.id)
    elementFile.find('.upload_file_percent').html(percent);
    elementFile.find('.progress-bar').width(percent);
}
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
    // console.log('FileUploaded')
    // console.log(file)
    // console.log(res)
    if (res !== undefined) {
        let response = JSON.parse(res.response)
        if (response.code === 200) {
            let data = response.data
            // console.log(data)
            let url = data.path
            let elementFile = $('#' + file.id)
            let responseElement = {$inputElement}
            let params = up.getOption('multipart_params')
            if (params['x:store_in_db'] === true || params['x:store_in_db'] === 'true') {
                responseElement.val(data.id)
            } else {
                responseElement.val(url)
            }
            elementFile.find('.upload_file_thumb img').attr('src', '{$fileBaseUrl}' + url)
            elementFile.removeClass('upload_file_loading');
            elementFile.find('.upload_file_status').remove();
        }
    }
}
JS_BIND;
        return $js;
    }
    
    protected function bindUploadComplete() {
        $js = /** @lang JavaScript */ <<<JS_BIND
function (up, files) {
    // console.log('UploadComplete')
    up.disableBrowse(false)
}
JS_BIND;
        return $js;
    }
    
    protected function bindRefresh() {
        $js = /** @lang JavaScript */ <<<JS_BIND
function(up) {
    // console.log('Refresh')
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
function (up, err) {
    // console.log('Error')
    let errorElement = $('#' + up.settings.error_container)
    let errMsg = ''
    if (JSON.parse(err.response).error !== undefined) {
        errMsg = JSON.parse(err.response).error
    }
    errorElement.html('Error #:' + err.code + ' ' + err.message + errMsg).show()
}
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
