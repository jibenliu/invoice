<?php

namespace app\modules\gscheck\models\invoiceVerify;

use yii\redis\Connection;
use yii\web\BadRequestHttpException;
use app\components\verifyProcedure\ocrs\OcrData;

trait CheckTenantTrait
{
	public $logStatus;
	public $chargeStatus;

	public $tenantInfo = [];

	public function checkTenant($attribute)
	{
		/** @var Connection $redis */
		$redis = \Yii::$app->redis;
		$leftApis = $redis->get(OcrData::VERIFY_AUTHORIZATION_COUNT_CACHE_KEY_PREFIX . $this->$attribute);
		if ($leftApis < 0) { //小于0代表已超支，必须先充值才能用
			throw new BadRequestHttpException("接口次数已耗尽！");
		} elseif ($leftApis == NULL) {
			$tenant = TenantVerifyInvoiceSetting::findOne(['tenant_code' => $this->$attribute]);
			if (!$tenant) {
				throw new BadRequestHttpException("租户未授权！");
			}
			if ($tenant->left_charge_count < 0) {
				throw new BadRequestHttpException("接口次数已耗尽！");
			}
			$apiSetting = TenantVerifyInvoiceApiSetting::findOne(['tenant_code' => $this->$attribute, 'api_code' => $this->getApiCode()]);
			if ($apiSetting->api_status == TenantVerifyInvoiceApiSetting::STATUS_INACTIVE) {
				throw new BadRequestHttpException("该接口未授权！");
			}
			if ($apiSetting->charge_status == TenantVerifyInvoiceApiSetting::STATUS_ACTIVE) {
				$redis->set(OcrData::VERIFY_AUTHORIZATION_COUNT_CACHE_KEY_PREFIX . $this->$attribute, $tenant->left_charge_count);
			}
			$this->tenantInfo = [
				"tenantId" => $apiSetting->tenant_id,
				"tenantName" => $apiSetting->tenant_name,
				"tenantCode" => $apiSetting->tenant_code,
			];
			$this->logStatus = $apiSetting->api_status;
			$this->chargeStatus = $apiSetting->charge_status;
		} else {
			$this->tenantInfo = [
				"tenantCode" => $this->$attribute,
			];
			$this->logStatus = TenantVerifyInvoiceApiSetting::STATUS_ACTIVE;
			$this->chargeStatus = TenantVerifyInvoiceApiSetting::STATUS_ACTIVE;
		}
	}
}