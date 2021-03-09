<?php


namespace davidxu\upload\callbacks;

use davidxu\upload\models\Attachment;
use Yii;
use yii\base\Action;
use yii\i18n\PhpMessageSource;

/**
 * Class QiniuCallbackAction
 * @package davidxu\upload\callbacks
 *
 */
class QiniuCallbackAction extends Action
{
    
    public $modelClass = Attachment::class;
    
    public function init()
    {
        parent::init();
        $this->registerTranslations();
    }
    
    public function run()
    {
        if (Yii::$app->request->isPost) {
            $result = [];
            $storeInDB = Yii::$app->request->post('store_in_db', 'false');
            if ($storeInDB === true || $storeInDB === 'true') {
                /** @var Attachment $model  */
                $model = new $this->modelClass;
                $model->attributes = Yii::$app->request->post();
                $extension = explode('.', $model->extension);
                $model->extension = $extension[count($extension) - 1];
                if ($model->width === 'null') {
                    $model->width = 0;
                }
                if ($model->height === 'null') {
                    $model->height = 0;
                }
                if ($model->save()) {
                    $result = $model;
                }
            }
            return $result;
        }
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
