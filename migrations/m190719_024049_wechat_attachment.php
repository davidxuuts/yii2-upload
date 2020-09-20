<?php

use yii\db\Migration;

class m190719_024049_wechat_attachment extends Migration
{
    private $tableName = '{{%addon_wechat_attachment}}';
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql' || $this->db->driverName === 'mariadb') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        $this->execute('SET foreign_key_checks = 0');
        
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey()->comment('ID'),
            'file_name' => $this->string(200)->null()->comment('Original name'),
            'local_url' => $this->string(1024)->null()->comment('Local URL'),
            'media_type' => $this->string(15)->null()->defaultValue('images')
                ->comment('Media type'),
            'media_id' => $this->string(50)->null()->comment('Wechat media ID'),
            'media_url' => $this->string(1024)->null()->comment('Wechat media URL'),
            'width' => $this->integer()->null()->defaultValue(0)->comment('Width'),
            'height' => $this->integer()->null()->defaultValue(0)->comment('Height'),
            'year' => $this->integer()->null()->defaultValue(0)->comment('Year'),
            'month' => $this->integer()->null()->defaultValue(0)->comment('Month'),
            'day' => $this->integer()->null()->defaultValue(0)->comment('Day'),
            'description' => $this->string(255)->null()->comment('Video description'),
            'is_temporary' => $this->string(10)->null()
                ->comment('Wechat file type[tmp:temporary;perm:permission]'),
            'link_type' => $this->tinyInteger(2)->null()->defaultValue(1)
                ->comment('Link type[1:wechat;2:local]'),
            'status' => $this->tinyInteger(4)->defaultValue(1)
                ->comment('Status[-1:Deleted;0:Disabled;1:Enabled]'),
            'created_at' => $this->integer()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
                ->comment('Created at'),
            'updated_at' => $this->integer()->notNull()
                ->defaultExpression('CURRENT_TIMESTAMP')
                ->comment('Updated at')
        ], $tableOptions);
        $this->addCommentOnTable($this->tableName, 'Addon wechat attachment table');
        
        $this->createIndex('media_id',$this->tableName,'media_id',0);
        $this->execute('SET foreign_key_checks = 1');
    }

    public function down()
    {
        $this->execute('SET foreign_key_checks = 0');
        $this->dropIndex('media_id', $this->tableName);
        $this->dropTable($this->tableName);
        $this->execute('SET foreign_key_checks = 1');
    }
}
