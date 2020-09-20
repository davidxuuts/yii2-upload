<?php


namespace davidxu\upload\actions;

use davidxu\upload\models\Attachment;
use Qiniu\Etag;
use Yii;
use yii\base\Action;
use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\i18n\PhpMessageSource;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class LocalUploadAction extends Action
{
    public $uploadPath = '';
    public $uploadBaseUrl = '';
    public $modelClass = Attachment::class;
    public $fileExtLimit = 'jpg,jpeg,png,bmp,gif';
    public $maxFileSize = 100 * 1024 * 1024;
    public $allowAnony = false;
    public $renameFile = true;
    
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
        if (empty($this->uploadPath) || $this->uploadPath === '') {
            $this->uploadPath = Yii::getAlias('@webroot/uploads');
        }
        if (empty($this->uploadBaseUrl) || $this->uploadBaseUrl === '') {
            $this->uploadBaseUrl = Url::to('@web/uploads');
        }
        if (!file_exists($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0755, true)) {
                throw new InvalidArgumentException(
                    Yii::t('uploadtr', '{uploadPath} can not be created', [
                        'uploadPath' => $this->uploadPath,
                    ])
                );
            }
        } else {
            if (!is_dir($this->uploadPath)) {
                throw new InvalidArgumentException(
                    Yii::t('uploadtr', '{uploadPath} is not a dir', [
                        'uploadPath' => $this->uploadPath,
                    ])
                );
            } else {
                if (!is_writable($this->uploadPath)) {
                    throw new InvalidArgumentException(
                        Yii::t('uploadtr', '{uploadPath} is not writable', [
                            'uploadPath' => $this->uploadPath,
                        ])
                    );
                }
            }
        }
    
        if (Yii::$app->request->getIsPost()) {
            $file = $_FILES['file'];
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
                        $date = date('Ymd');
                        if ($this->renameFile) {
                            $filename = date('His') . '_' . uniqid('', false) . '.' . $ext;
                        } else {
                            $filename = $file['name'];
                        }
                        $filePathName = $date . DIRECTORY_SEPARATOR . $filename;
                        [$saveResult, $dest] = $this->save(
                            $file['tmp_name'],  $filePathName
                        );
                        
                        if ($saveResult) {
                            $storeInDB = Yii::$app->request->post('x:store_in_db', false);
                            $savePath = $this->uploadPath . DIRECTORY_SEPARATOR . $filePathName;
                            $result = [
                                'path' => $this->uploadBaseUrl . DIRECTORY_SEPARATOR . $dest,
                                'store_in_db' => $storeInDB,
                                'code' => 1,
                            ];
                            if ($storeInDB) {
                                $attachment = $this->writeToDB(
                                    $file,
                                    $this->uploadBaseUrl . DIRECTORY_SEPARATOR . $filePathName,
                                    $savePath,
                                    Yii::$app->request->post(),
                                    $ext
                                );
                                $result = $attachment;
                            }
                        } else {
                            $result = [
                                'error' => [
                                    'code' => 103,
                                    'message' => Yii::t('uploadtr', 'Upload failed:[{error}]', [
                                        'error' => $dest,
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
        } else {
            $result = [
                'error' => [
                    'code' => 105,
                    'message' => Yii::t('uploadtr', 'Request error'),
                ],
            ];
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
    
    /**
     * @param string $src Uploaded file source path
     * @param string $dest Uploaded file destination path
     * @return array(bool,string,string)
     */
    protected function save($src, $dest)
    {
        if (strpos($dest, DIRECTORY_SEPARATOR) > 0) {
            $dir = rtrim($this->uploadPath, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . ltrim(substr($dest, 0, strrpos($dest, DIRECTORY_SEPARATOR)),
                    DIRECTORY_SEPARATOR);
            if (file_exists($dir)) {
                if (!is_dir($dir)) {
                    return [false, '', $dir . Yii::t('uploadtr', 'File exists, create dir failed')];
                }
            } else {
                @mkdir($dir, 0755, true);
            }
        }
        $path = rtrim($this->uploadPath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . ltrim($dest, DIRECTORY_SEPARATOR);
        if (!(move_uploaded_file($src, $path) || !file_exists($path))) {
            return [false, '', Yii::t('uploadtr', 'Save file failed')];
        } else {
            return [true, $dest, Yii::t('uploadtr', 'Save file successfully')];
        }
    }
    
    /**
     * @param array $file File
     * @param string File name in DB
     * @param string|null $savePath File path in Harddisk
     * @param array $postData
     * @param string $ext File extension
     * @param string $drive Drive
     * @return Attachment
     */
    protected function writeToDB($file, $filename, $savePath, array $postData, $ext, $drive = 'local')
    {
        /** @var Attachment $model */
        $model = new $this->modelClass;
        $model->member_id = $postData['x:member_id'];
        $model->drive = $drive;
        $model->specific_type = $file['type'];
        $model->name = $file['name'];
        $model->size = $file['size'];
        $model->path = $filename;
        $model->extension = $ext;
        $model->year = $postData['x:year'];
        $model->month = $postData['x:month'];
        $model->day = $postData['x:day'];
        [$etag, $err] = Etag::sum($savePath);
        if ($err === null) {
            $model->hash = $etag;
        }
        $model->save(false);
        return $model;
    }
}
