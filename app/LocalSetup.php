<?php
declare(strict_types=1);
require_once __DIR__.'/Backup.php';
final class LocalSetup
{
    public function __construct(private PDO $db,private array $user){}
    public static function backupDirectory():string
    {
        $dir=getenv('VACAREL_BACKUP_DIR')?:((getenv('LOCALAPPDATA')?:dirname(__DIR__,4)).DIRECTORY_SEPARATOR.'Vacarel'.DIRECTORY_SEPARATOR.'Backups');
        $root=strtolower(str_replace('\\','/',dirname(__DIR__)));
        if(str_starts_with(strtolower(str_replace('\\','/',$dir)),$root.'/'))throw new RuntimeException('El respaldo debe guardarse fuera de la carpeta web.');
        return $dir;
    }
    public function status():array
    {
        if(!in_array($this->user['role'],['admin','supervisor'],true))throw new DomainException('No tenés permiso para revisar la preparación.',403);
        $checks=[];$add=static function(string $id,string $title,bool $ok,string $detail,?string $link=null)use(&$checks):void{$checks[]=['id'=>$id,'title'=>$title,'ok'=>$ok,'detail'=>$detail,'link'=>$link];};
        $extensions=['pdo_mysql','bcmath','mbstring','dom','xml','zlib'];$missing=array_values(array_filter($extensions,fn($e)=>!extension_loaded($e)));
        $add('runtime','Componentes del sistema',!$missing,$missing?'Faltan componentes: '.implode(', ',$missing):'PHP y sus componentes están disponibles.');
        $exportReady=is_file(__DIR__.'/vendor/autoload.php')&&is_file(__DIR__.'/../assets/fonts/Inter-Regular.ttf')&&(is_dir(__DIR__.'/cache')?is_writable(__DIR__.'/cache'):is_writable(__DIR__));
        $add('exports','Excel y PDF',$exportReady,$exportReady?'Bibliotecas, tipografía y carpeta de trabajo disponibles.':'Faltan bibliotecas, tipografía o permiso de escritura.','reports');
        $add('database','Conexión con la base de datos',true,'La conexión y las consultas respondieron correctamente.');
        foreach(['products'=>['Productos','products','SELECT COUNT(*) FROM products WHERE active=1'],'suppliers'=>['Proveedores','suppliers',"SELECT COUNT(*) FROM parties WHERE kind='suppliers' AND active=1"],'customers'=>['Clientes','customers',"SELECT COUNT(*) FROM parties WHERE kind='customers' AND active=1"],'stock'=>['Mercadería recibida','receipts','SELECT COALESCE(SUM(quantity),0) FROM lots']] as $id=>[$title,$link,$sql]){$count=(int)$this->db->query($sql)->fetchColumn();$add($id,$title,$count>0,$count>0?$count.' '.($id==='stock'?'unidades físicas registradas.':'registros activos.'):'Pendiente de cargar los datos reales de Vacarel.',$link);}
        $row=$this->db->query('SELECT payload FROM billing_settings WHERE id=1')->fetchColumn();$s=$row?json_decode($row,true,64,JSON_THROW_ON_ERROR):[];
        $today=date('Y-m-d');$enabled=!empty($s['enabled'])&&!empty($s['fiscal_checked'])&&!empty($s['calibrated'])&&($s['valid_from']??'9999')<=$today&&($s['valid_until']??'')>=$today&&($s['next']??0)>0&&($s['next']??0)<=($s['end']??0);
        $requirements=[];if(empty($s['fiscal_checked']))$requirements[]='confirmación de habilitación fiscal';if(($s['next']??0)<1)$requirements[]='próximo número físico';if(empty($s['timbrado'])||empty($s['valid_until']))$requirements[]='timbrado y vigencia';if(empty($s['calibrated']))$requirements[]='prueba de alineación';if(empty($s['enabled']))$requirements[]='activación de emisión';
        $add('billing','Facturación preimpresa',$enabled,$enabled?'Emisión habilitada según la configuración registrada.':('Pendiente: '.implode(', ',$requirements).'.'),'numbering');
        $files=glob(self::backupDirectory().DIRECTORY_SEPARATOR.'vacarel-*.json')?:[];rsort($files);$last=null;
        foreach($files as $f){try{$verified=Backup::verify($f);$last=$verified['manifest'];break;}catch(Throwable $e){continue;}}
        $recent=$last&&(time()-strtotime($last['created_at']))<172800;
        $add('backup','Respaldo local',$recent,$last?'Último respaldo: '.(new DateTimeImmutable($last['created_at']))->setTimezone(new DateTimeZone('America/Asuncion'))->format('d/m/Y H:i').'.':'Pendiente de crear el primer respaldo. Usá el acceso «Respaldar Vacarel».');
        return ['checks'=>$checks,'ready_for_full_operation'=>false,'pending_modules'=>['Órdenes y facturas de compra','Cobranzas y cuenta corriente aplicada','Pagos, caja y bancos','Reparto y entregas','Notas de crédito y anulaciones','Liquidación y pago de comisiones'],'backup_directory'=>self::backupDirectory()];
    }
}
