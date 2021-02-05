<?php


namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;


use yii\base\Model;
use yii\helpers\BaseInflector;

class VehicleInvoice extends Model
{
	public $ManuModel;
	public $PayerCode;
	public $Tax;
	public $VinNum;
	public $PriceTax;
	public $Saler;
	public $Price;
	public $MachineCode;
	public $TaxRate;
	public $InvoiceCode;
	public $InvoiceDate;
	public $EngineNum;
	public $InvoiceNum;
	public $PriceTaxLow;

	public function rules()
	{
		return [
			[[
				'ManuModel',
				'PayerCode',
				'Tax',
				'VinNum',
				'PriceTax',
				'Saler',
				'Price',
				'MachineCode',
				'TaxRate',
				'InvoiceCode',
				'InvoiceDate',
				'EngineNum',
				'InvoiceNum',
				'PriceTaxLow',
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
			} elseif ($key == 'Tax') {
				$ret['amount_tax'] = $item;
			} elseif ($key == 'Saler') {
				$ret['seller_name'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}
		}
		return $ret;
	}
}