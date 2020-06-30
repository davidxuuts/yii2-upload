<?php

use yii\helpers\Html;
use yii\base\Model;
use davidxu\upload\helpers\FormatHelper;
/**
 * @var array $htmlOptions
 * @var array $previewOptions
 * @var Model $model
 * @var bool $multiSelection
 * @var bool $storeInDB
 * @var int $maxFileNumber
 * @var string $browseLabel
 * @var array $browseOptions
 * @var string $uploadLabel
 * @var array $uploadOptions
 * @var string $errorContainer
 * @var string $uploadBaseUrl
 */
?>
<?php
echo Html::beginTag('div', $htmlOptions);

$data = $model->$attribute;
$html = '';
if (is_array($data)) {
    foreach ($data as $key => $item) {
        $html .= Html::beginTag('li', ['class' => 'upload_file', 'id' => 'upload_' . $key]);
        $html .= Html::beginTag('div', ['class' => 'upload_file_thumb']); //div.upload_file_thumb
        $html .= $storeInDB
            ? Html::img($uploadBaseUrl . $model->attachment->path)
            : Html::img($uploadBaseUrl . $data);
        $html .= Html::endTag('div'); //div.upload_file_thumb
        $html .= Html::beginTag('div', ['class' => 'upload_file_name']); //div.upload_file_name
        $html .= Html::tag('span', $storeInDB
            ? basename($model->attachment->name)
            : basename($data));
        $html .= Html::endTag('div'); //div.upload_file_name
        $html .= Html::tag(
            'div',
            $storeInDB ? FormatHelper::formatBytes($model->attachment->size, 0) : 'Unknown',
            ['class' => 'upload_file_size']
        );
        $html .= Html::beginTag('div', ['class' => 'upload_file_action']); //div.upload_file_action
        $html .= Html::tag(
            'span',
            Yii::t('uploadtr', 'Remove'),
            ['class' => 'upload_action_icon']
        );
        $html .= Html::endTag('div'); //div.upload_file_action
        $html .= Html::endTag('li');
    }
    if ($maxFileNumber > 0 && (count($data) > $maxFileNumber)) {
        $containerOptions['style'] = 'display:none;';
    }
} else {
    if ($multiSelection) {
//        $attribute .= '[0]';
    }
    echo Html::activeHiddenInput($model, $attribute);
    if ($data) {
        $html .= Html::beginTag('li', [
            'class' => 'upload_file',
        ]); //li.upload_file
        $html .= Html::beginTag('div', ['class' => 'upload_file_thumb']); //div.upload_file_thumb
        $html .= $storeInDB
            ? Html::img($uploadBaseUrl . $model->attachment->path)
            : Html::img($uploadBaseUrl . $data);
        $html .= Html::endTag('div'); //div.upload_file_thumb
        $html .= Html::beginTag('div', ['class' => 'upload_file_name']); //div.upload_file_name
        $html .= Html::tag('span', $storeInDB
            ? basename($model->attachment->name)
            : basename($data));
        $html .= Html::endTag('div'); //div.upload_file_name
        $html .= Html::tag(
            'div',
            $storeInDB ? FormatHelper::formatBytes($model->attachment->size, 0) : 'Unknown',
            ['class' => 'upload_file_size']
        );
        $html .= Html::beginTag('div', ['class' => 'upload_file_action']); //div.upload_file_action
        $html .= Html::tag(
            'span',
            Yii::t('uploadtr', 'Remove'),
            ['class' => 'upload_action_icon']
        );
        $html .= Html::endTag('div'); //div.upload_file_action
        $html .= Html::endTag('li'); //li.upload_file
        $containerOptions['style'] = 'display:none;';
    }
}
?>
<?= Html::tag('ul', $html, $previewOptions) ?>
<?= Html::beginTag('div', $containerOptions);?>
<?= Html::a($browseLabel, 'javascript:;', $browseOptions) ?>
<?= Html::a($uploadLabel, 'javascript:;', $uploadOptions) ?>
<?= Html::endTag('div') ?>
<?= Html::tag('div', null, ['class' => 'upload_error', 'id' => $errorContainer]) ?>
<?= Html::endTag('div');
