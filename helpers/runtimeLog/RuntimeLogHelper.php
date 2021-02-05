<?php

namespace app\common\helpers\runtimeLog;

use Yii;
use yii\redis\Connection;

use app\common\models\RuntimeLogModel;

class RuntimeLogHelper
{
	const RUNTIME_LOG_QUEUE_KEY = 'invoice_runtime_log_queue';
	const VERIFY_RUNTIME_LOG_QUEUE_KEY = 'verify_runtime_log_queue';

	const INVOICE_CHANNEL = "invoice";//开票日志
	const VERIFY_CHANNEL = "verify";//查验请求日志

	public static $channel_queue_key_map = [
		self::INVOICE_CHANNEL => self::RUNTIME_LOG_QUEUE_KEY,
		self::VERIFY_CHANNEL => self::VERIFY_RUNTIME_LOG_QUEUE_KEY,
	];

	const TRACE_LEVEL = 0;
	const INFO_LEVEL = 1;
	const WARNING_LEVEL = 2;
	const ERROR_LEVEL = 3;

	public static function sendLog2Queue($data, $key = self::RUNTIME_LOG_QUEUE_KEY)
	{
		$model = new RuntimeLogModel();
		$model->load($data, '');
		/** @var Connection $redis */
		$redis = Yii::$app->redis;
		$redis->lpush($key, json_encode($model));
	}
}