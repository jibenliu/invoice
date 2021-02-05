<?php


namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;


use yii\base\Model;
use yii\helpers\BaseInflector;

class RollTicket extends Model
{
	public $AmountInWords;
	public $AmountInFiguers;
	public $City;
	public $PurchaserName;
	public $Province;
	public $InvoiceType;
	public $MachineNum;
	public $SellerRegisterNum;
	public $MachineCode;
	public $TotalTax;
	public $CheckCode;
	public $InvoiceCode;
	public $InvoiceDate;
	public $Payee;
	public $PurchaserRegisterNum;
	public $SellerName;
	public $InvoiceNum;

	public function rules()
	{
		return [
			[[
				'AmountInWords',
				'AmountInFiguers',
				'City',
				'PurchaserName',
				'Province',
				'InvoiceType',
				'MachineNum',
				'SellerRegisterNum',
				'MachineCode',
				'TotalTax',
				'CheckCode',
				'InvoiceCode',
				'InvoiceDate',
				'Payee',
				'PurchaserRegisterNum',
				'SellerName',
				'InvoiceNum'], 'string'],
		];
	}

	public function fields()
	{
		$fields = parent::fields();
		$ret = [];
		foreach ($fields as $key => $item) {
			if ($key == 'AmountInWords') {
				$ret['capital_amount_with_tax'] = $item;
			} elseif ($key == 'InvoiceNum') {
				$ret['invoice_no'] = $item;
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
			}elseif ($key == 'PurchaserName') {
				$ret['buyer_name'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}
		}
		return $ret;
	}
}