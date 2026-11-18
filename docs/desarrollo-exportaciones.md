# Exportaciones y formato de informes

Usar una plantilla compartida para imprimir y generar PDF: misma fuente Inter local, paleta azul de Vacarel, encabezado con logo, filtros, métricas y tabla con encabezados repetidos. Exportar PDF directamente, sin depender del diálogo de impresión. Generar Excel XLSX real con resumen y detalle, tipos de celda seguros, importes numéricos y textos conservados. Mantener CSV.

Todas las salidas reutilizan Reports y sus permisos/filtros, con resultados completos. Dependencias PHP en app/vendor (carpeta protegida); no cambiar la base ni la configuración global de PHP. Pruebas de firmas, contenido, fórmula inyectada, paginación, autorización, fechas y formato compartido. Inspeccionar PDF renderizado y pantalla.

## Entrega y verificación
- Completado: plantilla HTML/PDF común, Inter local con licencia, colores/logo, encabezados repetidos, paginación PDF y botones Excel/PDF/CSV/Imprimir.
- Dependencias fijadas: dompdf 3.1.6 y SimpleXLSXGen 1.5.17. Instaladas en app/vendor; cache de fuentes en app/cache.
- Pruebas: run.php, billing.php, reports.php, print-client.cjs, reports-client.cjs, report-exports.php, verify-report-exports.py y http-smoke.ps1 pasaron. Sintaxis PHP/JS y composer.json verificados.
- Excel inspeccionado con openpyxl: tipos numéricos, códigos como texto, fórmulas desactivadas, hojas, filtros y fuente. PDF inspeccionado con pypdf y renderizado: 61 filas en 5 páginas, Inter incrustada, tildes, encabezados, última fila y números de página.
- Navegador: descarga real XLSX/PDF, contenido validado y vista de impresión revisada, sin errores de consola. Captura: artifacts/informes-impresion-estilo.png (datos ficticios). Sistema real abierto con los nuevos botones.
- Revisión propia, sin subagentes: exportaciones reutilizan autorización/filtros/resultados completos; datos escapados, rutas de recursos internas y acceso remoto PDF desactivado. No se modificaron datos de negocio. Base, servidor y descargas de prueba eliminados.
- Corrección durante pruebas: ruta file:// en Windows y composición blanca del logo para JPEG; archivos regenerados y comprobados.
