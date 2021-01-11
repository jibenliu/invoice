<?php

namespace common\components\invoice\daXiang;

use yii\base\Model;
use common\components\invoice\DetailTrait;
use common\validators\ByteStringValidator;

class InvoiceValidate extends Model
{
    use DetailTrait;

    public static $detailRules = [
        ['specification', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 40],
        ['unit', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 22],
        ['name', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 92],
    ];

    public function rules()
    {
        return [
	        ['amount_with_tax', 'double'],
            [['buyer_company_name', 'seller_company_name'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'max' => 100
            ],
            [['buyer_company_address', 'seller_company_address'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'max' => 80
            ],
            [['buyer_company_bank', 'seller_company_bank'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'max' => 70
            ],
            [['buyer_company_bank_code', 'seller_company_bank_code'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'max' => 30],
            [['checker', 'payee', 'drawer'],
                ByteStringValidator::class,
                'toEncoding' => 'GBK',
                'max' => 8
            ],
            ['remark', ByteStringValidator::class, 'toEncoding' => 'GBK', 'max' => 200],
            ['details', 'checkDetail', 'skipOnEmpty' => false]
        ];
    }
}