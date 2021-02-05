<?php


namespace app\common\helpers;


class InvoiceHelpers
{
	const SPECIAL_INVOICE = '01';
	const TRANSPORT_SPECIAL_INVOICE = '02';
	const MOTOR_PURCHASE_INVOICE = '03';
	const NORMAL_INVOICE = '04';
	const ELECTRONIC_NORMAL_INVOICE = '05';
	const ROLL_NORMAL_INVOICE = '11';
	const TRANSPORT_ELECTRONIC_NORMAL_INVOICE = '14';
	const SECOND_HAND_MOTOR_PURCHASE_INVOICE = '15';

	/**
	 * 发票类型数组
	 * @var array
	 */
	public static $invoiceType = [
		self::SPECIAL_INVOICE => '增值税专用发票',
		self::TRANSPORT_SPECIAL_INVOICE => '货运运输业增值税专用发票',
		self::MOTOR_PURCHASE_INVOICE => '机动车销售统一发票',
		self::NORMAL_INVOICE => '增值税普通发票',
		self::ELECTRONIC_NORMAL_INVOICE => '增值税普通发票（电子）',
		self::ROLL_NORMAL_INVOICE => '增值税普通发票（卷式）',
		self::TRANSPORT_ELECTRONIC_NORMAL_INVOICE => '电子普通[通行费]发票',
		self::SECOND_HAND_MOTOR_PURCHASE_INVOICE => '二手车销售统一发票',
	];

	/*增值税发票行政区划*/
	public static $VATInvoiceArea = [
		"1100" => "北京",
		"1200" => "天津",
		"1300" => "河北",
		"1400" => "山西",
		"1500" => "内蒙古",
		"2100" => "辽宁",
		"2200" => "吉林",
		"2300" => "黑龙江",
		"3100" => "上海",
		"3200" => "江苏",
		"3300" => "浙江",
		"3400" => "安徽",
		"3500" => "福建",
		"3600" => "江西",
		"3700" => "山东",
		"4100" => "河南",
		"4200" => "湖北",
		"4300" => "湖南",
		"4400" => "广东",
		"4500" => "广西壮族自治区",
		"4600" => "海南",
		"5000" => "重庆",
		"5100" => "四川",
		"5200" => "贵州",
		"5300" => "云南",
		"5400" => "西藏",
		"6100" => "陕西",
		"6200" => "甘肃",
		"6300" => "青海",
		"6400" => "宁夏",
		"6500" => "新疆",
		"3702" => "青岛",
		"3502" => "厦门",
		"3302" => "宁波",
		"4403" => "深圳",
	];

	/*普通发票行政区划*/
	public static $ordinaryInvoiceArea = [
		"11" => "北京市",
		"12" => "天津市",
		"13" => "河北省",
		"14" => "山西省",
		"15" => "内蒙古自治区",
		"21" => "辽宁省",
		"22" => "吉林省",
		"23" => "黑龙江省",
		"31" => "上海市",
		"32" => "江苏省",
		"33" => "浙江省",
		"34" => "安徽省",
		"35" => "福建省",
		"36" => "江西省",
		"37" => "山东省",
		"41" => "河南省",
		"42" => "湖北省",
		"43" => "湖南省",
		"44" => "广东省",
		"45" => "广西壮族自治区",
		"46" => "海南省",
		"50" => "重庆市",
		"51" => "四川省",
		"52" => "贵州省",
		"53" => "云南省",
		"54" => "西藏自治区",
		"61" => "陕西省",
		"62" => "甘肃省",
		"63" => "青海省",
		"64" => "宁夏回族自治区",
		"65" => "新疆维吾尔自治区",
		"3702" => "青岛市",
		"3502" => "厦门市",
		"3302" => "宁波市",
		"4403" => "深圳市",
	];

	/**
	 * 获取发票类型
	 * 增值税专用发票：01
	 * 货运运输业增值税专用发票：02
	 * 机动车销售统一发票：03
	 * 增值税普通发票：04
	 * 增值税普通发票（电子）：10
	 * 增值税普通发票（卷式）：11
	 * @param $invoiceCode
	 * @return mixed|string
	 */
	public static function getInvoiceTypeByInvoiceNo($invoiceCode)
	{
		$type = '';
		if (strlen($invoiceCode) == 10) {
			$mark = substr($invoiceCode, 7, 1);

			if ($mark == 1 || $mark == 5) {
				$type = "01";
			} elseif ($mark == 6 || $mark == 3) {
				$type = "04";
			} elseif ($mark == 7 || $mark == 2) {
				$type = "02";
			}
		} elseif (strlen($invoiceCode) == 12) {  // 新版电子发票
			$code = ['144031539110', '131001570151', '133011501118', '111001571071'];
			for ($i = 0; $i < count($code); $i++) {
				if ($invoiceCode == $code[$i]) {
					$type = "10";
					break;
				}
			}

			$first = substr($invoiceCode, 0, 1);
			$last = substr($invoiceCode, 10, 2);
			if ($type == '') {
				if ($first == '0' && $last == '11') {
					$type = '10';
				} // 卷式发票
				elseif ($first == '0' && ($last == '06' || $last == '07')) {
					$type = '11';
				} elseif ($first == '0' && ($last == '04' || $last == '05')) {
					$type = "04";
				} elseif ($first == '1' && $last == '11') {
					$type = "04";
				}
				if ($first == '0' && $last == '12') {
					$type = "14";
				}
			}

			// 机动车发票
			if ($type == '') {
				$b = substr($invoiceCode, 7, 1);
				if ($b == 2 && $first != '0') {
					$type = '03';
				}

			}
		}

		return $type;
	}

	public static function getInvoiceTypeName($code)
	{
		$type = self::getInvoiceTypeByInvoiceNo($code);
		return isset(self::$invoiceType[$type]) ? self::$invoiceType[$type] : '';
	}

	public static function getInvoiceAreaByInvoiceNo($code)
	{
		if ($code) {
			$code = (string)$code;
			switch (strlen($code)) {
				case 10:
					if (isset(self::$VATInvoiceArea[substr($code, 0, 4)])) {
						return self::$VATInvoiceArea[substr($code, 0, 4)];
					} else {
						return '';
					}
				case 12:
					if (substr($code, 0, 1) == 1) {
						if (isset(self::$ordinaryInvoiceArea[substr($code, 1, 4)])) {
							return self::$ordinaryInvoiceArea[substr($code, 1, 4)];
						} elseif (isset(self::$ordinaryInvoiceArea[substr($code, 1, 2)])) {
							return self::$ordinaryInvoiceArea[substr($code, 1, 2)];
						} else {
							return '';
						}
					} else {
						if (isset(self::$VATInvoiceArea[substr($code, 1, 4)])) {
							return self::$VATInvoiceArea[substr($code, 1, 4)];
						} else {
							return '';
						}
					}
				default:
					return '';
			}
		} else {
			return '';
		}
	}

	public static function getInvoiceTypeNameByInvoiceSn($invoiceNo)
	{
		return self::getInvoiceAreaByInvoiceNo($invoiceNo) . self::getInvoiceTypeName($invoiceNo);
	}
}