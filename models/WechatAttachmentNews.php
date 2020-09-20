<?php
namespace davidxu\upload\models;

use yii\db\ActiveQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%addon_wechat_attachment_news}}".
 *
 * @property int $id ID
 * @property int $attachment_id Wechat attachment ID
 * @property string $title Title
 * @property string $thumb_media_id Wechat Media ID
 * @property string $thumb_url Wechat thumbnail URL
 * @property string $author Author
 * @property string $digest Digest
 * @property int $show_cover_pic 0:false,1:true
 * @property int $sort Sort order
 * @property string $content Content
 * @property string $content_source_url Content source url
 * @property string $media_url Wechat media URL
 * @property int $year Year
 * @property int $month Month
 * @property int $day Day
 * @property int $status Status[-1:Deleted;0:Disabled;1:Enabled]
 * @property int $created_at Created at
 * @property int $updated_at Updated at
 *
 * @property Attachment $attachment
 */
class WechatAttachmentNews extends ActiveRecord
{
    
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;
    public const STATUS_DELETE = -1;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%addon_wechat_attachment_news}}';
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['attachment_id', 'show_cover_pic', 'sort', 'year', 'month', 'day', 'status'], 'integer'],
            [['content'], 'string'],
            [['title', 'thumb_media_id'], 'string', 'max' => 50],
            [['thumb_url', 'digest', 'content_source_url'], 'string', 'max' => 255],
            [['author'], 'string', 'max' => 64],
            [['media_url'], 'string', 'max' => 1024],
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
            'attachment_id' => Yii::t('app', 'Wechat attachment ID'),
            'title' => Yii::t('app', 'Title'),
            'thumb_media_id' => Yii::t('app', 'Wechat media ID'),
            'thumb_url' => Yii::t('app', 'Wechat thumbnail URL'),
            'author' => Yii::t('app', 'Author'),
            'digest' => Yii::t('app', 'Digest'),
            'show_cover_pic' => Yii::t('app', 'Show cover picture'),
            'sort' => Yii::t('app', 'Sort order'),
            'content' => Yii::t('app', 'Content'),
            'content_source_url' => Yii::t('app', 'Content source url'),
            'media_url' => Yii::t('app', 'Wechat media URL'),
            'year' => Yii::t('app', 'Year'),
            'month' => Yii::t('app', 'Month'),
            'day' => Yii::t('app', 'Day'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created at'),
            'updated_at' => Yii::t('app', 'Updated at'),
        ];
    }
    
    /**
     * @return ActiveQuery
     */
    public function getAttachment(): ActiveQuery
    {
        return $this->hasOne(WechatAttachment::class, ['id' => 'attachment_id']);
    }
}
