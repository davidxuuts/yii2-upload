<?php


namespace davidxu\upload\actions;

use davidxu\upload\models\WechatAttachment;

use davidxu\upload\UploadAsset;
use Yii;
use yii\base\Action;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\i18n\PhpMessageSource;
use yii\web\Response;

class WechatUploadAction extends Action
{
    public $modelClass = WechatAttachment::class;
    public $allowAnony = true;
    public $renameFile = false;
    public $fileDataName = 'file';
    public $maxFileSize = 10 * 1024 * 1024;
    public $fileExtLimit;
    public $baseUrl = '/';
    
    public function init()
    {
        parent::init();
        $this->registerTranslations();
    
        if (Yii::$app->user->isGuest && !$this->allowAnony) {
            $result = [
                'error' => [
                    'code' => 101,
                    'message' => Yii::t(
                        'uploadtr',
                        'Anonymous user is not allowed, please login first'
                    ),
                ],
            ];
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        }
    }
    
    public function run()
    {
        $result = [
            'error' => [
                'code' => 105,
                'message' => Yii::t('uploadtr', 'Request error'),
            ],
        ];
        if (Yii::$app->request->getIsPost()) {
            $type = Yii::$app->request->post('media_type', 'image');
            switch (trim($type)) {
                case 'image':
                    $this->maxFileSize = 10 * 1024 * 1024;
                    $this->fileExtLimit = 'bmp,png,jpeg,jpg,gif';
                    break;
                case 'voice':
                    $this->maxFileSize = 2 * 1024 * 1024;
                    $this->fileExtLimit = 'mp3,wma,wav,amr';
                    break;
                case 'video':
                    $this->maxFileSize = 10 * 1024 * 1024;
                    $this->fileExtLimit = 'mp4,mpeg';
                    break;
                case 'thumb':
                    $this->maxFileSize = 64 * 1024;
                    $this->fileExtLimit = 'jpg';
                    break;
                case 'news':
                    $this->maxFileSize = 10 * 1024 * 1024;
                    $this->fileExtLimit = 'bmp,png,jpeg,jpg,gif';
                    break;
                default:
                    $this->maxFileSize = 10 * 1024 * 1024;
                    $this->fileExtLimit = 'bmp,png,jpeg,jpg,gif';
            }
            $file = $_FILES[$this->fileDataName];
            if ($file['error'] === 0 && $file['size'] > 0) {
                if ($file['size'] > $this->maxFileSize) {
                    $result = [
                        'error' => [
                            'code' => 102,
                            'message' => Yii::t(
                                'uploadtr',
                                'Upload failed:[ file size more than {fileSizeLimit}]', [
                                    'fileSizeLimit' => $this->maxFileSize,
                                ]
                            ),
                        ],
                    ];
                } else {
                    $ext = substr(strtolower(strrchr($file['name'], '.')), 1);
                    if (empty($this->maxFileSize) || in_array($ext, explode(',', $this->fileExtLimit))
                    ) {
                        if ($this->renameFile) {
                            $filename = date('His') . '_' . uniqid('', false) . '.' . $ext;
                        } else {
                            $filename = $file['name'];
                        }
                        rename($file['tmp_name'], $file['tmp_name'] . '.' . $ext);
                        $uploadToWechatServer = $this->uploadToWechatServer(
                            $file['tmp_name'] . '.' . $ext,
                            Yii::$app->request->post()
                        );
                        if (!array_key_exists('errcode', $uploadToWechatServer)) {
                            $attachment = $this->writeToDB(
                                $uploadToWechatServer,
                                $filename,
                                Yii::$app->request->post(),
                            );
                            $result = $attachment->attributes;
                            $result['url'] = (empty($this->baseUrl) || $this->baseUrl === '/')
                                ? $result['image_url']
                                : $this->baseUrl . '/analysis/image?attach=' . $attachment->media_url;
                        } else {
                            $result = [
                                'error' => [
                                    'code' => $uploadToWechatServer['errcode'],
                                    'message' => Yii::t('uploadtr', 'Upload failed:[{error}]', [
                                        'error' => $uploadToWechatServer['errmsg'],
                                    ]),
                                ],
                            ];
                        }
                    } else {
                        $result = [
                            'error' => [
                                'code' => 104,
                                'message' => Yii::t('uploadtr', 'Upload type not allowed: [{type}]', [
                                    'type' => $ext,
                                ]),
                            ],
                        ];
                    }
                }
            }
        }
    
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $result;
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
  
    protected function uploadToWechatServer($path, $postData)
    {
        $result = [];
        $wechatApp = Yii::$app->wechat->app;
        $type = trim(Yii::$app->request->post('media_type'));
        if (trim($postData['is_temporary']) === 'tmp') {
            if ($type === 'image') {
                $result = $wechatApp->media->uploadImage($path);
            }
            if ($type === 'voice') {
                $result = $wechatApp->media->uploadVoice($path);
            }
            if ($type === 'video') {
                $result = $wechatApp->media->uploadVideo($path, $postData['file_name'], $postData['description']);
            }
            if ($type === 'thumb') {
                $result = $wechatApp->media->uploadThumb($path);
            }
        }
        if (trim($postData['is_temporary']) === 'perm') {
            if ($type === 'image') {
                $result = $wechatApp->material->uploadImage($path);
            }
            if ($type === 'voice') {
                $result = $wechatApp->material->uploadVoice($path);
            }
            if ($type === 'video') {
                $result = $wechatApp->material->uploadVideo($path, $postData['file_name'], $postData['description']);
            }
            if ($type === 'thumb') {
                $result = $wechatApp->material->uploadThumb($path);
            }
        }
        unlink($path);
        return $result;
    }
    
    /**
     * @param array $wechatResult Wechat returned result array
     * @param string $filename File name in DB
     * @param string|null $savePath File path in Harddisk
     * @param array $postData
     * @return Attachment
     */
    protected function writeToDB($wechatResult, $filename, array $postData)
    {
        /** @var WechatAttachment $model */
        $model = new $this->modelClass;
        if ($model->hasAttribute('merchant_id')) {
            $model->merchant_id = $postData['merchant_id'] ?? 0;
        }
        $mediaUrl = array_key_exists('url', $wechatResult)
            ? str_ireplace('http', 'https', $wechatResult['url']) : null;
        $mediaId = array_key_exists('media_id', $wechatResult) ? $wechatResult['media_id'] : null;
        $model->file_name = $filename;
        $model->local_url = $mediaUrl;
        $model->media_type = $postData['media_type'];
        $model->media_id = $mediaId;
        $model->media_url = $mediaUrl;
        $model->width = $postData['width'] ?? 0;
        $model->height = $postData['height'] ?? 0;
        $model->year = $postData['year'];
        $model->month = $postData['month'];
        $model->day = $postData['day'];
        $model->description = $postData['description'] ?? '';
        $model->is_temporary = $postData['is_temporary'];
        $model->link_type = $postData['link_type'];
        $model->status = 1;
        $model->save(false);
        return $model;
    }
}
