<?php


namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;


use yii\base\Model;
use yii\helpers\BaseInflector;

class VatRollTicket extends Model
{
	public $AmountInWords;
	public $AmountInFiguers;
	public $InvoiceType;
	public $SellerRegisterNum;
	public $MachineNum;
	public $MachineCode;
	public $TotalTax;
	public $CheckCode;
	public $InvoiceCode;
	public $InvoiceDate;
	public $PurchaserRegisterNum;
	public $InvoiceNum;

	public function rules()
	{
		return [
			[[
				'AmountInWords',
				'AmountInFiguers',
				'InvoiceType',
				'SellerRegisterNum',
				'MachineNum',
				'MachineCode',
				'TotalTax',
				'CheckCode',
				'InvoiceCode',
				'InvoiceDate',
				'PurchaserRegisterNum',
				'InvoiceNum',
			], 'string'],
		];
	}

	public function fields()
	{
		$fields = parent::fields();
		$ret = [];
		foreach ($fields as $key => $item) {
			if ($key == 'InvoiceNum') {
				$ret['invoice_no'] = $item;
			} elseif ($key == 'AmountInWords') {
				$ret['capital_amount_with_tax'] = $item;
			} elseif ($key == 'AmountInFiguers') {
				$ret['amount_with_tax'] = $item;
			} elseif ($key == 'InvoiceType') {
				$ret['invoice_type_name'] = $item;
			} elseif ($key == 'SellerRegisterNum') {
				$ret['seller_taxno'] = $item;
			} elseif ($key == 'TotalTax') {
				$ret['amount_tax'] = $item;
			} elseif ($key == 'PurchaserRegisterNum') {
				$ret['buyer_taxno'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}
		}
		return $ret;
	}
}