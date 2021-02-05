<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;

use yii\helpers\BaseInflector;

trait VatInvoiceTrait
{
	public $AmountInWords;
	public $NoteDrawer;
	public $SellerAddress;
	public $SellerRegisterNum;
	public $Remarks;
	public $SellerBank;
	public $TotalTax;
	public $CheckCode;
	public $InvoiceCode;
	public $InvoiceDate;
	public $PurchaserRegisterNum;
	public $InvoiceTypeOrg;
	public $Password;
	public $AmountInFiguers;
	public $PurchaserBank;
	public $PurchaserName;
	public $InvoiceType;//@TODO 汉字
	public $PurchaserAddress;
	public $Payee;
	public $SellerName;
	public $InvoiceNum;

	public function rules()
	{
		return [
			[[
				'AmountInWords',
				'NoteDrawer',
				'SellerAddress',
				'SellerRegisterNum',
				'Remarks',
				'SellerBank',
				'TotalTax',
				'CheckCode',
				'InvoiceCode',
				'InvoiceDate',
				'PurchaserRegisterNum',
				'InvoiceTypeOrg',
				'Password',
				'AmountInFiguers',
				'PurchaserBank',
				'PurchaserName',
				'PurchaserAddress',
				'Payee',
				'SellerName',
				'InvoiceNum',
			], 'string'],
		];
	}

	public function fields()
	{
		$fields = parent::fields();
		$ret = [];
		foreach ($fields as $key => $item) {
			if ($key == 'AmountInWords') {
				$ret['capital_amount_with_tax'] = $item;
			} elseif ($key == 'NoteDrawer') {
				$ret['drawer'] = $item;
			} elseif ($key == 'SellerRegisterNum') {
				$ret['seller_taxno'] = $item;
			} elseif ($key == 'TotalTax') {
				$ret['amount_tax'] = $item;
			} elseif ($key == 'PurchaserRegisterNum') {
				$ret['buyer_taxno'] = $item;
			} elseif ($key == 'PurchaserBank') {
				$ret['buyer_bank'] = $item;
			} elseif ($key == 'AmountInFiguers') {
				$ret['amount_with_tax'] = $item;
			} elseif ($key == 'InvoiceType') {
				$ret['invoice_type_name'] = $item;
			} elseif ($key == 'PurchaserName') {
				$ret['buyer_name'] = $item;
			} elseif ($key == 'PurchaserAddress') {
				$ret['buyer_address'] = $item;
			} elseif ($key == 'InvoiceNum') {
				$ret['invoice_no'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}
		}
		return $ret;
	}
}