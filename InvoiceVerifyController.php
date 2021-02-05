<?php

namespace app\modules\gscheck\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\ContentNegotiator;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use app\common\helpers\ResponseHelper;
use app\modules\gscheck\models\invoiceVerify\ImageOCR;
use app\components\verifyProcedure\verifys\InvoiceData;
use app\modules\gscheck\models\invoiceVerify\InvoiceVerify;

class InvoiceVerifyController extends Controller
{
	/**
	 * @var string|array the configuration for creating the serializer that formats the response data.
	 */
	public $serializer = 'yii\rest\Serializer';
	/**
	 * @inheritdoc
	 */
	public $enableCsrfValidation = FALSE;

	public function init()
	{
		parent::init();
		Yii::$app->getResponse()->on(Response::EVENT_BEFORE_SEND, [ResponseHelper::className(), 'beforeSend']);
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'contentNegotiator' => [
				'class' => ContentNegotiator::className(),
				'formats' => [
					'application/json' => Response::FORMAT_JSON,
				],
			],
		];
	}

	/**
	 * 图片识别
	 * @return array|array[]
	 * @throws BadRequestHttpException
	 * @throws ServerErrorHttpException
	 */
	public function actionOcr()
	{
		$params = Yii::$app->request->post();
		$model = new ImageOCR();
		return $model->process($params);
	}

	/**
	 * 单个批量查询
	 * @return InvoiceData|array
	 * @throws BadRequestHttpException
	 */
	public function actionVerify()
	{
		$params = Yii::$app->request->post();
		$model = new InvoiceVerify();
		return $model->process($params);
	}
}