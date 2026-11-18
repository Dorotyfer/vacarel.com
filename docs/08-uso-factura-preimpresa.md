# Uso de la factura preimpresa

Formato indicado por el usuario: media carta horizontal, 216 × 140 mm. Los precios del catálogo ya incluyen IVA. La tasa puede registrarse al recibir una compra y cambiarse por ítem durante la venta. La recepción conserva su tasa histórica y actualiza la tasa sugerida del producto; cambiarla en una venta no modifica el producto ni facturas anteriores.

## Preparar el equipo

1. Abrir Configurar impresión como administrador o supervisor.
2. Cargar timbrado, establecimiento, punto, vigencia, rango autorizado y próximo número físico. No deducir el próximo número de la foto.
3. Guardar papel, fuente, separación y posiciones. Las coordenadas iniciales son aproximaciones a la fotografía.
4. Abrir prueba guardada y usar papel común. En el controlador seleccionar 216 × 140 mm, escala 100 %, sin encabezados ni pies. Comparar contra el formulario real y corregir desplazamientos y posiciones de cada campo.
5. Confirmar la habilitación fiscal y la prueba física, y recién entonces habilitar emisión. La aplicación instalada conserva emisión desactivada hasta completar estos pasos.

La impresión ordinaria abre el diálogo del navegador. Selección automática de impresora e impresión silenciosa necesitan una configuración adicional de la estación de trabajo. No se envía papel real durante las pruebas automatizadas.

## Confirmar una venta

Seleccionar cliente con nombre, RUC, dirección y teléfono; contado o crédito, con plazo en días para crédito; productos, cantidades enteras y tasa de IVA por línea. Revisar total e IVA. Confirmar que el número mostrado coincide con el papel cargado y usar Confirmar venta e imprimir. Si el navegador bloquea la ventana, abrir la factura desde el listado.

Los precios se obtienen del servidor; una modificación de precio, cliente, numeración o configuración posterior a la revisión exige revisarla nuevamente. Se conserva el detalle fiscal original. Repetir una solicitud no crea otra factura.

La impresión contiene exclusivamente fecha, nombre, RUC, dirección, teléfono, X de contado o crédito, plazo si corresponde, código, unidades, descripción, precio unitario, importe en exentas/5 %/10 %, total y liquidación IVA 5 %/10 %. No imprime logo, numeración, líneas, títulos, subtotal ni total IVA. Descripciones largas ocupan varias filas; si no caben se bloquea la emisión antes de consumir un número.

Importe de línea = unidades × precio unitario final redondeado al guaraní. Las columnas gravadas contienen el importe completo, no solo el impuesto. Liquidación 5 % = total gravado 5 % / 21; liquidación 10 % = total gravado 10 % / 11, redondeadas al guaraní. Referencia técnica de DNIT: https://www.dnit.gov.py/documents/20123/1396787/RETENCION%2BIVA%2B04.06.26.pdf/ef20c801-27b9-3431-af38-0989a43c450d?t=1780927090176 . Validar el tratamiento de cada producto y el criterio de redondeo con el contador.

## Stock y seguimiento

Confirmar reserva lotes aptos por vencimiento. El botón Confirmar despacho descuenta las existencias una sola vez. La factura no genera un segundo descuento. Se conservan saldos pendientes; contado no inventa un recibo de cobro. El circuito de caja/cobranzas todavía debe incorporarse.

Cada apertura del diálogo de impresión mediante el botón registra un intento. Después el operador declara resultado correcto o fallido; cerrar el diálogo no acredita impresión física. Reimprimir requiere administrador o supervisor y motivo; conserva el mismo documento, plantilla y número.

Este incremento no implementa notas de crédito, anulaciones ni regularización de devoluciones. Deben incorporarse antes de poner en marcha el circuito fiscal completo. No se habilita producción solo por guardar una plantilla.

## Verificar desarrollo

`php tests/run.php`, `php tests/billing.php`, `node tests/print-client.cjs` y `tests/http-smoke.ps1`. Las pruebas SQL usan bases temporales y no crean facturas de negocio. Para instalaciones anteriores ejecutar `php bin/migrate-billing.php` con una cuenta administrativa de base de datos; agrega tablas y la tasa histórica de compra sin activar emisión.
