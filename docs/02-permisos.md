# Roles y permisos

Permisos por recurso y acción: consultar, crear, editar borrador, confirmar, aprobar, anular, imprimir, exportar. Denegación por defecto. Validación en PHP en cada solicitud, incluida consulta de un ID y exportación; ocultar botones no constituye autorización. Varios roles por usuario, con alcance global o asignado. Desactivar usuario revoca acceso sin borrar historia.

| Rol | Acceso autorizado | Restricciones |
|---|---|---|
| Administrador | Usuarios, configuración, permisos y mantenimiento | No omite invariantes de stock, vencimiento o numeración |
| Gerencia/supervisor | Consulta global, costos, márgenes, excepciones de crédito, descuentos, ajustes, anulaciones y comisiones | Motivo obligatorio; no aprueba su propio ajuste |
| Compras | Proveedores, solicitudes, cotizaciones, órdenes, costos y facturas de compra | Pagar requiere permiso separado |
| Depósito | Recepciones, lotes, conteos, preparación, despacho y propuesta de ajustes | Sin aprobar sus propios ajustes ni modificar documentos fiscales |
| Vendedor | Clientes/pedidos asignados, precios comerciales, saldos y comisiones propias | Sin costos, márgenes, permisos o cambios de crédito |
| Facturación/caja | Emisión, impresión, cobros, recibos y caja asignada | Anulación y reapertura requieren supervisor |
| Repartidor/cobrador | Entregas, incidencias, cobros y rendiciones asignadas | Sin cambios de precios, crédito o cartera ajena |
| Contador | Consulta/exportación de documentos, impuestos, saldos y movimientos | Sin modificar ventas ni stock |

Auditar actor, fecha, acción, recurso, valores anteriores/nuevos y motivo para cambios de precio/costo, permisos, ajustes, excepciones, anulaciones, reimpresiones, cierres y liquidaciones. No registrar contraseñas ni secretos. Ningún usuario operativo puede modificar auditoría.
