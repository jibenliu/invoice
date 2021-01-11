<?php

namespace common\components\db;

use Yii;

class User extends \yii\web\User
{
    public function switchIdentity($identity, $duration = 0)
    {
        $this->setIdentity($identity);

        if (!$this->enableSession) {
            return;
        }

        if ($this->enableAutoLogin && ($this->autoRenewCookie || $identity === null)) {
            $this->removeIdentityCookie();
        }

        $token = $identity->getAuthKey();
        $redisTool = new RedisHashTools();
        $redisTool->setAllField($token, ['userInfo' => $identity, 'db' => serialize(Yii::$app->getDb())]);
    }
}