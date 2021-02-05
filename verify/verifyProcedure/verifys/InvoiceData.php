<?php
/**
 * Author: lf.
 * Email: brian.liu@gaopeng.com
 * Created at: 2018-05-28 15:41
 */

namespace app\components\verifyProcedure\verifys;

use app\modules\gscheck\models\invoiceVerify\Invoice;

class InvoiceData extends Invoice
{
	const PIAOTONG_CHANNEL = 0;

	public static $verifyChannelConfig = [
		self::PIAOTONG_CHANNEL => '票通',
	];

	public $remoteReturn;
	public $decryptReturn;
	public $requestUrl;
	public $requestParams;
	public $encryptParams;

	public function rules()
	{
		return array_merge(parent::rules(), [
			[['remoteReturn', 'decryptReturn', 'requestParams', 'encryptParams', 'requestUrl'], 'string'],
		]);
	}
}