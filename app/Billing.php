<?php
declare(strict_types=1);
require_once __DIR__.'/InvoiceMath.php';
require_once __DIR__.'/InvoiceLayout.php';

final class Billing
{
    public function __construct(private PDO $db,private array $user) {}
    public function authorize(bool $admin=false): void
    {
        if(!in_array($this->user['role'],$admin?['admin','supervisor']:['admin','supervisor','caja','contador'],true)) throw new DomainException('No tenés permiso para facturación.',403);
    }
    private function writeAccess(): void { $this->authorize();if($this->user['role']==='contador')throw new DomainException('Acceso de consulta.',403); }
    private function rows(string $sql,array $args=[]): array { $s=$this->db->prepare($sql);$s->execute($args);return $s->fetchAll(PDO::FETCH_ASSOC); }
    private function lock(): string {return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';}
    private function log(string $action,int $id,array $data): void
    {
        $this->db->prepare('INSERT INTO audit(user_id,action,resource,resource_id,before_data,after_data,created_at) VALUES(?,?,?,?,NULL,?,?)')->execute([$this->user['id'],$action,'sales',$id,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),gmdate('c')]);
    }
    public static function defaults(): array
    {
        $fields=[
            'date'=>[20,22,35],'name'=>[26,25.7,68],'ruc'=>[13,29.4,39],'address'=>[15,33,49],
            'phone'=>[77,29.4,19],'cash_x'=>[81.5,23,3],'credit_x'=>[94,23,3],'term'=>[91,26.5,7],
            'code'=>[6,39,12],'quantity'=>[18,39,6],'description'=>[25,39,29],'unit_price'=>[55,39,10],
            'exempt'=>[66,39,10],'vat5_gross'=>[77,39,9],'vat10_gross'=>[87,39,9],
            'total'=>[74,77,22],'iva5'=>[30,81,15],'iva10'=>[57,81,15]
        ];
        return ['width'=>216,'height'=>140,'offset_x'=>0,'offset_y'=>0,'font_size'=>9,'line_step'=>4,'max_lines'=>8,'fields'=>$fields,'timbrado'=>'','establishment'=>'001','point'=>'001','start'=>0,'end'=>0,'next'=>0,'valid_from'=>'','valid_until'=>'','price_mode'=>'included','fiscal_checked'=>false,'calibrated'=>false,'enabled'=>false,'_revision'=>0];
    }
    public function settings(): array
    {
        $this->authorize();$r=$this->rows('SELECT * FROM billing_settings WHERE id=1')[0]??null;
        if(!$r)return self::defaults();$s=json_decode($r['payload'],true,64,JSON_THROW_ON_ERROR);$s['_revision']=(int)$r['revision'];return $s;
    }
    private function validDate(string $s): bool {$date=DateTimeImmutable::createFromFormat('!Y-m-d',$s);return $date && $date->format('Y-m-d')===$s;}
    public function saveSettings(array $input): void
    {
        $this->authorize(true);$s=self::defaults();
        foreach(['width'=>[80,450],'height'=>[80,450],'offset_x'=>[-20,20],'offset_y'=>[-20,20],'font_size'=>[6,14],'line_step'=>[2,10],'max_lines'=>[1,20]] as $key=>[$min,$max]) {
            if(!isset($input[$key]) || !is_numeric($input[$key]) || !is_finite((float)$input[$key]) || $input[$key]<$min || $input[$key]>$max) throw new DomainException('Revisá tamaño, márgenes y cantidad de líneas.',422);
            $s[$key]=(float)$input[$key];
        }
        $s['max_lines']=(int)$s['max_lines'];
        foreach($s['fields'] as $field=>$coords) {
            $v=$input['fields'][$field]??null;
            if(!is_array($v)||count($v)!==3)throw new DomainException('Falta la posición de '.$field.'.',422);
            foreach($v as $number)if(!is_numeric($number)||!is_finite((float)$number)||$number<0||$number>100)throw new DomainException('Posición fuera del papel.',422);
            $v=array_map('floatval',array_values($v));if($v[2]<=0||$v[0]+$v[2]>100)throw new DomainException('Un campo excede el ancho del papel.',422);
            $s['fields'][$field]=$v;
        }
        if($s['fields']['description'][1]+($s['max_lines']-1)*$s['line_step']>69) throw new DomainException('Las líneas invaden el apartado de totales. Reducí filas o separación.',422);
        foreach(['timbrado','establishment','point','valid_from','valid_until','price_mode'] as $key) $s[$key]=trim((string)($input[$key]??''));
        foreach(['start','end','next'] as $key){$s[$key]=filter_var($input[$key]??0,FILTER_VALIDATE_INT);if($s[$key]===false||$s[$key]<0||$s[$key]>9999999)throw new DomainException('Numeración inválida.',422);}
        foreach(['fiscal_checked','calibrated','enabled'] as $key)$s[$key]=filter_var($input[$key]??false,FILTER_VALIDATE_BOOLEAN);
        if(!in_array($s['price_mode'],['included','excluded'],true))throw new DomainException('Definí si los precios incluyen IVA.',422);
        if(!preg_match('/^\d{3}$/D',$s['establishment'])||!preg_match('/^\d{3}$/D',$s['point']))throw new DomainException('Establecimiento y punto requieren tres dígitos.',422);
        if($s['enabled'] && (!$s['fiscal_checked']||!$s['calibrated']||!preg_match('/^\d{1,12}$/D',$s['timbrado'])||!$this->validDate($s['valid_from'])||!$this->validDate($s['valid_until'])||$s['valid_from']>$s['valid_until']||$s['start']<1||$s['end']<$s['start']||$s['next']<$s['start']||$s['next']>$s['end']))throw new DomainException('Para habilitar emisión, completá autorización, rango, vigencia y confirmá validación fiscal y prueba física.',422);
        $this->db->beginTransaction();
        try {
            $old=$this->rows('SELECT * FROM billing_settings WHERE id=1'.$this->lock())[0]??null;
            if((int)($input['_revision']??0)!==(int)($old['revision']??0))throw new DomainException('La configuración cambió. Volvé a abrirla.',409);
            if($s['timbrado']!=='') {
                $prefix=$s['timbrado'].'/'.$s['establishment'].'/'.$s['point'].'/';
                $used=$this->rows('SELECT fiscal_key FROM sales WHERE fiscal_key LIKE ?',[$prefix.'%']);
                foreach($used as $document)if($s['next']<=(int)substr($document['fiscal_key'],strlen($prefix)))throw new DomainException('No se puede retroceder a una numeración ya emitida.',409);
            }
            unset($s['_revision']);$json=json_encode($s,JSON_THROW_ON_ERROR);
            if($old)$this->db->prepare('UPDATE billing_settings SET payload=?,revision=revision+1 WHERE id=1')->execute([$json]);
            else $this->db->prepare('INSERT INTO billing_settings(id,payload,revision) VALUES(1,?,1)')->execute([$json]);
            $this->log('billing_settings',0,['enabled'=>$s['enabled'],'next'=>$s['next']]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function catalog(): array
    {
        $this->authorize();return ['numbering'=>$this->numbering(),'customers'=>$this->rows("SELECT id,name,document,address,phone,credit_days FROM parties WHERE kind='customers' AND active=1 ORDER BY name"),'products'=>$this->rows('SELECT id,code,name,price,tax FROM products WHERE active=1 ORDER BY name')];
    }
    public function numbering():array
    {
        $s=$this->settings();$out=array_intersect_key($s,array_flip(['timbrado','establishment','point','start','end','next','valid_from','valid_until','fiscal_checked','calibrated','enabled','_revision']));
        $out['formatted_next']=$s['next']>0?$this->formatNumber((int)$s['next'],$s):'';
        return $out;
    }
    public function saveNumbering(array $input):void
    {
        $this->authorize(true);$s=$this->settings();
        if((int)($input['_revision']??-1)!==$s['_revision'])throw new DomainException('La numeración cambió. Volvé a cargarla.',409);
        foreach(['start','end','next'] as $k)if(filter_var($input[$k]??null,FILTER_VALIDATE_INT)===false||(int)$input[$k]<1)throw new DomainException('Indicá el inicio, final y próximo número físico.',422);
        if((int)$input['end']<(int)$input['start']||(int)$input['next']<(int)$input['start']||(int)$input['next']>(int)$input['end'])throw new DomainException('El próximo número debe estar dentro del rango autorizado.',422);
        foreach(['timbrado','establishment','point','start','end','next','valid_from','valid_until','fiscal_checked'] as $k){if(!array_key_exists($k,$input))throw new DomainException('Faltan datos de numeración.',422);$s[$k]=$input[$k];}
        if(!$this->validDate((string)$s['valid_from'])||!$this->validDate((string)$s['valid_until'])||$s['valid_from']>$s['valid_until']||!preg_match('/^\d{1,12}$/D',(string)$s['timbrado']))throw new DomainException('Revisá el timbrado y las fechas de vigencia.',422);
        $this->saveSettings($s);
    }
    private function formatNumber(int $n,array $s):string{return $s['establishment'].'-'.$s['point'].'-'.str_pad((string)$n,7,'0',STR_PAD_LEFT);}
    private function physicalNumber(array $data,array $s):int
    {
        $raw=trim((string)($data['physical_number']??''));if($raw==='')return (int)$s['next'];
        if(preg_match('/^(\d{3})-(\d{3})-(\d{7})$/D',$raw,$m)){if($m[1]!==$s['establishment']||$m[2]!==$s['point'])throw new DomainException('El formulario pertenece a otra serie.',422);$raw=$m[3];}
        if(!preg_match('/^\d{1,7}$/D',$raw))throw new DomainException('Cargá un número de factura válido.',422);
        $n=(int)$raw;if($n<1||$n<(int)$s['start']||$n>(int)$s['end']||$n<(int)$s['next'])throw new DomainException('El número está usado, es anterior al próximo o está fuera del rango autorizado.',409);
        $reason=trim((string)($data['number_reason']??''));if(mb_strlen($reason)>190||($n>(int)$s['next']&&$reason===''))throw new DomainException('Indicá por qué se utiliza un formulario posterior al propuesto (máximo 190 caracteres).',422);
        return $n;
    }
    private function quoteToken(array $snapshot,int $revision,int $number,array $data):string
    {return hash('sha256',json_encode([$snapshot,$revision,$number,trim((string)($data['number_reason']??''))],JSON_THROW_ON_ERROR));}
    private function prepare(array $data,array $settings,bool $lock=false): array
    {
        $condition=$data['condition']??'';
        if(!in_array($condition,['cash','credit'],true))throw new DomainException('Seleccioná contado o crédito.',422);
        $days=$condition==='credit'?filter_var($data['credit_days']??null,FILTER_VALIDATE_INT):0;
        if($days===false || ($condition==='credit'&&($days<1||$days>3650)))throw new DomainException('Indicá el plazo de crédito en días.',422);
        $customer=$this->rows("SELECT * FROM parties WHERE id=? AND kind='customers' AND active=1".($lock?$this->lock():''),[(int)($data['customer_id']??0)])[0]??null;
        if(!$customer)throw new DomainException('Seleccioná un cliente activo.',422);
        foreach(['name','document','address','phone'] as $field)if(trim($customer[$field])==='')throw new DomainException('Completá nombre, RUC, dirección y teléfono del cliente antes de facturar.',422);
        $lines=$data['lines']??[];
        if(!is_array($lines)||!$lines||count($lines)>$settings['max_lines'])throw new DomainException('La cantidad de productos excede las filas de la factura.',422);
        $items=[];$seen=[];
        foreach($lines as $line) {
            $id=(int)($line['product_id']??0);if(isset($seen[$id]))throw new DomainException('Unificá las cantidades del producto repetido.',422);$seen[$id]=true;
            $product=$this->rows('SELECT * FROM products WHERE id=? AND active=1'.($lock?$this->lock():''),[$id])[0]??null;
            if(!$product)throw new DomainException('Hay un producto inexistente o inactivo.',422);
            $items[]=['product_id'=>$id,'code'=>$product['code'],'name'=>$product['name'],'quantity'=>$line['quantity']??0,'price'=>$product['price'],'tax'=>$line['tax']??$product['tax']];
        }
        $totals=InvoiceMath::calculate($items,$settings['price_mode']==='included');
        InvoiceLayout::rows($totals['lines'],$settings);
        $snapshot=[...$totals,'customer'=>array_intersect_key($customer,array_flip(['id','name','document','address','phone'])),'condition'=>$condition,'credit_days'=>$days,'date'=>date('Y-m-d')];
        InvoiceLayout::validate($snapshot,$settings);
        return $snapshot;
    }
    public function preview(array $data): array
    {
        $this->authorize();$settings=$this->settings();$result=$this->prepare($data,$settings);
        $number=$this->physicalNumber($data,$settings);
        $result['quote_token']=$this->quoteToken($result,$settings['_revision'],$number,$data);
        $result['expected_number']=$this->formatNumber($number,$settings);
        $result['can_issue']=$settings['enabled'] && $settings['calibrated'] && $settings['fiscal_checked'] && date('Y-m-d')>=$settings['valid_from'] && date('Y-m-d')<=$settings['valid_until'] && $number>0 && $number>=$settings['start'] && $number<=$settings['end'];
        return $result;
    }
    public function confirm(array $data): int
    {
        $this->writeAccess();$key=trim((string)($data['key']??''));
        if($key===''||strlen($key)>100)throw new DomainException('Falta la clave de operación.',422);
        $payload=$data;unset($payload['key']);$hash=hash('sha256',json_encode($payload,JSON_THROW_ON_ERROR));
        $this->db->beginTransaction();
        try {
            $row=$this->rows('SELECT * FROM billing_settings WHERE id=1'.$this->lock())[0]??null;
            $existing=$this->rows('SELECT * FROM sales WHERE operation_key=?',[$key])[0]??null;
            if($existing){if($existing['payload_hash']!==$hash)throw new DomainException('La operación ya fue utilizada con otros datos.',409);$this->db->commit();return (int)$existing['id'];}
            $settings=$row?json_decode($row['payload'],true,64,JSON_THROW_ON_ERROR):self::defaults();
            if(!$settings['enabled']||!$settings['calibrated']||!$settings['fiscal_checked'])throw new DomainException('Configurá y habilitá la factura preimpresa antes de confirmar ventas.',422);
            if(date('Y-m-d')<$settings['valid_from']||date('Y-m-d')>$settings['valid_until']||$settings['next']>$settings['end'])throw new DomainException('La autorización está fuera de vigencia o agotada.',422);
            $snap=$this->prepare($data,$settings,true);$customerId=$snap['customer']['id'];
            $physicalNumber=$this->physicalNumber($data,$settings);
            $quoteToken=$this->quoteToken($snap,(int)$row['revision'],$physicalNumber,$data);
            if(!hash_equals($quoteToken,(string)($data['quote_token']??'')))throw new DomainException('Cambió la información de la venta. Volvé a revisar precios, IVA y total antes de confirmar.',409);
            if($snap['condition']==='credit') {
                $c=$this->rows('SELECT credit_limit FROM parties WHERE id=?',[$customerId])[0];
                $debt=$this->rows('SELECT COALESCE(SUM(balance),0) AS balance FROM sales WHERE customer_id=?',[$customerId])[0]['balance'];
                $overdue=$this->rows('SELECT id FROM sales WHERE customer_id=? AND balance>0 AND due_on<?',[$customerId,date('Y-m-d')]);
                if($overdue || bccomp(bcadd((string)$debt,$snap['total'],0),(string)$c['credit_limit'],0)>0)throw new DomainException('El cliente tiene deuda vencida o excede su límite de crédito.',422);
            }
            $allocations=[];
            foreach($snap['lines'] as $line) {
                $remaining=$line['quantity'];
                $lots=$this->rows("SELECT * FROM lots WHERE product_id=? AND state='available' AND quantity>0 AND (expiry IS NULL OR expiry>=?) ORDER BY expiry IS NULL,expiry,received_at,id".$this->lock(),[$line['product_id'],date('Y-m-d')]);
                foreach($lots as $lot) {
                    $reserved=(int)$this->rows("SELECT COALESCE(SUM(a.quantity),0) AS qty FROM sale_allocations a JOIN sales s ON s.id=a.sale_id WHERE a.lot_id=? AND s.status='invoiced'",[$lot['id']])[0]['qty'];
                    $take=min($remaining,max(0,(int)$lot['quantity']-$reserved));
                    if($take){$allocations[]=[$line['product_id'],$lot['id'],$take,$lot['unit_cost']];$remaining-=$take;}
                    if(!$remaining)break;
                }
                if($remaining)throw new DomainException('Stock disponible insuficiente para '.$line['name'].'.',409);
            }
            $number=$this->formatNumber($physicalNumber,$settings);
            if(($data['expected_number']??'')!==$number)throw new DomainException('Cambió el próximo número físico. Revisá la venta y el formulario antes de confirmar.',409);
            $fiscalKey=$settings['timbrado'].'/'.$settings['establishment'].'/'.$settings['point'].'/'.$physicalNumber;
            $snap['number']=$number;$snap['template']=$settings;
            $this->db->prepare("INSERT INTO sales(operation_key,payload_hash,customer_id,user_id,number,fiscal_key,issued_on,condition_name,credit_days,due_on,total,balance,snapshot,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$key,$hash,$customerId,$this->user['id'],$number,$fiscalKey,$snap['date'],$snap['condition'],$snap['credit_days'],date('Y-m-d',strtotime('+'.$snap['credit_days'].' days')),$snap['total'],$snap['total'],json_encode($snap,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),gmdate('c')]);
            $id=(int)$this->db->lastInsertId();foreach($allocations as $allocation)$this->db->prepare('INSERT INTO sale_allocations(sale_id,product_id,lot_id,quantity,unit_cost) VALUES(?,?,?,?,?)')->execute([$id,...$allocation]);
            if($physicalNumber>(int)$settings['next'])$this->log('number_gap',$id,['from'=>(int)$settings['next'],'to'=>$physicalNumber-1,'reason'=>trim((string)$data['number_reason'])]);
            $settings['next']=$physicalNumber+1;$this->db->prepare('UPDATE billing_settings SET payload=?,revision=revision+1 WHERE id=1')->execute([json_encode($settings,JSON_THROW_ON_ERROR)]);
            $this->log('invoice',$id,['number'=>$number,'total'=>$snap['total'],'taxes'=>array_column($snap['lines'],'tax','product_id')]);$this->db->commit();return $id;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function list(): array { $this->authorize();return $this->rows('SELECT s.id,s.number,s.issued_on,s.condition_name,s.total,s.balance,s.status,p.name AS customer FROM sales s JOIN parties p ON p.id=s.customer_id ORDER BY s.id DESC LIMIT 500'); }
    public function document(int $id): array
    {
        $this->authorize();$row=$this->rows('SELECT * FROM sales WHERE id=?',[$id])[0]??null;if(!$row)throw new DomainException('Factura inexistente.',404);
        $row['snapshot']=json_decode($row['snapshot'],true,64,JSON_THROW_ON_ERROR);unset($row['payload_hash'],$row['operation_key']);
        $row['attempts']=$this->rows('SELECT id,result,reason,created_at FROM print_attempts WHERE sale_id=? ORDER BY id',[$id]);return $row;
    }
    public function dispatch(int $id): void
    {
        $this->writeAccess();$this->db->beginTransaction();
        try {
            $s=$this->rows('SELECT * FROM sales WHERE id=?'.$this->lock(),[$id])[0]??null;
            if(!$s)throw new DomainException('Factura inexistente.',404);
            if($s['status']==='dispatched'){$this->db->commit();return;}
            foreach($this->rows('SELECT * FROM sale_allocations WHERE sale_id=? ORDER BY product_id,lot_id',[$id]) as $a) {
                $lot=$this->rows('SELECT * FROM lots WHERE id=?'.$this->lock(),[$a['lot_id']])[0];
                if($lot['state']!=='available'||($lot['expiry'] && $lot['expiry']<date('Y-m-d'))||$lot['quantity']<$a['quantity'])throw new DomainException('Un lote reservado ya no está apto para despachar.',409);
                $this->db->prepare('UPDATE lots SET quantity=quantity-? WHERE id=?')->execute([$a['quantity'],$lot['id']]);
                $this->db->prepare("INSERT INTO movements(product_id,lot_id,quantity,type,reference,created_at,user_id) VALUES(?,?,?,'dispatch',?,?,?)")->execute([$a['product_id'],$lot['id'],-$a['quantity'],$s['number'],gmdate('c'),$this->user['id']]);
            }
            $this->db->prepare("UPDATE sales SET status='dispatched' WHERE id=?")->execute([$id]);$this->log('dispatch',$id,[]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function printIntent(int $id,string $reason,string $key=''): int
    {
        $this->writeAccess();if(strlen($reason)>255||strlen($key)>100)throw new DomainException('Motivo demasiado largo.',422);
        $key=$key?:bin2hex(random_bytes(16));$this->db->beginTransaction();
        try {
            $sale=$this->rows('SELECT id FROM sales WHERE id=?'.$this->lock(),[$id]);if(!$sale)throw new DomainException('Factura inexistente.',404);
            $previous=$this->rows('SELECT * FROM print_attempts WHERE request_key=?',[$key])[0]??null;
            if($previous){if((int)$previous['sale_id']!==$id||(int)$previous['user_id']!==(int)$this->user['id'])throw new DomainException('Intento de otra operación.',409);$this->db->commit();return (int)$previous['id'];}
            $count=$this->rows('SELECT id FROM print_attempts WHERE sale_id=?',[$id]);
            if($count){$this->authorize(true);if(trim($reason)==='')throw new DomainException('Indicá el motivo de la reimpresión.',422);}
            $this->db->prepare('INSERT INTO print_attempts(sale_id,user_id,request_key,reason,result,created_at) VALUES(?,?,?,?,?,?)')->execute([$id,$this->user['id'],$key,$reason,'unknown',gmdate('c')]);
            $attempt=(int)$this->db->lastInsertId();$this->log('print_attempt',$id,['attempt'=>$attempt,'reason'=>$reason]);$this->db->commit();return $attempt;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function printResult(int $id,string $result): void
    {
        $this->writeAccess();if(!in_array($result,['printed','failed','unknown'],true))throw new DomainException('Resultado inválido.',422);
        $attempt=$this->rows('SELECT * FROM print_attempts WHERE id=? AND user_id=?',[$id,$this->user['id']])[0]??null;
        if(!$attempt)throw new DomainException('Intento inexistente.',404);
        $this->db->beginTransaction();try{$this->db->prepare('UPDATE print_attempts SET result=? WHERE id=?')->execute([$result,$id]);$this->log('print_result',(int)$attempt['sale_id'],['attempt'=>$id,'result'=>$result]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
}
