# QA de Índice ERP

Esta carpeta define cómo validar manual y automáticamente cada entrega de Índice
ERP. Es documentación viva: todo cambio visible, contrato API, regla de acceso,
migración o evento de auditoría debe actualizarla en la misma entrega.

## Documentos

| Documento | Uso |
|---|---|
| [Plan de pruebas](TEST-PLAN.md) | Alcance, ambientes, datos, criterios de entrada/salida y ejecución |
| [Casos de prueba](TEST-CASES.md) | Casos funcionales y de seguridad actualmente implementados |
| [Regresión y evidencias](REGRESSION.md) | Checklist por entrega, evidencia requerida y reporte de defectos |
| [Plantilla para nuevas funcionalidades](FEATURE-TEMPLATE.md) | Contrato QA que debe completarse conforme crece el ERP |

## Estado cubierto

La línea base cubre:

- Bootstrap del primer administrador.
- Sesiones Sanctum con cookies y CSRF.
- Login, logout, cambio y recuperación de contraseña.
- Contraseñas temporales y bloqueo del resto de la aplicación.
- Roles `admin`, `operator` y `viewer` con capacidades efectivas.
- Administración de usuarios, estado e identidad.
- Catálogo actual de libros.
- Auditoría append-only y correlación mediante `X-Request-ID`.

Los módulos futuros de catálogo ampliado, inventario, compras, ventas y reportes
deben añadir sus casos usando `FEATURE-TEMPLATE.md`; no deben reemplazar ni
reducir esta regresión.

## Regla de mantenimiento

Una funcionalidad no está terminada hasta que:

1. Sus criterios de aceptación tienen casos positivos, negativos y de límites.
2. Las capacidades y roles afectados están identificados.
3. Los contratos HTTP y estados esperados están documentados.
4. Se definieron datos de prueba y limpieza.
5. Existen pruebas automatizadas para invariantes o regresiones plausibles.
6. Se ejecutó el flujo real en la interfaz o cliente consumidor.
7. Se anexó evidencia y cualquier riesgo residual.
