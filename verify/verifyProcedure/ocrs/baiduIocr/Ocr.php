<?php

namespace app\components\verifyProcedure\ocrs\baiduIocr;

use yii\base\Model;
use yii\helpers\BaseInflector;
use yii\web\ServerErrorHttpException;
use app\components\verifyProcedure\ocrs\OcrData;
use app\components\verifyProcedure\ocrs\OcrInterface;

class Ocr extends Model implements OcrInterface
{
	/** @var OcrData */
	private $ret;

	public function getChannel()
	{
		return OcrData::BAIDU_IOCR;
	}

	public function getContentType()
	{
		return OcrData::CONTENT_TYPE_FILE_BASE64;
	}

	/**
	 * @param $params
	 * @return OcrData
	 * @throws ServerErrorHttpException
	 */
	public function recognition($params)
	{
		$this->ret = new OcrData();
		$manager = new BaiduApiManager();
		$result = $manager->iocrRecognize($params);
		$this->ret->remoteReturn = $result;
		$this->ret->requestUrl = $manager->getSendUrl();
		$this->ret->requestParams = $manager->getSendParams();
		if (!isset($result['error_code']) || $result['error_code'] != 0 || !isset($result['data']) || !isset($result['data']['ret'])) {
			throw new ServerErrorHttpException("百度ICOR接口响应异常！");
		}
		if (isset($result['data']['templateSign']) && $result['data']['templateSign'] != "mixed_receipt") { //单张发票
			$this->ret->invoiceDetailArr[$result['data']['templateSign']] = $this->mapApi2Invoice($result['data']);
		} else {
			foreach ($result['data']['ret'] as $index => $item) {
				$this->ret->invoiceDetailArr[$item['templateSign']] = $this->mapApi2Invoice($item);
			}
		}
	}

	/**
	 * 将IOCR的接口映射为本地发票格式
	 * @param array $ret
	 * @return Model
	 */
	public function mapApi2Invoice($ret)
	{
		$invoice = NULL;
		if (in_array($ret['templateSign'], BaiduApiManager::$invoice_type_config)) {
			$temp = [];
			foreach ($ret['ret'] as $item) {
				if (isset($item['word_name']) && isset($item['word'])) {
					$temp[$item['word_name']] = $item['word'];
				}
			}
			/** @var Model $invoice */
			$invoicePath =  'app\\components\\verifyProcedure\\ocrs\\baiduIocr\\invoiceType\\' . BaseInflector::camelize($ret['templateSign']);
			$invoice = (new $invoicePath());
			$invoice->load($temp, '');
		}
		return $invoice;
	}

	public function getRet()
	{
		return $this->ret;
	}
}