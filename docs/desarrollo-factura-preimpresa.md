# Venta e impresión preimpresa

Implementar calculadora decimal, venta con reserva FEFO y confirmación idempotente, numeración física validada y snapshot inmutable, pantalla de ventas y plantilla sin diseño preimpreso. Despachar descuenta físico una sola vez. Contado/crédito con plazo y límite; no registrar cobros ficticios. La impresión se abre al confirmar; los intentos/resultados se registran por separado. El navegador no permite garantizar impresión silenciosa.

La fotografía aportada define los campos, no dimensiones ni próximo número. Ajustes por campo, tamaño y desplazamiento se deben calibrar. Emisión real desactivada hasta que un responsable configure timbrado, numeración, precios con/sin IVA y confirme habilitación y prueba física. No asignar como próximo número el de la fotografía. Imprimir únicamente fecha, cliente, RUC, dirección, teléfono, X de condición, plazo, código/unidades/descripción/precio, importe en exentas/5/10, total y liquidación 5/10. No imprimir subtotal, total IVA, logo, títulos, líneas, número ni fondo.

Pruebas: importes con IVA incluido y excluido, columnas mixtas, redondeo en guaraní, crédito/plazo, autorización, reservas concurrentes, reintentos, vigencia/rango, ausencia de stock negativo, snapshot y restricción de reimpresión. Pruebas HTTP con base temporal y revisión de impresión PDF/página sin enviar papel real.
