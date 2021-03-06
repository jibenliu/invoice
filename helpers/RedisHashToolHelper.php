<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/10
 * Time: 10:02
 */

namespace app\common\helpers;

use Yii;
use yii\db\Exception;

/**
 * 此处封装的是redis的hash格式存储和读取工具
 * Class RedisHashTools
 * @package common\components\RedisTools
 */
class RedisHashToolHelper
{
	public $expire;
	public $prefix;

	/** @var yii\redis\Connection $redis */
	public $redis = 'redis';


	public function __construct($prefix = NULL, $expire = NULL)
	{
		$this->prefix = !empty($prefix) ? $prefix : '_userLoginInfo_';
		$this->expire = !empty($expire) ? $expire : Yii::$app->params['user.apiTokenExpire'];
		$this->redis = Yii::$app->redis;
	}

	/**
	 * 设置键值对
	 * @param string $key
	 * @param string $field
	 * @param string $value
	 * @param int $expire
	 * @param string $prefix
	 */
	public function setKey($key, $field, $value, $expire, $prefix)
	{
		$this->redis->hset($prefix . $key, $field, $value);
		$this->redis->expire($prefix . $key, $expire);
	}

	/**
	 * 设置某个键的值
	 * @param string $key
	 * @param string $field
	 * @param string $value
	 */
	public function setField($key, $field, $value)
	{
		$this->setKey($key, $field, $value, $this->expire, $this->prefix);
	}

	/**
	 * 获取某个键的值
	 * @param string $key
	 * @param string $field
	 * @return string
	 */
	public function getField($key, $field)
	{
		return $this->redis->hget($this->prefix . $key, $field);
	}

	/**
	 * 获取所有的键
	 * @param string $key
	 * @return array
	 */
	public function getAllField($key)
	{
		$temp = $this->redis->hgetall($this->prefix . $key);
		$count = count($temp);
		$arr = [];
		for ($i = 0; $i < $count; $i++) {
			if ($i % 2 == 0) {
				$tmpArr = json_decode($temp[$i + 1], TRUE);
				$arr[$temp[$i]] = is_array($tmpArr) ? $tmpArr : $temp[$i + 1];
			}
		}
		return $arr;
	}

	/**
	 * 设置所有hash属性
	 * @param string $key
	 * @param array $arr
	 * @throws Exception
	 */
	public function setAllField($key, $arr)
	{
		$ret = [];
		$ret[] = $this->prefix . $key;
		foreach ($arr as $k => $v) {
			$ret[] = $k;
			$ret[] = is_string($v) ? $v : json_encode($v);
		}
		$this->redis->executeCommand('hmset', $ret);//hmset一次操作，减少链接出现错误的可能性
		$this->expire($key);
	}

	/**
	 * 设置有效期
	 * @param string $key
	 * @return bool
	 */
	public function expire($key)
	{
		return $this->redis->expire($this->prefix . $key, $this->expire);
	}

	/**
	 * 刷新有效期
	 * @param string $key
	 * @return bool
	 */
	public function flush($key)
	{
		return $this->redis->expire($this->prefix . $key, $this->expire);
	}

	/**
	 * 删除缓存
	 * @param string $key
	 * @return bool
	 */
	public function clean($key)
	{
		return $this->redis->expire($this->prefix . $key, 0);
	}

	/**
	 * 清空缓存
	 * @return mixed
	 */
	public function cleanAll()
	{
		return $this->redis->flushdb();
	}
}