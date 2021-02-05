<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;

use yii\base\Model;
use yii\helpers\BaseInflector;

class QuotaInvoice extends Model
{
	public $invoice_code;
	public $invoice_rate;
	public $invoice_number;

	public function rules()
	{
		return [
			[['invoice_code', 'invoice_rate', 'invoice_number'], 'string'],
		];
	}

	public function fields()
	{
		$fields = parent::fields();
		$ret = [];
		foreach ($fields as $key => $item) {
			if ($key == 'invoice_rate') {
				$ret['capital_amount_with_tax'] = $item;
			} else {
				$ret[BaseInflector::underscore($key)] = $item;
			}
		}
		return $ret;
	}
}