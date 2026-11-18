# Procesos y reglas

## Compras

Solicitud: borrador → presentada → aprobada/rechazada → convertida/cancelada. Cotización: registrada → seleccionada/descartada. Orden: borrador → confirmada → parcial → recibida; cancelación solo del pendiente, con motivo. Compra directa permite recepción sin orden.

Recepción: borrador → confirmada → revertida mediante movimiento compensatorio cuando sea posible. Confirmar crea entradas en unidad base por producto, lote y ubicación. Excedentes requieren supervisor. Cantidad, precio y calidad discrepantes conservan resolución y responsable; la factura puede llegar después. Cada recepción conserva su costo y proveedor aunque comparta lote de fabricante.

Factura de proveedor: borrador → registrada → parcialmente pagada → pagada. Registra deuda, nunca nueva entrada de stock. Vinculación de líneas con recepciones permite controlar cantidades ya facturadas. Devolución física y nota de crédito del proveedor son independientes y vinculadas.

## Existencias

Disponible = físico apto − reservado apto. Bloqueados, vencidos y cuarentena permanecen físicos y no disponibles. Vencer no equivale a merma. Sin negativos, incluso con permisos elevados. Transferencias conservan lote y separan origen, tránsito y recepción destino.

FEFO ordena vencimiento ascendente, después fecha de recepción e ID; sin vencimiento se usa fecha de recepción. No seleccionar lotes bloqueados o vencidos. Excepción FEFO requiere supervisor y motivo, nunca permite mercadería no apta. Productos controlados exigen vencimiento; sin lote legible se crea lote interno conservando esa condición.

Costo promedio ponderado por producto: (valor anterior + valor recibido) / (cantidad anterior + cantidad recibida). Guardar costo aplicado en cada salida; una compra posterior no modifica márgenes históricos. Costos sin IVA recuperable; ajustes de costos posteriores dejan diferencia documentada.

Conteo genera propuesta de ajuste con cantidad teórica, contada y fecha de corte. Conciliar movimientos posteriores al corte antes de aprobación por otra persona. Toda corrección deja movimientos; no editar saldos directamente.

## Venta y reparto

Pedido: borrador → confirmado/reservado → preparado → facturado → despachado → entregado/parcial/rechazado → cerrado. Preparaciones parciales generan expediciones separadas y conservan pendientes en el pedido. Cancelar libera solo reservas pendientes; un despacho se corrige con retorno físico.

Confirmar valida precio autorizado, crédito y disponibilidad; reserva atómicamente. Para crédito se considera deuda más pedidos de crédito comprometidos sin doble contar importes ya facturados. Mora o exceso requieren supervisor y motivo. Revalidar aptitud de lotes y crédito antes de facturar/despachar.

Preparar asigna lotes aptos. Facturar cubre únicamente lo preparado. Despachar consume reserva y stock una sola vez. Registrar entrega conserva entregado, rechazado y pendiente; su suma debe coincidir con lo despachado. Retornos ingresan en cuarentena; inspección decide liberación, merma o devolución a proveedor. Crédito fiscal no ingresa mercadería automáticamente.

## Dinero y comisiones

Cobro: registrado → aplicado parcial/total → rendido cuando corresponda. Aplicaciones nunca superan saldo de factura ni disponible del cobro. Anticipo no genera comisión sobre cobrado hasta su aplicación. Pago a proveedor sigue reglas equivalentes. Revertir conserva original, motivo y contrapartida.

Cobrar en ruta acredita tenencia del cobrador; rendir transfiere esa tenencia a caja/banco, sin registrar un segundo ingreso comercial. Caja: abierta → cierre propuesto → cerrada; supervisor autoriza reapertura con motivo. Transferencia interna no cuenta como venta ni gasto.

Cada venta captura vendedor, porcentaje y base vigentes. Base individual: facturado neto de IVA o cobrado neto proporcional aplicado. Descuentos reducen base; notas de crédito y reversos ajustan sin duplicar devoluciones. Una devolución posterior a liquidación crea ajuste para la siguiente. El cobrador no sustituye al vendedor.

Liquidación: borrador → revisada → aprobada → pagada. Al aprobar se bloquean entradas incluidas para impedir liquidarlas dos veces. Pago genera salida de tesorería identificada.
