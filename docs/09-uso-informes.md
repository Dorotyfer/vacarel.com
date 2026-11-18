# Informes de gestión

Entrar en **Informes**, elegir una consulta, ajustar los filtros y pulsar **Consultar**. **Restablecer** vuelve a los filtros iniciales. Los informes por período comienzan con el mes actual; las fechas usan la hora de Paraguay. La pantalla identifica los filtros de los resultados consultados, aunque después se modifiquen los campos sin consultar todavía.

Los totales incluyen todos los resultados filtrados. La tabla muestra hasta 50 registros por página. **Excel** descarga un XLSX real con hojas Resumen y Detalle, filtros de columnas, encabezado fijo e importes numéricos. Los códigos y números de factura se conservan como texto; valores con más de 15 cifras significativas también, para evitar el redondeo de Excel. La fuente indicada es Inter; Excel puede sustituirla si no está instalada.

**PDF** descarga directamente el documento con Inter incorporada, logo, colores de Vacarel, filtros, totales, encabezados repetidos y números de página. **Imprimir** usa la misma plantilla y tipografía en A4 horizontal. **CSV** conserva la descarga UTF-8 con separador punto y coma. Todas las salidas incluyen todas las páginas y aplican los mismos permisos. No imprimen sobre el talonario ni consumen numeración fiscal.

## Consultas disponibles

| Informe | Criterio |
|---|---|
| Existencias y valorización | Físico actual, reservas de facturas pendientes de despacho, disponible apto y no apto. Valor físico al costo promedio actual, visible según rol. |
| Lotes y vencimientos | Partidas con existencia: vencidas, 0–7, 8–30, 31–60, 61–90, más de 90 días y sin vencimiento. Cada partida conserva su recepción de origen. |
| Necesidades de reposición | Productos activos cuyo disponible apto es inferior al mínimo. Sugerencia = mínimo menos disponible. |
| Movimientos de stock | Recepciones y despachos del período, con saldo previo y posterior por producto. La suma de variaciones no sustituye el saldo de cada producto. |
| Recepciones por proveedor | Cantidad originalmente recibida y costo ingresado, según permiso; no la cantidad que queda en el lote. No representa facturas de compra ni crédito fiscal. |
| Trazabilidad de lotes | Proveedor, recepción, lote y facturas con unidades reservadas o despachadas. Despacho no significa entrega confirmada. La existencia se repite si hay varios destinos; no sumarla. |
| Facturación por operación | Documentos emitidos en el período con cliente, precio y condición originales. El emisor es el operador, no el vendedor comercial. |
| Productos facturados | Unidades e importes agrupados por producto, código, descripción e IVA emitidos. Cambios posteriores del catálogo no alteran el informe. |
| Liquidación de IVA registrado | Exentas, gravadas 5 % y 10 %, IVA liquidado y total guardados al emitir. No prepara una declaración tributaria ni considera crédito fiscal. |
| Saldos y antigüedad | Saldo registrado actual y atraso: no vencido, 1–30, 31–60, 61–90 y más de 90 días. No reconstruye saldos históricos. |
| Margen sobre lotes asignados | Facturado menos IVA liquidado menos costo conservado en la asignación de lotes. Incluye reservas pendientes de despacho. No es margen al costo promedio histórico ni utilidad neta. |
| Pendientes de despacho | Facturas actuales con reserva, todavía sin despacho confirmado. |
| Actividad por operador | Eventos de auditoría con responsable, recurso y registro; excluye contenido sensible de los cambios. |

## Permisos

| Rol | Informes |
|---|---|
| Administrador / supervisor | Todos. |
| Contador | Inventario, ventas, saldos, margen y pendientes. Sin actividad global de usuarios. |
| Compras | Existencias con valor, vencimientos, reposición, recepciones con costo y trazabilidad. |
| Depósito | Existencias, vencimientos, reposición, movimientos, recepciones y trazabilidad, sin costos ni valorizaciones. |
| Caja | Facturación, productos facturados, IVA, saldos y pendientes. Sin costos ni margen. |
| Vendedor / repartidor | Sin informes globales hasta implementar cartera y operaciones asignadas. |

Se comprueban permisos en consultas, filtros, descarga e impresión. Depósito no recibe importes ocultos en las respuestas.

## Límites

Cobranzas, pagos, caja, bancos, reparto, notas de crédito, anulaciones y liquidación de comisiones siguen pendientes. No se infieren cobros a partir de ventas al contado. Los saldos muestran lo registrado en la factura; requieren integrar cobranzas para reflejar aplicaciones de dinero. El margen excluye gastos, comisiones y devoluciones aún no implementadas.

Los informes son de consulta y no modifican stock, facturas ni dinero. La lectura usa una transacción consistente para combinar reservas y existencias. Los resultados y agregados se calculan en memoria antes de paginar: para grandes volúmenes se deberá trasladar agregación/paginación a SQL y exportar por bloques. Este módulo no necesita migración de base de datos.

Pruebas: `php tests/reports.php`, `node tests/reports-client.cjs`, `php tests/report-exports.php` y `tests/http-smoke.ps1`. La última usa MariaDB temporal y elimina la base al terminar. `tests/verify-report-exports.py` inspecciona los XLSX/PDF ficticios con openpyxl y pypdf (solo herramientas de verificación). `tests/reports-browser-fixture.php` solo genera datos ficticios en una base temporal validada.

Instalación de exportaciones: ejecutar `composer install --no-dev` usando PHP 8.1 o superior con DOM, XML, mbstring y zlib. Las dependencias están fijadas en composer.lock, dentro de app/vendor, protegido por el servidor. app/cache debe permitir escritura al usuario de PHP para almacenar las métricas de Inter. Las fuentes locales y su licencia SIL OFL están en assets/fonts. No se usa una conexión externa al generar archivos. No requiere extensión ZIP ni GD durante la ejecución; Composer puede necesitar ZIP o una herramienta de extracción durante la instalación.
