<?php

namespace common\components\db\models;

use Yii;

/**
 * This is the model class for table "{{%p_instance}}".
 *
 * @property int $instance_id
 * @property string $host
 * @property int $port
 * @property string $user_name
 * @property string $password
 * @property int $decrypt_auth
 * @property int $is_used
 */
class Instance extends \yii\db\ActiveRecord
{
    public static function getDb()
    {
        return Yii::$app->get('dbConfig');
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%p_instance}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['instance_id', 'port', 'decrypt_auth', 'is_used'], 'integer'],
            [['host', 'user_name', 'password'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'instance_id' => 'Instance ID',
            'host' => 'Host',
            'port' => 'Port',
            'user_name' => 'User Name',
            'password' => 'Password',
            'decrypt_auth' => 'Decrypt Auth',
            'is_used' => 'Is Used',
        ];
    }
}
