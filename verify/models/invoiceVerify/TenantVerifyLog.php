<?php

namespace app\modules\gscheck\models\invoiceVerify;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%y_tenant_verify_log}}".
 *
 * @property int $id 主键
 * @property string $tenant_code 创建租户代码
 * @property string $tenant_id 创建租户id
 * @property string $api_code 接口类型
 * @property int $api_count 耗用次数
 * @property int $created_at 创建时间
 */
class TenantVerifyLog extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'tenant_verify_log';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('db_logsinvoice');
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['tenant_code', 'tenant_id', 'api_code', 'created_at'], 'required'],
			[['api_count', 'created_at'], 'integer'],
			[['tenant_code', 'tenant_id', 'api_code'], 'string', 'max' => 50],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '主键',
			'tenant_code' => '创建租户代码',
			'tenant_id' => '创建租户id',
			'api_code' => '接口类型',
			'api_count' => '耗用次数',
			'created_at' => '创建时间',
		];
	}

	public function behaviors()
	{
		return [
			[
				'class' => TimestampBehavior::class,
				'attributes' => [
					ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
				],
			],
		];
	}
}
