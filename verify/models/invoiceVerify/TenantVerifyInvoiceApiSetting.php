<?php

namespace app\modules\gscheck\models\invoiceVerify;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%p_tenant_verify_invoice_api_setting}}".
 *
 * @property int $id 自增id
 * @property string $tenant_id 租户id
 * @property string $tenant_name 租户名称
 * @property string $tenant_code 租户代码
 * @property string $api_name 接口名称
 * @property string $api_code 接口代码
 * @property int $api_status 启用状态
 * @property int $log_status 日志状态
 * @property int $charge_status 计费状态
 * @property int $created_at 创建时间
 * @property int $updated_at 最后更新时间
 */
class TenantVerifyInvoiceApiSetting extends \yii\db\ActiveRecord
{
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	public static $status_config = [
		self::STATUS_ACTIVE => '启用',
		self::STATUS_INACTIVE => '禁用',
	];

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%p_tenant_verify_invoice_api_setting}}';
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
			[['id', 'tenant_id', 'tenant_name', 'tenant_code', 'log_status', 'charge_status', 'created_at', 'updated_at'], 'required'],
			[['id'], 'integer'],
			[['tenant_id'], 'string', 'max' => 50],
			[['tenant_name'], 'string', 'max' => 255],
			[['tenant_code'], 'string', 'max' => 100],
			[['api_name', 'api_code'], 'string', 'max' => 200],
			[['api_status', 'log_status', 'charge_status'], 'string', 'max' => 1],
			[['api_status', 'log_status', 'charge_status'], 'default', 'value' => 0],
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
			'api_name' => '接口名称',
			'api_code' => '接口代码',
			'api_status' => '启用状态',
			'log_status' => '日志状态',
			'charge_status' => '计费状态',
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
