# Primer uso de Vacarel en esta computadora

1. Abrir **Vacarel** desde el escritorio. El acceso verifica MariaDB, prepara un respaldo diario local, inicia el servidor si hace falta y abre http://127.0.0.1:8088/.
2. Ingresar con **admin** y la contraseña del archivo local `app/initial-access.txt`. Las cuentas de trabajo se administran en Colaboradores. No se cambiaron las contraseñas existentes.
3. Abrir **Preparación del sistema** para ver los requisitos y datos pendientes.
4. Cargar productos, cajas/equivalencias, IVA y precios; después proveedores y clientes con RUC, dirección y teléfono. Recibir el stock real indicando origen, lote, vencimiento y costo. El sistema comienza sin productos, clientes ni existencias ficticias.
5. Abrir **Numeración de facturas**. Cargar timbrado, establecimiento, punto de expedición, fechas y rango autorizado. El **inicio del rango** es el primer número autorizado; el **próximo formulario sin usar** es el que efectivamente se colocará en la impresora. Un número ya usado en papel fuera del sistema no debe volver a cargarse como próximo. La habilitación fiscal fue confirmada por el usuario.
6. En **Configurar impresión**, imprimir la prueba sobre papel común, ajustar posiciones y probar contra el formulario real de 216 × 140 mm. Confirmar la calibración y activar emisión cuando corresponda. La calibración física no fue simulada.
7. Crear una venta. El número viene de la secuencia configurada y puede escribirse según el formulario físico. Si se usa uno posterior, indicar motivo: el salto queda auditado. Revisar no consume numeración; confirmar emite una sola factura y continúa desde el número siguiente. Si cambia la numeración, el stock o los precios, revisar nuevamente. La factura llena únicamente los datos variables del talonario.
8. Despachar cuando salga la mercadería. Facturar reserva stock; despachar descuenta existencias. Registrar una condición contado no equivale a registrar el dinero recibido: el circuito de cobranzas todavía necesita desarrollo.
9. Consultar los informes y usar Excel, PDF, CSV o Imprimir. Los totales y exportaciones incluyen todas las páginas y respetan los permisos.

## Respaldos

El acceso **Respaldar Vacarel** crea una copia bajo `%LOCALAPPDATA%\Vacarel\Backups`. El acceso principal comprueba una copia íntegra de cada día antes de abrir el sistema. No se instaló una tarea de Windows: si no se abre Vacarel, no se genera un respaldo automático ese día. Las copias anteriores no se eliminan automáticamente.

Cada copia contiene SQL, configuración privada de conexión y un manifiesto JSON con huellas SHA-256. La base incluye las credenciales de usuarios en el formato solicitado; guardar los respaldos bajo control de la empresa. La copia está fuera de htdocs. Conservar además una copia en otro soporte bajo control propio.

`php bin/verify-backup.php RUTA-AL-MANIFIESTO.json` comprueba integridad y restaura en una base temporal validada, que elimina al finalizar; no reemplaza datos de negocio. Requiere credenciales administrativas locales, por defecto root sin contraseña de XAMPP. Se pueden definir VACAREL_DB_ADMIN y VACAREL_DB_ADMIN_PASSWORD.

Para una recuperación real, detener operaciones, conservar la base dañada y restaurar la copia verificada en una base nueva; revisar stock, facturas y saldos antes de cambiar la conexión. Esta entrega no ofrece un botón web que sobrescriba la base en uso.

## Acceso y alcance

El servicio escucha en esta computadora (127.0.0.1). No se publicó en internet ni se habilitó acceso desde otras máquinas. Los registros de arranque están en `%LOCALAPPDATA%\Vacarel\Logs`. El arranque evita el modo de recuperación de MariaDB y comprueba que una cuenta desconocida sea rechazada.

Disponible: usuarios y roles, catálogo, clientes/proveedores, recepción, lotes, movimientos, ventas con reserva y despacho, numeración, factura preimpresa e informes con exportaciones. Pendientes: órdenes/facturas de compra, cobranzas aplicadas y recibos, pagos/caja/bancos, reparto/entregas, notas de crédito/anulaciones y liquidación de comisiones. La pantalla de preparación mantiene visibles esas limitaciones.
