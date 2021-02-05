<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;

use yii\base\Model;
use yii\helpers\BaseInflector;

class TollInvoice extends Model
{
	public $Entrance;
	public $Time;
	public $InvoiceNum;
	public $InvoiceCode;
	public $Exit;
	public $Date;
	public $Fare;

	public function rules()
	{
		return [
			[[
				'Entrance',
				'Time',
				'InvoiceNum',
				'InvoiceCode',
				'Exit',
				'Date',
				'Fare',
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
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}
		}
		return $ret;
	}
}