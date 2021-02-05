<?php

namespace app\modules\gscheck\models\invoiceVerify;

use yii\base\Model;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;

class ImageFileUpload extends Model
{
	/**
	 * @var UploadedFile
	 */
	public $content;

	public function rules()
	{
		return [
			['content', 'image', 'minHeight' => ImageOCR::MIN_WIDTH, 'minWidth' => ImageOCR::MIN_WIDTH, 'maxWidth' => ImageOCR::MAX_WIDTH, 'maxHeight' => ImageOCR::MAX_WIDTH],
			[['content'], 'file', 'skipOnEmpty' => FALSE, 'maxSize' => ImageOCR::MAX_IMAGE_FILE_SIZE, 'message' => '上传图片过大，请剪切后再上传！'],
		];
	}

	public function upload()
	{
		$this->content = UploadedFile::getInstanceByName('content');
		if ($this->validate()) {
			$savePath = 'uploads/' . md5($this->content->baseName . '_' . time()) . '.' . $this->content->extension;
			$this->content->saveAs($savePath);
			return ['file' => $savePath, 'base64' => base64_encode(file_get_contents($savePath))];
		} else {
			throw new BadRequestHttpException(current($this->getFirstErrors()));
		}
	}
}