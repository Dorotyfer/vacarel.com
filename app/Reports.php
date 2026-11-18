<?php
declare(strict_types=1);

/** Read-only reports. Fiscal amounts always come from the issued snapshot. */
final class Reports
{
    private string $today;
    private const DEFINITIONS = [
        'inventory'=>['Existencias y valorización','Inventario',false,['product_id']],
        'expiries'=>['Lotes y vencimientos','Inventario',false,['product_id','supplier_id','lot','band']],
        'reorder'=>['Necesidades de reposición','Inventario',false,['product_id']],
        'movements'=>['Movimientos de stock','Inventario',true,['product_id']],
        'receipts'=>['Recepciones por proveedor','Inventario',true,['product_id','supplier_id']],
        'trace'=>['Trazabilidad de lotes','Inventario',false,['product_id','supplier_id','lot']],
        'sales'=>['Facturación por operación','Ventas y saldos',true,['customer_id','operator_id','condition','status']],
        'products'=>['Productos facturados','Ventas y saldos',true,['product_id','customer_id','operator_id','condition']],
        'vat'=>['Liquidación de IVA registrado','Ventas y saldos',true,['customer_id','operator_id','condition']],
        'balances'=>['Saldos y antigüedad','Ventas y saldos',false,['customer_id','band']],
        'margin'=>['Margen sobre lotes asignados','Ventas y saldos',true,['customer_id','operator_id','condition']],
        'pending'=>['Pendientes de despacho','Control operativo',false,['customer_id','operator_id']],
        'activity'=>['Actividad por operador','Control operativo',true,['operator_id']],
    ];
    public function __construct(private PDO $db, private array $user, ?string $today=null)
    { $this->today=$today??(new DateTimeImmutable('now',new DateTimeZone('America/Asuncion')))->format('Y-m-d'); }
    private function allowed(string $type): bool
    {
        if(!isset(self::DEFINITIONS[$type]))return false;
        $role=$this->user['role'];
        if(in_array($role,['admin','supervisor'],true))return true;
        $map=['contador'=>['inventory','expiries','reorder','movements','receipts','trace','sales','products','vat','balances','margin','pending'],
            'compras'=>['inventory','expiries','reorder','receipts','trace'],
            'deposito'=>['inventory','expiries','reorder','movements','receipts','trace'],
            'caja'=>['sales','products','vat','balances','pending']];
        return in_array($type,$map[$role]??[],true);
    }
    private function authorize(string $type): void
    {if(!$this->allowed($type))throw new DomainException('No tenés permiso para este informe.',403);}
    private function costs(): bool{return in_array($this->user['role'],['admin','supervisor','compras','contador'],true);}
    public function catalog(): array
    {
        $list=[];foreach(self::DEFINITIONS as $key=>[$title,$group,$period,$filters])if($this->allowed($key))$list[]=['id'=>$key,'title'=>$title,'group'=>$group,'period'=>$period,'filters'=>$filters];
        return $list;
    }
    private function query(string $sql,array $params=[]): array{$s=$this->db->prepare($sql);$s->execute($params);return $s->fetchAll(PDO::FETCH_ASSOC);}
    public function options(string $type): array
    {
        $this->authorize($type);$keys=self::DEFINITIONS[$type][3];$out=[];
        if(in_array('product_id',$keys,true))$out['product_id']=$this->query('SELECT id,code,name FROM products ORDER BY name');
        foreach(['customer_id'=>'customers','supplier_id'=>'suppliers'] as $key=>$kind)if(in_array($key,$keys,true))$out[$key]=$this->query('SELECT id,name FROM parties WHERE kind=? ORDER BY name',[$kind]);
        if(in_array('operator_id',$keys,true))$out['operator_id']=$this->query('SELECT id,name FROM users ORDER BY name');
        if(in_array('condition',$keys,true))$out['condition']=[['id'=>'cash','name'=>'Contado'],['id'=>'credit','name'=>'Crédito']];
        if(in_array('status',$keys,true))$out['status']=[['id'=>'invoiced','name'=>'Pendiente de despacho'],['id'=>'dispatched','name'=>'Despachada']];
        if(in_array('band',$keys,true))$out['band']=array_map(fn($b)=>['id'=>$b,'name'=>$b],$type==='balances'?['No vencido','1–30 días','31–60 días','61–90 días','Más de 90 días']:['Vencido','0–7 días','8–30 días','31–60 días','61–90 días','Más de 90 días','Sin vencimiento']);
        return $out;
    }
    private function date(string $s): string
    { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$s);if(!$d||$d->format('Y-m-d')!==$s)throw new DomainException('Revisá las fechas del informe.',422);return $s; }
    private function filters(string $type,array $input): array
    {
        $allowed=self::DEFINITIONS[$type][3];$period=self::DEFINITIONS[$type][2];if($period)$allowed=array_merge($allowed,['from','to']);$allowed[]='page';
        $f=[];foreach($input as $k=>$v){if(!is_scalar($v))throw new DomainException('Filtro inválido.',422);if(!in_array($k,$allowed,true))throw new DomainException('Este informe no admite el filtro '.$k.'.',422);if((string)$v!=='')$f[$k]=trim((string)$v);}
        foreach(['product_id','supplier_id','customer_id','operator_id','page'] as $key)if(isset($f[$key])&&(!ctype_digit($f[$key])||(int)$f[$key]<1||(int)$f[$key]>2147483647))throw new DomainException('Filtro numérico inválido.',422);
        if(isset($f['lot'])&&mb_strlen($f['lot'])>80)throw new DomainException('Lote demasiado largo.',422);
        foreach(['condition','status','band'] as $key)if(isset($f[$key])){ $values=array_column($this->options($type)[$key]??[],'id');if(!in_array($f[$key],$values,true))throw new DomainException('Opción de filtro inválida.',422); }
        if($period){$f['from']=$this->date($f['from']??substr($this->today,0,7).'-01');$f['to']=$this->date($f['to']??$this->today);if($f['from']>$f['to'])throw new DomainException('La fecha inicial debe ser anterior a la final.',422);}
        return $f;
    }
    private function utc(string $date,bool $next=false): string
    { $d=new DateTimeImmutable($date.' 00:00:00',new DateTimeZone('America/Asuncion'));if($next)$d=$d->modify('+1 day');return $d->setTimezone(new DateTimeZone('UTC'))->format('c'); }
    private function local(string $v): string{return (new DateTimeImmutable($v))->setTimezone(new DateTimeZone('America/Asuncion'))->format('Y-m-d H:i');}
    private function days(string $date): int{return (int)(new DateTimeImmutable($this->today))->diff(new DateTimeImmutable($date))->format('%r%a');}
    private static function rounded(string $v): string{return bcadd($v,bccomp($v,'0',4)<0?'-0.5':'0.5',0);}
    private static function integer(mixed $v): string{return bcadd((string)$v,'0',0);}
    private function lots(): array
    {return $this->query('SELECT l.*,p.name AS product,p.code AS product_code,p.average_cost,s.name AS supplier FROM lots l JOIN products p ON p.id=l.product_id LEFT JOIN parties s ON s.id=l.supplier_id ORDER BY p.name,l.expiry,l.id');}
    private function allocations(): array
    {return $this->query("SELECT a.*,s.status,s.number,s.snapshot FROM sale_allocations a JOIN sales s ON s.id=a.sale_id WHERE s.status IN ('invoiced','dispatched') ORDER BY a.id");}
    private function stocks(array $f): array
    {
        $reserved=[];foreach($this->allocations() as $a)if($a['status']==='invoiced')$reserved[$a['lot_id']]=($reserved[$a['lot_id']]??0)+(int)$a['quantity'];
        $stock=[];foreach($this->query('SELECT id,code,name,min_stock,average_cost,active FROM products ORDER BY name,id') as $p){if(isset($f['product_id'])&&(int)$f['product_id']!==(int)$p['id'])continue;$stock[$p['id']]=['code'=>$p['code'],'product'=>$p['name'],'physical'=>0,'reserved'=>0,'available'=>0,'unfit'=>0,'minimum'=>(int)$p['min_stock'],'active'=>(int)$p['active'],'cost'=>$p['average_cost']];}
        foreach($this->lots() as $l){if(!isset($stock[$l['product_id']]))continue;$s=&$stock[$l['product_id']];$q=(int)$l['quantity'];$r=$reserved[$l['id']]??0;$apt=$l['state']==='available'&&(!$l['expiry']||$l['expiry']>=$this->today);$s['physical']+=$q;$s['reserved']+=$r;if($apt)$s['available']+=max(0,$q-$r);else $s['unfit']+=$q;unset($s);}
        foreach($stock as &$s){if($this->costs())$s['value']=self::rounded(bcmul((string)$s['physical'],(string)$s['cost'],4));unset($s['cost']);}unset($s);return array_values($stock);
    }
    private function sales(array $f,bool $period=true): array
    {
        $where=["s.status IN ('invoiced','dispatched')"];$args=[];
        if($period){$where[]='s.issued_on>=? AND s.issued_on<=?';array_push($args,$f['from'],$f['to']);}
        foreach(['customer_id'=>'s.customer_id','operator_id'=>'s.user_id','condition'=>'s.condition_name','status'=>'s.status'] as $k=>$col)if(isset($f[$k])){$where[]="$col=?";$args[]=$f[$k];}
        $rows=$this->query('SELECT s.*,u.name AS operator FROM sales s LEFT JOIN users u ON u.id=s.user_id WHERE '.implode(' AND ',$where).' ORDER BY s.issued_on DESC,s.id DESC',$args);
        foreach($rows as &$r)$r['saved']=json_decode($r['snapshot'],true,512,JSON_THROW_ON_ERROR);unset($r);return $rows;
    }
    public function run(string $type,array $input=[],bool $paginate=true): array
    {
        // Keep reservations, physical quantities and totals in one consistent read.
        $ownTransaction=!$this->db->inTransaction();
        if($ownTransaction)$this->db->beginTransaction();
        try{$result=$this->build($type,$input,$paginate);if($ownTransaction)$this->db->commit();return $result;}
        catch(Throwable $e){if($ownTransaction&&$this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    private function filterLabels(string $type,array $f): array
    {
        $labels=['from'=>'Desde','to'=>'Hasta','product_id'=>'Producto','supplier_id'=>'Proveedor','customer_id'=>'Cliente','operator_id'=>'Operador','condition'=>'Condición','status'=>'Estado','lot'=>'Lote contiene','band'=>'Plazo / antigüedad'];
        $options=$this->options($type);$out=[];
        foreach($f as $key=>$value){$display=$value;if(isset($options[$key])){foreach($options[$key] as $o)if((string)$o['id']===(string)$value){$display=($o['code']??'')!==''?$o['code'].' · '.$o['name']:$o['name'];break;}}$out[$labels[$key]??$key]=$display;}
        return $out;
    }
    private function build(string $type,array $input,bool $paginate): array
    {
        $this->authorize($type);$f=$this->filters($type,$input);$rows=[];$columns=[];$note='';$money=[];
        if(in_array($type,['inventory','reorder'],true)){
            $columns=['code'=>'Código','product'=>'Producto','physical'=>'Físico (un.)','reserved'=>'Reservado (un.)','available'=>'Disponible apto (un.)','unfit'=>'No apto (un.)','minimum'=>'Mínimo (un.)'];
            $rows=$this->stocks($f);foreach($rows as &$r){$r['suggested']=max(0,$r['minimum']-$r['available']);if($type==='reorder'&&(!$r['active']||!$r['suggested']))$r=null;elseif($r!==null){unset($r['active']);if($type==='inventory')unset($r['suggested']);}}unset($r);$rows=array_values(array_filter($rows));
            if($type==='reorder'){$columns['suggested']='Reposición sugerida (un.)';foreach($rows as &$r)unset($r['value']);unset($r);}elseif($this->costs()){$columns['value']='Valor físico (Gs.)';$money[]='value';}
            $note='Existencias actuales. Disponible excluye reservas, vencidos y bloqueados. Valorización al costo promedio actual; la reposición es una sugerencia, sin órdenes de compra pendientes.';
        }elseif($type==='expiries'){
            $columns=['product'=>'Producto','lot'=>'Lote','supplier'=>'Proveedor','expiry'=>'Vencimiento','band'=>'Plazo','quantity'=>'Unidades físicas','state'=>'Estado'];
            foreach($this->lots() as $l){if((int)$l['quantity']<=0||!$this->lotMatch($l,$f))continue;$days=$l['expiry']?$this->days($l['expiry']):null;$band=$days===null?'Sin vencimiento':($days<0?'Vencido':($days<=7?'0–7 días':($days<=30?'8–30 días':($days<=60?'31–60 días':($days<=90?'61–90 días':'Más de 90 días')))));if(isset($f['band'])&&$f['band']!==$band)continue;$r=['product'=>$l['product'],'lot'=>$l['code'],'supplier'=>$l['supplier'],'expiry'=>$l['expiry'],'band'=>$band,'quantity'=>(int)$l['quantity'],'state'=>$this->lotState($l)];if($this->costs())$r['value']=self::rounded(bcmul((string)$l['quantity'],(string)$l['average_cost'],4));$rows[]=$r;}
            if($this->costs()){$columns['value']='Valor físico (Gs.)';$money[]='value';}$note='Estado actual por ingreso de lote. Un vencido conserva su existencia física. El valor usa costo promedio actual.';
        }elseif($type==='movements'){
            $columns=['date'=>'Fecha local','product'=>'Producto','lot'=>'Lote','kind'=>'Operación','opening'=>'Saldo previo del producto','quantity'=>'Variación (un.)','closing'=>'Saldo posterior del producto','reference'=>'Referencia','operator'=>'Operador'];
            $args=[$this->utc($f['from'])];$where='';if(isset($f['product_id'])){$where=' AND m.product_id=?';$args[]=$f['product_id'];}
            $opening=[];foreach($this->query('SELECT m.product_id,SUM(m.quantity) AS q FROM movements m WHERE m.created_at<?'.$where.' GROUP BY m.product_id',$args) as $r)$opening[$r['product_id']]=(int)$r['q'];
            $args=[$this->utc($f['from']),$this->utc($f['to'],true)];if(isset($f['product_id']))$args[]=$f['product_id'];
            foreach($this->query('SELECT m.*,p.name AS product,l.code AS lot,u.name AS operator FROM movements m JOIN products p ON p.id=m.product_id JOIN lots l ON l.id=m.lot_id LEFT JOIN users u ON u.id=m.user_id WHERE m.created_at>=? AND m.created_at<?'.$where.' ORDER BY m.created_at,m.id',$args) as $m){$q=(int)$m['quantity'];$before=$opening[$m['product_id']]??0;$opening[$m['product_id']]=$before+$q;$rows[]=['date'=>$this->local($m['created_at']),'product'=>$m['product'],'lot'=>$m['lot'],'kind'=>$m['type']==='receipt'?'Recepción':($m['type']==='dispatch'?'Despacho':$m['type']),'opening'=>$before,'quantity'=>$q,'closing'=>$before+$q,'reference'=>$m['reference'],'operator'=>$m['operator']];}
            $note='Saldo por producto reconstruido desde sus movimientos. Fechas en hora de Paraguay. Los saldos de distintos productos no se suman.';
        }elseif($type==='receipts'){
            $columns=['date'=>'Fecha local','supplier'=>'Proveedor','product'=>'Producto','lot'=>'Lote','quantity'=>'Unidades recibidas','reference'=>'Referencia','purchase_tax'=>'IVA cargado (%)'];
            foreach($this->lots() as $l){if(!$this->lotMatch($l,$f)||$l['received_at']<$this->utc($f['from'])||$l['received_at']>=$this->utc($f['to'],true))continue;$q=$this->query("SELECT COALESCE(SUM(quantity),0) AS q FROM movements WHERE lot_id=? AND type='receipt'",[$l['id']])[0]['q'];$r=['date'=>$this->local($l['received_at']),'supplier'=>$l['supplier'],'product'=>$l['product'],'lot'=>$l['code'],'quantity'=>(int)$q,'reference'=>$l['reference'],'purchase_tax'=>$l['purchase_tax']];if($this->costs()){$r['unit_cost']=(string)$l['unit_cost'];$r['value']=self::rounded(bcmul((string)$q,(string)$l['unit_cost'],4));}$rows[]=$r;}
            if($this->costs()){$columns['unit_cost']='Costo unitario (Gs.)';$columns['value']='Costo recibido (Gs.)';$money=['unit_cost','value'];}$note='Ingresos físicos originales, aunque la mercadería ya se haya despachado. No representa facturas de compra, deuda ni crédito fiscal.';
        }elseif($type==='trace'){
            $columns=['product'=>'Producto','lot'=>'Lote','supplier'=>'Proveedor','reference'=>'Origen','received'=>'Recepción local','expiry'=>'Vencimiento','physical'=>'Existencia del ingreso','invoice'=>'Factura destino','customer'=>'Cliente documentado','quantity'=>'Unidades asignadas','state'=>'Etapa'];$byLot=[];foreach($this->allocations() as $a)$byLot[$a['lot_id']][]=$a;
            foreach($this->lots() as $l){if(!$this->lotMatch($l,$f))continue;foreach($byLot[$l['id']]??[null] as $a){$s=$a?json_decode($a['snapshot'],true,512,JSON_THROW_ON_ERROR):[];$rows[]=['product'=>$l['product'],'lot'=>$l['code'],'supplier'=>$l['supplier'],'reference'=>$l['reference'],'received'=>$this->local($l['received_at']),'expiry'=>$l['expiry'],'physical'=>(int)$l['quantity'],'invoice'=>$a['number']??'—','customer'=>$s['customer']['name']??'—','quantity'=>$a?(int)$a['quantity']:0,'state'=>$a?($a['status']==='invoiced'?'Reservado':'Despachado'):$this->lotState($l)];}}
            $note='Recorrido documentado: origen, reserva y despacho. Despacho no confirma entrega al cliente. La existencia de un ingreso se repite si tiene varios destinos; no debe sumarse.';
        }elseif($type==='activity'){
            $columns=['date'=>'Fecha local','operator'=>'Operador','action'=>'Acción','resource'=>'Recurso','record'=>'Registro'];$args=[$this->utc($f['from']),$this->utc($f['to'],true)];$where='';if(isset($f['operator_id'])){$where=' AND a.user_id=?';$args[]=$f['operator_id'];}
            foreach($this->query('SELECT a.created_at,u.name AS operator,a.action,a.resource,a.resource_id FROM audit a LEFT JOIN users u ON u.id=a.user_id WHERE a.created_at>=? AND a.created_at<?'.$where.' ORDER BY a.created_at DESC,a.id DESC',$args) as $a)$rows[]=['date'=>$this->local($a['created_at']),'operator'=>$a['operator'],'action'=>$a['action'],'resource'=>$a['resource'],'record'=>$a['resource_id']];$note='Operaciones auditadas por operador; no incluye contraseñas ni contenido sensible de los cambios.';
        }else{
            $sales=$this->sales($f,!in_array($type,['balances','pending'],true));
            if($type==='products'){
                $columns=['code'=>'Código emitido','product'=>'Producto emitido','tax'=>'IVA (%)','quantity'=>'Unidades facturadas','total'=>'Facturado IVA incluido (Gs.)'];$money=['total'];$groups=[];
                foreach($sales as $s)foreach($s['saved']['lines'] as $l){if(isset($f['product_id'])&&(int)$f['product_id']!==(int)$l['product_id'])continue;$key=json_encode([$l['product_id'],$l['code'],$l['name'],$l['tax']]);if(!isset($groups[$key]))$groups[$key]=['code'=>$l['code'],'product'=>$l['name'],'tax'=>$l['tax'],'quantity'=>0,'total'=>'0'];$groups[$key]['quantity']+=(int)$l['quantity'];$groups[$key]['total']=bcadd($groups[$key]['total'],(string)$l['amount'],0);}$rows=array_values($groups);$note='Cantidades e importes facturados. Conserva nombres, precios e IVA de los documentos emitidos; no equivale a entregas ni cobros.';
            }else{
                $columns=['date'=>'Emisión','number'=>'Factura','customer'=>'Cliente al emitir','operator'=>'Operador emisor'];
                foreach($sales as $s){$v=$s['saved'];$r=['date'=>$s['issued_on'],'number'=>$s['number'],'customer'=>$v['customer']['name']??'','operator'=>$s['operator']];
                    if($type==='balances'){if(bccomp((string)$s['balance'],'0',0)<=0)continue;$days=max(0,-$this->days($s['due_on']));$band=$days===0?'No vencido':($days<=30?'1–30 días':($days<=60?'31–60 días':($days<=90?'61–90 días':'Más de 90 días')));if(isset($f['band'])&&$f['band']!==$band)continue;$r+=['due'=>$s['due_on'],'days'=>$days,'band'=>$band,'balance'=>self::integer($s['balance'])];
                    }elseif($type==='pending'){if($s['status']!=='invoiced')continue;$q=$this->query('SELECT COALESCE(SUM(quantity),0) AS q FROM sale_allocations WHERE sale_id=?',[$s['id']])[0]['q'];$r+=['quantity'=>(int)$q,'total'=>self::integer($v['total'])];
                    }elseif($type==='vat'){$r+=array_map(fn($n)=>self::integer($n),array_intersect_key($v,array_flip(['exempt','vat5_gross','vat10_gross','iva5','iva10','total'])));
                    }elseif($type==='margin'){$cost='0';foreach($this->query('SELECT quantity,unit_cost FROM sale_allocations WHERE sale_id=?',[$s['id']]) as $a)$cost=bcadd($cost,bcmul((string)$a['quantity'],(string)$a['unit_cost'],4),4);$cost=self::rounded($cost);$net=bcsub(bcsub((string)$v['total'],(string)$v['iva5'],0),(string)$v['iva10'],0);$r+=['net'=>$net,'cost'=>$cost,'margin'=>bcsub($net,$cost,0),'state'=>$s['status']==='invoiced'?'Reservado':'Despachado'];
                    }else{$r+=['condition'=>$s['condition_name']==='cash'?'Contado':'Crédito','state'=>$s['status']==='invoiced'?'Pendiente de despacho':'Despachada','total'=>self::integer($v['total'])];}$rows[]=$r;
                }
                if($type==='balances'){$columns+=['due'=>'Vencimiento','days'=>'Días de atraso','band'=>'Antigüedad actual','balance'=>'Saldo registrado (Gs.)'];$money=['balance'];$note='Saldos registrados actuales, al '.$this->today.'. No reconstruye saldos históricos. La condición contado no implica un cobro registrado; el circuito de cobranzas está pendiente.';}
                elseif($type==='pending'){$columns+=['quantity'=>'Unidades reservadas','total'=>'Facturado (Gs.)'];$money=['total'];$note='Facturas actuales con stock reservado y despacho pendiente. No confirma preparación, reparto ni entrega.';}
                elseif($type==='vat'){$columns+=['exempt'=>'Exentas (Gs.)','vat5_gross'=>'Gravadas 5 % (Gs.)','vat10_gross'=>'Gravadas 10 % (Gs.)','iva5'=>'IVA 5 % liquidado (Gs.)','iva10'=>'IVA 10 % liquidado (Gs.)','total'=>'Total facturado (Gs.)'];$money=['exempt','vat5_gross','vat10_gross','iva5','iva10','total'];$note='Importes de venta guardados al emitir, con IVA incluido. No calcula crédito fiscal, declaración impositiva ni notas de crédito aún no implementadas.';}
                elseif($type==='margin'){$columns+=['net'=>'Venta sin IVA (Gs.)','cost'=>'Costo asignado (Gs.)','margin'=>'Margen (Gs.)','state'=>'Etapa'];$money=['net','cost','margin'];$note='Venta neta de IVA menos costo conservado de los lotes asignados; no usa costo promedio histórico. Incluye reservas pendientes de despacho. Excluye gastos, comisiones y devoluciones aún no implementadas; no es utilidad neta.';}
                else{$columns+=['condition'=>'Condición','state'=>'Estado','total'=>'Facturado IVA incluido (Gs.)'];$money=['total'];$note='Documentos emitidos en el período. El operador emisor no representa al vendedor comercial. Facturación y cobranzas son indicadores diferentes.';}
            }
        }
        $summary=[['key'=>'records','label'=>'Registros encontrados','value'=>count($rows),'format'=>'number']];foreach($money as $key){if($key==='unit_cost')continue;$sum='0';foreach($rows as $r)$sum=bcadd($sum,(string)($r[$key]??0),0);$summary[]=['key'=>$key,'label'=>$columns[$key],'value'=>$sum,'format'=>'money'];}
        $unitKeys=match($type){'inventory'=>['physical','reserved','available','unfit'],'reorder'=>['suggested'],'expiries','receipts','products','pending'=>['quantity'],'movements'=>['quantity'],default=>[]};
        foreach($unitKeys as $key){$sum='0';foreach($rows as $r)$sum=bcadd($sum,(string)$r[$key],0);$summary[]=['key'=>$key,'label'=>$type==='movements'?'Variación neta (un.)':$columns[$key],'value'=>$sum,'format'=>'number'];}
        $count=count($rows);$pages=max(1,(int)ceil($count/50));$page=min((int)($f['page']??1),$pages);unset($f['page']);$filterLabels=$this->filterLabels($type,$f);
        $chart=[];if($type==='sales'){foreach($rows as $r)$chart[$r['date']]=bcadd($chart[$r['date']]??'0',$r['total'],0);ksort($chart);}
        return ['type'=>$type,'title'=>self::DEFINITIONS[$type][0],'as_of'=>$this->today,'generated_at'=>(new DateTimeImmutable('now',new DateTimeZone('America/Asuncion')))->format('Y-m-d H:i'),'period'=>self::DEFINITIONS[$type][2],'filters'=>$f,'filter_labels'=>$filterLabels,'columns'=>array_map(fn($key,$label)=>['key'=>$key,'label'=>$label,'format'=>in_array($key,$money,true)?'money':'text'],array_keys($columns),array_values($columns)),'rows'=>$paginate?array_slice($rows,($page-1)*50,50):$rows,'summary'=>$summary,'note'=>$note,'chart'=>array_map(fn($d,$v)=>['date'=>$d,'total'=>$v],array_keys($chart),array_values($chart)),'pagination'=>['page'=>$page,'pages'=>$pages,'total'=>$count,'size'=>50]];
    }
    private function lotMatch(array $l,array $f): bool
    {foreach(['product_id','supplier_id'] as $k)if(isset($f[$k])&&(int)$f[$k]!== (int)$l[$k])return false;return !isset($f['lot'])||stripos($l['code'],$f['lot'])!==false;}
    private function lotState(array $l): string
    {return $l['expiry']&&$l['expiry']<$this->today?'Vencido':(['available'=>'Apto','blocked'=>'Bloqueado','quarantine'=>'Cuarentena'][$l['state']]??$l['state']);}
    public static function csvCell(string $s): string
    {return !preg_match('/^-?\d+(\.\d+)?$/D',$s)&&preg_match('/^[\s\x00-\x20]*[=+\-@]/u',$s)?"'".$s:$s;}
}
