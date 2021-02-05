<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr;

use Yii;
use yii\redis\Connection;
use app\common\helpers\CurlHelper;
use yii\web\ServerErrorHttpException;
use app\components\baiduOcr\BaseBaiduOcr;

class BaiduApiManager
{
	const BUS_INVOICE = "bus_ticket";//汽车票
	const TOLL_INVOICE = "toll_invoice";//通行费发票
	const VAT_INVOICE_V2 = "vat_invoice_v2";//增值税发票v2
	const VAT_INVOICE = "vat_invoice";//增值税发票
	const TAXI_RECEIPT = "taxi_receipt";//出租发票
	const TRAIN_TICKET = "train_ticket";//火车票
	const QUOTA_INVOICE = "quota_invoice";//定额发票
	const AIR_TICKET = "air_ticket";//行程单
	const VEHICLE_INVOICE = "vehicle_invoice";//机动车销售发票
	const PRINT_INVOICE = "printed_invoice";//通用机打发票
//	const BANK_RECEIPT = "bank_receipt";//银行回单
//	const BANK_CHECK = "bank_check";//银行支票
//	const BANK_DRAFT = "bank_draft";//银行汇票
	const VAT_ROLL_TICKET = "vat_roll_ticket";//增值税卷票
	const ROLL_TICKET = "roll_ticket";//卷票
	const TRAVEL_ITINERARY = "travel_itinerary";//行程单

	public static $invoice_type_config = [
		self::BUS_INVOICE,
		self::TOLL_INVOICE,
		self::VAT_INVOICE_V2,
		self::VAT_INVOICE,
		self::TAXI_RECEIPT,
		self::TRAIN_TICKET,
		self::QUOTA_INVOICE,
		self::AIR_TICKET,
		self::VEHICLE_INVOICE,
		self::PRINT_INVOICE,
		self::VAT_ROLL_TICKET,
		self::ROLL_TICKET,
		self::TRAVEL_ITINERARY,
	];

	const BAIDU_OCR_API_CACHE_KEY = "baidu_ocr_cache_key";

	/**
	 * 获取access token url
	 * @var string
	 */
	protected $accessTokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';

	/**
	 * iocr图片识别
	 * @var string
	 */
	protected static $iocr = 'https://aip.baidubce.com/rest/2.0/solution/v1/iocr/recognise/finance';

	/**
	 * appId
	 * @var string
	 */
	protected $appId = '';

	/**
	 * apiKey
	 * @var string
	 */
	protected $apiKey = '';

	/**
	 * secretKey
	 * @var string
	 */
	protected $secretKey = '';

	private $tokenCachePath = '';

	private $sendUrl = "";
	private $sendParams = [];

	public function __construct()
	{
		$this->tokenCachePath = 'authKey' . DIRECTORY_SEPARATOR . md5($this->apiKey);
	}

	/**
	 * 认证
	 * @return array
	 * @throws ServerErrorHttpException
	 */
	private function getToken()
	{
		$content = $this->readAuthCache();
		if (!empty($content)) return $content['access_token'];
		$params = [
			'grant_type' => 'client_credentials',
			'client_id' => $this->apiKey,
			'client_secret' => $this->secretKey,
		];
		$response = CurlHelper::http_post($this->accessTokenUrl, $params);
		$ret = json_decode($response, TRUE);
		if (is_array($ret)) {
			$this->saveAuthCache($ret);
		} else {
			throw new ServerErrorHttpException("OCR接口获取token失败！");
		}
		return $ret['access_token'];
	}

	/**
	 * 读取本地缓存
	 * @return array
	 */
	private function readAuthCache()
	{
		/** @var Connection $redis */
		$redis = Yii::$app->redis;
		$tokenContent = $redis->get(self::BAIDU_OCR_API_CACHE_KEY);
		if ($tokenContent) {
			$obj = json_decode($tokenContent, TRUE);
			if ($obj['time'] + $obj['expires_in'] - 30 > time()) { //设置curl30秒超时空闲
				return $obj;
			}
		}
		return [];
	}

	/**
	 * 写本地缓存
	 * @param array $obj
	 */
	private function saveAuthCache($obj)
	{
		$obj['time'] = time();
		/** @var Connection $redis */
		$redis = Yii::$app->redis;
		$redis->set(self::BAIDU_OCR_API_CACHE_KEY, json_encode($obj));
	}

	/**
	 * IOCR 通用发票文件识别
	 * @param string $base64Img
	 * @return array|bool
	 * @throws ServerErrorHttpException
	 */
	public function iocrRecognize($base64Img)
	{
		$params = ['image' => $base64Img, 'classifierId' => 1];
		$url = self::$iocr . '?access_token=' . $this->getToken();
		$this->sendUrl = $url;
		$this->sendParams = $params;
		$ret = CurlHelper::http_post($url, $params, "application/x-www-form-urlencoded");
		return $ret ? json_decode($ret, TRUE) : FALSE;
	}

	public function getSendUrl()
	{
		return $this->sendUrl;
	}

	public function getSendParams()
	{
		return $this->sendParams;
	}
}