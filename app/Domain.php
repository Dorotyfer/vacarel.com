<?php
declare(strict_types=1);

final class Domain
{
    public function __construct(private PDO $db, private array $user) {}
    public function allowed(string $module, bool $write=false): bool
    {
        $role=$this->user['role'];
        if($module==='setup')return !$write && in_array($role,['admin','supervisor'],true);
        if($module==='numbering')return in_array($role,['admin','supervisor','caja','contador'],true)&&(!$write||in_array($role,['admin','supervisor'],true));
        if($module==='reports')return !$write && in_array($role,['admin','supervisor','compras','deposito','caja','contador'],true);
        if($module==='billing_settings')return in_array($role,['admin','supervisor'],true);
        if($module==='sales')return in_array($role,['admin','supervisor','caja','contador'],true) && (!$write || $role!=='contador');
        if($module==='collaborators') return $role==='admin';
        if(in_array($role,['admin','supervisor'],true)) return true;
        $map=['compras'=>['products','suppliers'], 'deposito'=>['products','suppliers','inventory','receipts','movements'], 'contador'=>['products','customers','suppliers','inventory','movements','audit'], 'vendedor'=>[], 'caja'=>['customers'], 'repartidor'=>[]];
        if (!in_array($module,$map[$role]??[],true)) return false;
        if(!$write) return true;
        return ($role==='compras' && in_array($module,['products','suppliers'],true)) || ($role==='deposito' && $module==='receipts');
    }
    public function authorize(string $module, bool $write=false): void
    { if(!$this->allowed($module,$write)) throw new DomainException('No tenés permiso para esta operación.',403); }
    private function text(array $data,string $key,int $max=190,bool $required=true): string
    {
        $value=trim((string)($data[$key]??''));
        if(($required && $value==='') || mb_strlen($value)>$max) throw new DomainException('Revisá el campo '.$key.'.',422);
        return $value;
    }
    private function integer(mixed $value,int $min,int $max): int
    {
        if(filter_var($value,FILTER_VALIDATE_INT)===false || (int)$value<$min || (int)$value>$max) throw new DomainException('Cantidad o valor entero fuera de rango.',422);
        return (int)$value;
    }
    private function amount(mixed $value): string
    {
        $s=(string)$value;
        if(!preg_match('/^\d{1,12}(\.\d{1,4})?$/D',$s)) throw new DomainException('Importe inválido. Usá un número positivo sin separador de miles.',422);
        return $s;
    }
    private function audit(string $action,string $resource,int $id,mixed $before,array $after): void
    {
        $s=$this->db->prepare('INSERT INTO audit(user_id,action,resource,resource_id,before_data,after_data,created_at) VALUES(?,?,?,?,?,?,?)');
        $s->execute([$this->user['id'],$action,$resource,$id,$before===null?null:json_encode($before,JSON_UNESCAPED_UNICODE),json_encode($after,JSON_UNESCAPED_UNICODE),gmdate('c')]);
    }
    public function list(string $module): array
    {
        $this->authorize($module);
        return match($module) {
            'products'=>$this->products(),
            'customers','suppliers'=>$this->rows('SELECT * FROM parties WHERE kind=? ORDER BY name',[$module]),
            'inventory'=>$this->rows("SELECT l.*,p.name AS product,s.name AS supplier,CASE WHEN l.expiry IS NOT NULL AND l.expiry < ? THEN 'expired' ELSE l.state END AS effective_state FROM lots l JOIN products p ON p.id=l.product_id JOIN parties s ON s.id=l.supplier_id ORDER BY l.expiry IS NULL,l.expiry,l.id",[date('Y-m-d')]),
            'movements'=>$this->db->query('SELECT m.*,p.name AS product,l.code AS lot,u.name AS actor FROM movements m JOIN products p ON p.id=m.product_id JOIN lots l ON l.id=m.lot_id JOIN users u ON u.id=m.user_id ORDER BY m.id DESC LIMIT 500')->fetchAll(),
            'collaborators'=>$this->db->query('SELECT id,name,username,role,active,commission_rate,commission_basis FROM users ORDER BY name')->fetchAll(),
            'audit'=>$this->db->query('SELECT a.*,u.name AS actor FROM audit a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 300')->fetchAll(),
            default=>throw new DomainException('Sección inexistente.',404)
        };
    }
    private function rows(string $sql,array $args): array { $s=$this->db->prepare($sql);$s->execute($args);return $s->fetchAll(); }
    private function products(): array
    {
        return $this->rows("SELECT p.*,COALESCE(st.physical,0) AS stock,COALESCE(r.reserved,0) AS reserved,
            CASE WHEN COALESCE(st.apt,0)>COALESCE(r.apt_reserved,0) THEN COALESCE(st.apt,0)-COALESCE(r.apt_reserved,0) ELSE 0 END AS available
            FROM products p
            LEFT JOIN (SELECT product_id,SUM(quantity) AS physical,SUM(CASE WHEN state='available' AND (expiry IS NULL OR expiry>=?) THEN quantity ELSE 0 END) AS apt FROM lots GROUP BY product_id) st ON st.product_id=p.id
            LEFT JOIN (SELECT a.product_id,SUM(a.quantity) AS reserved,SUM(CASE WHEN l.state='available' AND (l.expiry IS NULL OR l.expiry>=?) THEN a.quantity ELSE 0 END) AS apt_reserved FROM sale_allocations a JOIN sales s ON s.id=a.sale_id JOIN lots l ON l.id=a.lot_id WHERE s.status='invoiced' GROUP BY a.product_id) r ON r.product_id=p.id ORDER BY p.name",[date('Y-m-d'),date('Y-m-d')]);
    }
    public function save(string $module,array $data): int
    {
        $this->authorize($module,true);
        if($module==='products') {
            $table='products';
            $fields=['code'=>$this->text($data,'code',80),'name'=>$this->text($data,'name'),'box_size'=>$this->integer($data['box_size']??1,1,100000),'price'=>$this->amount($data['price']??0),'min_stock'=>$this->integer($data['min_stock']??0,0,100000000),'tax'=>$this->integer($data['tax']??10,0,10),'track_expiry'=>$this->integer($data['track_expiry']??1,0,1),'active'=>$this->integer($data['active']??1,0,1)];
            if(!in_array($fields['tax'],[0,5,10],true)) throw new DomainException('IVA inválido.',422);
        } elseif(in_array($module,['customers','suppliers'],true)) {
            $table='parties';$fields=['kind'=>$module,'name'=>$this->text($data,'name'),'document'=>$this->text($data,'document',80,false),'phone'=>$this->text($data,'phone',80,false),'address'=>$this->text($data,'address',255,false),'credit_limit'=>$this->amount($data['credit_limit']??0),'credit_days'=>$this->integer($data['credit_days']??0,0,3650),'active'=>$this->integer($data['active']??1,0,1)];
        } elseif($module==='collaborators') {
            $table='users'; $fields=['name'=>$this->text($data,'name'),'username'=>$this->text($data,'username'),'role'=>$this->text($data,'role',30),'active'=>$this->integer($data['active']??1,0,1),'commission_rate'=>$this->amount($data['commission_rate']??0),'commission_basis'=>$this->text($data,'commission_basis',20)];
            if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,39}$/D',$fields['username']) || !in_array($fields['role'],['admin','supervisor','compras','deposito','vendedor','caja','repartidor','contador'],true) || !in_array($fields['commission_basis'],['invoiced','collected'],true) || bccomp($fields['commission_rate'],'100',4)>0) throw new DomainException('Revisá el usuario (3 a 40 letras, números, punto, guion o guion bajo), rol y comisión.',422);
            if(!empty($data['password'])) {
                if(mb_strlen($data['password'])<12) throw new DomainException('La contraseña debe tener al menos 12 caracteres.',422);
                $fields['password']=(string)$data['password'];
            } elseif(empty($data['id'])) throw new DomainException('Ingresá una contraseña inicial.',422);
            if((int)($data['id']??0)===(int)$this->user['id'] && ($fields['active']!==1 || $fields['role']!==$this->user['role'])) throw new DomainException('No podés desactivar tu acceso ni cambiar tu propio rol.',422);
        } else throw new DomainException('Operación inexistente.',404);
        $id=(int)($data['id']??0);
        $this->db->beginTransaction();
        try {
            $before=null;
            if($id) {
                $before=$this->rows("SELECT * FROM $table WHERE id=?",[$id])[0]??null;
                if(!$before || ($table==='parties' && $before['kind']!==$module)) throw new DomainException('Registro inexistente.',404);
                $sql="UPDATE $table SET ".implode(',',array_map(fn($k)=>"$k=?",array_keys($fields))).' WHERE id=?';
                $this->db->prepare($sql)->execute([...array_values($fields),$id]);
            } else {
                $sql="INSERT INTO $table (".implode(',',array_keys($fields)).') VALUES('.implode(',',array_fill(0,count($fields),'?')).')';
                $this->db->prepare($sql)->execute(array_values($fields));$id=(int)$this->db->lastInsertId();
            }
            unset($fields['password']);if($before) unset($before['password']);
            $this->audit($before?'update':'create',$module,$id,$before,$fields);
            $this->db->commit();return $id;
        } catch(Throwable $e) {$this->db->rollBack();throw $e;}
    }
    public function receive(array $data): int
    {
        $this->authorize('receipts',true);
        $key=$this->text($data,'key',100); $hash=hash('sha256',json_encode($data));
        $qty=$this->integer($data['quantity']??0,1,1000000);
        if(!in_array($data['presentation']??'',['unit','box'],true)) throw new DomainException('Presentación inválida.',422);
        $cost=$this->amount($data['cost']??'');$reference=$this->text($data,'reference');$code=$this->text($data,'lot_code',100);
        $expiry=$data['expiry']??null;
        if($expiry!==null && $expiry!=='') {
            $date=DateTimeImmutable::createFromFormat('!Y-m-d',$expiry);
            if(!$date || $date->format('Y-m-d')!==$expiry || $expiry<date('Y-m-d')) throw new DomainException('Vencimiento inválido o ya vencido.',422);
        } else $expiry=null;
        $this->db->beginTransaction();
        try {
            $lock=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
            $product=$this->rows('SELECT * FROM products WHERE id=?'.$lock,[(int)($data['product_id']??0)])[0]??null;
            if(!$product || !$product['active']) throw new DomainException('Producto inexistente o inactivo.',422);
            $previous=$this->rows('SELECT * FROM operations WHERE operation_key=?',[$key])[0]??null;
            if($previous) {
                if($previous['payload_hash']!==$hash) throw new DomainException('La clave de operación ya se usó con otros datos.',409);
                $this->db->commit(); return (int)$previous['result_id'];
            }
            $supplier=$this->rows("SELECT id FROM parties WHERE id=? AND kind='suppliers' AND active=1",[(int)($data['supplier_id']??0)])[0]??null;
            if(!$supplier) throw new DomainException('Seleccioná un proveedor activo.',422);
            if($product['track_expiry'] && !$expiry) throw new DomainException('Este producto requiere vencimiento.',422);
            $factor=$data['presentation']==='box'?(int)$product['box_size']:1;
            $base=$qty*$factor;
            if($base>100000000) throw new DomainException('La cantidad supera el máximo permitido.',422);
            $unitCost=bcdiv($cost,(string)$factor,4);
            $old=(string)($this->rows('SELECT COALESCE(SUM(quantity),0) AS qty FROM lots WHERE product_id=?',[$product['id']])[0]['qty']);
            $average=bcdiv(bcadd(bcmul($old,(string)$product['average_cost'],8),bcmul((string)$base,$unitCost,8),8),bcadd($old,(string)$base,0),4);
            $purchaseTax=$this->integer($data['tax']??$product['tax'],0,10);
            if(!in_array($purchaseTax,[0,5,10],true))throw new DomainException('IVA de compra inválido.',422);
            $this->db->prepare('INSERT INTO lots(product_id,supplier_id,code,expiry,quantity,unit_cost,purchase_tax,reference,received_at,user_id) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$product['id'],$supplier['id'],$code,$expiry,$base,$unitCost,$purchaseTax,$reference,gmdate('c'),$this->user['id']]);
            $id=(int)$this->db->lastInsertId();
            $this->db->prepare("INSERT INTO movements(product_id,lot_id,quantity,type,reference,created_at,user_id) VALUES(?,?,?,'receipt',?,?,?)")->execute([$product['id'],$id,$base,$reference,gmdate('c'),$this->user['id']]);
            $this->db->prepare('UPDATE products SET average_cost=?,tax=? WHERE id=?')->execute([$average,$purchaseTax,$product['id']]);
            $this->db->prepare('INSERT INTO operations(operation_key,payload_hash,result_id) VALUES(?,?,?)')->execute([$key,$hash,$id]);
            $this->audit('receive','lots',$id,null,['quantity'=>$base,'reference'=>$reference,'unit_cost'=>$unitCost,'purchase_tax'=>$purchaseTax]);
            $this->db->commit();return $id;
        } catch(Throwable $e) {$this->db->rollBack();throw $e;}
    }
    public function dashboard(): array
    {
        $restricted=!$this->allowed('inventory');
        if($restricted) return ['restricted'=>true];
        $today=date('Y-m-d');$until=date('Y-m-d',strtotime('+30 days'));
        return [
            'products'=>(int)$this->db->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn(),
            'units'=>(int)$this->db->query('SELECT COALESCE(SUM(quantity),0) FROM lots')->fetchColumn(),
            'low_stock'=>array_map(fn($p)=>['name'=>$p['name'],'min_stock'=>$p['min_stock'],'stock'=>$p['available']],array_values(array_filter($this->products(),fn($p)=>$p['active'] && $p['available']<$p['min_stock']))),
            'expiring'=>$this->rows('SELECT l.code,l.expiry,l.quantity,p.name FROM lots l JOIN products p ON p.id=l.product_id WHERE l.quantity>0 AND l.expiry<=? ORDER BY l.expiry',[$until]),
            'recent'=>$this->rows('SELECT m.quantity,m.created_at,p.name FROM movements m JOIN products p ON p.id=m.product_id ORDER BY m.id DESC LIMIT 6',[])
        ];
    }
}

