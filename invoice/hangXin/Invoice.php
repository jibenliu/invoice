<?php

namespace common\components\invoice\hangXin;

use yii\web\BadRequestHttpException;
use common\components\invoice\AbstractInvoice;

class Invoice extends AbstractInvoice
{
    /**
     * @return bool
     * @throws BadRequestHttpException
     */
    public function validateInvoice()
    {
        $model = new InvoiceValidate();
        $attributeArr = array_merge(json_decode(json_encode($this->invoice), TRUE), $this->invoice->toArray());
        $model->load($attributeArr, '');
        if (!$model->validate()) {
            throw new BadRequestHttpException(current($model->getFirstErrors()));
        }
        return TRUE;
    }

    public function applyInvoice()
    {
        return TRUE;
    }

    public function checkInvoice()
    {
        return TRUE;
    }

    public function nullifyInvoice()
    {
        return TRUE;
    }

    public function printInvoice()
    {
        return TRUE;
    }
}