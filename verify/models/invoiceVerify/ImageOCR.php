<?php

namespace app\modules\gscheck\models\invoiceVerify;

use yii\base\Model;
use yii\web\UploadedFile;
use app\common\alioss\OssService;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use app\components\verifyProcedure\VerifyService;

class ImageOCR extends Model
{
	use CheckTenantTrait;

	const MIN_WIDTH = 15;
	const MAX_WIDTH = 4096;
	const MAX_IMAGE_FILE_SIZE = 1024 * 1024 * 4;

	const IMAGE_UPLOAD_TYPE_FILE = 0;
	const IMAGE_UPLOAD_TYPE_BASE64 = 1;
	const IMAGE_UPLOAD_TYPE_URL = 2;

	public static $uploadTypeConfig = [
		self::IMAGE_UPLOAD_TYPE_FILE => '文件上传',
		self::IMAGE_UPLOAD_TYPE_BASE64 => '图片base64信息上传',
		self::IMAGE_UPLOAD_TYPE_URL => '图片URL上传',
	];

	public $upload_type;
	public $content;
	public $tenant_code;
	private $tenant;

	private $contentArr = [];

	//同时支持图片文件上传，图片URL地址，图片base64位信息上传
	public function rules()
	{
		return [
			[['tenant_code', 'upload_type'], 'required'],
			[['upload_type', 'tenant_code'], 'trim'],
			['content', 'safe'],
			['tenant_code', 'checkTenant'],
			['upload_type', 'default', 'value' => self::IMAGE_UPLOAD_TYPE_FILE],
			['upload_type', 'in', 'range' => array_keys(self::$uploadTypeConfig)],
			[['upload_type'], 'required', 'message' => '{attribute}不能为空'],
			['upload_type', 'checkType'],
		];
	}

	public function attributeLabels()
	{
		return [
			'upload_type' => '上传类型',
		];
	}

	public function getApiCode()
	{
		return VerifyService::OCR_API_CODE;
	}

	public function checkType($attribute)
	{
		if (!$this->hasErrors()) {
			switch ($this->$attribute) {
				case self::IMAGE_UPLOAD_TYPE_FILE:
					$model = new ImageFileUpload();
					$this->content = $model->content = UploadedFile::getInstanceByName('content');
					break;
				case self::IMAGE_UPLOAD_TYPE_BASE64:
					$model = new Image64Upload();
					break;
				case self::IMAGE_UPLOAD_TYPE_URL:
					$model = new ImageUrlUpload();
					break;
				default:
					$model = new ImageFileUpload();
			}
			$params = ["content" => $this->content];
			$model->load($params, '');
			if ($model->validate()) {
				$this->contentArr = $model->upload();
			} else {
				throw new BadRequestHttpException(current($model->getFirstErrors()));
			}
		}
	}

	public function process($params)
	{
		$this->load($params, '');
		if ($this->validate()) {
			$ossService = new OssService();
			$ossResult = $ossService->uploadFile($this->contentArr['file'], 'uploads/' . $this->contentArr['file']);//保证一个文件只有一个地址
			if (!$ossResult) {
				throw new ServerErrorHttpException("图片上传失败！");
			}
//			$ossResult = 'https://inv.jss.com.cn/group5/M01/14/07/wKj6zl8OsouIKdC3AADBOVzD-EUAAWsxwO2rbAAAMFR802.pdf';//本地环境无法使用oss
			InvoiceImage::setTableSuffixByUrl($ossResult);
			$imageLog = InvoiceImage::findOne(['image_url' => $ossResult]);
			if (!$imageLog) {
				$this->contentArr['imageUrl'] = $ossResult;
				$service = new VerifyService($this->tenantInfo, $this->logStatus, $this->chargeStatus);
				return $service->imageOCR($this->contentArr);
			} else {
				return $this->getInvoiceData($imageLog); //如果有记录就直接读取记录
			}
		} else {
			throw new BadRequestHttpException(current($this->getFirstErrors()));
		}
	}

	/**
	 * @param InvoiceImage $imageLog
	 * @return array
	 */
	public function getInvoiceData($imageLog)
	{
		InvoiceImageDetail::setTableSuffix($imageLog::getPartitionIndex());
		$details = InvoiceImageDetail::find()
			->select(['invoice_type', 'ocr_content'])
			->where(['image_id' => $imageLog->id])
			->asArray()
			->all();
		$ret = [];
		foreach ($details as $detail) {
			$ret[$detail['invoice_type']] = json_decode($detail['ocr_content'], TRUE);
		}
		return $ret;
	}
}