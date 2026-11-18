$ErrorActionPreference = 'Stop'
$project = Split-Path $PSScriptRoot -Parent
$php = 'C:\xampp\php\php.exe'
$databaseName = 'vacarel_test_' + [guid]::NewGuid().ToString('N').Substring(0,12)
$previousDsn = $env:VACAREL_DSN
$previousUser = $env:VACAREL_DB_USER
$previousPassword = $env:VACAREL_DB_PASSWORD
$server = $null
function Check($condition, $label) { if (-not $condition) { throw $label }; Write-Output "OK $label" }
function Post-Json($action, $module, $payload, $session, $csrf) {
    Invoke-WebRequest -UseBasicParsing -Uri "http://127.0.0.1:8091/api.php?action=$action&module=$module" -Method Post -ContentType 'application/json' -Headers @{'X-CSRF-Token'=$csrf} -Body ($payload | ConvertTo-Json -Compress -Depth 32) -WebSession $session
}
try {
    & $php "$PSScriptRoot/mysql-fixture.php" $databaseName
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear la base de prueba' }
    $env:VACAREL_DSN = "mysql:host=127.0.0.1;dbname=$databaseName;charset=utf8mb4"
    $env:VACAREL_DB_USER = 'root'
    $env:VACAREL_DB_PASSWORD = ''
    $server = Start-Process -FilePath $php -ArgumentList '-S','127.0.0.1:8091','router.php' -WorkingDirectory $project -WindowStyle Hidden -PassThru
    $ready = $false
    for ($i=0; $i -lt 30; $i++) { try { $homeResponse = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/' -SessionVariable session; $ready=$true; break } catch { Start-Sleep -Milliseconds 200 } }
    Check $ready 'servidor disponible'
    $csrf = [regex]::Match($homeResponse.Content,'name="csrf-token" content="([^"]+)"').Groups[1].Value
    Check ($csrf.Length -eq 64) 'formulario con CSRF'
    try { Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=list&module=products' -WebSession $session; throw 'Acceso anónimo permitido' } catch { Check ($_.Exception.Response.StatusCode.value__ -eq 401) 'API exige sesión' }
    foreach($path in @('api.php?action=report_data&type=inventory','informes-exportar.php?type=sales','informes-imprimir.php?type=sales')){
        try{Invoke-WebRequest -UseBasicParsing "http://127.0.0.1:8091/$path" -WebSession $session;throw 'Informe anonimo permitido'}catch{Check ($_.Exception.Response.StatusCode.value__ -eq 401) "informes exigen sesion: $path"}
    }
    try { Post-Json 'login' '' @{username='admin';password='Test-only-Password-123'} $session 'incorrecto'; throw 'CSRF omitido' } catch { Check ($_.Exception.Response.StatusCode.value__ -eq 403) 'CSRF inválido rechazado' }
    $null = Post-Json 'login' '' @{username='admin';password='Test-only-Password-123'} $session $csrf
    $identity = (Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=session' -WebSession $session).Content | ConvertFrom-Json
    $csrf = $identity.csrf
    Check ($identity.user.role -eq 'admin') 'inicio de sesión'
    $setupState=((Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=setup_status' -WebSession $session).Content | ConvertFrom-Json).data
    Check ($setupState.ready_for_full_operation -eq $false -and $setupState.checks.Count -ge 8) 'preparacion distingue requisitos y modulos pendientes'
    $null = Post-Json 'save' 'products' @{code='HTTP1';name='Producto HTTP';box_size=12;price=10000;min_stock=30;tax=10;track_expiry=1} $session $csrf
    $null = Post-Json 'save' 'suppliers' @{name='Proveedor HTTP'} $session $csrf
    $receipt = @{product_id=1;supplier_id=1;quantity=2;presentation='box';cost=120000;lot_code='HTTP-LOT';expiry='2030-12-31';reference='Prueba aislada';key='http-test-1'}
    $null = Post-Json 'receive' '' $receipt $session $csrf
    $null = Post-Json 'receive' '' $receipt $session $csrf
    $products = (Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=list&module=products' -WebSession $session).Content | ConvertFrom-Json
    Check ($products.data[0].stock -eq 24) 'MariaDB persiste 24 unidades sin duplicación'
    Check ([decimal]$products.data[0].average_cost -eq 10000) 'costo por caja convertido'
    $page = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/' -WebSession $session
    Check ($page.Content.Contains('Lotes y existencias')) 'interfaz autenticada renderizada'
    $settings = ((Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=billing_settings' -WebSession $session).Content | ConvertFrom-Json).data
    $settings.timbrado='99999999'; $settings.start=100; $settings.end=102; $settings.next=100
    $settings.valid_from=(Get-Date).AddDays(-1).ToString('yyyy-MM-dd'); $settings.valid_until=(Get-Date).AddYears(1).ToString('yyyy-MM-dd')
    $settings.fiscal_checked=$true; $settings.calibrated=$true; $settings.enabled=$true
    $null = Post-Json 'billing_settings_save' '' $settings $session $csrf
    $numbering=((Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=numbering' -WebSession $session).Content|ConvertFrom-Json).data
    Check ($numbering.formatted_next -eq '001-001-0000100') 'propuesta desde modulo de numeracion'
    $null = Post-Json 'save' 'customers' @{name='Cliente HTTP';document='80000000-1';address='Dirección de prueba';phone='0991000000';credit_limit=100000;credit_days=30} $session $csrf
    $sale = @{customer_id=2;condition='cash';credit_days=0;lines=@(@{product_id=1;quantity=2;tax=5});key='http-sale-1';expected_number='001-001-0000100'}
    $quote = (Post-Json 'sale_preview' '' $sale $session $csrf).Content | ConvertFrom-Json
    Check ([decimal]$quote.data.total -eq 20000 -and [decimal]$quote.data.iva5 -eq 952) 'IVA editable en venta con precio incluido'
    $sale.quote_token = $quote.data.quote_token
    $savedSale = (Post-Json 'sale_confirm' '' $sale $session $csrf).Content | ConvertFrom-Json
    $sameSale = (Post-Json 'sale_confirm' '' $sale $session $csrf).Content | ConvertFrom-Json
    Check ($savedSale.id -eq $sameSale.id) 'confirmar venta dos veces emite una sola factura'
    $printPage = Invoke-WebRequest -UseBasicParsing "http://127.0.0.1:8091/preimpresa.php?id=$($savedSale.id)" -WebSession $session
    Check ($printPage.Content.Contains('field-vat5_gross') -and -not $printPage.Content.Contains('<img')) 'impresión contiene datos y no repite el logo'
    $printStyle = Invoke-WebRequest -UseBasicParsing "http://127.0.0.1:8091/preimpresa-estilo.php?id=$($savedSale.id)" -WebSession $session
    Check ($printStyle.Content.Contains('size:216mm 140mm')) 'papel media carta horizontal'
    $attempt = (Post-Json 'print_intent' '' @{id=$savedSale.id;key='print-http-1'} $session $csrf).Content | ConvertFrom-Json
    $null = Post-Json 'print_result' '' @{id=$attempt.id;result='unknown'} $session $csrf
    $null = Post-Json 'sale_dispatch' '' @{id=$savedSale.id} $session $csrf
    $null = Post-Json 'sale_dispatch' '' @{id=$savedSale.id} $session $csrf
    $afterSale = (Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=list&module=products' -WebSession $session).Content | ConvertFrom-Json
    Check ($afterSale.data[0].stock -eq 22) 'despacho descuenta una vez sin duplicar al facturar'
    $numberingAfter=((Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=numbering' -WebSession $session).Content|ConvertFrom-Json).data
    Check ($numberingAfter.next -eq 101) 'secuencia compartida avanza al confirmar'
    $report = ((Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=report_data&type=vat' -WebSession $session).Content | ConvertFrom-Json).data
    Check ($report.rows[0].iva5 -eq '952' -and $report.summary[-1].value -eq '20000') 'informe IVA conserva documento y total completo'
    $csv = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/informes-exportar.php?type=vat' -WebSession $session
    Check ($csv.Headers['Content-Type'].Contains('text/csv') -and $csv.Content.Contains('20000')) 'exportacion CSV autenticada'
    $excel = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/informes-exportar.php?type=vat&format=xlsx' -WebSession $session
    Check ($excel.Headers['Content-Type'].Contains('spreadsheetml.sheet') -and $excel.Headers['Content-Disposition'].Contains('.xlsx')) 'Excel XLSX autenticado'
    $pdfReport = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/informes-exportar.php?type=vat&format=pdf' -WebSession $session
    Check ($pdfReport.Headers['Content-Type'].Contains('application/pdf') -and $pdfReport.Headers['Content-Disposition'].Contains('.pdf')) 'PDF descargable autenticado'
    try{Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/informes-exportar.php?type=vat&format=exe' -WebSession $session;throw 'Formato invalido permitido'}catch{Check ($_.Exception.Response.StatusCode.value__ -eq 422) 'formatos no permitidos rechazados'}
    $reportPrint = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/informes-imprimir.php?type=vat' -WebSession $session
    Check ($reportPrint.Content.Contains('20.000') -and $reportPrint.Content.Contains('informes-imprimir.js')) 'vista de impresion con datos originales'
    $manualSale=@{customer_id=2;condition='cash';credit_days=0;lines=@(@{product_id=1;quantity=1;tax=10});key='http-physical-number';physical_number='001-001-0000102';number_reason='Prueba de formulario posterior';expected_number='001-001-0000102'}
    $manualQuote=(Post-Json 'sale_preview' '' $manualSale $session $csrf).Content|ConvertFrom-Json
    Check ($manualQuote.data.expected_number -eq '001-001-0000102') 'numero fisico cargado en venta'
    $stillNext=((Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=numbering' -WebSession $session).Content|ConvertFrom-Json).data
    Check ($stillNext.next -eq 101) 'revision no consume numeracion'
    $manualSale.quote_token=$manualQuote.data.quote_token
    $manualSaved=(Post-Json 'sale_confirm' '' $manualSale $session $csrf).Content|ConvertFrom-Json
    $manualRetry=(Post-Json 'sale_confirm' '' $manualSale $session $csrf).Content|ConvertFrom-Json
    $advanced=((Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=numbering' -WebSession $session).Content|ConvertFrom-Json).data
    Check ($manualSaved.id -eq $manualRetry.id -and $advanced.next -eq 103) 'numero manual se confirma una vez y continua secuencia'
    try { Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/app/config.local.php'; throw 'Directorio expuesto' } catch { Check ($_.Exception.Response.StatusCode.value__ -eq 404) 'archivos internos protegidos' }
    $null = Post-Json 'logout' '' @{} $session $csrf
    $homeResponse = Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/' -WebSession $session
    $csrf = [regex]::Match($homeResponse.Content,'name="csrf-token" content="([^"]+)"').Groups[1].Value
    $null = Post-Json 'login' '' @{username='seller';password='Test-only-Password-123'} $session $csrf
    try { Invoke-WebRequest -UseBasicParsing 'http://127.0.0.1:8091/api.php?action=list&module=products' -WebSession $session; throw 'Costos expuestos' } catch { Check ($_.Exception.Response.StatusCode.value__ -eq 403) 'vendedor sin acceso a costos' }
    foreach($path in @('api.php?action=setup_status','api.php?action=numbering','api.php?action=report_data&type=sales','api.php?action=report_options&type=activity','api.php?action=report_catalog','informes-exportar.php?type=sales','informes-exportar.php?type=sales&format=xlsx','informes-exportar.php?type=sales&format=pdf','informes-imprimir.php?type=sales')){
        try{Invoke-WebRequest -UseBasicParsing "http://127.0.0.1:8091/$path" -WebSession $session;throw 'Informe global expuesto'}catch{Check ($_.Exception.Response.StatusCode.value__ -eq 403) "permisos de informe por servidor: $path"}
    }
    Write-Output 'Pruebas HTTP y MariaDB completadas.'
} finally {
    if ($server -and -not $server.HasExited) { Stop-Process -Id $server.Id }
    $env:VACAREL_DSN=$previousDsn; $env:VACAREL_DB_USER=$previousUser; $env:VACAREL_DB_PASSWORD=$previousPassword
    & $php "$PSScriptRoot/mysql-fixture.php" $databaseName 'drop'
}

