<?php

namespace davidxu\upload\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%addon_wechat_attachment}}".
 *
 * @property int $id ID
 * @property string $file_name Original name
 * @property string $local_url Local URL
 * @property string $media_type Media type
 * @property string $media_id Wechat media ID
 * @property string $media_url Wechat media URL
 * @property int $width Width
 * @property int $height Height
 * @property int $year Year
 * @property int $month Month
 * @property int $day Day
 * @property string $description Video description
 * @property string $is_temporary Wechat file type[tmp:temporary;perm:permission]
 * @property int $link_type Link type[1:wechat;2:local]
 * @property int $status Status[-1:Deleted;0:Disabled;1:Enabled]
 * @property int $created_at Created at
 * @property int $updated_at Updated at
 *
 * @property WechatAttachmentNews $news
 *
 */
class WechatAttachment extends ActiveRecord
{
    
    /**
     * 微信图片前缀
     */
    public const WECHAT_MEDIAL_URL = 'http://mmbiz.qpic.cn';
    public const LINK_TYPE_WECHAT = 1;
    public const LINK_TYPE_LOCAL = 2;
    
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;
    public const STATUS_DELETE = -1;
    
    public const TYPE_NEWS = 'news';
    public const TYPE_TEXT = 'text';
    public const TYPE_VOICE = 'voice';
    public const TYPE_IMAGE = 'image';
    public const TYPE_CARD = 'card';
    public const TYPE_VIDEO = 'video';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%addon_wechat_attachment}}';
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['width', 'height', 'year', 'month', 'day', 'link_type', 'status'], 'integer'],
            [['file_name'], 'string', 'max' => 200],
            [['local_url', 'media_url'], 'string', 'max' => 1024],
            [['media_type'], 'string', 'max' => 15],
            [['media_id'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 255],
            [['is_temporary'], 'string', 'max' => 10],
            [['link_type'], 'in', 'range' => [self::LINK_TYPE_WECHAT, self::LINK_TYPE_LOCAL]],
            [['link_type'], 'default', 'value' => self::LINK_TYPE_WECHAT],
            [['status'], 'in', 'range' => [self::STATUS_ENABLED, self::STATUS_DISABLED, self::STATUS_DELETE]],
            [['status'], 'default', 'value' => self::STATUS_ENABLED],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'file_name' => Yii::t('app', 'Original name'),
            'local_url' => Yii::t('app', 'Local URL'),
            'media_type' => Yii::t('app', 'Media type'),
            'media_id' => Yii::t('app', 'Wechat media ID'),
            'media_url' => Yii::t('app', 'Wechat media URL'),
            'width' => Yii::t('app', 'Width'),
            'height' => Yii::t('app', 'Height'),
            'year' => Yii::t('app', 'Year'),
            'month' => Yii::t('app', 'Month'),
            'day' => Yii::t('app', 'Day'),
            'description' => Yii::t('app', 'Video description'),
            'is_temporary' => Yii::t('app', 'File type'),
            'link_type' => Yii::t('app', 'Wechat link'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created at'),
            'updated_at' => Yii::t('app', 'Updated at'),
        ];
    }
    
    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }
    
    /**
     * 关联图文
     *
     * @return ActiveQuery
     */
    public function getNews(): ActiveQuery
    {
        return $this->hasMany(WechatAttachmentNews::class, ['attachment_id' => 'id']);
    }
}
