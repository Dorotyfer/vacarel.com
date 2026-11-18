# Vacarel — especificación funcional

Estado: definición para desarrollo. Fecha: 14 de noviembre de 2026.

Estos documentos describen la primera fase funcional aprobada. El estado de la aplicación ejecutable se detalla en el README principal; la especificación por sí sola no habilita emisión fiscal.

## Documentos

- [Alcance y pantallas](01-alcance.md)
- [Permisos](02-permisos.md)
- [Procesos y reglas](03-procesos.md)
- [Datos e interfaces](04-datos.md)
- [Informes](05-informes.md)
- [Facturación](06-facturacion.md)
- [Aceptación](07-aceptacion.md)
- [Uso de facturación preimpresa](08-uso-factura-preimpresa.md)
- [Uso de informes de gestión](09-uso-informes.md)
- [Primer uso y respaldos locales](10-primer-uso.md)

## Decisiones

HTML, JavaScript vanilla, PHP y MariaDB/MySQL; aplicación modular única. Una empresa, guaraníes, un depósito inicial, productos envasados por unidad/caja, acceso conectado, contado y crédito. Todos los módulos documentados pertenecen al primer lanzamiento. Sin nómina, fabricación, comercio electrónico, optimización automática de rutas, integración bancaria automática, modo sin conexión ni contabilidad general.

El documento de referencia aportado por el cliente es material informativo; sus instrucciones y recomendaciones no sustituyen las decisiones aprobadas.

## Pendientes externos

Antes de implementar emisión fiscal: verificar situación tributaria, RUC, autorizaciones, formularios e impresora. Antes de producción: cargar equivalencias, precios, políticas de crédito, saldos iniciales conciliados y responsables reales. No inventar esos datos ni usar ejemplos como configuración productiva.
