<?php

namespace app\modules\gscheck\models\invoiceVerify;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%invoice_image_detail}}".
 *
 * @property int $id 自增id
 * @property int $image_id 图片id
 * @property string $invoice_type 发票类别
 * @property string $ocr_content OCR识别内容
 */
class InvoiceImageDetail extends ActiveRecord
{
	use TableHelperTrait;

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'y_invoice_image_detail_' . self::getPartitionIndex();
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
			[['image_id', 'ocr_content', 'invoice_type'], 'required'],
			[['image_id'], 'integer'],
			[['invoice_type'], 'string', 'max' => 50],
			[['ocr_content'], 'string', 'max' => 5000],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '自增id',
			'image_id' => '图片id',
			'invoice_type' => '发票类别',
			'ocr_content' => 'OCR识别内容',
		];
	}
}
