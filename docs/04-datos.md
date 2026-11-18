# Diccionario de datos e interfaces

Todas las entidades tienen ID interno, fechas y actor creador; documentos usan estado y versión para concurrencia. Cantidades y dinero se almacenan como DECIMAL, nunca coma flotante. Fechas civiles y vencimientos se interpretan en America/Asuncion; instantes de auditoría se guardan en UTC.

| Entidad | Datos y relaciones principales |
|---|---|
| Empresa y autorización fiscal | RUC, razón social, establecimiento, punto, modalidad, vigencia, rango y siguiente número |
| Producto | Código único, descripción, marca, categoría, unidad base, IVA, control de lote/vencimiento y mínimo |
| Presentación | Producto, nombre, factor positivo, código; factor queda capturado en documentos históricos |
| Precio | Producto/presentación, lista, vigencia, importe, mínimo y autor |
| Proveedor y oferta | Identificación, contactos, condiciones; relación muchos a muchos con producto, precio y plazo |
| Cliente | Identificación, direcciones, zona, vendedor, lista, límite y plazo |
| Colaborador y comisión | Persona, usuario opcional; porcentaje entre 0 y 100, base y vigencia sin solapamiento |
| Compra | Solicitud/cotización/orden y líneas; proveedor, destino, cantidades, precios y estado |
| Recepción | Proveedor, documento de origen, orden opcional; líneas con producto, presentación, cantidad base, lote, costo y ubicación |
| Lote | Producto, código fabricante/interno, vencimiento y estado; múltiples recepciones de origen |
| Saldo y movimiento | Producto, lote, ubicación, físico, reservado; movimiento firmado con origen, destino, documento y costo |
| Pedido y expedición | Cliente, vendedor, condición, líneas, reservas, preparación por lote, cantidades entregadas y rechazadas |
| Documento fiscal | Tipo, autorización, número único, original relacionado, importes, snapshot inmutable y estado |
| Impresión | Documento, intento, usuario, fecha, resultado declarado y motivo de reimpresión |
| Cuenta y vencimiento | Cliente/proveedor, documento, total, fecha y saldo derivado de aplicaciones y créditos |
| Cobro/pago y aplicación | Persona, medio, monto, custodio/cuenta; aplicaciones muchos a muchos con vencimientos |
| Tesorería y rendición | Caja/banco/custodio, sesión, movimientos, comprobantes y transferencias vinculadas |
| Comisión y liquidación | Vendedor, venta, regla capturada, base, importe, ajuste de origen y liquidación |
| Usuario y auditoría | Credencial protegida, roles, alcance; evento con actor, recurso, cambios y motivo |

## Contratos para desarrollo

PHP expone acciones de negocio separadas: confirmar recepción, reservar pedido, preparar, emitir documento, despachar, registrar entrega, recibir retorno, aplicar cobro, rendir y liquidar. No exponer actualización arbitraria de saldos ni estados.

Cada acción verifica sesión, permisos, alcance, versión y precondiciones. Mutaciones exigen protección CSRF y clave de idempotencia. Duplicar una solicitud devuelve la operación original sin repetir efectos. Respuestas incluyen ID, estado y versión; errores distinguen datos inválidos, acceso denegado y conflicto de estado/disponibilidad.

Transacciones y bloqueo de filas protegen reservas, rangos fiscales, aplicaciones y liquidaciones. Índices únicos protegen código de producto, clave fiscal (tipo, autorización, establecimiento, punto y número), aplicaciones idempotentes y movimientos de origen. Consultas parametrizadas y cookies de sesión protegidas. Por decisión explícita del usuario, el acceso usa nombre de usuario y la contraseña se almacena en texto legible. No se devuelve en listados ni se copia a auditoría.

Respaldos incluyen base, documentos y configuración necesaria; secretos se gestionan fuera del directorio público. Programar respaldo diario y comprobar restauración en entorno separado antes de producción.
