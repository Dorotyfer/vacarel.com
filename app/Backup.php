<?php
declare(strict_types=1);
final class Backup
{
    private array $connection;
    public function __construct(private array $config)
    {
        if(!preg_match('/dbname=([a-zA-Z][a-zA-Z0-9_]*)/',$config['dsn'],$m))throw new DomainException('Base de respaldo inválida.');
        $this->connection=['database'=>$m[1],'host'=>'127.0.0.1','port'=>3306,'user'=>$config['user'],'password'=>$config['password']];
        if(preg_match('/host=([^;]+)/',$config['dsn'],$h)&&!in_array($h[1],['127.0.0.1','localhost'],true))throw new DomainException('El respaldo local requiere una base local.');
        if(preg_match('/port=(\d+)/',$config['dsn'],$p))$this->connection['port']=(int)$p[1];
    }
    private function run(string $tool,array $args,array $credentials,?string $input=null):void
    {
        $bin=dirname(PHP_BINARY).'/../mysql/bin/'.$tool.(PHP_OS_FAMILY==='Windows'?'.exe':'');
        if(!is_file($bin))throw new RuntimeException('No se encontró la herramienta de respaldo de XAMPP.');
        $option=tempnam(sys_get_temp_dir(),'vacarel-cnf-');$quote=static fn($v)=>'"'.str_replace(['\\','"',"\n","\r"],['\\\\','\\"','\\n','\\r'],(string)$v).'"';
        $body="[client]\n";foreach(['host','port','user','password'] as $k)$body.=$k.'='.$quote($credentials[$k]??$this->connection[$k])."\n";
        file_put_contents($option,$body);@chmod($option,0600);
        try{
            $pipes=[];$proc=proc_open([$bin,'--defaults-extra-file='.$option,...$args],[0=>$input?['file',$input,'r']:['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
            if(!is_resource($proc))throw new RuntimeException('No se pudo iniciar el respaldo.');
            if(!$input)fclose($pipes[0]);stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($proc);
            if($code!==0){error_log('Vacarel backup: '.preg_replace('/password[^\s]*/i','credencial',$error));throw new RuntimeException('No se pudo completar el respaldo o la restauración de prueba.');}
        }finally{if(is_file($option))unlink($option);}
    }
    public function create(string $directory):string
    {
        if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))throw new RuntimeException('No se pudo preparar la carpeta de respaldos.');
        $directory=realpath($directory);$web=strtolower(str_replace('\\','/',dirname(__DIR__,2)));
        if(str_starts_with(strtolower(str_replace('\\','/',$directory)).'/',$web.'/'))throw new DomainException('Guardá los respaldos fuera de la carpeta web.');
        $prefix='vacarel-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));$sql=$directory.'/'.$prefix.'.sql';
        try{
            $this->run('mysqldump',['--default-character-set=utf8mb4','--single-transaction','--quick','--skip-lock-tables','--skip-add-locks','--skip-triggers','--no-tablespaces','--hex-blob','--result-file='.$sql,$this->connection['database']],$this->connection);
            if(!is_file($sql)||filesize($sql)<100)throw new RuntimeException('El respaldo está vacío.');
            $configFile=$directory.'/'.$prefix.'.config.php';file_put_contents($configFile,"<?php\nreturn ".var_export($this->config,true).";\n",LOCK_EX);@chmod($configFile,0600);@chmod($sql,0600);
            $manifest=$directory.'/'.$prefix.'.json';$data=['created_at'=>gmdate('c'),'file'=>basename($sql),'sha256'=>hash_file('sha256',$sql),'bytes'=>filesize($sql),'connection_file'=>basename($configFile),'connection_sha256'=>hash_file('sha256',$configFile)];
            file_put_contents($manifest,json_encode($data,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),LOCK_EX);return $manifest;
        }catch(Throwable $e){if(is_file($sql))unlink($sql);throw $e;}
    }
    public static function verify(string $manifest):array
    {
        $m=json_decode(file_get_contents($manifest),true,32,JSON_THROW_ON_ERROR);$dir=dirname(realpath($manifest));
        if(!is_array($m)||!preg_match('/^vacarel-\d{8}-\d{6}-[a-f0-9]{8}\.sql$/D',$m['file']??''))throw new DomainException('Manifiesto inválido.');
        $sql=$dir.'/'.$m['file'];if(!is_file($sql)||!hash_equals($m['sha256']??'',hash_file('sha256',$sql)))throw new DomainException('El respaldo fue alterado o está incompleto.');
        if(isset($m['connection_file'])){if(basename($m['connection_file'])!==$m['connection_file'])throw new DomainException('Configuración de respaldo inválida.');$c=$dir.'/'.$m['connection_file'];if(!is_file($c)||!hash_equals($m['connection_sha256']??'',hash_file('sha256',$c)))throw new DomainException('La configuración respaldada está incompleta.');}
        return ['sql'=>$sql,'manifest'=>$m];
    }
    public function testRestore(string $manifest,array $admin):array
    {
        $verified=self::verify($manifest);$name='vacarel_test_'.bin2hex(random_bytes(6));
        $db=new PDO('mysql:host=127.0.0.1;port='.$this->connection['port'].';charset=utf8mb4',$admin['user'],$admin['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        try{
            $this->run('mysql',['--default-character-set=utf8mb4','--binary-mode=1','--database='.$name],$admin,$verified['sql']);$db->exec("USE `$name`");$counts=[];
            foreach($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t){if(!preg_match('/^[a-zA-Z0-9_]+$/D',$t))throw new RuntimeException('Tabla inválida.');$counts[$t]=(int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();}
            $balances=[];foreach(['stock'=>"SELECT COALESCE(SUM(quantity),0) FROM lots",'invoiced'=>"SELECT COALESCE(SUM(total),0) FROM sales",'outstanding'=>"SELECT COALESCE(SUM(balance),0) FROM sales"] as $key=>$sql)$balances[$key]=(string)$db->query($sql)->fetchColumn();
            return ['verified'=>true,'counts'=>$counts,'balances'=>$balances];
        }finally{if(preg_match('/^vacarel_test_[a-f0-9]{12}$/D',$name))$db->exec("DROP DATABASE `$name`");}
    }
}
