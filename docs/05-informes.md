# Catálogo de informes

Este documento define el alcance completo previsto. La entrega actual incluye 13 consultas; su funcionamiento, permisos y límites están en [Uso de informes](09-uso-informes.md). Caja, cobranzas, pagos, reparto, notas de crédito y comisiones requieren completar sus respectivos circuitos.

Filtros comunes: período, estado y dimensiones permitidas por rol. Exportar aplica exactamente filtros y permisos de la consulta. CSV neutraliza celdas interpretables como fórmulas. Impresión muestra filtros, fecha de generación y moneda.

| Informe | Criterio y filtros específicos |
|---|---|
| Existencias | Físico, reservado, apto disponible y bloqueado por producto/lote/ubicación; no sumar categorías superpuestas |
| Valorización | Cantidad física por costo aplicado; separar stock no apto y valor en riesgo |
| Movimientos | Entradas/salidas, saldo inicial y final por fecha, producto, lote, origen y responsable |
| Reposición | Disponible contra mínimo; mostrar compras pendientes sin tratarlas como existencia |
| Rotación y sin movimiento | Salidas del período y stock promedio; mostrar período y ausencia de datos; sin división por cero |
| Vencimientos | Vencidos y próximos 7, 30, 60 y 90 días en intervalos excluyentes; filtros por lote/proveedor |
| Trazabilidad | Recepciones/proveedores y expediciones/clientes de cada lote; cantidades y documentos |
| Compras | Neto, impuestos y total por proveedor/producto/período; créditos separados |
| Órdenes y diferencias | Pedido, recibido, rechazado, facturado y pendiente; resolución de diferencias |
| Evolución de costo | Precios comparables en unidad base por proveedor y fecha |
| Ventas | Facturación menos notas de crédito por producto, cliente, vendedor y zona; anulados excluidos |
| Margen bruto | Venta sin IVA menos costo histórico de mercadería vendida, ajustado por devoluciones; no equivale a utilidad final |
| Saldos y antigüedad | Saldo al corte; no vencido, 1–30, 31–60, 61–90 y más de 90 días; anticipos separados |
| Caja y bancos | Apertura, entradas, salidas, transferencias, esperado, contado y diferencia; por cuenta/sesión |
| Reparto | Pendientes, entregados, rechazados y retornados por ruta, vendedor y repartidor |
| Rendiciones | Cobrado, bajo custodia, rendido y diferencia por colaborador/medio |
| Comisiones | Base, porcentaje capturado, generado, ajustado, liquidado, pagado y pendiente por colaborador |

Los informes históricos reconstruyen al corte usando movimientos y aplicaciones fechadas; no usan el saldo actual como sustituto. Ventas se agrupan por emisión, cobros por recepción del dinero y despachos por salida física.
