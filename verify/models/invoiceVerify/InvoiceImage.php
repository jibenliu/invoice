<?php

namespace app\modules\gscheck\models\invoiceVerify;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%invoice_image}}".
 *
 * @property int $id 自增id
 * @property string $image_url 图片地址
 * @property int $ocr_status ocr状态码
 * @property string $tenant_code 创建租户代码
 * @property int $created_at 创建时间
 * @property int $updated_at 最后更新时间
 *
 * @property InvoiceImageDetail[] $details 发票详情
 */
class InvoiceImage extends ActiveRecord
{
	const OCR_SUCCESS = 1;
	const OCR_FAIL = 0;

	use TableHelperTrait;

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'y_invoice_image_' . self::getPartitionIndex();
	}

	public static function getDb()
	{
		return \Yii::$app->get('db_common');
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['image_url', 'ocr_status', 'tenant_code', 'created_at', 'updated_at'], 'required'],
			[['created_at', 'updated_at'], 'integer'],
			[['image_url'], 'string', 'max' => 500],
			[['ocr_status'], 'string', 'max' => 1],
			[['tenant_code'], 'string', 'max' => 50],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '自增id',
			'image_url' => '图片地址',
			'ocr_status' => 'ocr状态码',
			'tenant_code' => '创建租户代码',
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

	public function getDetails()
	{
		InvoiceImageDetail::setTableSuffix(self::getPartitionIndex());
		return $this->hasOne(InvoiceImageDetail::className(), ['invoice_id' => 'id']);
	}
}
