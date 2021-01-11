<?php

namespace common\components\invoice\invoiceTool;

use yii\web\BadRequestHttpException;
use common\components\invoice\AbstractInvoice;

class Invoice extends AbstractInvoice
{
    public function validateInvoice()
    {
        throw new BadRequestHttpException('开票点渠道错误！');
    }

    public function applyInvoice()
    {
        throw new BadRequestHttpException('开票点渠道错误！');
    }

    public function checkInvoice()
    {
        throw new BadRequestHttpException('开票点渠道错误！');
    }

    public function nullifyInvoice()
    {
        throw new BadRequestHttpException('开票点渠道错误！');
    }

    public function printInvoice()
    {
        throw new BadRequestHttpException('开票点渠道错误！');
    }
}