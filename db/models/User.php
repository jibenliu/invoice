<?php

namespace common\components\db\models;

use Yii;
use yii\db\Expression;
use common\models\Role;
use yii\db\ActiveRecord;
use common\models\UserRole;
use common\models\TaxEntity;
use yii\web\IdentityInterface;
use common\models\ErpApiSeting;
use common\models\TaxEntityRole;
use yii\behaviors\TimestampBehavior;
use common\components\db\RedisHashTools;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $real_name
 * @property string $nick_name
 * @property string $tenant_code
 * @property int $group_id
 * @property string $password
 * @property string $email
 * @property string $auth_key
 * @property string $employ_no
 * @property string $phone_num
 * @property integer $status
 * @property integer $is_admin
 * @property integer $origin
 * @property string $created_time
 * @property string $updated_time
 *
 * @property Tenants $tenants
 * @property Instance $instance
 * @property ErpApiSeting $setting
 * @property Role[] $roles
 * @property TaxEntity[] $taxEntities[]
 * @property TaxEntityRole[] $taxEntityRoles[]
 */
class User extends ActiveRecord implements IdentityInterface
{
	const STATUS_DELETED = 2;
	const STATUS_LOCKED = 0;
	const STATUS_ACTIVE = 1;

	static $status_config = [
		self::STATUS_DELETED => '删除',
		self::STATUS_LOCKED => '锁定',
		self::STATUS_ACTIVE => '有效',
	];

	const ADMIN_ROLE = 1;
	const NOT_ADMIN_ROLE = 0;

	public static $is_admin_config = [
		self::ADMIN_ROLE => '管理员',
		self::NOT_ADMIN_ROLE => '非管理员',
	];

	const ORIGIN_SHOULOU = 0;
	const ORIGIN_SELF = 1;

	static $origin_config = [
		self::ORIGIN_SHOULOU => '售楼',
		self::ORIGIN_SELF => '自有',
	];

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'vat_user';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			[
				'class' => TimestampBehavior::class,
				'createdAtAttribute' => 'create_time',
				'updatedAtAttribute' => 'update_time',
				'value' => new Expression('NOW()'),
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['origin', 'integer'],
			['origin', 'default', 'value' => self::ORIGIN_SHOULOU],
			['status', 'default', 'value' => self::STATUS_ACTIVE],
			['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
			[['real_name', 'nick_name', 'employ_no', 'phone_num'], 'string', 'max' => 255],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'username' => '账号',
			'email' => '邮箱',
			'status' => '状态',
			'origin' => '来源',
			'real_name' => '真实姓名',
			'nick_name' => '昵称',
		];
	}

	/**
	 * @inheritdoc
	 */
	public static function findIdentity($id)
	{
		return static::findOne($id);
	}

	/**
	 * @inheritdoc
	 */
	public static function findIdentityByAccessToken($token, $type = NULL)
	{
		$redisTool = new RedisHashTools();
		$arr = $redisTool->getAllField($token);
		if (empty($arr)) return FALSE;
		Yii::$app->set('db', unserialize($arr['db']));
		$redisTool->flush($token);
		return unserialize($arr['userInfo']);
	}

	/**
	 * Finds user by username
	 *
	 * @param string $username
	 * @return static|null
	 */
	public static function findByUsername($username)
	{
		return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
	}

	/**
	 * @inheritdoc
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @inheritdoc
	 */
	public function getAuthKey()
	{
		return $this->auth_key;
	}

	/**
	 * @inheritdoc
	 */
	public function validateAuthKey($authKey)
	{
		return $this->getAuthKey() === $authKey;
	}

	/**
	 * Validates password
	 *
	 * @param string $password password to validate
	 * @return bool if password provided is valid for current user
	 */
	public function validatePassword($password)
	{
		return Yii::$app->security->validatePassword($password, $this->password_hash);
	}

	/**
	 * @param string $prefix
	 * @return string
	 */
	public function generateAccessToken($prefix = "")
	{
		return $prefix . Yii::$app->security->generateRandomString();
	}

	public function getTenants()
	{
		return $this->hasOne(Tenants::class, ['tenant_code' => 'tenant_code']);
	}

	public function getInstance()
	{
		return $this->hasOne(Instance::class, ['instance_id' => 'instance_id'])->via('tenants');
	}

	public function getRoles()
	{
		return $this->hasMany(UserRole::class, ['user_id' => 'id']);
	}

	public function getEntityRoles()
	{
		return $this->hasMany(TaxEntityRole::class, ['role_id' => 'id'])->via('roles');
	}

	public function getEntities()
	{
		return $this->hasMany(TaxEntity::class, ['id' => 'tax_entity_id'])->via('entityRoles');
	}

	public function getSetting()
	{
		return $this->hasOne(ErpApiSeting::class, ['tenant_id' => 'tenant_id'])->via('tenants');
	}
}
