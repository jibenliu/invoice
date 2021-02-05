<?php
/**
 * 开票接口调用入口类
 * Created by PhpStorm.
 * User: jibenliu
 * Date: 2019/12/6
 * Time: 12:01
 */

namespace common\components\invoice;

use Yii;
use common\models\Invoice;
use common\models\TaxPoint;
use api\models\invoice\InvoiceSave;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Class InvoiceDispatch
 * @package App\Lib
 *
 * @property AbstractInvoice $invoiceObj
 */
class InvoiceDispatch
{
    /** @var AbstractInvoice */
    private $invoiceObj;

    const BASE_DIR = 'common\components\invoice\\';

    /**
     * InvoiceDispatch constructor.
     * @param Invoice $invoice
     * @throws InvalidConfigException
     */
    public function __construct(Invoice $invoice)
    {
        $taxPoint = $invoice->_taxPoint ?? $invoice->taxPoint;
        if ($taxPoint->is_cloud_channel == TaxPoint::CLOUD_CHANNEL) {
            $taxSystem = TaxPoint::YUNSHUI_PARTNER_INVOICE;
        } else {
            if ($invoice instanceof InvoiceSave) {
                $taxSystem = TaxPoint::$tax_system_config[$invoice->_taxPoint->tax_system_id];
            } else {
                $taxPoint = TaxPoint::findOne($invoice->attachedInfo->tax_point_id);
                $taxSystem = TaxPoint::$tax_system_config[$taxPoint->tax_system_id];
            }
        }
        switch ($taxSystem) {
            case TaxPoint::HANG_XIN:
                $dirName = TaxPoint::HANG_XIN;
                break;
            case TaxPoint::UKEY:
            case TaxPoint::BAI_WANG:
                $dirName = TaxPoint::BAI_WANG;
                break;
            case TaxPoint::DA_XIANG:
                $dirName = TaxPoint::DA_XIANG;
                break;
            case TaxPoint::YUNSHUI_PARTNER_INVOICE:
                $dirName = TaxPoint::YUNSHUI_PARTNER_INVOICE;
                break;
            default :
                $dirName = 'invoiceTool';
                break;
        }
        $this->invoiceObj = Yii::createObject([
            'class' => self::BASE_DIR . $dirName . '\Invoice',
            'invoice' => $invoice
        ]);
    }

    /**
     * 发票校验
     * @return bool
     * @throws \Throwable
     */
    public function validateInvoice()
    {
        return $this->invoiceObj->validateInvoice();
    }

    /**
     * 发票开具
     * @return bool
     * @throws \Throwable
     */
    public function applyInvoice()
    {
        return $this->invoiceObj->applyInvoice();
    }

    /**
     * 发票开具状态查询
     * @return bool|array
     * @throws \Throwable
     */
    public function checkInvoice()
    {
        return $this->invoiceObj->checkInvoice();
    }

    /**
     * 发票作废
     * @return bool
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function nullifyInvoice()
    {
        return $this->invoiceObj->nullifyInvoice();
    }

    /**
     * 发票打印
     * @return bool
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function printInvoice()
    {
        return $this->invoiceObj->printInvoice();
    }
}
