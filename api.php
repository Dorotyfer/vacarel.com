<?php
require __DIR__.'/app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
function reply(array $body,int $status=200): never {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
try {
    $db=database(); $action=$_GET['action']??'session';
    $post=$_SERVER['REQUEST_METHOD']==='POST';
    $data=$post?json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR):[];
    if($post && (!is_array($data) || !hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))) reply(['error'=>'La sesión del formulario venció. Actualizá la página.'],403);
    if($action==='login' && $post) {
        $username=strtolower(trim((string)($data['username']??'')));$ip=$_SERVER['REMOTE_ADDR']??'';
        $identity=hash('sha256',$ip);$now=time();
        $db->prepare('DELETE FROM login_attempts WHERE attempted_at<?')->execute([$now-900]);
        $s=$db->prepare('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=?');$s->execute([$identity]);
        if((int)$s->fetchColumn()>=8) reply(['error'=>'Demasiados intentos. Esperá 15 minutos.'],429);
        $s=$db->prepare('SELECT * FROM users WHERE username=? AND active=1');$s->execute([$username]);$u=$s->fetch();
        $valid=hash_equals($u['password']??bin2hex(random_bytes(32)),(string)($data['password']??''));
        if(!$u || !$valid) {$db->prepare('INSERT INTO login_attempts(identity_hash,attempted_at) VALUES(?,?)')->execute([$identity,$now]);reply(['error'=>'Usuario o contraseña incorrectos.'],401);}
        session_regenerate_id(true);$_SESSION['user_id']=$u['id'];$_SESSION['activity']=$now;$_SESSION['csrf']=bin2hex(random_bytes(32));
        reply(['ok'=>true]);
    }
    $user=currentUser($db);
    if(!$user) reply(['error'=>'Ingresá para continuar.'],401);
    $app=new Domain($db,$user);
    $billing=new Billing($db,$user);
    if($action==='session') reply(['user'=>$user,'csrf'=>$_SESSION['csrf']]);
    if($action==='logout' && $post) {$_SESSION=[];session_destroy();reply(['ok'=>true]);}
    if($action==='dashboard' && !$post) reply(['data'=>$app->dashboard()]);
    if($action==='setup_status' && !$post)reply(['data'=>(new LocalSetup($db,$user))->status()]);
    if(in_array($action,['report_catalog','report_options','report_data'],true) && !$post){
        $reports=new Reports($db,$user);$type=(string)($_GET['type']??'');
        $filters=$_GET;unset($filters['action'],$filters['type'],$filters['module']);
        if($action==='report_catalog'){$app->authorize('reports');reply(['data'=>$reports->catalog()]);}
        if($action==='report_options')reply(['data'=>$reports->options($type)]);
        reply(['data'=>$reports->run($type,$filters)]);
    }
    if($action==='billing_settings' && !$post)reply(['data'=>$billing->settings()]);
    if($action==='numbering' && !$post)reply(['data'=>$billing->numbering()]);
    if($action==='numbering_save' && $post){$billing->saveNumbering($data);reply(['ok'=>true]);}
    if($action==='billing_settings_save' && $post){$billing->saveSettings($data);reply(['ok'=>true]);}
    if($action==='sales_catalog' && !$post)reply(['data'=>$billing->catalog()]);
    if($action==='sales_list' && !$post)reply(['data'=>$billing->list()]);
    if($action==='sale_preview' && $post)reply(['data'=>$billing->preview($data)]);
    if($action==='sale_confirm' && $post)reply(['id'=>$billing->confirm($data)]);
    if($action==='sale_dispatch' && $post){$billing->dispatch((int)($data['id']??0));reply(['ok'=>true]);}
    if($action==='sale_document' && !$post)reply(['data'=>$billing->document((int)($_GET['id']??0))]);
    if($action==='print_intent' && $post)reply(['id'=>$billing->printIntent((int)($data['id']??0),(string)($data['reason']??''),(string)($data['key']??''))]);
    if($action==='print_result' && $post){$billing->printResult((int)($data['id']??0),(string)($data['result']??''));reply(['ok'=>true]);}
    $module=(string)($_GET['module']??'');
    if($action==='list' && !$post) reply(['data'=>$app->list($module)]);
    if($action==='save' && $post) reply(['id'=>$app->save($module,$data)]);
    if($action==='receive' && $post) reply(['id'=>$app->receive($data)]);
    reply(['error'=>'Operación no disponible.'],404);
} catch(DomainException $e) {reply(['error'=>$e->getMessage()],in_array($e->getCode(),[403,404,409,422],true)?$e->getCode():422);}
catch(JsonException $e) {reply(['error'=>'Solicitud inválida.'],400);}
catch(PDOException $e) {error_log($e->getMessage());reply(['error'=>$e->getCode()==='23000'?'El código o usuario ya existe, o una referencia no es válida.':'No se pudo guardar o consultar la información.'],409);}
catch(Throwable $e) {error_log($e->getMessage());reply(['error'=>'No se pudo conectar con el sistema. Revisá la instalación y la base de datos.'],503);}
