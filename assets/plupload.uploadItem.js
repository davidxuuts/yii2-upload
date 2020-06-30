
var UploadItem = function () {
    return {
        tplUploadItem: function (uploader, file, prepareUploadText, removeFileText) {
            console.log(uploader)
            console.log(file)
            var settings = uploader.settings;
            var path = file.url;
            var filesize = file.size;
            if (window.plupload) {
                filesize = window.plupload.formatSize(filesize);
            }
            var temp = '<li class="upload_file upload_file_loading" id="' + file.id + '">';

            if (settings.multi_selection === true) {
                temp += '<input id="' + settings.id + '-' + file.id + '" name="' + settings.input_name + '[' + file.id + ']' + '" value="' + path + '" type="hidden" class="upload_file_input">';
            }

            if (path === undefined) {
                path = settings.error_image_url;
            }

            temp += '<div class="upload_file_thumb"><img src="' + path + '"></div>';

            temp += '<div class="upload_file_status">' +
                '<div class="upload_file_progress">' +
                '<div class="upload_file_percent">?</div>' +
                '<div class="progress">' +
                '<span class="progress-bar"></span>' +
                '</div>' +
                '<span class="upload_file_mark">' + prepareUploadText + '</span>' +
                '</div>' +
                '</div>';

            temp += '<div class="upload_file_name"><span>' + file.name + '</span></div>';


            temp += '<div class="upload_file_size">' + filesize + '</div>';
            temp += '<div class="upload_file_action">' +
                '<span class="upload_action_icon">' + removeFileText + '</span>' +
                '</div>';
            return temp += '</li>';
        }
    };
}();