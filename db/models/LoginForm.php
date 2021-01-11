<?php

namespace common\components\db\models;

use Yii;
use yii\base\Model;
use yii\db\Exception;
use yii\redis\Connection;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use common\components\db\RedisHashTools;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $tenant_code;
    public $username;
    public $password;

    /** @var User $_user */
    private $_user;

    public $wrongTimes;
    public $captcha;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tenant_code', 'username'], 'trim'],
            [['tenant_code', 'username', 'password'], 'required'],
            ['tenant_code', 'validateTenantCode'],
            ['password', 'validatePassword'],
            ['captcha', 'captcha', 'skipOnEmpty' => TRUE, 'message' => '验证码错误！'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'tenant_code' => '租户代码',
            'username' => '用户名',
            'password' => '密码',
        ];
    }

    /**
     * @param $attribute
     * @return bool
     * @throws InvalidConfigException
     */
    public function validateTenantCode($attribute)
    {
        if (!$this->hasErrors()) {
            /** @var Tenants $tenant */
            $tenant = Tenants::find()
                ->where(['tenant_code' => $this->$attribute])
                ->andWhere(['is_enable' => Tenants::STATUS_ACTIVE])
                ->andWhere(['LIKE', 'zzs_version', 'zzs2.0'])
                ->one();
            if (!$tenant) {
                $this->addError($attribute, '集团代码或账户名错误！');
                return FALSE;
            }
            if (!$tenant->instance) {
                $this->addError($attribute, '该集团尚未初始化，无法登陆！');
                return FALSE;
            }
            $dsn = 'mysql:host=' . $tenant->instance->host . ';port=' . $tenant->instance->port . ';dbname=' . $tenant->db_name;
            $username = $tenant->instance->user_name;
            $password = $tenant->instance->password;
            Yii::$app->set('db', [
                'class' => 'yii\db\Connection',
                'dsn' => $dsn,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8',
                'tablePrefix' => 'vat_',
                //先从缓存中取数据表结构信息 开启后默认有效时间为1小时
                'enableSchemaCache' => TRUE,
            ]);
        }
    }

    /**
     *
     * @param $attribute
     * @return bool
     */
    public function validatePassword($attribute)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            /** @var Connection $redis */
            $redis = Yii::$app->redis;
            $key = $this->tenant_code . '_' . $this->username;
            $wrongTimes = $redis->get('wrong_password_times_' . $key);
            $wrongTimes = $wrongTimes ?? 0;
            $this->wrongTimes = $wrongTimes;
            if ($wrongTimes >= 10) {
                $redis->set('wrong_password_times_' . $key, $wrongTimes + 1);
                $redis->expire('wrong_password_times_' . $key, 10 * 60);
                $this->addError($attribute, '账户名或密码错误次数已达10次，账号锁定10分钟！');
                return FALSE;
            }
            if (!$user || $user->password != md5($this->$attribute)) {
                $this->wrongTimes = $wrongTimes + 1;
                $redis->set('wrong_password_times_' . $key, $this->wrongTimes);
                $redis->expire('wrong_password_times_' . $key, 10 * 60);
                $this->addError($attribute, '账户名或密码错误！');
                return FALSE;
            } else {
                $redis->del('wrong_password_times_' . $key);
                return TRUE;
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return User|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function login()
    {
        $user = $this->getUser();
        if ($user) {
            if ($user->status == User::STATUS_ACTIVE) {
                $user->tenant_code = $this->tenant_code;
                $redisTool = new RedisHashTools();
                Yii::$app->user->login($user, Yii::$app->params['user.apiTokenExpire']);
                $token = $user->generateAccessToken();
                $redisTool->setAllField($token, ['userInfo' => serialize($user), 'db' => serialize(Yii::$app->db)]);
                $user->auth_key = $token;
                $user->save(FALSE);
                return $user;
            } else if ($user->status == User::STATUS_LOCKED) {
                throw new BadRequestHttpException('该用户已被禁用，禁止登录');
            }
        } else {
            throw new NotFoundHttpException('未找到该用户！');
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === NULL) {
            $this->_user = User::findOne(['username' => $this->username]);
        }
        return $this->_user;
    }
}
