# Casos de prueba

## Convenciones

- Prioridad **P0**: seguridad, pérdida de acceso o integridad; ejecutar en cada
  release.
- Prioridad **P1**: flujo principal; ejecutar cuando cambia el módulo y en
  regresión completa.
- Prioridad **P2**: borde o presentación; ejecutar en regresión completa.
- Para llamadas directas de la SPA, usar un cliente con cookie jar: solicitar
  primero `GET /sanctum/csrf-cookie` y conservar cookies y encabezado XSRF.
- Verificar siempre el estado HTTP además del texto visible.

## 1. Bootstrap y registro público

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| BOOT-001 | P0 | En una base sin admin, ejecutar `php artisan app:bootstrap-admin`; ingresar nombre, correo y contraseña válidos. | Se crea o promueve un único admin activo, sin imprimir la contraseña; se registra `system.admin_bootstrapped`. |
| BOOT-002 | P0 | Ejecutar nuevamente el comando cuando ya existe un admin activo. | No modifica datos y termina de forma controlada. |
| BOOT-003 | P1 | Ejecutar dos instancias del comando simultáneamente. | El lock evita dos bootstrap independientes; al final existe al menos un admin activo y no hay emails duplicados. |
| REG-001 | P0 | Abrir `/register` y enviar `POST /api/register`. | No aparece formulario; la API responde `404`; no se crea usuario. |

## 2. Login, sesión y logout

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| AUTH-001 | P0 | Iniciar sesión con cada cuenta activa y contraseña correcta. | `200`; cookie de sesión regenerada; redirección a libros; sesión contiene rol y capacidades exactas. |
| AUTH-002 | P0 | Probar email inexistente, contraseña incorrecta y cuenta inactiva. | Todos responden `422` con el mismo mensaje genérico; no se revela cuál condición ocurrió. |
| AUTH-003 | P1 | Ingresar email con mayúsculas y espacios exteriores. | Se normaliza y autentica la cuenta correcta. |
| AUTH-004 | P0 | Realizar seis intentos fallidos dentro de un minuto con la misma identidad e IP. | Los primeros intentos usan la respuesta genérica; al superar cinco, responde `429`. |
| AUTH-005 | P0 | Capturar el ID de sesión antes y después de un login correcto. | El identificador cambia; no existe fijación de sesión. |
| AUTH-006 | P0 | Cerrar sesión y volver a solicitar `GET /api/user` y `/api/books`. | Logout responde `204`; las solicitudes posteriores responden `401`; el token CSRF anterior deja de ser operativo. |
| AUTH-007 | P0 | Mantener una sesión abierta, desactivar la cuenta desde otro admin y efectuar una solicitud. | La siguiente solicitud responde `401`, invalida la sesión y la SPA vuelve al login. |
| AUTH-008 | P0 | Reactivar la cuenta del caso anterior sin volver a iniciar sesión. | La sesión revocada no se restaura; requiere un login nuevo. |
| AUTH-009 | P1 | Solicitar una ruta protegida sin cookie o con cookie inválida. | `401`, nunca `403` ni contenido protegido. |

## 3. Contraseñas y recuperación

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| PWD-001 | P0 | Iniciar sesión con cuenta marcada `must_change_password`. | Solo permite consultar sesión, cambiar contraseña o cerrar sesión; cualquier libro, usuario o auditoría responde `403`; la SPA fuerza `/change-password`. |
| PWD-002 | P0 | Cambiar contraseña con la actual correcta, una nueva de 15 o más caracteres y confirmación coincidente. | `200`; se limpia la obligación temporal; se accede a libros; se registra `user.password_changed`. |
| PWD-003 | P1 | Probar contraseña actual incorrecta, nueva menor a 15, mayor a 255 o confirmación distinta. | `422` y error en el campo correspondiente; no cambia el hash ni la obligación temporal. |
| PWD-004 | P0 | Abrir dos sesiones, cambiar contraseña en una y usar la otra. | La sesión actual continúa; las otras sesiones quedan invalidadas. |
| REC-001 | P0 | Solicitar recuperación para un correo existente y otro inexistente. | Mismo estado y mensaje genérico; no enumera cuentas; aplica rate limit. |
| REC-002 | P0 | Abrir el enlace recibido en el buzón QA y definir contraseña válida. | El token restablece una sola vez, limpia la obligación temporal y registra solicitud/finalización. |
| REC-003 | P0 | Reutilizar token consumido, alterar token o usar email distinto. | `422` genérico; no cambia contraseña. |
| REC-004 | P1 | Restablecer contraseña de una cuenta inactiva e intentar login. | El reset puede finalizar, pero el login continúa rechazado con mensaje genérico. |
| REC-005 | P1 | Superar cinco solicitudes de recuperación por identidad/IP en un minuto. | Responde `429` sin indicar existencia de la cuenta. |

## 4. Capacidades y autorización

Matriz esperada:

| Acción | admin | operator | viewer |
|---|:---:|:---:|:---:|
| Ver catálogo | Sí | Sí | Sí |
| Crear/editar/eliminar catálogo | Sí | Sí | No |
| Ver/administrar usuarios | Sí | No | No |
| Ver auditoría | Sí | No | No |

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| AZ-001 | P0 | Consultar `GET /api/user` con los tres roles. | La lista `capabilities` coincide exactamente con la matriz documentada. |
| AZ-002 | P0 | Con operator y viewer, abrir `/users` y llamar directamente a `/api/users`. | No hay navegación; la SPA redirige; la API responde `403`. |
| AZ-003 | P0 | Con operator y viewer, abrir `/audit-logs` y llamar directamente a `/api/audit-logs`. | No hay navegación; la SPA redirige; la API responde `403`; la denegación queda auditada. |
| AZ-004 | P0 | Con viewer, abrir `/books/create`, `/books/{id}/edit` y llamar POST/PUT/DELETE directamente. | No se muestran controles; la SPA redirige; la API responde `403`; no hay modificación. |
| AZ-005 | P1 | Con operator, ejecutar alta, edición y baja de libro. | Operaciones permitidas; no adquiere capacidades de usuarios o auditoría. |
| AZ-006 | P0 | Cambiar IDs de usuarios y libros en URLs y payloads. | Recursos existentes siguen la Policy; inexistentes responden `404`; no hay escalamiento ni datos de otro ámbito. |
| AZ-007 | P0 | Intentar cambiar el propio rol de admin por API. | `403`; el rol permanece; se registra denegación. |

## 5. Administración de usuarios

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| USR-001 | P1 | Abrir usuarios como admin sin filtros. | Lista paginada con nombre, correo, rol, estado y tipo de credencial; carga y vacío son distinguibles. |
| USR-002 | P1 | Buscar por fragmento de nombre/correo y combinar filtros de rol y estado. | Solo aparecen coincidencias; total y páginas corresponden al filtro. |
| USR-003 | P0 | Crear usuario con nombre/email con espacios y mayúsculas, rol, estado y contraseña temporal válida. | `201`; normaliza nombre/email/rol; marca contraseña temporal; registra actor en `created_by` y `updated_by`; audita `user.created`. |
| USR-004 | P1 | Crear con email duplicado, rol inválido, contraseña corta o confirmación distinta. | `422` por campo; no hay usuario parcial ni auditoría de éxito. |
| USR-005 | P1 | Editar nombre y correo. | Cambia solo identidad; conserva rol/estado; actualiza `updated_by`; audita `user.updated` con nombres de campos, no valores. |
| USR-006 | P0 | Cambiar rol de viewer a operator y comprobar nueva sesión. | Audita roles anterior/nuevo; las capacidades efectivas cambian según la matriz. |
| USR-007 | P0 | Desactivar y reactivar una cuenta con confirmación visual. | Cambia estado, autoría y evento correspondiente; desactivar revoca la sesión abierta. |
| USR-008 | P0 | Con un único admin activo, intentar desactivarlo o degradarlo. | La operación queda rechazada; permanece admin activo; mensaje controlado, sin cambio parcial. |
| USR-009 | P0 | Con exactamente dos admins, lanzar simultáneamente operaciones que dejarían cero admins activos. | El bloqueo transaccional permite como máximo una; siempre queda al menos un admin activo. |
| USR-010 | P1 | Repetir un cambio de rol/estado con el mismo valor. | Respuesta idempotente; no crea ruido de auditoría. |
| USR-011 | P1 | Intentar borrar físicamente un usuario desde UI o API. | No existe operación de eliminación; se usa desactivación. |

## 6. Catálogo de libros

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| CAT-001 | P1 | Listar libros y cambiar página/cantidad/orden. | Datos y metadata de paginación coherentes; no hay `meta` o `links` duplicados. |
| CAT-002 | P1 | Buscar por título, autor e ISBN, incluyendo cero resultados. | Solo coincidencias; estado vacío visible y sin error. |
| CAT-003 | P1 | Crear libro con todos los campos válidos. | `201`; precios conservan dos decimales y stock entero. |
| CAT-004 | P1 | Crear sin título/autor/ISBN, con ISBN duplicado, precio negativo o stock negativo/no entero. | `422` por campo; no se persiste registro parcial. |
| CAT-005 | P1 | Editar identidad, precios y stock de un libro. | `200`; vista/listado refleja los nuevos valores. |
| CAT-006 | P1 | Eliminar un libro tras confirmar. | `204`; deja de aparecer en listado y consultas normales; la fila se conserva como soft delete. |
| CAT-007 | P1 | Cancelar confirmación de eliminación. | No se envía DELETE y el libro permanece. |
| CAT-008 | P1 | Consultar stock bajo con valores 0, 10 y 11. | 0 y 10 aparecen; 11 no aparece. |
| CAT-009 | P2 | Probar textos largos, Unicode y caracteres especiales válidos. | Se muestran como texto; no se ejecuta HTML o JavaScript. |

## 7. Auditoría y correlación

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| AUD-001 | P0 | Ejecutar login correcto/fallido, logout, recuperación, cambio de contraseña y administración de usuario. | Se crea el evento estable correspondiente con actor/sujeto cuando aplica. |
| AUD-002 | P0 | Inspeccionar metadata y respuesta de auditoría. | No contiene contraseñas, hashes, cookies, tokens, cadenas de conexión ni payloads completos. |
| AUD-003 | P1 | Filtrar por evento, actor, request ID y rango de fechas; combinar filtros. | Resultados, total y paginación coinciden exactamente. |
| AUD-004 | P0 | Comparar `X-Request-ID` de una respuesta con el evento generado. | UUID generado por servidor, expuesto por CORS y persistido en el evento. |
| AUD-005 | P0 | Enviar un `X-Request-ID` controlado por cliente, con saltos de línea o texto arbitrario. | Se ignora y reemplaza por UUID del servidor; no se inyecta en logs. |
| AUD-006 | P0 | Intentar actualizar o eliminar un `AuditLog` mediante aplicación/API. | No existen endpoints; el modelo rechaza mutación y borrado. |
| AUD-007 | P1 | Eliminar un actor en una base aislada donde se permita la operación técnica. | El evento permanece con `actor_id` nullable; no se pierde trazabilidad del evento. |
| AUD-008 | P1 | Validar actor nulo y sujeto nulo en eventos de sistema/fallos desconocidos. | La UI muestra “Sistema” o “—” sin error. |

## 8. Contratos HTTP y manejo de errores

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| API-001 | P0 | Repetir casos sin sesión, sin permiso, recurso inexistente, validación inválida y rate limit. | Estados `401`, `403`, `404`, `422` y `429` respectivamente. |
| API-002 | P1 | Revisar respuestas paginadas de libros, usuarios y auditoría. | Contienen `data`, `meta` y `links` una sola vez, con valores consistentes. |
| API-003 | P1 | Forzar un error validado desde la SPA. | Mensaje general en español y errores por campo cuando correspondan; no hay stack trace. |
| API-004 | P0 | Revisar encabezados/cookies en HTTPS publicado. | Cookies `Secure`, `HttpOnly` donde corresponde y `SameSite` configurado; CORS solo permite orígenes declarados. |
| API-005 | P1 | Confirmar `X-Request-ID` en éxito y errores controlados. | UUID presente y utilizable para correlacionar diagnóstico. |

## 9. Migraciones y compatibilidad

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| MIG-001 | P0 | Migrar una base previa con usuarios existentes. | Usuarios conservados como activos `viewer`, sin promoción implícita y con credenciales intactas. |
| MIG-002 | P0 | Ejecutar migraciones desde una base vacía. | Todas terminan; tabla de auditoría y campos de seguridad existen; `personal_access_tokens` queda eliminada. |
| MIG-003 | P1 | Ejecutar rollback en base desechable y volver a migrar. | Down/up completos sin pérdida fuera del alcance esperado. |
| MIG-004 | P0 | Ejecutar bootstrap después de migrar datos existentes. | Promoción explícita; nunca selección heurística por ID, antigüedad o email. |

## 10. Presentación y accesibilidad básica

| ID | Pri. | Caso y pasos | Resultado esperado |
|---|---:|---|---|
| UI-001 | P2 | Recorrer formularios con teclado. | Orden lógico, foco visible, submit y cancelación operables. |
| UI-002 | P2 | Revisar labels, nombres accesibles de botones y errores. | Controles identificables; errores asociados visualmente al campo. |
| UI-003 | P2 | Probar escritorio y viewport móvil en login, contraseñas, libros, usuarios y auditoría. | Sin controles inaccesibles ni pérdida de información crítica; tablas permiten uso razonable. |
| UI-004 | P2 | Simular respuesta lenta y error de red. | Estado de carga visible, acciones no duplicadas y mensaje recuperable. |
