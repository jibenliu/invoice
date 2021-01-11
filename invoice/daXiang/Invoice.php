<?php

namespace common\components\invoice\daXiang;

use Yii;
use common\models\InvoiceRule;
use common\helpers\CurlHelper;
use common\helpers\ToolsHelper;
use common\helpers\InvoiceTools;
use api\models\invoice\InvoiceSave;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use common\models\TaxSystemPropValue;
use yii\web\ServerErrorHttpException;
use common\models\config\ApplyInvoiceList;
use common\models\Invoice as InvoiceModel;
use common\components\invoice\AbstractInvoice;

class Invoice extends AbstractInvoice
{
    const API_PREFIX = '/invoice/v1';

    const INVOICE_APPLY = 'AllocateInvoices';
    const INVOICE_NULLIFY = 'DeprecateInvoices';
    const INVOICE_CHECK = 'GetAllocatedInvoices';

    const ENCRYPT_CODE = 0;// 加密标识 0: 不加密; 1: 加密 ;
    const ZIP_CODE = 0;//  压缩标识  0: 不压缩; 1: 压缩 ;

    const PAPER_INVOICE = 1;//纸质发票
    const ELECTRONIC_INVOICE = 2;//电子发票

    const DEPRECATE_TYPE_EMPTY = 0;//空白发票作废
    const DEPRECATE_TYPE_BLUE = 1;//正数发票作废
    const DEPRECATE_TYPE_RED = 1;//负数发票作废

    private static $KPJH = NULL;
    private static $KPZDBS = NULL;
    private static $SKYSKL = NULL;
    private static $FWQDZ = NULL;
    private static $BMBBBH = NULL;

    private static $constConfig = [
        'KPJH',
        'KPZDBS',
        'SKYSKL',
        'FWQDZ',
        'BMBBBH',
    ];

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->getConfigs($this->invoice);
    }

    /**
     * 发票校验
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

    /**
     * 开具入口
     * @return bool
     * @throws BadRequestHttpException
     */
    public function applyInvoice()
    {
        try {
            $headParams = $this->invoiceHeadParams($this->invoice);
            /** @var array $postData */
            $postData = $this->generateSignal($headParams, self::INVOICE_APPLY);
            $url = self::$FWQDZ . self::INVOICE_APPLY;
            \Yii::info(['大象开票请求日志' => ['headParams' => $headParams, 'data' => $postData, 'url' => $url]]);
            $res = CurlHelper::http_post($url, $postData);
            $ret = json_decode($res, TRUE);
            Yii::info(['大象开票请求响应日志' => ['res' => $res, 'ret' => $ret]]);
            if (
                !isset($ret['responseStatus']) ||
                (
                    isset($ret['responseStatus']['code']) &&
                    $ret['responseStatus']['code'] != '0000'
                )
            ) {
                throw new ServerErrorHttpException('服务异常，请稍后重试！');
            }
            $ret = json_decode(base64_decode($ret['responseData']['content']), TRUE);
            if (!$ret) throw new ServerErrorHttpException('服务异常，请稍后重试！');
            if ($ret['STATUS_CODE'] == '010000') {
                return FALSE;
            } else {
                throw new BadRequestHttpException($ret['STATUS_MESSAGE']);
            }
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * 发票作废
     * @return bool
     * @throws \Throwable
     */
    public function nullifyInvoice()
    {
        $invoiceData = $this->invoice;
        try {
            $headParams = json_encode([
                'ZFPCH' => ToolsHelper::createGuid(),
                'FP_DM' => $invoiceData->ticket_code,
                'FPQH' => $invoiceData->ticket_sn,
                'FPZH' => $invoiceData->ticket_sn,
                'ZFLX' => $invoiceData->is_red == InvoiceRule::BLUE_INVOICE ? self::DEPRECATE_TYPE_BLUE : self::DEPRECATE_TYPE_RED,//0-空白废票  1-正数废票  2-负数废票
//                'ZFYY' => $invoiceData->remark,
            ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
            /** @var array $postData */
            $postData = $this->generateSignal($headParams, self::INVOICE_NULLIFY);
            $url = self::$FWQDZ . self::INVOICE_NULLIFY;
            \Yii::info(['大象作废发票请求日志' => ['headParams' => $headParams, 'data' => $postData, 'url' => $url]]);
            $res = CurlHelper::http_post($url, $postData);
            $ret = json_decode($res, TRUE);
            Yii::info(['大象作废发票请求响应日志' => ['res' => $res, 'ret' => $ret]]);
            if (
                !isset($ret['responseStatus']) ||
                (
                    isset($ret['responseStatus']['code']) &&
                    $ret['responseStatus']['code'] != '0000'
                )
            ) {
                throw new ServerErrorHttpException('服务异常，请稍后重试！');
            }
            $ret = json_decode(base64_decode($ret['responseData']['content']), TRUE);
            if (!$ret) throw new ServerErrorHttpException('服务异常，请稍后重试！');
            if ($ret['STATUS_CODE'] == '040000') {
                return TRUE;
            } elseif ($ret['STATUS_CODE'] == '040002') {
                throw new BadRequestHttpException('发票作废失败！');
            }
        } catch (BadRequestHttpException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Exception $e) {
            throw new ServerErrorHttpException($e->getMessage());
        }
    }

    /**
     * 发票开具结果数据获取
     * @return bool|mixed
     * @throws \Throwable
     */
    public function checkInvoice()
    {
        $invoiceData = $this->invoice;
        try {
            $headParams = json_encode([
                'FPLX' => self::PAPER_INVOICE,
                'DDQQPCH' => $invoiceData->attachedInfo->serial_no,
            ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
            /** @var array $postData */
            $postData = $this->generateSignal($headParams, self::INVOICE_CHECK);
            $url = self::$FWQDZ . self::INVOICE_CHECK;
            \Yii::info(['大象查询发票状态请求日志' => ['headParams' => $headParams, 'data' => $postData, 'url' => $url]]);
            $res = CurlHelper::http_post($url, $postData);
            $ret = json_decode($res, TRUE);
            Yii::info(['大象查询发票状态请求响应日志' => ['res' => $res, 'ret' => $ret]]);
            if (
                !isset($ret['responseStatus']) ||
                (
                    isset($ret['responseStatus']['code']) &&
                    $ret['responseStatus']['code'] != '0000'
                )
            ) {
                throw new ServerErrorHttpException('服务异常，请稍后重试！');
            }
            $ret = json_decode(base64_decode($ret['responseData']['content']), TRUE);
            if (!$ret) throw new ServerErrorHttpException('服务异常，请稍后重试！');
            switch ($ret['STATUS_CODE']) {
                case '020111': //开具中
                    return FALSE;
                case '020000': //开具成功需要回写代码号码日期
                    self::deleteApplyInvoiceList($invoiceData);
                    return $ret;
                default: //开具失败
                    throw new BadRequestHttpException($ret['COMMON_INVOICE_INFOS'][0]['STATUS_MESSAGE']);
            }
        } catch (BadRequestHttpException $e) {
            throw new BadRequestHttpException($e->getMessage());
        } catch (\Exception $e) {
            Yii::error(['查询发票状态接口异常' => [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getLine(),
            ]]);
            throw new ServerErrorHttpException($e->getMessage());
        }
    }

    public function printInvoice()
    {
        throw new BadRequestHttpException("该渠道暂不支持打印！");
    }

    /**
     * @param InvoiceSave $invoiceData
     * @throws InvalidConfigException
     */
    public function getConfigs($invoiceData)
    {
        $propValues = TaxSystemPropValue::findAll(['tax_point_id' => $invoiceData->tax_point_id ?? $invoiceData->attachedInfo->tax_point_id]);
        foreach ($propValues as $value) {
            if ($value->tax_system_prop_id == 21) self::$KPJH = $value->prop_value ?? '';
            if ($value->tax_system_prop_id == 22) self::$KPZDBS = $value->prop_value ?? '';
            if ($value->tax_system_prop_id == 23) self::$SKYSKL = $value->prop_value ?? '';
            if ($value->tax_system_prop_id == 24) self::$FWQDZ = $value->prop_value ?? '';
            if ($value->tax_system_prop_id == 25) self::$BMBBBH = $value->prop_value ?? '';
        }
        foreach (self::$constConfig as $item) {
            if (self::$$item === NULL) {
                throw new InvalidConfigException('开票点配置错误！');
            }
        }
    }

    /**
     * 获取请求头部信息
     *
     * 1.对参数按字母排序
     * 2.拼接请求字符串 注意：1、“参数值”为原始值而非url 编码后的值。2、若输入参数中包含下划线，则需要将其转换为“.”
     * 3.拼接签名原文字符串
     * 4.生成签名串
     *
     * @param string $content 参数
     * @param string $method 请求方法
     * @param string $type http请求类型
     *
     * @return array
     */
    private function generateSignal($content, $method, $type = 'POST')
    {
        $encryptedStr = '';
        $signalArr = [
            'SecretId' => self::$KPZDBS,  //标识用户身份的SecretId
            'Timestamp' => time(),  //时间戳
            'Nonce' => rand(10000, 99999), //随机正数
            'encryptCode' => self::ENCRYPT_CODE,//加密标识 0: 不加密; 1:加密
            'zipCode' => self::ZIP_CODE, //压缩标识; 0:不压缩; 1: 压缩
            'content' => base64_encode($content), //业务请求参数
        ];
        ksort($signalArr);
        foreach ($signalArr as $key => $value) {
            $value = str_replace('_', '.', $value);
            $encryptedStr .= "{$key}={$value}&";
        }
        $encryptedStr = rtrim($encryptedStr, '&');
        $encryptedStr = $type . substr(self::$FWQDZ, strpos(self::$FWQDZ, '//') + 2) . $method . '?' . $encryptedStr;
        Yii::info(['加密字符串日志' => ['string' => $encryptedStr, 'key' => self::$SKYSKL]]);
        $encryptedStr = hash_hmac('SHA1', $encryptedStr, self::$SKYSKL, TRUE);
        $Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encryptedStr));//文档留坑
        $signalArr['Signature'] = $Signature;
        return $signalArr;
    }

    /**
     * 开具发票参数信息
     * @param InvoiceModel $invoiceData
     * @return string
     */
    private function invoiceHeadParams($invoiceData)
    {
        $headParams = [
            'COMMON_ORDER_BATCH' => [
                'DDQQPCH' => $invoiceData->attachedInfo->serial_no,
                //'NSRSBH' => !YII_ENV_PROD ? '150001194112132161' : $invoiceData->seller_company_tax_code,
                'NSRSBH' => $invoiceData->seller_company_tax_code,
                'SLDID' => -1,
                'KPJH' => self::$KPJH,
                'FPLX' => $invoiceData->invoice_type != InvoiceRule::ELECTRONIC_INVOICE ? self::PAPER_INVOICE : self::ELECTRONIC_INVOICE,
                'FPLB' => $invoiceData->invoice_type == InvoiceRule::ELECTRONIC_INVOICE ? 51 : $invoiceData->invoice_type,
            ],
        ];
        $orders = [
            'COMMON_ORDER_HEAD' => [
                'DDQQLSH' => $invoiceData->attachedInfo->serial_no,
                'NSRSBH' => $invoiceData->seller_company_tax_code,
                'NSRMC' => $invoiceData->seller_company_name,
                'KPLX' => $invoiceData->is_red == InvoiceRule::BLUE_INVOICE ? 0 : 1,
                'BMB_BBH' => self::$BMBBBH,
                'XSF_NSRSBH' => $invoiceData->seller_company_tax_code,
                'XSF_MC' => $invoiceData->seller_company_name,
                'XSF_DZ' => $invoiceData->taxEntity->address,
                'XSF_DH' => $invoiceData->taxEntity->mobile,
                'XSF_YH' => $invoiceData->seller_company_bank,
                'XSF_ZH' => $invoiceData->seller_company_bank_code,
                'GMF_QYLX' => $invoiceData->voucher->cst_type == '个人' ? '03' : '01',
                'GMF_NSRSBH' => $invoiceData->buyer_company_tax_code,
                'GMF_MC' => $invoiceData->buyer_company_name,
                'GMF_DZ' => $invoiceData->buyer_company_address,
                'GMF_GDDH' => $invoiceData->buyer_company_phone,
                'GMF_YH' => $invoiceData->buyer_company_bank,
                'GMF_ZH' => $invoiceData->buyer_company_bank_code,
                'KPR' => $invoiceData->drawer,
                'SKR' => $invoiceData->payee,
                'FHR' => $invoiceData->checker,
                'YFP_DM' => ($invoiceData->is_red == InvoiceRule::RED_INVOICE) ? $invoiceData->blueInvoice->ticket_code : '',     //"原发票代码",
                'YFP_HM' => ($invoiceData->is_red == InvoiceRule::RED_INVOICE) ? $invoiceData->blueInvoice->ticket_sn : '',     //"原发票号码",
                'QD_BZ' => 0,     //"原发票号码",
                'JSHJ' => number_format(round($invoiceData->amount_with_tax, 2), 2, ".", ""),
                'HJJE' => number_format(round($invoiceData->getTotalAmount(), 2), 2, ".", ""),
                'HJSE' => number_format(round($invoiceData->getTotalAmountTax(), 2), 2, ".", ""),
                'BZ' => $invoiceData->remark,
                'CHYY' => ($invoiceData->is_red == InvoiceRule::RED_INVOICE) ? $invoiceData->redNotice->remark : '',
                'TSCHBZ' => $invoiceData->is_red == InvoiceRule::RED_INVOICE ? 1 : 0,//红票才有该参数
                'DDH' => date('YmdHis') . '_' . $invoiceData->id,
                'THDH' => $invoiceData->is_red == InvoiceRule::RED_INVOICE ? substr($invoiceData->blueInvoice->attachedInfo->serial_no, strrpos($invoiceData->blueInvoice->attachedInfo->serial_no, '_') + 1, 16) : '',
            ],
        ];
        $count = count($invoiceData->invoiceDetails);
        foreach ($invoiceData->invoiceDetails as $detail) {
            if ($invoiceData->attachedInfo->is_pay_advance == InvoiceRule::PAY_ADVANCE_NO) {
                if ($detail['tax_rate'] != 0) {
                    $YHZCBS = 0;
                    $LSLBS = "";
                    $ZZSTSGL = "";
                } else {
                    $YHZCBS = 0;
                    $LSLBS = 3;
                    $ZZSTSGL = "";
                }
            } else {
                $YHZCBS = 1;
                $LSLBS = 2;
                $ZZSTSGL = "不征税";
            }
            $item = [
                'FPHXZ' => ($invoiceData->is_red == InvoiceRule::RED_INVOICE && $count >= 8) ? 6 : 0,
                'SPBM' => $detail->goods_tax_no,
                'YHZCBS' => $YHZCBS,
                'LSLBS' => $LSLBS,
                'ZZSTSGL' => $ZZSTSGL,
                'XMMC' => $detail->name,
                'GGXH' => $detail->specification,
                'DW' => $detail->unit,
                'XMSL' => !empty($detail->quantity) ? $this->getFloat2Precise($detail->quantity) : '',
                'XMDJ' => !empty($detail->price) ? $this->getFloat2Precise($detail->price) : '',
                'XMJE' => number_format(round($detail->amount, 2), 2, ".", ""),
                'HSBZ' => ($detail->tax_rate == 0) ? 1 : 0,
                'SL' => round($detail->tax_rate / 100, 4),
                'SE' => number_format(round($detail->amount_tax, 2), 2, ".", ""),
            ];
            $orders['ORDER_INVOICE_ITEMS'][] = $item;
        }
        $headParams['COMMON_ORDERS'][] = $orders;
        return json_encode($headParams, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
    }

    /**
     * 写入申请开票表列表
     * @param InvoiceModel $invoice
     * @throws ServerErrorHttpException
     */
    public static function saveApplyInvoiceList(InvoiceModel $invoice)
    {
        $tenantCode = InvoiceTools::getTenantCode();
        $applyInvoice = ApplyInvoiceList::findOne([
            'tenant_code' => $tenantCode,
            'serial_no' => $invoice->attachedInfo->serial_no,
        ]);
        if (!$applyInvoice) {
            $applyInvoice = new ApplyInvoiceList();
        }
        $applyInvoice->tenant_code = $tenantCode;
        $applyInvoice->invoice_id = $invoice->id;
        $applyInvoice->serial_no = $invoice->attachedInfo->serial_no;
        $applyInvoice->invoice_no = $invoice->attachedInfo->invoice_no;
        $applyInvoice->status = InvoiceModel::INVOICING;
        $applyInvoice->query_count = $applyInvoice->query_count ? ($applyInvoice->query_count + 1) : 0;
        $applyInvoice->save();
    }

    /**
     * 删除申请开票表列表数据
     * @param InvoiceModel $invoice
     * @throws \Throwable
     */
    public static function deleteApplyInvoiceList(InvoiceModel $invoice)
    {
        $tenantCode = InvoiceTools::getTenantCode();
        ApplyInvoiceList::deleteAll([
            'tenant_code' => $tenantCode,
            'serial_no' => $invoice->attachedInfo->serial_no,
        ]);
    }

    /**
     * @param $number
     * @param int $floatPrecise
     * @param int $intPrecise
     * @return false|float|string
     */
    private function getFloat2Precise($number, $floatPrecise = 8, $intPrecise = 2)
    {
        $round = round($number, $floatPrecise);
        return (int)$round == $round ? number_format($round, $intPrecise, ".", "") : $round;
    }
}