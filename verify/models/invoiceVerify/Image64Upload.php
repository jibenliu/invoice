<?php


namespace app\modules\gscheck\models\invoiceVerify;


use yii\base\Model;
use yii\web\BadRequestHttpException;

class Image64Upload extends Model
{
	public $content;

	public function rules()
	{
		return [
			['content', 'required', 'message' => '图片base64信息不能为空'],
			['content', 'string'],
		];
	}

	public function upload()
	{
		preg_match('/^(data:\s*image\/(\w+);base64,)/', $this->content, $result); // 可以判断是否是 base64的图片
		$type = $result[2];
		$extensions = strtolower($type);
		if (!in_array($extensions, ['gif', 'jpg', 'png', 'jpeg', 'bmp'])) {
			throw new BadRequestHttpException('上传的图片不在允许内');
		}
		$size = getimagesize($this->content);
		if (empty($size)) {
			throw new BadRequestHttpException('上传的图片base64信息无法解析');
		}
		$width = $size[0];
		$height = $size[1];
		if ($width < ImageOCR::MIN_WIDTH || $height < ImageOCR::MIN_WIDTH || $width > ImageOCR::MAX_WIDTH || $height > ImageOCR::MAX_WIDTH) {
			throw new BadRequestHttpException('上传的图片尺寸不在允许内，最小边长不能超过' . ImageOCR::MIN_WIDTH . '最大边长不能超过' . ImageOCR::MAX_WIDTH);
		}
		$data = base64_decode(str_replace($result[1], '', $this->content));
		if (strlen($data) > ImageOCR::MAX_IMAGE_FILE_SIZE) {
			throw new BadRequestHttpException('上传的图片大小不能超过' . ImageOCR::MAX_IMAGE_FILE_SIZE . 'bytes');
		}
		$savePath = 'uploads/' . md5($data) . '.' . $extensions;
		file_put_contents($savePath, $data);
		return ['file' => $savePath, 'base64' => base64_encode($data)];
	}
}