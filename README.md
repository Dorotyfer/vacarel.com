# Vacarel

Sistema de gestión de distribuidora de alimentos.

La especificación completa está en [documentación](docs/README.md). La aplicación incluye autenticación, permisos, catálogo, recepciones, inventario, auditoría, [13 informes de gestión](docs/09-uso-informes.md) y un módulo inicial de ventas con reserva FEFO, despacho y [facturación preimpresa](docs/08-uso-factura-preimpresa.md).

Tecnología prevista: HTML, JavaScript vanilla, PHP y MariaDB/MySQL.

## Instalación local

Requisitos: PHP 8.1 o superior con pdo_mysql, bcmath y mbstring; MariaDB/MySQL activo en 127.0.0.1:3306. En XAMPP iniciar MySQL y Apache. Desde esta carpeta ejecutar `C:\xampp\php\php.exe bin/install.php`. Crea una base nueva `vacarel` y un usuario SQL con permisos limitados; se detiene si la base ya existe. Por defecto usa root local sin contraseña solo para la instalación. Se pueden establecer VACAREL_DB_ADMIN y VACAREL_DB_ADMIN_PASSWORD para otra cuenta instaladora.

Para descargar informes en Excel XLSX y PDF, ejecutar `composer install --no-dev`. Requiere DOM/XML y zlib, y escritura en app/cache para fuentes. Las dependencias PHP quedan en app/vendor, protegidas junto al resto de archivos internos. No hay migraciones de datos para las exportaciones. Ver [uso de informes](docs/09-uso-informes.md).

El acceso usa nombre de usuario y contraseña, sin exigir correo. El instalador muestra una contraseña aleatoria una sola vez para `admin`. Se puede elegir cuenta inicial mediante VACAREL_ADMIN_USERNAME y VACAREL_ADMIN_PASSWORD (mínimo 12 caracteres). Los usuarios admiten de 3 a 40 letras, números, puntos, guiones o guiones bajos. Por pedido explícito del usuario, la contraseña queda en texto legible en la base; listados, respuestas de sesión y auditoría la excluyen. No usar root como conexión de la aplicación. La configuración generada queda excluida del control de versiones.

Para la instalación inicial anterior basada en correo, `C:\xampp\php\php.exe bin/migrate-usernames.php` migra la única cuenta inicial a `admin` verificando su contraseña original desde el archivo local de acceso o VACAREL_ADMIN_PASSWORD. Conserva la contraseña y no modifica colaboradores desconocidos. Si ya existen varias cuentas, requiere una migración específica porque sus hashes no permiten recuperar las contraseñas originales. La migración utiliza las credenciales administrativas de base de datos indicadas arriba y puede repetirse sin volver a cambiar la cuenta.

Abrir http://localhost/vacarel.com/ con Apache. Alternativa: `C:\xampp\php\php.exe -S 127.0.0.1:8088 router.php` y abrir http://127.0.0.1:8088/. El router es obligatorio para impedir acceso a carpetas internas con el servidor PHP. En Apache se requiere AllowOverride y mod_rewrite para aplicar .htaccess; en otro servidor configurar las mismas restricciones antes de exponerlo.

## Verificación

`C:\xampp\php\php.exe tests/run.php`, `C:\xampp\php\php.exe tests/billing.php` y `C:\xampp\php\php.exe tests/reports.php` ejecutan pruebas aisladas en SQLite en memoria (requiere pdo_sqlite); no modifican datos de negocio. `node tests/print-client.cjs` verifica la impresión con un sustituto de prueba, sin impresora real. `node tests/reports-client.cjs` verifica precisión de importes en pantalla. `tests/http-smoke.ps1` comprueba sesiones, CSRF, permisos, ventas, informes, CSV e impresión con MariaDB temporal.

## Uso y límites actuales

En esta computadora, usar los accesos del escritorio **Vacarel** y **Respaldar Vacarel**. El primero verifica los servicios y una copia diaria local antes de abrir http://127.0.0.1:8088/. Ver [primer uso](docs/10-primer-uso.md). La pantalla **Preparación del sistema** muestra los datos y circuitos pendientes. `php bin/check.php` comprueba la instalación sin mostrar credenciales.

**Numeración de facturas** permite cargar rango y próximo formulario. En cada venta se propone el siguiente número; se puede cargar uno posterior indicando motivo, con validación de rango/serie y auditoría. La secuencia avanza únicamente al confirmar la factura. Las pruebas no consumen numeración real.

Crear productos y proveedores; recibir mercadería por unidad/caja indicando lote, vencimiento y documento de origen; consultar existencias y movimientos. Configurar colaboradores y comisión individual desde una cuenta administradora. Se conserva cada recepción como partida independiente aunque coincida el código de lote.

Ventas y facturas permite revisar importes con IVA editable, confirmar, abrir la impresión y despachar. Configurar impresión permite ajustar media carta 216 × 140 mm y cada campo. Para una instalación anterior ejecutar `C:\xampp\php\php.exe bin/migrate-billing.php`; conserva datos y deja emisión desactivada. Faltan configurar autorización/numeración y probar en la impresora física. La página de alineación usa datos ficticios únicamente para imprimir sobre papel común, sin consumir numeración.

Todavía faltan órdenes de compra completas, reparto/entregas, cobros, pagos, liquidación de comisiones, notas de crédito y anulaciones. El saldo de las facturas está pendiente de integrar con cobranzas; una venta al contado no se trata automáticamente como cobrada. Se controla límite y mora para crédito. Configurar comisiones todavía no genera liquidaciones.

Para producción faltan validación fiscal, circuito completo, respaldos automatizados/restauración, HTTPS y revisión de despliegue. No publicar configuraciones, respaldos ni credenciales. Mantener base y servicios accesibles solo según la red autorizada.
