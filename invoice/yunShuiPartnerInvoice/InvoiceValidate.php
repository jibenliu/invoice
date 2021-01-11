<?php

namespace common\components\invoice\yunShuiPartnerInvoice;

use yii\base\Model;
use common\models\InvoiceRule;
use common\components\invoice\DetailTrait;
use common\validators\ByteStringValidator;

class InvoiceValidate extends Model
{
    use DetailTrait;

    public static $detailRules = [
        [['specification', 'goods_tax_no'], ByteStringValidator::class, 'toEncoding' => 'GBK', 'min' => 1, 'max' => 40],
        ['unit', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 20],
        ['name', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 100],
    ];

    public function rules()
    {
        return [
            ['amount_with_tax', 'double'],
            [['receiver', 'receiver_phone', 'receiver_email'], 'required', 'when' => function ($model) {
                return $model->invoice_type == InvoiceRule::ELECTRONIC_INVOICE;
            }],
            [['buyer_company_name', 'seller_company_name','buyer_company_address', 'seller_company_address','buyer_company_bank','seller_company_bank'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'min' => 4,
                'max' => 100
            ],
            [['buyer_company_bank_code', 'seller_company_bank_code'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'max' => 30],
            [['checker', 'payee', 'drawer'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'min' => 1,
                'max' => 8
            ],
            ['receiver', ByteStringValidator::class, 'toEncoding' => 'GBK', 'min' => 1, 'max' => 10],
            ['receiver_phone', 'match', 'pattern' => '/^1[3456789]\d{9}$/'],
            ['receiver_email', ByteStringValidator::class, 'toEncoding' => 'GBK', 'min' => 4, 'max' => 50],
            ['receiver_email', 'email'],
            ['seller_company_phone', ByteStringValidator::class, 'toEncoding' => 'GBK', 'min' => 7, 'max' => 20],
            ['buyer_company_bank', ByteStringValidator::class, 'toEncoding' => 'GBK', 'min' => 8, 'max' => 30],
            [['buyer_company_name', 'buyer_company_address'], 'match', 'pattern' => '/[^\<\>\"\'\&%\/]/', 'message' => '购方名称和购方地址不能包含特殊字符'],
            ['remark', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 130],
            ['details', 'checkDetail', 'skipOnEmpty' => FALSE]
        ];
    }
}