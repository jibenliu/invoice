## 不同渠道开票相关服务demo
##### 里面只包含不同渠道的特殊化校验，不包含通用型的校验
######实例化

    $invoice = Invoice::findOne(1);

    //$invoice = InvoiceSave::findOne(1);<br/>

    $dispatch = Yii::createObject(
        ['class'=>'common\components\invoice\InvoiceDispatch'],
        [$invoice]
    );

######校验票面

    $dispatch->validateInvoice();

######开票

    $dispatch->applyInvoice();
    
######查验发票开具状态

    $dispath->checkInvoice();
    
######作废发票
    $dispatch->nullifyInvoice();
    
#####如果需要新增开票渠道请先参考InvoiceDispatch类，并且继承AbstractInvoice抽象类
