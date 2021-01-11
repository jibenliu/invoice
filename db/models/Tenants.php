<?php

namespace common\components\db\models;

use Yii;
use common\models\ErpApiSeting;

/**
 * This is the model class for table "p_tenants".
 *
 * @property string $tenant_id tenant_id作为租户唯一标识，手动生成  生成规则：my+13位字符串 例：my3973dsfsssabc
 * @property string $crop_id 客户GUID
 * @property string $tenant_name
 * @property string $tenant_code
 * @property string $owner_area
 * @property string $db_name
 * @property string $instance_id
 * @property string $admin_name
 * @property string $admin_mail
 * @property string $init_pwd
 * @property string $create_time
 * @property string $update_time
 * @property int $is_enable 0：禁用  1：启用
 * @property string $global_corp_id
 * @property string $admin_phone
 * @property string $contract_number
 * @property string $admin_email
 * @property string $contract_name
 * @property string $contract_num
 * @property string $taxno_limit
 * @property int $status
 * @property string $last_err
 *
 * @property Instance $instance
 * @property ErpApiSeting $setting
 */
class Tenants extends \yii\db\ActiveRecord
{
    CONST STATUS_ACTIVE = 1;
    CONST STATUS_INACTIVE = 0;

    public static $status_config = [
        self::STATUS_ACTIVE => '启用',
        self::STATUS_INACTIVE => '禁用',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'p_tenants';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return Yii::$app->get('dbConfig');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tenant_id', 'crop_id'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['status', 'taxno_limit'], 'integer'],
            [['tenant_id', 'tenant_code', 'instance_id', 'admin_mail', 'contract_num'], 'string', 'max' => 100],
            [['crop_id', 'admin_email', 'contract_name'], 'string', 'max' => 255],
            [['tenant_name'], 'string', 'max' => 200],
            [['owner_area', 'db_name', 'admin_name', 'init_pwd', 'global_corp_id', 'admin_phone', 'contract_number'], 'string', 'max' => 50],
            [['is_enable'], 'string', 'max' => 1],
            [['last_err'], 'string', 'max' => 500],
            [['tenant_code'], 'unique'],
            [['tenant_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tenant_id' => 'tenant_id作为租户唯一标识，手动生成 生成规则：my+13位字符串 例：my3973dsfsssabc',
            'crop_id' => '客户GUID',
            'tenant_name' => 'Tenant Name',
            'tenant_code' => 'Tenant Code',
            'owner_area' => 'Owner Area',
            'db_name' => 'Db Name',
            'instance_id' => 'Instance ID',
            'admin_name' => 'Admin Name',
            'admin_mail' => 'Admin Mail',
            'init_pwd' => 'Init Pwd',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_enable' => '0：禁用 1：启用',
            'taxno_limit' => '租户税号授权数',
            'global_corp_id' => 'Global Corp ID',
            'admin_phone' => 'Admin Phone',
            'contract_number' => 'Contract Number',
            'admin_email' => 'Admin Email',
            'contract_name' => 'Contract Name',
            'contract_num' => 'Contract Num',
            'status' => 'Status',
            'last_err' => 'Last Err',
        ];
    }

    public function getInstance()
    {
        return $this->hasOne(Instance::class, ['instance_id' => 'instance_id']);
    }

    public function getSetting()
    {
        return $this->hasOne(ErpApiSeting::class, ['tenant_id' => 'tenant_id']);
    }
}
