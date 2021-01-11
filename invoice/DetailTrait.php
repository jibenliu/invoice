<?php

namespace common\components\invoice;

use common\models\Invoice;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;

/**
 * Trait DetailTrait
 * @package common\components\invoice
 */
trait DetailTrait
{
    public $details;
    public $ticket_code;
    public $ticket_sn;
    public $buyer_company_name;
    public $buyer_company_address;
    public $buyer_company_bank;
    public $buyer_company_bank_code;
    public $seller_company_name;
    public $seller_company_phone;
    public $seller_company_address;
    public $seller_company_bank;
    public $seller_company_bank_code;
    public $checker;
    public $payee;
    public $receiver;
    public $receiver_phone;
    public $receiver_email;
    public $drawer;
    public $remark;
    public $amount_with_tax;

    public $invoice_type;

    public static $detailAttributeLabels = [
        'name' => '货物或应税劳务、服务名称',
        'goods_tax_name' => '商品名称',
        'goods_tax_no' => '商品编码',
        'price' => '单价',
        'tax_rate' => '税率',
        'amount_tax' => '税额',
        'amount' => '不含税金额',
        'quantity' => '数量',
        'specification' => '规格型号',
        'item_short_name' => '商品简称',
        'unit' => '单位',
    ];

    public static $commonDetailRules = [
        [['name', 'goods_tax_name', 'goods_tax_no', 'price', 'tax_rate', 'amount_tax', 'amount', 'quantity', 'price', 'item_short_name'], 'trim'],
        [
            [
                'goods_tax_no',
                'goods_tax_name',
                'item_short_name'
            ],
            'required',
            'message' => '{attribute}不能为空，请设置商品编码'
        ],
        [['name', 'amount_tax', 'amount'], 'required'],
        ['tax_rate', 'default', 'value' => 0],
        ['quantity', 'match', 'pattern' => '/^-?(0|[1-9][0-9]*)(\.[0-9]{0,8})?$/', 'message' => '数量小数点后不能超过8位'],
        ['price', 'match', 'pattern' => '/^(0|[1-9][0-9]*)(\.[0-9]{0,8})?$/', 'message' => '单价小数点后不能超过8位且不能为负数'],
        ['tax_rate', 'match', 'pattern' => '/^(0|[1-9][0-9]*)(\.[0-9]{0,2})?$/', 'message' => '税率小数点后不能超过2位'],
        ['tax_rate', 'number', 'max' => '100'],
        [['amount', 'amount_tax'], 'match', 'pattern' => '/^-?(0|[1-9][0-9]*)(\.[0-9]{0,2})?$/'],
    ];

    public function attributeLabels()
    {
        return array_merge([
            'details' => '发票详情',
            'receiver' => '收票人姓名',
            'receiver_phone' => '收票人手机号',
            'receiver_email' => '收票人邮箱',
        ], (new Invoice())->attributeLabels());
    }

    /**
     * @param $attribute
     * @throws InvalidConfigException
     */
    public function checkDetail($attribute)
    {
        $amountWithTax = 0;
        foreach ($this->$attribute as $index => $detail) {
            $model = new DynamicModel($detail);
            $model = $model::validateData($detail, array_merge(self::$commonDetailRules, static::$detailRules));
            if ($model->hasErrors()) {
                $message = $msg = current($model->getFirstErrors());
                foreach (self::$detailAttributeLabels as $key => $value) {
                    $itemKey = $model->generateAttributeLabel($key);
                    if (preg_match('/^' . $itemKey . '/i', $message)) {
                        $msg = preg_replace('/^' . $itemKey . '/i', $value, $message);
                        break;
                    }
                }
                $this->addError($attribute, '第' . ($index + 1) . '行记录' . $msg);
                break;
            } else {
                if (!empty($detail['price']) && !empty($detail['quantity']) && round(bcmul($detail['price'], $detail['quantity'], 10), 2) != round($detail['amount'], 2)) {
                    $this->addError($attribute, '第' . ($index + 1) . '行记录单价*数量不等于不含税金额！');
                    break;
                }
                if (abs(bcmul($detail['amount'], ($detail['tax_rate'] / 100), 10) - $detail['amount_tax']) > 0.01) {
                    $this->addError($attribute, '第' . ($index + 1) . '行记录不含税金额*税率不等于税额！');
                    break;
                }
                $amountWithTax += $detail['amount'] + $detail['amount_tax'];
            }
        }
        if (round($amountWithTax, 2) != round($this->amount_with_tax, 2)) {
            $this->addError($attribute, '开票金额和开票详情金额之和不符！');
        }
    }
}