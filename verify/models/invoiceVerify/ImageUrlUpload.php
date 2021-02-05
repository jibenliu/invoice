<?php

namespace app\modules\gscheck\models\invoiceVerify;

use yii\base\Model;
use yii\web\BadRequestHttpException;

class ImageUrlUpload extends Model
{
	public $content;

	public function rules()
	{
		return [
			['content', 'required', 'message' => '图片地址不能为空'],
			['content', 'url'],
		];
	}

	public function upload()
	{
		$headers = ['User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36'];
		$url = str_replace(" ", "%20", $this->content);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RANGE, '0-167');// 跟踪301跳转
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$context = curl_exec($ch);
		curl_close($ch);
		$size = getimagesize($context);
		if (empty($size)) {
			throw new BadRequestHttpException('上传的图片base64信息无法解析');
		}
		if ($size > ImageOCR::MAX_IMAGE_FILE_SIZE) {
			throw new BadRequestHttpException('上传的图片大小不能超过' . ImageOCR::MAX_IMAGE_FILE_SIZE . 'bytes');
		}
		$width = $size[0];
		$height = $size[1];
		if ($width < ImageOCR::MIN_WIDTH || $height < ImageOCR::MIN_WIDTH || $width > ImageOCR::MAX_WIDTH || $height > ImageOCR::MAX_WIDTH) {
			throw new BadRequestHttpException('上传的图片尺寸不在允许内，最小边长不能超过' . ImageOCR::MIN_WIDTH . '最大边长不能超过' . ImageOCR::MAX_WIDTH);
		}
		$savePath = 'uploads/' . md5($context) . '.' . pathinfo($this->content, PATHINFO_EXTENSION);
		file_put_contents($savePath, $context);
		return ['file' => $savePath, 'base64' => base64_encode($context)];
	}
}