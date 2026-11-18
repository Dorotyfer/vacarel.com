<?php
declare(strict_types=1);

final class InvoiceMath
{
    public static function round(string $amount): string {return bcadd($amount,'0.5',0);}
    public static function calculate(array $items,bool $included=true): array
    {
        if(!$items || count($items)>50) throw new DomainException('Agregá productos a la venta.',422);
        $out=['lines'=>[],'exempt'=>'0','vat5_gross'=>'0','vat10_gross'=>'0','total'=>'0'];
        foreach($items as $item) {
            if(filter_var($item['quantity']??null,FILTER_VALIDATE_INT)===false || (int)$item['quantity']<1 || (int)$item['quantity']>1000000) throw new DomainException('Cantidad de venta inválida.',422);
            $price=(string)($item['price']??'');
            if(!preg_match('/^\d{1,12}(\.\d{1,4})?$/D',$price)) throw new DomainException('Precio inválido.',422);
            $tax=filter_var($item['tax']??null,FILTER_VALIDATE_INT);
            if(!in_array($tax,[0,5,10],true)) throw new DomainException('IVA inválido: elegí exento, 5 % o 10 %.',422);
            $gross=self::round($included?$price:bcdiv(bcmul($price,(string)(100+$tax),8),'100',8));
            $amount=bcmul($gross,(string)$item['quantity'],0);
            $column=$tax===0?'exempt':($tax===5?'vat5_gross':'vat10_gross');
            $line=['product_id'=>(int)($item['product_id']??0),'code'=>(string)($item['code']??''),'name'=>(string)($item['name']??''),'quantity'=>(int)$item['quantity'],'unit_price'=>$gross,'tax'=>$tax,'exempt'=>'','vat5_gross'=>'','vat10_gross'=>'','amount'=>$amount];
            $line[$column]=$amount;$out['lines'][]=$line;
            $out[$column]=bcadd($out[$column],$amount,0);$out['total']=bcadd($out['total'],$amount,0);
        }
        $out['iva5']=self::round(bcdiv($out['vat5_gross'],'21',8));
        $out['iva10']=self::round(bcdiv($out['vat10_gross'],'11',8));
        return $out;
    }
}
