# Implementación de informes

Agregar servicio de consulta Reports sin mutar documentos ni stock, catálogo autorizado por rol y filtros validados por informe. Separar informes de existencias/saldos actuales de consultas por período; no ofrecer saldos históricos sin movimientos de cobranzas. Fechas operativas America/Asuncion y límites UTC en movimientos y auditoría.

Entregar existencias/valorización, vencimientos, reposición, movimientos, recepciones, facturación, productos facturados, IVA, saldos/antigüedad actuales, pendientes de despacho, trazabilidad, actividad y margen sobre costo de lotes asignados. Emisor es operador, no vendedor comercial. Usar snapshot fiscal e importes originales, nunca recalcular con precios/IVA actuales. No simular caja, compras fiscales ni comisiones.

Servicio devuelve columnas, filas, métricas, filtros aplicados y paginación; métricas sobre todos los resultados filtrados, no solamente página visible. Exportación completa y vista de impresión comparten servicio/filtros/permisos. CSV neutraliza fórmulas. Interfaz vanilla con categorías, filtros, resultados vacíos, totales y gráfico sencillo para facturación. Sin bibliotecas ni migraciones adicionales.

Pruebas primero: IVA snapshot, categorías de vencimiento excluyentes, reservado/físico/disponible, saldo registrado, límites de fecha local, filtros, totales/paginación, costos ocultos a depósito, permisos de endpoints y CSV seguro. Verificar PHP/JS, pruebas existentes e interfaz con datos temporales; no crear operaciones reales.

## Avance
- Servicio, permisos, filtros y cálculos implementados; pruebas SQLite de IVA histórico, stock, fechas, roles, paginación y CSV pasan.
- Interfaz, exportación completa y vista de impresión conectadas; prueba HTTP en MariaDB temporal pasa.
- Sin repositorio Git: trabajo directo en la carpeta autorizada. Revisión realizada por el mismo implementador, sin subagentes por instrucción del usuario.
- Revisión final y documentación completadas.

## Verificación final
- Completado: 13 informes, filtros validados, permisos de servidor, CSV UTF-8 completo e impresión A4 horizontal.
- Pasan tests/run.php, tests/billing.php, tests/reports.php, tests/print-client.cjs, tests/reports-client.cjs y tests/http-smoke.ps1; sintaxis PHP/JS verificada.
- Revisión de interfaz: filtros, caso sin resultados, restablecer, gráfico, totales y vista de impresión verificados. Sin errores de consola. Captura con datos temporales: artifacts/informes-prueba.png.
- Revisión propia final: las consultas no escriben operaciones; costos excluidos en servidor para depósito; snapshot fiscal conservado; totales globales no dependen de página. Límite documentado: agregación en memoria para volúmenes iniciales.
- Base y servidor de prueba eliminados. Sistema real abierto en Informes sin agregar datos de negocio.
