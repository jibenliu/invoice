<?php

namespace app\components\verifyProcedure\verifys;

use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

interface VerifyInterface
{
	/**
	 * 反向定位当前接口类名
	 * @return integer
	 */
	public function getChannel();

	/**
	 * 执行查验操作
	 * @param $params
	 * @return InvoiceData
	 * @throws ServerErrorHttpException
	 * @throws BadRequestHttpException
	 */
	public function check($params);

	/**
	 * 返回查验结构体
	 * @return InvoiceData
	 */
	public function getRet();
}