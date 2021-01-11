<?php

namespace common\components\invoice\baiWang;

use yii\base\Model;
use common\components\invoice\DetailTrait;
use common\validators\ByteStringValidator;

class InvoiceValidate extends Model
{
    use DetailTrait;

    public static $detailRules = [
        ['specification', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 36],
        ['unit', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 14],
        ['name', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 92],
    ];

    public function rules()
    {
        return [
            [['ticket_code', 'ticket_sn', 'amount_with_tax'], 'required'],
            [[
                'buyer_company_name',
                'buyer_company_address',
                'buyer_company_bank',
                'seller_company_name',
                'seller_company_address',
                'seller_company_bank'
            ], ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 100],
            [['checker', 'payee', 'drawer'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'max' => 16],
            ['remark', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 138],
            ['details', 'checkDetail', 'skipOnEmpty' => FALSE],
        ];
    }
}