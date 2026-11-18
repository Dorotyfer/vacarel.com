# Venta desde el móvil de reparto

El vendedor usará la aplicación web desde su celular para confirmar la carga, consultar su stock, registrar una venta e imprimir el ticket en un toque. La venta aparecerá en la cuenta principal del administrador como **Pendiente de facturar**. El ticket estará disponible inmediatamente, aunque la factura todavía no se haya emitido.

## Operación desde el celular

- Completar el rol **Vendedor**, que ya existe en el alta de usuarios. El administrador principal dará de alta los vehículos y asignará un móvil a cada vendedor activo. Cada móvil tendrá un solo vendedor activo a la vez. Una reasignación conservará el historial y el stock del vehículo.
- Mostrar tres accesos grandes al ingresar: **Recibir carga**, **Mi inventario** y **Vender**. En la venta, permitir buscar productos por nombre o código, ajustar cantidades, seleccionar un cliente existente y revisar el total antes de confirmar. Mostrar solo los productos disponibles en el móvil y aplicar los precios del catálogo desde el servidor.
- Limitar los permisos del vendedor a las cargas pendientes y el stock de su móvil, el registro de ventas y la consulta e impresión de sus propios tickets. Validar estos permisos en el servidor, incluso cuando se accede por un enlace directo.

## Inventario, venta y facturación

- El administrador prepara la carga por lote. Al enviarla, las cantidades salen del stock disponible del depósito y quedan en tránsito. Al confirmar la recepción, pasan al stock vendible del móvil. Si el conteo difiere, el vendedor deja la carga pendiente y el administrador la cancela y corrige. Bloquear la reasignación del móvil mientras tenga cargas en tránsito.
- Registrar la venta, descontar el stock del móvil por lote y crear el pendiente fiscal en una sola operación. Guardar vendedor, vehículo, cliente, cantidades, precios, impuestos y total originales. Descontar primero los lotes que vencen antes y bloquear lotes vencidos y faltantes. Usar una clave de operación para que un doble toque o un reintento tras un corte devuelva la misma venta.
- Agregar al panel del administrador principal una bandeja con contador de pendientes y acceso directo a **Revisar y emitir**. Reutilizar la facturación preimpresa y su numeración, tomando los importes y lotes guardados en la venta. Vincular una sola factura por venta, sin volver a descontar mercadería. Si falla la emisión, conservar el pendiente. Una vez emitida, usar el flujo fiscal existente para imprimir o reimprimir el mismo documento.

## Ticket térmico

- Mostrar **Imprimir ticket** al guardar la venta y permitir volver a abrirlo desde las ventas propias. El botón abrirá la impresión del celular con una plantilla para papel de 2 pulgadas, con número interno, fecha, productos, cantidades, total y la leyenda **SIN VALOR FISCAL**. Cancelar o repetir la impresión conservará la misma venta.
- Validar la impresión física desde el navegador con el modelo de térmica y los celulares que se usarán. La primera versión usará la impresión del sistema del celular. Si esa combinación requiere una aplicación del fabricante, resolver y probar esa integración antes de habilitar el flujo en reparto.

## Pruebas y alcance inicial

- Probar el recorrido completo con dos vendedores y dos móviles. Verificar carga pendiente, recepción, aislamiento del stock y los permisos, venta, ticket, aparición en la bandeja y emisión de una sola factura. Comprobar que las existencias del depósito y del vehículo coincidan en cada paso.
- Probar ventas simultáneas por la última unidad, doble confirmación, corte de conexión después de guardar, falta de numeración fiscal y reimpresión. En un celular real, comprobar que un ticket con nombres largos y varias líneas salga completo y legible.
- La primera versión será web con conexión, para ventas de contado a clientes existentes. El administrador principal se identificará mediante su cuenta de usuario. Aplicar una migración que conserve los usuarios y ventas históricos y habilitar primero un vendedor y un móvil para la prueba en reparto.
