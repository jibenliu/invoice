<?php

namespace app\modules\gscheck\models\invoiceVerify;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%invoice}}".
 *
 * @property int $id 主键
 * @property string $invoice_type 发票类型(01-增值税专用发票 03-机动车发票 04-增值税普通发票 10-电子发票 11-卷式发票 14-电子发票通行费)
 * @property string $invoice_no 发票号码
 * @property string $tenant_code 识别租户代码
 * @property string $invoice_code 发票代码
 * @property string $seller_taxno 销方税号
 * @property string $seller_name 销方名称
 * @property string $seller_address 销方地址
 * @property string $seller_tel 销方电话
 * @property string $seller_bank_name 销方银行名称
 * @property string $seller_bank_account 销方银行账号
 * @property string $buyer_taxno 购方税号
 * @property string $buyer_name 购方名称
 * @property string $buyer_address 购方地址
 * @property string $buyer_tel 购方电话
 * @property string $buyer_bank_name 购方银行名称
 * @property string $buyer_bank_account 购方银行账号
 * @property string $tax_rate 税率
 * @property string $amount_with_tax 含税金额
 * @property string $amount_without_tax 不含税金额
 * @property string $capital_amount_with_tax 含税金额大写
 * @property string $tax_amount 税额
 * @property string $deductions 可抵扣金额
 * @property string $remark 备注
 * @property string $payee 收款人
 * @property string $checker 复核人
 * @property string $drawer 开票人
 * @property string $invoice_date 开票日期
 * @property string $receiver 电票接收人
 * @property string $receiver_phone 电票接收人邮箱手机
 * @property string $receiver_email 电票接收人邮箱
 * @property int $is_valid 是否作废
 * @property string $check_code 校验码
 * @property string $machine_code 机器码
 * @property int $verify_times 发票查验次数
 * @property int $flag 发票标识 1 公司，2个人
 * @property int $created_at 创建时间
 * @property int $updated_at 最后更新时间
 *
 * @property InvoiceDetail[] $details 发票详情
 */
class Invoice extends \yii\db\ActiveRecord
{
	use TableHelperTrait;

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'y_invoice_' . self::getPartitionIndex();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['tax_rate', 'amount_with_tax', 'amount_without_tax', 'tax_amount', 'deductions'], 'number'],
			[['invoice_date', 'verify_times', 'flag', 'created_at', 'updated_at'], 'integer'],
			[['invoice_type'], 'string', 'max' => 5],
			[['tenant_code'], 'string', 'max' => 50],
			[['invoice_no', 'invoice_code', 'seller_taxno', 'seller_name', 'seller_tel', 'seller_bank_name', 'seller_bank_account', 'buyer_taxno', 'buyer_name', 'buyer_tel', 'buyer_bank_name', 'buyer_bank_account', 'check_code', 'machine_code'], 'string', 'max' => 50],
			[['seller_address', 'buyer_address', 'capital_amount_with_tax'], 'string', 'max' => 150],
			[['remark'], 'string', 'max' => 4000],
			[['drawer', 'checker', 'payee'], 'string', 'max' => 20],
			[['is_valid'], 'string', 'max' => 1],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '主键',
			'invoice_type' => '发票类型(01-增值税专用发票 03-机动车发票 04-增值税普通发票 10-电子发票 11-卷式发票 14-电子发票通行费)',
			'invoice_no' => '发票号码',
			'tenant_code' => '识别租户代码',
			'invoice_code' => '发票代码',
			'seller_taxno' => '销方税号',
			'seller_name' => '销方名称',
			'seller_address' => '销方地址',
			'seller_tel' => '销方电话',
			'seller_bank_name' => '销方银行名称',
			'seller_bank_account' => '销方银行账号',
			'buyer_taxno' => '购方税号',
			'buyer_name' => '购方名称',
			'buyer_address' => '购方地址',
			'buyer_tel' => '购方电话',
			'buyer_bank_name' => '购方银行名称',
			'buyer_bank_account' => '购方银行账号',
			'tax_rate' => '税率',
			'amount_with_tax' => '含税金额',
			'amount_without_tax' => '不含税金额',
			'capital_amount_with_tax' => '含税金额大写',
			'tax_amount' => '税额',
			'is_valid' => '是否作废',
			'deductions' => '可抵扣金额',
			'remark' => '备注',
			'payee' => '收款人',
			'checker' => '复核人',
			'drawer' => '开票人',
			'invoice_date' => '开票日期',
			'check_code' => '校验码',
			'machine_code' => '机器码',
			'verify_times' => '发票查验次数',
			'flag' => '发票标识 1 公司，2个人',
			'created_at' => '创建时间',
			'updated_at' => '最后更新时间',
		];
	}

	public function getDetails()
	{
		InvoiceDetail::setTableSuffixByDate($this->invoice_date);
		return $this->hasOne(InvoiceDetail::className(), ['invoice_id' => 'id']);
	}

	public function behaviors()
	{
		return [
			[
				'class' => TimestampBehavior::class,
			],
		];
	}

	public static function createTable($suffix)
	{
		$create_invoice_table_by_month = <<<EDF
DELIMITER $$
DROP PROCEDURE IF EXISTS create_invoice_table_by_month $$
CREATE PROCEDURE create_invoice_table_by_month(IN monthStr VARCHAR(255))
BEGIN
    DECLARE i, table_exists INT DEFAULT 0;
	CALL get_table_by_name(CONCAT('y_invoice_',monthStr), table_exists);
	IF !table_exists THEN
		SET @create_sql = CONCAT('CREATE TABLE y_invoice_', monthStr, "(
			`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
			  `invoice_type` varchar(5) DEFAULT '' COMMENT '发票类型(01-增值税专用发票 03-机动车发票 04-增值税普通发票 10-电子发票 11-卷式发票 14-电子发票通行费)',
			  `tenant_code` varchar(50) DEFAULT '' COMMENT '识别租户代码',
			  `invoice_no` varchar(50) DEFAULT '' COMMENT '发票号码',
			  `invoice_code` varchar(50) DEFAULT '' COMMENT '发票代码',
			  `seller_taxno` varchar(50) DEFAULT '' COMMENT '销方税号',
			  `seller_name` varchar(50) DEFAULT '' COMMENT '销方名称',
			  `seller_address` varchar(150) DEFAULT '' COMMENT '销方地址',
			  `seller_tel` varchar(50) DEFAULT '' COMMENT '销方电话',
			  `seller_bank_name` varchar(50) DEFAULT '' COMMENT '销方银行名称',
			  `seller_bank_account` varchar(50) DEFAULT '' COMMENT '销方银行账号',
			  `buyer_taxno` varchar(50) DEFAULT '' COMMENT '购方税号',
			  `buyer_name` varchar(50) DEFAULT '' COMMENT '购方名称',
			  `buyer_address` varchar(150) DEFAULT '' COMMENT '购方地址',
			  `buyer_tel` varchar(50) DEFAULT '' COMMENT '购方电话',
			  `buyer_bank_name` varchar(50) DEFAULT '' COMMENT '购方银行名称',
			  `buyer_bank_account` varchar(50) DEFAULT '' COMMENT '购方银行账号',
			  `tax_rate` decimal(18,2) DEFAULT '0.00' COMMENT '税率',
			  `amount_with_tax` decimal(18,2) DEFAULT '0.00' COMMENT '含税金额',
			  `amount_without_tax` decimal(18,2) DEFAULT '0.00' COMMENT '不含税金额',
			  `capital_amount_with_tax` varchar(100) DEFAULT '0.00' COMMENT '含税金额大写',
			  `tax_amount` decimal(18,2) DEFAULT '0.00' COMMENT '税额',
			  `deductions` decimal(18,2) DEFAULT '0.00' COMMENT '可抵扣金额',
			  `remark` varchar(4000) DEFAULT '' COMMENT '备注',
			  `is_valid` tinyint(1) DEFAULT 0 COMMENT '作废标记',
			  `payee` varchar(20) DEFAULT '' COMMENT '收款人',
			  `checker` varchar(20) DEFAULT '' COMMENT '复核人',
			  `drawer` varchar(20) DEFAULT '' COMMENT '开票人',
			  `invoice_date` int(11) DEFAULT '0' COMMENT '开票日期',
			  `check_code` varchar(50) NOT NULL DEFAULT '' COMMENT '校验码',
			  `machine_code` varchar(50) NOT NULL DEFAULT '' COMMENT '机器码',
			  `verify_times` smallint(5) NOT NULL DEFAULT 1 COMMENT '发票查验次数',
			  `flag` int(1) NOT NULL DEFAULT '1' COMMENT '发票标识 1 公司，2个人',
			  `receiver` varchar(50) DEFAULT '' COMMENT '电票接收人',
			  `receiver_phone` varchar(15) DEFAULT '' COMMENT '电票接收人电话',
			  `receiver_email` varchar(50) DEFAULT '' COMMENT '电票接收人邮箱',
			  `created_at` int(11) DEFAULT 0 COMMENT '创建时间',
			  `updated_at` int(11) DEFAULT 0 COMMENT '最后更新时间',
			  PRIMARY KEY (`id`),
			  KEY `code_no_index` (`invoice_code`,`invoice_no`,`invoice_date`,`amount_with_tax`) USING BTREE
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;");
		PREPARE stmt FROM @create_sql;
		EXECUTE stmt;
	END IF;
	CALL get_table_by_name(CONCAT('y_invoice_detail_',monthStr), table_exists);
	IF !table_exists THEN
		SET @create_detail_sql = CONCAT('CREATE TABLE y_invoice_detail_', monthStr, "(
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
			`invoice_id` int(11) DEFAULT '0' COMMENT '发票id',
			`item_name` varchar(50) DEFAULT '' COMMENT '货物及服务名称（电子发票通行费：项目名称）',
			`specification` varchar(50) DEFAULT '' COMMENT '型号规格（电子发票通行费：车牌号）',
			`quantity_unit` varchar(20) DEFAULT '' COMMENT '单位',
			`quantity` decimal(18,8) DEFAULT '0.00000000' COMMENT '数量',
			`price_type` tinyint(4) DEFAULT '0' COMMENT '价格方式（0=>不含税价、1=>含税价）',
			`tax_rate` decimal(18,2) DEFAULT '0.00' COMMENT '税率',
			`price` decimal(18,8) DEFAULT '0.00000000' COMMENT '单价',
			`amount_with_tax` decimal(18,2) DEFAULT '0.00' COMMENT '含税金额',
			`amount_without_tax` decimal(18,2) DEFAULT '0.00' COMMENT '不含税金额',
			`tax_amount` decimal(18,2) DEFAULT '0.00' COMMENT '税额',
			`deductions` decimal(18,2) DEFAULT '0.00' COMMENT '可抵扣金额',
			`goods_version` varchar(20) DEFAULT '' COMMENT '编码版本号',
			`goods_tax_no` varchar(20) DEFAULT '' COMMENT '税收分类编码',
			`goods_tax_name` varchar(100) DEFAULT '' COMMENT '商品税务名称',
			`tax_pre_con` varchar(10) DEFAULT '' COMMENT '零税率标志 免征  不征税 零税率',
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;");
		PREPARE stmt FROM @create_detail_sql;
		EXECUTE stmt;
	END IF;
END $$
DELIMITER ;
EDF;
		$createSql = "CALL create_invoice_table_by_month($suffix);";
		Yii::$app->db_common->createCommand($createSql)->execute();
	}
}
