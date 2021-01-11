<?php

namespace common\components\invoice;

use yii\base\BaseObject;
use api\models\invoice\InvoiceSave;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

abstract class AbstractInvoice extends BaseObject
{
    /** @var InvoiceSave */
    public $invoice;

    /**
     * 校验通过时返回true，校验失败或者其他问题（etc.netWork）抛出错误
     * @return bool
     * @throws \Throwable
     */
    abstract public function validateInvoice();

    /**
     * 开票通过时返回true，延时开票时返回false，校验失败或者其他问题（etc.netWork）抛出错误
     * 要求:
     *      false时将状态回滚为apply_invoicing
     *      抛错时将状态回滚为none_invoice
     * @return bool
     * @throws \Throwable
     */
    abstract public function applyInvoice();

    /**
     * 开票通过时返回正常结构体，正在开票时返回false，校验失败或者其他问题（etc.netWork）抛出错误
     * 要求:
     *      开票成功时将状态改成 has_invoiced，
     *      false时将状态回滚为apply_invoicing
     *      抛错时将状态回滚为none_invoice
     * @return bool
     * @throws \Throwable
     */
    abstract public function checkInvoice();

    /**
     * 作废成功时返回true，作废失败或者其他问题（etc.netWork）抛出错误
     * 要求:
     *      true时将状态改成 abolition_status
     *      抛错时将状态回滚为 none_red_status
     * @return bool
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    abstract public function nullifyInvoice();

    /**
     * 打印成功时返回true，打印失败返回false 其他问题（etc.netWork）抛出错误
     * 要求:
     *      true时将状态改成 已打印
     * @return bool
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    abstract public function printInvoice();
}