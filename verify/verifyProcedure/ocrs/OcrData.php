<?php

namespace app\components\verifyProcedure\ocrs;

use app\modules\gscheck\models\invoiceVerify\InvoiceImage;

class OcrData extends InvoiceImage
{
	const BAIDU_IOCR = 0;

	public static $ocrChannelConfig = [
		self::BAIDU_IOCR => '百度IOCR',
	];

	const CONTENT_TYPE_FILE = 'file';
	const CONTENT_TYPE_FILE_BASE64 = 'base64';
	const CONTENT_TYPE_URL = 'url';

	const VERIFY_AUTHORIZATION_COUNT_CACHE_KEY_PREFIX = 'invoice_verify_cache_key_';

	public $remoteReturn;
	public $decryptReturn;
	public $requestUrl;
	public $requestParams;
	public $encryptParams;

	public $invoiceDetailArr;

	public function rules()
	{
		return array_merge(parent::rules(), [
			[['remoteReturn', 'decryptReturn', 'requestParams', 'encryptParams'], 'string'],
		]);
	}
}