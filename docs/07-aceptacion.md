# Escenarios de aceptación

Estos son criterios para validar la futura aplicación, no pruebas ejecutadas de software existente. Cada escenario debe registrar datos iniciales, actor, operación, resultado y evidencia.

| ID | Escenario y resultado esperado |
|---|---|
| A01 | Recibir 2 cajas de 12; físico aumenta 24. Vender/despachar 5 unidades deja 19 |
| A02 | Orden de 30, recibir 10 y 8 en lotes distintos; recibido 18, pendiente 12; facturar compra no aumenta físico |
| A03 | Dos sesiones confirman pedidos por la última unidad; solo una reserva tiene éxito |
| A04 | Lote vence con 10 físicas; disponible queda cero sin desaparecer las 10. Merma posterior descuenta una sola vez |
| A05 | Pedido de 8, preparar/facturar/despachar 5; físico baja 5, factura cubre 5, pendiente conserva 3 |
| A06 | Fallar impresión tras emisión; reintento conserva número. Dos emisores concurrentes no comparten número |
| A07 | Despachar 5 y entregar 3; rechazo 2; retorno ingresa 2 en cuarentena. Nota de crédito ajusta deuda sin ingreso adicional |
| A08 | Factura de 110 con neto 100; cobrar 55 deja deuda 55. Comisión sobre cobrado usa base 50. Rendición no duplica cobro |
| A09 | Vendedor A 5% facturado y B 3% cobrado; sobre neto 100, A genera 5 al emitir, B genera 1,5 al cobrar la mitad |
| A10 | Comisión pagada 5; crédito por 20% de venta produce ajuste −1 para próxima liquidación y conserva liquidación original |
| A11 | Vendedor solicita por URL/API cartera ajena o costos; acceso denegado también en exportación. Cambio sensible conserva auditoría |
| A12 | Restaurar base y documentos en entorno separado; coinciden cantidades por lote, saldos, rangos utilizados y liquidaciones |
| A13 | Supervisor intenta vender lote bloqueado o crear stock negativo; rechazo aun con permiso elevado |
| A14 | Cobro de 100 aplicado 60 y 40 a dos facturas; siguiente aplicación se rechaza. Anticipo no aplicado no comisiona |
| A15 | Repetir recepción, despacho, cobro o pago con igual clave de operación no duplica movimientos |
| A16 | Inventario con movimiento posterior al conteo concilia antes del ajuste; autor del conteo no aprueba su propio ajuste |
| A17 | Cambio de porcentaje/precio/costo no modifica documentos, márgenes ni comisiones históricas |
| A18 | Exportación e impresión coinciden con filtros, alcance y totales; vencimientos y antigüedad no duplican intervalos |

## Puertas de salida

Definición funcional: revisar este catálogo con responsables de compras, depósito, ventas, caja y gerencia. Desarrollo posterior: ejecutar escenarios en base de pruebas. Producción: resolver habilitación fiscal, calibrar impresión física, conciliar carga inicial, verificar permisos y demostrar restauración. No declarar cumplimiento fiscal ni pruebas de aplicación a partir de esta documentación.
