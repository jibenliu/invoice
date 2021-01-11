<?php

namespace common\components\invoice\yunShuiPartnerInvoice;

use Yii;
use Exception;
use common\models\TaxPoint;
use common\helpers\CurlHelper;
use common\models\InvoiceRule;
use common\helpers\ToolsHelper;
use common\models\ErpApiSeting;
use common\models\TaxSystemProp;
use common\helpers\InvoiceTools;
use api\models\invoice\InvoiceSave;
use yii\web\BadRequestHttpException;
use common\models\TaxSystemPropValue;
use yii\web\ServerErrorHttpException;
use common\models\InvoiceAttachedInfo;
use common\components\db\models\Tenants;
use common\models\config\ApplyInvoiceList;
use common\models\Invoice as InvoiceModel;
use common\components\invoice\AbstractInvoice;
use common\helpers\runtimeLog\RuntimeLogHelper;
use common\components\invoice\daXiang\Invoice as DaXiangInvoice;

class Invoice extends AbstractInvoice
{
	const HOST_URL_EXT = [
		'prod' => '',
		'test' => '-ci',
		'dev' => '-ci',
		'qa' => '-qa',
	];
	const APPLY_INVOICE_SUCCESS = 11;
	const APPLY_INVOICE_FAIL = 12;
	const INVOICING = 1;
	const INVOICED = 2;
	const NULLIFIED_INVOICE = 3;
	const INVOICE_FAILED = 4;

	public $remote_status_config = [
		self::APPLY_INVOICE_SUCCESS => '申请成功',
		self::APPLY_INVOICE_FAIL => '申请失败',
		self::INVOICING => '开票中',
		self::INVOICED => '已开票',
		self::NULLIFIED_INVOICE => '已作废',
		self::INVOICE_FAILED => '开票失败',
	];

	/**
	 * @inheritDoc
	 */
	public function validateInvoice()
	{
		$model = new InvoiceValidate();
		$attributeArr = array_merge(json_decode(json_encode($this->invoice), TRUE), $this->invoice->toArray());
		$model->load($attributeArr, '');
		if (!$model->validate()) {
			throw new BadRequestHttpException(current($model->getFirstErrors()));
		}
		return TRUE;
	}

	/**
	 * @inheritDoc
	 */
	public function applyInvoice()
	{
		try {
			$data = $this->invoiceHeadParams();
			$headParams = ToolsHelper::desEcbEncrypt(json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES));
			$url = 'https://ys-e-invoice' . self::HOST_URL_EXT[YII_ENV] . '.fdccloud.com/vat-cloud/make-invoice';
			Yii::info(['云税开票请求日志' => ['headParams' => $headParams, 'data' => $data, 'url' => $url]]);
			$res = CurlHelper::http_post($url, $headParams);
			Yii::info(['云税开票响应未解密日志' => ['res' => $res]]);
			$logData = [
				'channel' => '云税开票',
				'tag' => '发票开具',
				'request_params' => $data,
				'encrypt_request_params' => $headParams,
				'url' => $url,
				'response_data' => $res,
				'level' => RuntimeLogHelper::TRACE_LEVEL,
			];
			if (!$res) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('云税开票响应异常！');
			}
			$ret = json_decode(ToolsHelper::desEcbDecrypt($res), TRUE);
			Yii::info(['云税开票响应已解密日志' => ['ret' => $ret]]);
			$logData['decrypt_data'] = $ret;
			if (!isset($ret['ResultCode'])) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('服务异常，请稍后重试！');
			}
			RuntimeLogHelper::sendLog2Queue($logData);
			if ($ret['ResultCode'] === '0000') {
				if ($ret['Data']['ResultFlag'] == TRUE) {
					if (!isset($ret['Data']['State']) || $ret['Data']['State'] == Invoice::INVOICED) { //即时开票
						$this->invoice->status = InvoiceModel::HAS_INVOICED;
						$this->invoice->ticket_code = $ret['Data']['InfoTypeCode'];
						$this->invoice->ticket_sn = $ret['Data']['InfoNumber'];
						$this->invoice->ticket_date = date('Y-m-d');
						$this->invoice->status = InvoiceModel::HAS_INVOICED;
						$this->invoice->save(FALSE);
						if (!empty($ret['Data']['PdfUrl'])) {
							$this->invoice->attachedInfo->pdf_url = $ret['Data']['PdfUrl'];
							$this->invoice->attachedInfo->save(FALSE);
						}
						return TRUE;
					} else {
						$this->invoice->attachedInfo->serial_no = $ret['Data']['ReqSerialNo'];//保存请求流水号
						$this->invoice->attachedInfo->save(FALSE);
						return FALSE;
					}
				} else {
					throw new BadRequestHttpException($ret['Data']['ResultMessage']);
				}
			} else {
				throw new BadRequestHttpException(empty($ret['ResultMessage']) ? '服务异常，请稍后重试！' : $ret['ResultMessage']);
			}
		} catch (BadRequestHttpException $e) {
			throw new BadRequestHttpException($e->getMessage());
		} catch (Exception $e) {
			throw new BadRequestHttpException($e->getMessage());
		}
	}

	/**
	 * @inheritDoc
	 */
	public function checkInvoice()
	{
		$taxPoint = ($this->invoice instanceof InvoiceSave) ? $this->invoice->_taxPoint : $this->invoice->taxPoint;
		try {
			$taxAmount = 0;
			foreach ($this->invoice->invoiceDetails as $detail) {
				$taxAmount += $detail->amount_tax;
			}
			$checkParams = [];
			$checkParams['BillCode'] = str_replace('-', '', $this->invoice->attachedInfo->invoice_no);//将invoice_no强行去掉-变成32位字符
			/** @var ErpApiSeting $setting */
			if (!empty(Yii::$app->user)) {
				$setting = Yii::$app->user->identity->setting;
			} else {
				$applyInvoice = ApplyInvoiceList::findOne(['invoice_no' => $this->invoice->attachedInfo->invoice_no]);
				if (!$applyInvoice) {
					throw new ServerErrorHttpException('数据异常！');
				}
				$tenant = Tenants::findOne(['tenant_code' => $applyInvoice->tenant_code]);
				$setting = $tenant->setting;
			}
			$checkParams['AppId'] = $setting->app_id;
			$checkParams['Content'] = [
				'STaxCode' => $this->invoice->seller_company_tax_code,
				'InfoKind' => $this->invoice->invoice_type,
				'InfoAmount' => $this->invoice->amount_with_tax,
				'InfoTaxAmount' => $taxAmount,
				'InvoiceType' => $this->invoice->is_red == InvoiceRule::BLUE_INVOICE ? 1 : 2,
				'KpSysCode' => $taxPoint->taxSystem->tax_system_code,
				'ReqSerialNo' => $this->invoice->attachedInfo->serial_no,
			];
			$checkInvoiceStr = ToolsHelper::desEcbEncrypt(json_encode($checkParams, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES));
			$url = 'https://ys-e-invoice' . self::HOST_URL_EXT[YII_ENV] . '.fdccloud.com/vat-cloud/invoice-query';
			Yii::info(['云税查询发票请求日志' => ['headParams' => $checkInvoiceStr, 'data' => $checkParams, 'url' => $url]]);
			$res = CurlHelper::http_post($url, $checkInvoiceStr);
			Yii::info(['云税查询发票响应未解密日志' => ['res' => $res]]);
			$logData = [
				'channel' => '云税开票',
				'tag' => '发票查询',
				'request_params' => $checkParams,
				'encrypt_request_params' => $checkInvoiceStr,
				'url' => $url,
				'response_data' => $res,
				'level' => RuntimeLogHelper::TRACE_LEVEL,
			];
			if (!$res) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('云税查询发票响应异常！');
			}
			$ret = json_decode(ToolsHelper::desEcbDecrypt($res), TRUE);
			Yii::info(['云税查询发票响应已解密日志' => ['ret' => $ret]]);
			$logData['decrypt_data'] = $ret;
			if (!isset($ret['ResultCode'])) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('服务异常，请稍后重试！');
			}
			RuntimeLogHelper::sendLog2Queue($logData);
			if ($ret['ResultCode'] === '0000') {
				if (isset($ret['Data']['ResultFlag']) && $ret['Data']['ResultFlag'] == TRUE) {
					if (isset($ret['Data']['State']) && $ret['Data']['State'] == self::INVOICED) {
						DaXiangInvoice::deleteApplyInvoiceList($this->invoice);
						return $ret['Data'];
					} elseif ($ret['Data']['State'] == self::INVOICE_FAILED) {
						$this->invoice->attachedInfo->invoice_no = ToolsHelper::createGuid();
						$this->invoice->attachedInfo->serial_no = InvoiceAttachedInfo::generateSerialNo();
						$this->invoice->attachedInfo->save(FALSE);
						throw new BadRequestHttpException($ret['Data']['ResultMessage']);
					} elseif ($ret['Data']['State'] == self::NULLIFIED_INVOICE) {
						$this->invoice->red_status = InvoiceModel::ABOLITION_STATUS;
						$this->invoice->save(FALSE);
						$this->invoice->trigger($this->invoice->is_red == InvoiceRule::RED_INVOICE ? InvoiceModel::EVENT_AFTER_NULLIFY_RED_INVOICE : InvoiceModel::EVENT_AFTER_NULLIFY_BLUE_INVOICE);
					}
				}
			}
			return FALSE;
		} catch (BadRequestHttpException $e) {
			throw new BadRequestHttpException($e->getMessage());
		} catch (Exception $e) {
			Yii::error(['云税查询发票状态接口异常' => [
				'message' => $e->getMessage(),
				'line' => $e->getLine(),
				'file' => $e->getLine(),
			]]);
			throw new ServerErrorHttpException($e->getMessage());
		}
	}

	/**
	 * 发票作废接口
	 * @inheritDoc
	 */
	public function nullifyInvoice()
	{
		try {
			$data = $this->invoiceNullifyParams();
			$headParams = ToolsHelper::desEcbEncrypt(json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES));
			$url = 'https://ys-e-invoice' . self::HOST_URL_EXT[YII_ENV] . '.fdccloud.com/vat-cloud/invoice-cancel';
			Yii::info(['云税作废请求日志' => ['headParams' => $headParams, 'data' => $data, 'url' => $url]]);
			$res = CurlHelper::http_post($url, $headParams);
			Yii::info(['云税作废响应未解密日志' => ['res' => $res]]);
			$logData = [
				'channel' => '云税开票',
				'tag' => '发票作废',
				'request_params' => $data,
				'encrypt_request_params' => $headParams,
				'url' => $url,
				'response_data' => $res,
				'level' => RuntimeLogHelper::TRACE_LEVEL,
			];
			if (!is_string($res)) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('云税作废响应异常，请稍后重试！');
			}
			$ret = json_decode(ToolsHelper::desEcbDecrypt($res), TRUE);
			$logData['decrypt_data'] = $ret;
			Yii::info(['云税作废响应已解密日志' => ['ret' => $ret]]);
			if (!isset($ret['ResultCode'])) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('服务异常，请稍后重试！');
			}
			RuntimeLogHelper::sendLog2Queue($logData);
			if ($ret['ResultCode'] === '0000') {
				if (isset($ret['Data']['ResultFlag']) && $ret['Data']['ResultFlag'] == TRUE) {
					return TRUE;
				} else {
					throw new BadRequestHttpException(!empty($ret['Data']['ResultMessage']) ? ('发票作废失败！失败原因：' . $ret['Data']['ResultMessage']) : '发票作废失败！');
				}
			} else {
				throw new BadRequestHttpException($ret['ResultMessage']);
			}
		} catch (BadRequestHttpException $e) {
			throw new BadRequestHttpException($e->getMessage());
		} catch (Exception $e) {
			Yii::error(['云税作废接口异常' => [
				'message' => $e->getMessage(),
				'line' => $e->getLine(),
				'file' => $e->getFile(),
			]]);
			throw new ServerErrorHttpException($e->getMessage());
		}
	}

	public function printInvoice()
	{
		return TRUE;
	}

	/**
	 * 获取开票机号
	 * @param $taxPoint
	 * @return mixed|string
	 */
	private function getKpjh($taxPoint)
	{
		$propValues = TaxSystemPropValue::find()
			->select([TaxSystemProp::tableName() . '.id', TaxSystemProp::tableName() . '.prop_code', TaxSystemPropValue::tableName() . '.prop_value'])
			->leftJoin(TaxSystemProp::tableName(), TaxSystemProp::tableName() . '.id=' . TaxSystemPropValue::tableName() . '.tax_system_prop_id')
			->where(['tax_point_id' => $taxPoint->id])
			->andWhere(['tax_system_id' => $taxPoint->tax_system_id])
			->asArray()
			->all();
		$Kpjh = '';
		foreach ($propValues as $prop) {
			if ($prop['prop_code'] == 'Kpjh') $Kpjh = $prop['prop_value'];
		}
		return $Kpjh;
	}

	/**
	 * 拼装请求体
	 * @return array
	 */
	public function invoiceHeadParams()
	{
		$taxPoint = ($this->invoice instanceof InvoiceSave) ? $this->invoice->_taxPoint : $this->invoice->taxPoint;
		$Kpjh = $this->getKpjh($taxPoint);
		$params['Content'] = $this->getHeadParams($taxPoint, $Kpjh);
		/** @var ErpApiSeting $setting */
		$setting = Yii::$app->user->identity->setting;
		$params['AppId'] = $setting->app_id;
		$params['BillCode'] = str_replace('-', '', $this->invoice->attachedInfo->invoice_no);
		return $params;
	}

	public function invoiceNullifyParams()
	{
		$taxPoint = ($this->invoice instanceof InvoiceSave) ? $this->invoice->_taxPoint : $this->invoice->taxPoint;
		$propValues = TaxSystemPropValue::find()
			->select([TaxSystemProp::tableName() . '.id', TaxSystemProp::tableName() . '.prop_code', TaxSystemPropValue::tableName() . '.prop_value'])
			->leftJoin(TaxSystemProp::tableName(), TaxSystemProp::tableName() . '.id=' . TaxSystemPropValue::tableName() . '.tax_system_prop_id')
			->where(['tax_point_id' => $taxPoint->id])
			->andWhere(['tax_system_id' => $taxPoint->tax_system_id])
			->asArray()
			->all();
		$Kpjh = $Keypwd = '';
		foreach ($propValues as $prop) {
			if ($prop['Kpjh']) $Kpjh = $prop['prop_value'];
			if ($prop['Keypwd']) $Keypwd = $prop['prop_value'];
		}
		$params['BillCode'] = str_replace('-', '', $this->invoice->attachedInfo->invoice_no);
		/** @var ErpApiSeting $setting */
		$setting = Yii::$app->user->identity->setting;
		$params['AppId'] = $setting->app_id;
		$params['Content'] = [
			'Keypwd' => $Keypwd,
			'Kpjh' => $Kpjh,
			'KpSysCode' => $taxPoint->taxSystem->tax_system_code,
			'InfoKind' => $this->invoice->invoice_type,
			'BillCode' => str_replace('-', '', $this->invoice->attachedInfo->invoice_no),
			'ReqSerialNo' => $this->invoice->attachedInfo->serial_no,
			'Num' => $this->invoice->ticket_sn,
			'TypeCode' => $this->invoice->ticket_code,
			'STaxCode' => $this->invoice->seller_company_tax_code,
			'Zflx' => 1,
			'Zfr' => $this->invoice->attachedInfo->abolitionist,
			'Zfyy' => $this->invoice->attachedInfo->abolition_remark,
			'TotalAmount' => $this->invoice->amount_with_tax,
		];
		return $params;
	}

	/**
	 * @param $number
	 * @param int $floatPrecise
	 * @param int $intPrecise
	 * @return false|float|string
	 */
	private function getFloat2Precise($number, $floatPrecise = 8, $intPrecise = 2)
	{
		$round = round($number, $floatPrecise);
		return (int)$round == $round ? number_format($round, $intPrecise, ".", "") : $round;
	}

	/**
	 * 批量开票逻辑
	 * @param $invoices
	 * @param $taxPointId
	 */
	public function batchApply($invoices, $taxPointId)
	{
		try {
			$data = $postData = $no2InvoiceMap = [];
			$taxPoint = TaxPoint::findOne($taxPointId);
			$Kpjh = $this->getKpjh($taxPoint);
			foreach ($invoices as $invoice) {
				/** @var InvoiceModel invoice */
				$this->invoice = $invoice;
				$postData[] = $this->getHeadParams($taxPoint, $Kpjh);
				$no2InvoiceMap[str_replace('-', '', $invoice->attachedInfo->invoice_no)] = $invoice;
				$this->invoice = NULL;
			}
			$setting = InvoiceTools::getTenantSetting();
			$data['AppId'] = $setting->app_id;
			$data['Content'] = $postData;
			$headParams = ToolsHelper::desEcbEncrypt(json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES));
			$url = 'https://ys-e-invoice' . self::HOST_URL_EXT[YII_ENV] . '.fdccloud.com/vat-cloud/kp-bill-list';
			Yii::info(['云税批量开票请求日志' => ['headParams' => $headParams, 'data' => $data, 'url' => $url]]);
			$res = CurlHelper::http_post($url, $headParams);
			Yii::info(['云税批量开票响应未解密日志' => ['res' => $res]]);
			$logData = [
				'channel' => '云税开票',
				'tag' => '批量开蓝票',
				'request_params' => $data,
				'encrypt_request_params' => $headParams,
				'url' => $url,
				'response_data' => $res,
				'level' => RuntimeLogHelper::TRACE_LEVEL,
			];
			if (!$res) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('云税开票响应异常！');
			}
			$ret = json_decode(ToolsHelper::desEcbDecrypt($res), TRUE);
			$logData['decrypt_data'] = $ret;
			Yii::info(['云税批量开票响应已解密日志' => ['ret' => $ret]]);
			if (!isset($ret['ResultCode'])) { //未传输，锁死单据和发票
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('服务异常，请稍后重试！');
			}
			RuntimeLogHelper::sendLog2Queue($logData);
			if (isset($ret['ResultFlag']) && $ret['ResultFlag'] == TRUE) { //已传输
				foreach ($ret['Data'] as $item) {
					/** @var InvoiceModel $invoice */
					$invoice = $no2InvoiceMap[$item['BillCode']];
					if ($item['Msg'] == '') {
						$invoice->status = InvoiceModel::INVOICING;
						$invoice->save(FALSE);
						DaXiangInvoice::saveApplyInvoiceList($invoice);
						$invoice->trigger(InvoiceModel::EVENT_AFTER_INVOICE_WAITING);
					} else {
						$invoice->status = InvoiceModel::INVOICE_FAILED;
						$invoice->save(FALSE);
						$invoice->attachedInfo->backfill_remark = $item['Msg'];
						$invoice->attachedInfo->save(FALSE);
						$invoice->trigger(InvoiceModel::EVENT_AFTER_INVOICE_FAILED);
					}
				}
			} else {
				throw new ServerErrorHttpException("批量开票接口异常！");
			}
		} catch (Exception $e) {
			Yii::info(['批量开票提交失败' => [
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'msg' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]]);
		}
	}

	public function batchRedApply($invoices, $taxPointId)
	{
		try {
			$data = $postData = $no2InvoiceMap = [];
			$taxPoint = TaxPoint::findOne($taxPointId);
			$Kpjh = $this->getKpjh($taxPoint);
			foreach ($invoices as $invoice) {
				/** @var InvoiceModel invoice */
				$this->invoice = $invoice;
				$postData[] = $this->getHeadParams($taxPoint, $Kpjh);
				$no2InvoiceMap[str_replace('-', '', $invoice->attachedInfo->invoice_no)] = $invoice;
				$this->invoice = NULL;
			}
			$setting = InvoiceTools::getTenantSetting();
			$data['AppId'] = $setting->app_id;
			$data['Content'] = $postData;
			$headParams = ToolsHelper::desEcbEncrypt(json_encode($data, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES));
			$url = 'https://ys-e-invoice' . self::HOST_URL_EXT[YII_ENV] . '.fdccloud.com/vat-cloud/kp-bill-list';
			Yii::info(['云税批量开红票请求日志' => ['headParams' => $headParams, 'data' => $data, 'url' => $url]]);
			$res = CurlHelper::http_post($url, $headParams);
			Yii::info(['云税批量开红票响应未解密日志' => ['res' => $res]]);
			$logData = [
				'channel' => '云税开票',
				'tag' => '批量开蓝票',
				'request_params' => $data,
				'encrypt_request_params' => $headParams,
				'url' => $url,
				'response_data' => $res,
				'level' => RuntimeLogHelper::TRACE_LEVEL,
			];
			if (!$res) {
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('云税开票响应异常！');
			}
			$ret = json_decode(ToolsHelper::desEcbDecrypt($res), TRUE);
			Yii::info(['云税批量开红票响应已解密日志' => ['ret' => $ret]]);
			$logData['decrypt_data'] = $ret;
			if (!isset($ret['ResultCode'])) { //未传输，锁死单据和发票
				$logData['level'] = RuntimeLogHelper::ERROR_LEVEL;
				RuntimeLogHelper::sendLog2Queue($logData);
				throw new ServerErrorHttpException('服务异常，请稍后重试！');
			}
			RuntimeLogHelper::sendLog2Queue($logData);
			if (isset($ret['ResultFlag']) && $ret['ResultFlag'] == TRUE) { //已传输
				foreach ($ret['Data'] as $item) {
					/** @var InvoiceModel $invoice */
					$invoice = $no2InvoiceMap[$item['BillCode']];
					if ($item['Msg'] == '') {
						$invoice->status = InvoiceModel::INVOICING;
						$invoice->save(FALSE);
						DaXiangInvoice::saveApplyInvoiceList($invoice);
						$invoice->trigger(InvoiceModel::EVENT_AFTER_INVOICE_WAITING);
					} else {
						$invoice->status = InvoiceModel::INVOICE_FAILED;
						$invoice->save(FALSE);
						$invoice->attachedInfo->backfill_remark = $item['Msg'];
						$invoice->attachedInfo->save(FALSE);
						$invoice->trigger(InvoiceModel::EVENT_AFTER_INVOICE_FAILED);
					}
				}
			} else {
				throw new ServerErrorHttpException("批量开票接口异常！");
			}
		} catch (Exception $e) {
			Yii::info(['批量开红票提交失败' => [
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'msg' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
			]]);
		}
	}

	/**
	 * @param TaxPoint $taxPoint
	 * @param string $Kpjh
	 * @return array
	 */
	private function getHeadParams(TaxPoint $taxPoint, string $Kpjh)
	{
		$details = [];
		$totalTax = $totalAmount = 0;
		foreach ($this->invoice->invoiceDetails as $detail) {
			$totalAmount += $detail->amount;
			$totalTax += $detail->amount_tax;
			$tmp = [];
			$tmp['GoodsName'] = $detail->name;
			$tmp['Standard'] = $detail->specification;
			$tmp['Unit'] = $detail->unit;
			$tmp['PriceKind'] = $detail->tax_rate == 0 ? 0 : 1;
			$tmp['Price'] = !empty($detail->price) ? (string)$this->getFloat2Precise($detail->price) : '';
			$tmp['Number'] = !empty($detail->quantity) ? (string)$this->getFloat2Precise($detail->quantity) : '';
			$tmp['TaxItem'] = $detail->goods_tax_no;
			$tmp['TaxRate'] = (string)round($detail->tax_rate / 100, 4);
			$tmp['Amount'] = (string)number_format(round($detail->amount, 2), 2, ".", "");
			$tmp['TaxAmount'] = (string)number_format(round($detail->amount_tax, 2), 2, ".", "");
			$tmp['DiscountFlag'] = 0;
			$tmp['DiscountRate'] = NULL;
			$tmp['GoodsNoVer'] = NULL;
			list($taxPre, $zeroTax, $taxPreCon) = InvoiceTools::getContentPre($detail->goods_tax_no, $detail->tax_rate);
			$tmp['TaxPre'] = $taxPre;
			$tmp['TaxPreCon'] = $taxPreCon;
			$tmp['ZeroTax'] = $zeroTax;
			$tmp['GoodsTaxNo'] = $detail->goods_tax_no;
			$tmp['GoodsTaxShortName'] = $detail->goods_tax_name;
			$tmp['CropGoodsNo'] = NULL;
			$tmp['TaxDeduction'] = NULL;
			$details[] = $tmp;
		}

		return [
			'BillCode' => str_replace('-', '', $this->invoice->attachedInfo->invoice_no),
			'Kpjh' => $Kpjh,
			'InvoiceTime' => date('Y-m-d H:i:s'),
			'InfoKind' => $this->invoice->invoice_type,
			'CName' => $this->invoice->buyer_company_name,
			'CAddress' => $this->invoice->buyer_company_address,
			'CPhone' => $this->invoice->buyer_company_phone ?? $this->invoice->attachedInfo->receiver_phone,
			'CBank' => $this->invoice->buyer_company_bank,
			'CBankCode' => $this->invoice->buyer_company_bank_code,
			'CTaxCode' => $this->invoice->buyer_company_tax_code,
			'CMobile' => $this->invoice->buyer_company_phone,
			'CCompanyType' => $this->invoice->voucher->cst_type,
			'TaxRate' => $totalAmount != 0 ? bcdiv($totalTax, $totalAmount, 4) : 0,
			'Cashier' => $this->invoice->payee,
			'Checker' => $this->invoice->checker,
			'Invoicer' => $this->invoice->drawer,
			'SName' => $this->invoice->seller_company_name,
			'STaxCode' => $this->invoice->seller_company_tax_code,
			'SAddress' => $this->invoice->seller_company_address,
			'SPhone' => $this->invoice->seller_company_phone,
			'SBank' => $this->invoice->seller_company_bank,
			'SBankCode' => $this->invoice->seller_company_bank_code,
			'Dkbz' => 0,
			'Notes' => $this->invoice->remark,
			'GoodsListFlag' => 0,
			'TotalAmount' => (string)number_format(round($totalAmount, 2), 2, ".", ""),
			'TotalTaxAmount' => (string)number_format(round($totalTax, 2), 2, ".", ""),
			'CorrespondingNumber' => ($this->invoice->is_red == InvoiceRule::RED_INVOICE) ? $this->invoice->blueInvoice->ticket_sn : '',
			'CorrespondingTypeCode' => ($this->invoice->is_red == InvoiceRule::RED_INVOICE) ? $this->invoice->blueInvoice->ticket_code : '',
			'DiscountFlag' => 0,
			'NegativeFlag' => 0,
			'NegNoticeNo' => ($this->invoice->is_red == InvoiceRule::RED_INVOICE) ? $this->invoice->redNotice->notice_sn : '',
			'InfoNumber' => '',
			'InfoTypeCode' => '',
			'InvoiceType' => ($this->invoice->is_red == InvoiceRule::RED_INVOICE) ? 2 : 1,
			'Tschbz' => 0,
			'Czdm' => '10',
			'TypeCode' => '',
			'Num' => '',
			'TakerName' => $this->invoice->attachedInfo->receiver,
			'TakerPhone' => $this->invoice->attachedInfo->receiver_phone,
			'TakerEmail' => $this->invoice->attachedInfo->receiver_email,
			'PushWay' => 2,//发送发式(-1,不推送;0,邮箱;1,手机(默认);2,邮箱、手机)
			'KpSysCode' => $taxPoint->taxSystem->tax_system_code,
			'Details' => $details,
		];
	}
}