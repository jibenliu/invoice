<?php

namespace app\modules\gscheck\models\invoiceVerify;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%p_tenant_verify_invoice_setting}}".
 *
 * @property int $id 自增id
 * @property string $tenant_id 租户id
 * @property string $tenant_name 租户名称
 * @property string $tenant_code 租户代码
 * @property int $charge_count 购买次数
 * @property int $left_charge_count 剩余次数
 * @property int $status 启用状态
 * @property int $created_at 创建时间
 * @property int $updated_at 最后更新时间
 */
class TenantVerifyInvoiceSetting extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%p_tenant_verify_invoice_setting}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_invoice');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'tenant_id', 'tenant_name', 'tenant_code', 'created_at', 'updated_at'], 'required'],
            [['id', 'charge_count', 'left_charge_count'], 'integer'],
            [['tenant_id'], 'string', 'max' => 50],
            [['tenant_name'], 'string', 'max' => 255],
            [['tenant_code'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 1],
            [['created_at', 'updated_at'], 'string', 'max' => 11],
            [['id', 'tenant_id', 'tenant_code'], 'unique', 'targetAttribute' => ['id', 'tenant_id', 'tenant_code']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '自增id',
            'tenant_id' => '租户id',
            'tenant_name' => '租户名称',
            'tenant_code' => '租户代码',
            'charge_count' => '购买次数',
            'left_charge_count' => '剩余次数',
            'status' => '启用状态',
            'created_at' => '创建时间',
            'updated_at' => '最后更新时间',
        ];
    }

	public function behaviors()
	{
		return [
			[
				'class' => TimestampBehavior::class,
			],
		];
	}
}
