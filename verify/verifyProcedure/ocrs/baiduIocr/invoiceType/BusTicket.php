<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;

use yii\base\Model;
use yii\helpers\BaseInflector;

class BusTicket extends Model
{
	public $DestinationStation;
	public $IdNum;
	public $Time;
	public $InvoiceNum;
	public $InvoiceCode;
	public $StartingStation;
	public $Date;
	public $Fare;

	public function rules()
	{
		return [
			[[
				'DestinationStation',
				'IdNum',
				'Time',
				'InvoiceNum',
				'InvoiceCode',
				'StartingStation',
				'Date',
				'Name',
				'Fare',
			], 'string',],
		];
	}

	public function fields()
	{
		$fields = parent::fields();
		$ret = [];
		foreach ($fields as $key => $item) {
			if ($key == 'InvoiceNum') {
				$ret['invoice_no'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}

		}
		return $ret;
	}
}