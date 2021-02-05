<?php

namespace app\modules\gscheck\models\invoiceVerify;

use yii\base\Model;
use yii\web\BadRequestHttpException;
use app\common\helpers\InvoiceHelpers;
use app\components\verifyProcedure\VerifyService;

class InvoiceVerify extends Model
{
	use CheckTenantTrait;

	public $tenant_code;
	public $invoice_code;//发票代码
	public $invoice_no;//发票号码
	public $invoice_date;//发票日期
	public $check_code;//校验码
	public $amount_without_tax;//合计不含税金额

	public function rules()
	{
		return [
			[['invoice_code', 'invoice_no', 'invoice_date', 'check_code', 'amount_without_tax', 'tenant_code'], 'trim'],
			[['invoice_code', 'invoice_no', 'invoice_date', 'tenant_code'], 'required'],
			['tenant_code', 'checkTenant'],
			['invoice_code', 'checkType'],
			['invoice_code', 'string', 'min' => 10, 'max' => 12],
			['invoice_no', 'string', 'min' => 8, 'max' => 8],
			['invoice_date', 'date', 'format' => 'yyyy-mm-dd'],
			['invoice_date', 'checkDate'],
			['amount_without_tax', 'match', 'pattern' => '/^-?(0|[1-9][0-9]*)(\.[0-9]{0,2})?$/'],
		];
	}

	public function attributeLabels()
	{
		return [
			'invoice_code' => '发票代码',
			'invoice_no' => '发票号码',
			'invoice_date' => '开票日期',
			'check_code' => '校验码',
			'amount_without_tax' => '合计不含税金额',
		];
	}

	public function checkType($attribute)
	{
		if (!$this->hasErrors()) {
			$invoiceType = InvoiceHelpers::getInvoiceAreaByInvoiceNo($this->$attribute);
			if (
				in_array($invoiceType,
					[
						InvoiceHelpers::SPECIAL_INVOICE,
						InvoiceHelpers::TRANSPORT_SPECIAL_INVOICE,
						InvoiceHelpers::MOTOR_PURCHASE_INVOICE,
						InvoiceHelpers::SECOND_HAND_MOTOR_PURCHASE_INVOICE,
					])
				&& empty($this->amount_with_tax)
			) {
				$this->addError($attribute, '当前发票类型下查验发票金额不能为空!');
			} elseif (empty($this->check_code)) {
				$this->addError($attribute, '当前发票类型下查验发票校验码不能为空!');
			}
		}
	}

	public function checkDate($attribute)
	{
		if (!$this->hasErrors() && $this->$attribute >= date('Y-m-d')) {
			$this->addError($attribute, '当日开具的发票请于次日查询!');
		}
	}

	public function getApiCode()
	{
		return VerifyService::INVOICE_VERIFY_API_CODE;
	}

	public function process($params)
	{
		$this->load($params, '');
		if ($this->validate()) {
			Invoice::setTableSuffixByDate($this->invoice_date);
			Invoice::createTable(Invoice::getPartitionIndex());
			$invoice = Invoice::findOne([
				'invoice_code' => $this->invoice_code,
				'invoice_no' => $this->invoice_no,
				'invoice_date' => $this->invoice_date,
			]);
			if (!$invoice) {
				$service = new VerifyService($this->tenantInfo, $this->logStatus, $this->chargeStatus);
				return $service->verify($params);
			}
			return [$invoice, 'details' => $invoice->details];
		} else {
			throw new BadRequestHttpException(current($this->getFirstErrors()));
		}
	}
}