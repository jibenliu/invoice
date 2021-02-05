<?php

namespace app\components\verifyProcedure\verifys\piaoTong;

class ApiManager
{
	public static $params = [
		'APPKEY' => '',
		'APPSECRET' => '',
		'UID' => '',
		'VERSION' => '1.0',
		'REGCODE' => '',
		'URL' => 'https://newfpcy.vpiaotong.com/servlet/fpcy',
	];

	public static function outerQrXml($params)
	{
		$password = self::makePwd();
		$appkey = self::$params['APPKEY'];
		$appSecret = self::$params['APPSECRET'];
		$uid = self::$params['UID'];
		$version = self::$params['VERSION'];
		$date = date('Y-m-d H:i:s');
		$randomString = date('YmdHis') . self::getRandomString(11);

		return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>
			    <interface xmlns=\"\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.chinatax.gov.cn/tirip/dataspec/interfaces.xsd\" version=\"PTYF1.0\">
			        <globalInfo>
			            <appKey>{$appkey}</appKey>
			            <appSecret>{$appSecret}</appSecret>
			            <UID>{$uid}</UID>
			            <version>{$version}</version>
			            <interfaceCode>{$params['interfaceCode']}</interfaceCode>
			            <passWord>{$password}</passWord>
			            <accessToken>test</accessToken>
			            <requestTime>{$date}</requestTime>
			            <dataExchangeId>{$randomString}</dataExchangeId>
			        </globalInfo>
			        <returnStateInfo>
			            <returnCode>0000</returnCode>
			            <returnMessage>0000</returnMessage>
			        </returnStateInfo>
			        <data>
			            <dataDescription>
			                <zipCode>0</zipCode>
			                <encryptCode>0</encryptCode>
			                <codeType>0</codeType>
			            </dataDescription>
			            <content>{$params['content']}</content>
			        </data>
			    </interface>";
	}

	public function innerQrXml($params)
	{
		$date = date('Y-m-d H:i:s');
		$xml = "<REQUEST_FPCY>
			    <TWODIMENSIONCODE>{$params['QrCode']}</TWODIMENSIONCODE>
			    <ACCOUNTTYPE></ACCOUNTTYPE>
			    <ACCOUNTNO></ACCOUNTNO>
			    <REQUESTTIME>{$date}</REQUESTTIME>
			</REQUEST_FPCY>";

		return base64_encode($xml);
	}

	public static function decodeResult($xml)
	{
		$objectXml = simplexml_load_string($xml);
		$xmlJson = json_encode($objectXml);
		return json_decode($xmlJson, TRUE);
	}

	private static function makePwd()
	{
		$rand = self::getRandomString(10);
		$md5Str = md5($rand . self::$params['REGCODE'], TRUE);
		$enCode = base64_encode($md5Str);
		return $rand . $enCode;
	}

	private static function getRandomString($len, $chars = NULL)
	{
		if (is_null($chars)) {
			$chars = "0123456789";
		} else {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		}
		mt_srand(10000000 * (double)microtime());
		for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
			$str .= $chars[mt_rand(0, $lc)];
		}
		return $str;
	}
}