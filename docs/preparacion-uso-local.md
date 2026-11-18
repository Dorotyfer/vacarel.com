# Preparación para uso local

Mantener la instalación y los datos existentes. Preparar un acceso directo que compruebe MariaDB y el servidor local, inicie solo los servicios faltantes y abra el sistema. No reutilizar un puerto ocupado por otro programa. Crear respaldos fuera de htdocs con manifiesto e integridad; probar una restauración exclusivamente en base temporal validada. Añadir diagnóstico CLI y estado inicial para administración con acceso restringido, requisitos y datos pendientes, sin mostrar credenciales.

Revisar configuración real de MariaDB: actualmente inicia con skip-grant-tables. Respaldar antes de corregir el modo de recuperación, mantener escucha local, probar cuenta de aplicación y rechazo de cuentas desconocidas. Conservar valores fiscales sin inventar próximo número ni confirmar calibración física.

Pruebas: respaldo consistente y restauración temporal; disponibilidad de todas las tablas y bibliotecas, acceso autenticado, permisos y exportaciones existentes; arranque idempotente; rutas internas inaccesibles. Sin datos de muestra en la base real. Entregar guía de acceso, carga inicial y límites funcionales. Los módulos todavía no programados no se consideran operativos por instalar o preparar el sistema.
