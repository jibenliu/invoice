<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;

use yii\base\Model;
use yii\helpers\BaseInflector;

class TaxiReceipt extends Model
{
	public $TaxiNum;
	public $InvoiceCode;
	public $Time;
	public $FuelOilSurcharge;
	public $Date;
	public $Fare;
	public $InvoiceNum;
	public $CallServiceSurcharge;

	public function rules()
	{
		return [
			[[
				'TaxiNum',
				'InvoiceCode',
				'Time',
				'FuelOilSurcharge',
				'Date',
				'Fare',
				'InvoiceNum',
				'CallServiceSurcharge',
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
			} elseif ($key == 'TaxiNum') {
				$ret['taxi_no'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}
		}
		return $ret;
	}
}