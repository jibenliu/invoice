<?php

namespace app\components\verifyProcedure;

use Yii;
use yii\redis\Connection;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use app\components\verifyProcedure\ocrs\OcrData;
use app\modules\gscheck\models\butchCheck\Tenants;
use app\common\helpers\runtimeLog\RuntimeLogHelper;
use app\components\verifyProcedure\ocrs\OcrInterface;
use app\modules\gscheck\models\invoiceVerify\Invoice;
use app\components\verifyProcedure\verifys\InvoiceData;
use app\modules\gscheck\models\invoiceVerify\InvoiceImage;
use app\components\verifyProcedure\verifys\VerifyInterface;
use app\modules\gscheck\models\invoiceVerify\InvoiceDetail;
use app\modules\gscheck\models\invoiceVerify\TenantVerifyLog;
use app\modules\gscheck\models\invoiceVerify\InvoiceImageDetail;
use app\modules\gscheck\models\invoiceVerify\TenantVerifyInvoiceSetting;
use app\modules\gscheck\models\invoiceVerify\TenantVerifyInvoiceApiSetting;

class VerifyService
{
	//不要随意改动，invoice项目的报表用到这个配置了
	const OCR_API_NAME = "OCR识别";
	const INVOICE_VERIFY_API_NAME = "查验接口";
	const OCR_API_CODE = "ocr_api";
	const INVOICE_VERIFY_API_CODE = "invoice_verify_api";
	public static $api_config = [
		self::OCR_API_CODE => self::OCR_API_NAME,
		self::INVOICE_VERIFY_API_CODE => self::INVOICE_VERIFY_API_NAME,
	];

	public $tenantInfo = [];
	public $logStatus;
	public $chargeStatus;

	public function __construct($tenantInfo, $logStatus, $chargeStatus)
	{
		$this->tenantInfo = $tenantInfo;
		$this->logStatus = $logStatus;
		$this->chargeStatus = $chargeStatus;
	}

	/**
	 * @param $params
	 * @return array
	 * @throws ServerErrorHttpException
	 */
	public function imageOCR($params)
	{
		$items = Yii::$app->params['imageOcrQueue'];
		$invoice = NULL;
		foreach ($items as $item => $config) {
			$runtimeLog = [];
			$item = lcfirst($item);
			$ocrPath = "app\\components\\verifyProcedure\\ocrs\\{$item}\Ocr";
			/** @var OcrInterface $object */
			$object = (new $ocrPath());
			try {
				$object->recognition($params[$object->getContentType()]); //一个OCR多个发票对象
				$runtimeLog['level'] = RuntimeLogHelper::TRACE_LEVEL;
				$invoice = $object->getRet();
				break;
			} catch (ServerErrorHttpException $exception) {
				$runtimeLog['level'] = RuntimeLogHelper::ERROR_LEVEL;
			} finally {
				$ret = $object->getRet();
				//删除本地文件
				unlink($params['file']);
				$invoiceCount = count($object->getRet()->invoiceDetailArr);
				if ($this->logStatus == TenantVerifyInvoiceApiSetting::STATUS_ACTIVE) {
					$this->saveVerifyLog(self::OCR_API_CODE, $invoiceCount);
				}
				//扣减次数
				if ($this->chargeStatus == TenantVerifyInvoiceApiSetting::STATUS_ACTIVE) {
					$this->reduceApiChargeCount($invoiceCount);
				}
				//运行日志入库
				$runtimeLog['channel'] = OcrData::$ocrChannelConfig[$object->getChannel()];
				$runtimeLog['request_params'] = $ret->requestParams;
				$runtimeLog['response_data'] = $ret->remoteReturn;
				$this->saveRuntimeLog($runtimeLog);
			}
		}
		if ($invoice == NULL) {
			throw new ServerErrorHttpException("OCR服务不可用!");
		}
		InvoiceImage::setTableSuffixByUrl($params['imageUrl']);
		$image = new InvoiceImage();
		$image->tenant_code = $this->tenantInfo['tenantCode'];
		$image->image_url = $params['imageUrl'];
		$image->ocr_status = InvoiceImage::OCR_SUCCESS;
		$image->save(FALSE);
		$ret = [];
		foreach ($invoice->invoiceDetailArr as $index => $detail) {
			InvoiceImageDetail::setTableSuffix($image::getPartitionIndex());
			$imageDetail = new InvoiceImageDetail();
			$imageDetail->image_id = $image->id;
			$imageDetail->ocr_content = json_encode($detail->toArray(), TRUE);
			$imageDetail->invoice_type = $index;
			$imageDetail->save(FALSE);
			$ret[$index] = $detail->toArray();
		}
		return ['list' => $ret];
	}

	/**
	 * 发票查验
	 * @param $params
	 * @return array
	 * @throws ServerErrorHttpException|BadRequestHttpException
	 */
	public function verify($params)
	{
		$items = Yii::$app->params['verifyQueue'];
		$invoice = $exception = NULL;
		foreach ($items as $item => $config) {
			$runtimeLog = [];
			$item = lcfirst($item);
			$verifyPath = "app\\components\\verifyProcedure\\verifys\\{$item}\\Verify";
			/** @var VerifyInterface $object */
			$object = (new $verifyPath());
			try {
				$object->check($params);
				$runtimeLog['level'] = RuntimeLogHelper::TRACE_LEVEL;
				$invoice = new Invoice();
				foreach ($object->getRet() as $key => $value) {
					if (isset($invoice->$key)) {
						$invoice->$key = $this->$key;
					}
				}
				$invoice->tenant_code = $this->tenantInfo['tenantCode'];
				$invoice->save(FALSE);
				InvoiceDetail::setTableSuffixByDate($invoice::getPartitionIndex());
				foreach ($object->getRet()->details as $key => $value) {
					$detail = new InvoiceDetail();
					foreach ($value as $k => $v) {
						if (isset($detail->$k)) {
							$detail->$k = $invoice->$v;
						}
					}
					$detail->id = $invoice->id;
					$detail->save(FALSE);
				}
				break;
			} catch (BadRequestHttpException $e) {
				$runtimeLog['level'] = RuntimeLogHelper::WARNING_LEVEL;
				$exception = $e;
				break;
			} catch (ServerErrorHttpException $e) {
				$runtimeLog['level'] = RuntimeLogHelper::ERROR_LEVEL;
			} finally {
				$ret = $object->getRet();
				//OCR日志异步入库
				if ($this->logStatus == TenantVerifyInvoiceApiSetting::STATUS_ACTIVE) {
					$this->saveVerifyLog(self::INVOICE_VERIFY_API_CODE, 1);
				}
				//扣减次数
				if ($this->chargeStatus == TenantVerifyInvoiceApiSetting::STATUS_ACTIVE) {
					$this->reduceApiChargeCount(1);
				}
				//运行日志入库
				$runtimeLog['channel'] = InvoiceData::$verifyChannelConfig[$object->getChannel()];
				$runtimeLog['request_params'] = $ret->requestParams;
				$runtimeLog['encrypt_request_params'] = $ret->encryptParams;
				$runtimeLog['response_data'] = $ret->remoteReturn;
				$runtimeLog['decrypt_data'] = $ret->decryptReturn;
				$this->saveRuntimeLog($runtimeLog);
			}
		}
		if ($invoice == NULL) throw new ServerErrorHttpException("查验服务不可用!");
		if ($exception) throw new BadRequestHttpException($exception->getMessage());
		return [$invoice, "details" => $invoice->details];
	}

	/**
	 * 存储查验相关的消耗日志
	 * @param $apiCode
	 * @param $count
	 */
	public function saveVerifyLog($apiCode, $count)
	{
		$ocrLog = new TenantVerifyLog();
		$ocrLog->tenant_code = $this->tenantInfo['tenantCode'];
		if (empty($this->tenantInfo['tenantId']) || empty($this->tenantInfo['tenantName'])) {
			$tenant = Tenants::findOne(['tenant_code' => $this->tenantInfo['tenantCode']]);
			$tenantId = $tenant->tenant_id;
		} else {
			$tenantId = $this->tenantInfo['tenantId'];
		}
		$ocrLog->tenant_id = $tenantId;
		$ocrLog->api_code = $apiCode;
		$ocrLog->api_count = $count;
		$ocrLog->save(FALSE);
	}

	/**
	 * 扣减接口次数
	 * @param $count
	 */
	public function reduceApiChargeCount($count)
	{
		/** @var Connection $redis */
		$redis = Yii::$app->redis;
		$redis->decrby(OcrData::VERIFY_AUTHORIZATION_COUNT_CACHE_KEY_PREFIX . $this->tenantInfo['tenantCode'], 1);//接口数量减少
		TenantVerifyInvoiceSetting::updateAllCounters(['left_charge_count' => (-1 * $count)], ['tenant_code' => $this->tenantInfo['tenantCode']]);
	}

	/**
	 * 保存运行日志
	 * @param $data
	 */
	public function saveRuntimeLog($data)
	{
		$data['tenant_code'] = $this->tenantInfo['tenantCode'];
		RuntimeLogHelper::sendLog2Queue($data, RuntimeLogHelper::VERIFY_RUNTIME_LOG_QUEUE_KEY);
	}
}