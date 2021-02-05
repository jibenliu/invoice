<?php

namespace app\modules\gscheck\models\invoiceVerify;

use Yii;

/**
 * This is the model class for table "{{%invoice_detail}}".
 *
 * @property int $id 主键
 * @property int $invoice_id 发票id
 * @property string $item_name 货物及服务名称（电子发票通行费：项目名称）
 * @property string $specification 型号规格（电子发票通行费：车牌号）
 * @property string $quantity_unit 单位
 * @property string $quantity 数量
 * @property int $price_type 价格方式（0=>不含税价、1=>含税价）
 * @property string $tax_rate 税率
 * @property string $price 单价
 * @property string $amount_with_tax 含税金额
 * @property string $amount_without_tax 不含税金额
 * @property string $tax_amount 税额
 * @property string $deductions 可抵扣金额
 * @property string $goods_version 编码版本号
 * @property string $goods_tax_no 税收分类编码
 * @property string $goods_tax_name 商品税务名称
 * @property string $tax_pre_con 征税标识 1:免征  2:不征税
 */
class InvoiceDetail extends \yii\db\ActiveRecord
{
	use TableHelperTrait;

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'y_invoice_detail_' . self::getPartitionIndex();
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
			[['invoice_id'], 'integer'],
			[['quantity', 'tax_rate', 'price', 'amount_with_tax', 'amount_without_tax', 'tax_amount', 'deductions'], 'number'],
			[['item_name', 'specification'], 'string', 'max' => 50],
			[['quantity_unit', 'goods_version', 'goods_tax_no'], 'string', 'max' => 20],
			[['price_type'], 'string', 'max' => 4],
			[['goods_tax_name'], 'string', 'max' => 100],
			[['tax_pre_con'], 'string', 'max' => 10],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '主键',
			'invoice_id' => '发票id',
			'item_name' => '货物及服务名称（电子发票通行费：项目名称）',
			'specification' => '型号规格（电子发票通行费：车牌号）',
			'quantity_unit' => '单位',
			'quantity' => '数量',
			'price_type' => '价格方式（0=>不含税价、1=>含税价）',
			'tax_rate' => '税率',
			'price' => '单价',
			'amount_with_tax' => '含税金额',
			'amount_without_tax' => '不含税金额',
			'tax_amount' => '税额',
			'deductions' => '可抵扣金额',
			'goods_version' => '编码版本号',
			'goods_tax_no' => '税收分类编码',
			'goods_tax_name' => '商品税务名称',
			'tax_pre_con' => '征税标识 1:免征  2:不征税',
		];
	}
}
