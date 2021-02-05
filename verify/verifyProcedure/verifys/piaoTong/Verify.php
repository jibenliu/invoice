<?php

namespace app\components\verifyProcedure\verifys\piaoTong;

use app\common\helpers\CurlHelper;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use app\common\helpers\InvoiceHelpers;
use app\modules\gscheck\models\invoiceVerify\Invoice;
use app\components\verifyProcedure\verifys\InvoiceData;
use app\components\verifyProcedure\verifys\VerifyInterface;
use app\modules\gscheck\models\invoiceVerify\InvoiceDetail;

class Verify implements VerifyInterface
{
	/** @var InvoiceData */
	private $ret;

	public function getChannel()
	{
		return InvoiceData::PIAOTONG_CHANNEL;
	}

	/**
	 * @param $params
	 * @return InvoiceData
	 * @throws BadRequestHttpException
	 * @throws ServerErrorHttpException
	 */
	public function check($params)
	{
		Invoice::setTableSuffixByDate($params['invoice_date']);
		$this->ret = new InvoiceData();
		$rule = ['01',
			InvoiceHelpers::getInvoiceTypeByInvoiceNo($params['invoice_code']),
			$params['invoice_code'],
			$params['invoice_no'],
			$params['amount_without_tax'],
			$params['invoice_date'],
			$params['check_code'],
			'DZYX',
		];
		$ruleString = implode(",", $rule);
		$outerXml['interfaceCode'] = 'IMMEDIATELY_TOWCODE_RESPONE';
		$manager = new ApiManager();
		$outerXml['content'] = $manager->innerQrXml(['QrCode' => $ruleString]);
		$this->ret->requestUrl = ApiManager::$params['URL'];
		$this->ret->requestParams = $rule;
		$xml = (new ApiManager())->outerQrXml($outerXml);
		$this->ret->encryptParams = ["parameter" => $xml];
		$result = CurlHelper::http_post(ApiManager::$params['URL'], ["parameter" => $xml]);
		$this->ret->remoteReturn = $result;
		$ret = $this->checkVerifyResponse($result);
		$this->ret->decryptReturn = $ret;
		$this->ret->invoice_type = InvoiceHelpers::getInvoiceTypeByInvoiceNo($params['invoice_code']);
		$this->ret->invoice_code = isset($ret['FPDM']) ? $ret['FPDM'] : '';
		$this->ret->invoice_no = isset($ret['FPHM']) ? $ret['FPHM'] : '';
		$this->ret->verify_times = isset($ret['CYCS']) ? $ret['CYCS'] : '';
		$this->ret->invoice_date = isset($ret['KPRQ']) ? $ret['KPRQ'] : '';
		$this->ret->seller_name = isset($ret['XFMC']) ? $ret['XFMC'] : '';
		$this->ret->seller_taxno = isset($ret['XFSBH']) ? $ret['XFSBH'] : '';
		$this->ret->seller_address = isset($ret['XFDZDH']) ? $ret['XFDZDH'] : '';
		$this->ret->seller_bank_name = isset($ret['XFYHZH']) ? $ret['XFYHZH'] : '';
		$this->ret->buyer_name = isset($ret['GFMC']) ? $ret['GFMC'] : '';
		$this->ret->buyer_taxno = isset($ret['GFSBH']) ? $ret['GFSBH'] : '';
		$this->ret->buyer_address = isset($ret['GFDZDH']) ? $ret['GFDZDH'] : '';
		$this->ret->buyer_bank_name = isset($ret['GFYHZH']) ? $ret['GFYHZH'] : '';
		$this->ret->check_code = isset($ret['JYM']) ? $ret['JYM'] : '';
		$this->ret->tax_amount = isset($ret['SE']) ? $ret['SE'] : '';
		$this->ret->amount_with_tax = isset($ret['JSHJ']) ? $ret['JSHJ'] : '';
		$this->ret->machine_code = isset($ret['JQBH']) ? $ret['JQBH'] : '';
		$this->ret->amount_without_tax = isset($ret['JE']) ? $ret['JE'] : '';
		$this->ret->is_valid = isset($ret['ZFBZ']) ? $ret['ZFBZ'] : '';
		$this->ret->remark = isset($ret['BZ']) ? $ret['BZ'] : '';
		$this->ret->payee = isset($ret['SKR']) ? $ret['SKR'] : '';
		if (isset($ret['DETAILLIST']['DETAIL'])) {
			foreach ($ret['DETAILLIST'] as $index => $detail) {
				$detail = new InvoiceDetail();
				$detail->item_name = isset($detail['HWMC']) ? $detail['HWMC'] : '';
				$detail->specification = isset($detail['GGXH']) ? $detail['GGXH'] : '';
				$detail->quantity_unit = isset($detail['DW']) ? $detail['DW'] : '';
				$detail->quantity = isset($detail['SL']) ? $detail['SL'] : '';
				$detail->price = isset($detail['DJ']) ? $detail['DJ'] : '';
				$detail->tax_rate = isset($detail['SLV']) ? $detail['SLV'] : '';
				$detail->tax_amount = isset($detail['SE']) ? $detail['SE'] : '';
				$this->ret->details[$index] = $detail;
			}
		}
	}

	/**
	 * @param $result
	 * @return mixed
	 * @throws BadRequestHttpException
	 * @throws ServerErrorHttpException
	 */
	public function checkVerifyResponse($result)
	{
		if (empty($result)) {
			throw new ServerErrorHttpException("查验接口响应异常！");
		}
		$xmlArray = ApiManager::decodeResult($result);
		if (isset($xmlArray['returnStateInfo']['returnCode']) || isset($xmlArray['returnStateInfo']['returnMessage'])) {
			throw new ServerErrorHttpException("查验接口响应异常！");
		}
		$returnCode = $xmlArray['returnStateInfo']['returnCode'];
		$returnMessage = $xmlArray['returnStateInfo']['returnMessage'];
		if ($returnCode != '0000') {
			if (in_array($returnCode, ['9999', '9006', '9009'])) {
				throw new ServerErrorHttpException('该区域国税服务异常或供应商接口异常,请稍后再试!');
			} elseif (in_array($returnCode, ['9104'])) {
				throw new BadRequestHttpException('纳税人识别号不存在!');
			} elseif (in_array($returnCode, ['1004'])) {
				throw new BadRequestHttpException('所查发票不存在!');
			} elseif (in_array($returnCode, ['1010'])) {
				throw new BadRequestHttpException('超过该张票当天查验次数!');
			} else {
				throw new BadRequestHttpException(base64_decode($returnMessage));
			}
		}
		if (isset($xmlArray['data']['dataDescription']) && !empty($xmlArray['data']['dataDescription']['zipCode'])) {
			$strContent = mb_convert_encoding($xmlArray['data']['content'], 'ISO-8859-1', 'utf-8');
			$strContent = base64_decode($strContent);
			$contentXml = gzdecode($strContent);
		} else {
			$contentXml = base64_decode($xmlArray['data']['content']);
		}
		return ApiManager::decodeResult($contentXml);
	}

	public function getRet()
	{
		return $this->ret;
	}
}