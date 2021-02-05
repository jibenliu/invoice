<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr\invoiceType;

use yii\base\Model;
use yii\helpers\BaseInflector;

class VatInvoiceV2 extends Model
{
	use VatInvoiceTrait;

	public function fields()
	{
		$fields = parent::fields();
		$ret = [];
		foreach ($fields as $key => $item) {
			$ret[BaseInflector::underscore($key)] = $item;
		}
		return $ret;
	}
}