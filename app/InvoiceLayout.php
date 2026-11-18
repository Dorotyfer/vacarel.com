<?php
declare(strict_types=1);
final class InvoiceLayout
{
    public static function validate(array $snapshot,array $settings): void
    {
        $mmPerCharacter=$settings['font_size']*25.4/72*0.6;
        $values=['date'=>date('d/m/Y',strtotime($snapshot['date'])),'name'=>$snapshot['customer']['name'],'ruc'=>$snapshot['customer']['document'],'address'=>$snapshot['customer']['address'],'phone'=>$snapshot['customer']['phone'],'term'=>$snapshot['condition']==='credit'?$snapshot['credit_days'].' días':'','total'=>number_format((float)$snapshot['total'],0,',','.'),'iva5'=>number_format((float)$snapshot['iva5'],0,',','.'),'iva10'=>number_format((float)$snapshot['iva10'],0,',','.')];
        $check=function(string $key,string $value)use($settings,$mmPerCharacter):void {
            $capacity=(int)floor($settings['fields'][$key][2]*$settings['width']/100/$mmPerCharacter);
            if(mb_strwidth($value)>$capacity || str_contains($value,"\n") || str_contains($value,"\r"))throw new DomainException('El dato del campo '.$key.' no cabe en el formulario. Ajustá su ancho o el tamaño de letra antes de emitir.',422);
        };
        foreach($values as $key=>$value)$check($key,$value);
        foreach($snapshot['lines'] as $line)foreach(['code','quantity','unit_price','exempt','vat5_gross','vat10_gross'] as $key){$value=(string)$line[$key];if($value!==''&&in_array($key,['unit_price','exempt','vat5_gross','vat10_gross'],true))$value=number_format((float)$value,0,',','.');$check($key,$value);}
        self::rows($snapshot['lines'],$settings);
    }
    public static function rows(array $lines,array $settings): array
    {
        $mmPerCharacter=$settings['font_size']*25.4/72*0.6;
        $capacity=max(1,(int)floor(($settings['fields']['description'][2]*$settings['width']/100)/$mmPerCharacter));
        $result=[];
        foreach($lines as $line) {
            $name=preg_replace('/\s+/u',' ',trim($line['name']));$parts=[];
            while(mb_strlen($name)>$capacity) {
                $chunk=mb_substr($name,0,$capacity);$space=mb_strrpos($chunk,' ');$cut=$space!==false && $space>0?$space:$capacity;
                $parts[]=mb_substr($name,0,$cut);$name=ltrim(mb_substr($name,$cut));
            }
            $parts[]=$name;
            foreach($parts as $index=>$part)$result[]=['slot'=>count($result),'description'=>$part,'line'=>$index===0?$line:null];
        }
        if(count($result)>$settings['max_lines'])throw new DomainException('Las descripciones ocupan más filas que las disponibles en este formulario. Dividí la venta antes de emitir.',422);
        return $result;
    }
}
