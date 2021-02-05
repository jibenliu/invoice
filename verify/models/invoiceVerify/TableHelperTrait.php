<?php


namespace app\modules\gscheck\models\invoiceVerify;


trait TableHelperTrait
{
	private static $_partitionIndex;

	public static function getPartitionIndex()
	{
		return self::$_partitionIndex;
	}

	/**
	 * 获取表名后缀
	 * @param $url
	 * @return int
	 */
	public static function setTableSuffixByUrl($url)
	{
		self::$_partitionIndex = self::modString($url);
		return self::$_partitionIndex;
	}

	/**
	 * 获取表名后缀
	 * @param $prefix
	 */
	public static function setTableSuffix($prefix)
	{
		self::$_partitionIndex = $prefix;
	}

	/**
	 * 获取表名后缀
	 * @param $ticketDate
	 * @return int
	 */
	public static function setTableSuffixByDate($ticketDate)
	{
		self::$_partitionIndex = date("Ym", strtotime($ticketDate));
		return self::$_partitionIndex;
	}

	/**
	 * 对字符串进行取模操作
	 * @param $str
	 * @param int $mod
	 * @return int
	 */
	public static function modString($str, $mod = 32)
	{
		$md5_string = md5($str);
		$ten_string = base_convert($md5_string, 16, 10);
		$short_string = substr($ten_string, 0, 8);
		return $short_string % $mod;
	}
}