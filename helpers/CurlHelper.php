<?php

namespace app\common\helpers;

class CurlHelper
{
	/**
	 * POST 请求
	 * @param string $url
	 * @param array|string $param
	 * @param string $content_type 上传类型
	 * @param boolean $post_file 是否文件上传
	 * @param array $header 伪造请求头
	 * @return string content
	 */
	public static function http_post($url, $param = [], $content_type = 'form', $post_file = FALSE, $header = [])
	{
		$oCurl = curl_init();
		if (stripos($url, "https://") !== FALSE) {
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		if (PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')) {
			$is_curlFile = TRUE;
		} else {
			$is_curlFile = FALSE;
			if (defined('CURLOPT_SAFE_UPLOAD')) {
				curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, FALSE);
			}
		}
		if (is_string($param)) {
			$strPOST = $param;
		} elseif ($post_file) {
			if ($is_curlFile) {
				foreach ($param as $key => $val) {
					if (substr($val, 0, 1) == '@') {
						$param[$key] = new \CURLFile(realpath(substr($val, 1)));
					}
				}
			}
			$strPOST = $param;
		} elseif ($content_type == 'application/x-www-form-urlencoded') {
			$strPOST = http_build_query($param);
			curl_setopt($oCurl, CURLOPT_HTTPHEADER, [
					'Content-Type: application/x-www-form-urlencoded',
					'charset: utf-8',
					'Content-Length: ' . strlen($strPOST),
					'Expect:',
				]
			);
		} elseif ($content_type == 'json') {
			$strPOST = json_encode($param);
			curl_setopt($oCurl, CURLOPT_HTTPHEADER, [
					'Content-Type: application/json',
					'Content-Length: ' . strlen($strPOST)]
			);
		} else {
			$aPOST = [];
			foreach ($param as $key => $val) {
				$aPOST[] = $key . "=" . urlencode($val);
			}
			$strPOST = join("&", $aPOST);
		}
		if (!empty($header)) {
			foreach ($header as $item) {
				curl_setopt($oCurl, CURLOPT_HTTPHEADER, $item);
			}
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_HEADER, 0);//不抓取头部信息。只返回数据
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_POST, TRUE);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
		curl_setopt($oCurl, CURLOPT_TIMEOUT, 60);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if (intval($aStatus["http_code"]) == 200) {
			return $sContent;
		} else {
			return FALSE;
		}
	}

	/**
	 * GET 请求
	 * @param $url
	 * @param array $header
	 * @return bool|mixed
	 */
	public static function http_get($url, $header = [])
	{
		$oCurl = curl_init();
		if (stripos($url, "https://") !== FALSE) {
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		if (!empty($header)) {
			foreach ($header as $item) {
				curl_setopt($oCurl, CURLOPT_HTTPHEADER, $item);
			}
		}
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);

		if (intval($aStatus["http_code"]) == 200) {
			return $sContent;
		} else {
			return FALSE;
		}
	}
}
