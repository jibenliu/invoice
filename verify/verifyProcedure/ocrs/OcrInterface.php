<?php

namespace app\components\verifyProcedure\ocrs;

use yii\web\ServerErrorHttpException;

interface OcrInterface
{
	/**
	 * OCR识别
	 * @param $params
	 * @return mixed
	 * @throws ServerErrorHttpException
	 */
	public function recognition($params);

	/**
	 * 反向定位渠道
	 * @return mixed
	 */
	public function getChannel();

	/**
	 * 获取当前OCR接口支持的类型
	 * @return mixed
	 */
	public function getContentType();

	/**
	 * 返回OCR结构体
	 * @return OcrData
	 */
	public function getRet();
}