<?php

namespace app\common\helpers;

use yii\base\Model;
use yii\web\Response;

class ResponseHelper extends Model
{
    //相应代码
    CONST CODE_ACTIVATED = 200;
    CONST CODE_EXPIRED = 416;
    CONST CODE_ERROR = 503;
    CONST CODE_OPERATE_NOT_PERMITTED = 403;
    CONST CODE_PARAMS_ERROR = 400;
    CONST CODE_STATUS_ERROR = 405;
    CONST CODE_SYSTEM_ERROR = 500;

    /**
     * @var array 代码配置
     */
    static $code_config = [
        self::CODE_ACTIVATED => 'success',
        self::CODE_EXPIRED => '登录超时',
        self::CODE_ERROR => 'FAIL',
        self::CODE_PARAMS_ERROR => '参数错误',
        self::CODE_OPERATE_NOT_PERMITTED => '权限不足',
        self::CODE_STATUS_ERROR => '状态错误',
        self::CODE_SYSTEM_ERROR => '系统错误',
    ];

	/**
	 * @param $event
	 * @return bool
	 */
	public static function beforeSend($event)
	{
		/** @var Response $response */
		$response = $event->sender;

		/**解决gii工具不能使用的问题 开始 **/
		if (in_array($response->format, [Response::FORMAT_HTML, Response::FORMAT_RAW])) {
			return TRUE;
		}

		$format = [];
		if ($response->statusCode == 200) {
			$format['code'] = self::CODE_ACTIVATED;
		} elseif ($response->statusCode == 401) {
			$format['code'] = self::CODE_EXPIRED;
		} else if ($response->statusCode == 403) {
			$format['code'] = self::CODE_OPERATE_NOT_PERMITTED;
		} else if (preg_match('/^4*/', $response->statusCode)) {
			$format['code'] = $response->statusCode;
		} else {
			$format['code'] = self::CODE_ERROR;
		}
		if (is_array($response->data) && isset($response->data['code'])) {
			$response->data['code'] && $format['code'] = $response->data['code']; //自定义错误码
			$format['data'] = isset($response->data['data']) ? $response->data['data'] : NULL;
			if ($response->statusCode >= 500 && YII_ENV_PROD) { //正式环境500错误不暴露message
				$format['message'] = $response->statusText;
			} else {
				$format['message'] = isset($response->data['message']) ? $response->data['message'] : $response->statusText;
			}
		} else {
			$format['data'] = $response->data;
			$format['message'] = $response->statusText;
		}
		$response->format = 'json';
		$response->data = self::response($format['code'], $format['data'], $format['message']);
		$response->statusCode = 200;
	}

    public static function getCodeLabel($code)
    {
        return self::$code_config[$code];
    }

    public static function response($code, $data = null, $message = '')
    {
        $message = $message ?: self::$code_config[$code];
        $response_data = self::buildResponse($code, $message, $data);
        //shell 环境下没有 request->get() 方法
        if (!\Yii::$app->request->isConsoleRequest && \Yii::$app->request->get("callback")) {
            $response_data = ['data' => $response_data, 'callback' => \Yii::$app->request->get("callback")];
        }
        return $response_data;
    }

    /**
     * 成功响应
     * @param mixed $data 返回的数据
     * @return array self::buildResponse
     */
    public static function success($data = null)
    {
        return self::response(self::CODE_ACTIVATED, $data);
    }

    /**
     * 失败响应
     * @param integer $code
     * @param string $message
     * @param mixed $data
     * @return array
     */
    public static function failed($code = self::CODE_ERROR, $message = '', $data = null)
    {
        return self::response($code, $data, $message);
    }

    public static function buildResponse($code, $message = "", $data = null)
    {
        return ['code' => $code, 'message' => $message, 'data' => $data];
    }
}