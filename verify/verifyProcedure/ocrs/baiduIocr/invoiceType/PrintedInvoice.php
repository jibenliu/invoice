<?php


namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;


use yii\base\Model;
use yii\helpers\BaseInflector;

class PrintedInvoice extends Model
{
	public $TotalTax;
	public $InvoiceCode;
	public $InvoiceDate;
	public $CommodityName;
	public $InvoiceNum;
	public $InvoiceType;

	public function rules()
	{
		return [
			[[
				'TotalTax',
				'InvoiceCode',
				'InvoiceDate',
				'CommodityName',
				'InvoiceNum',
				'InvoiceType',
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
			} elseif ($key == 'TotalTax') {
				$ret['amount_with_tax'] = $item;
			} elseif ($key == 'InvoiceType') {
				$ret['invoice_type_name'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}

		}
		return $ret;
	}
}