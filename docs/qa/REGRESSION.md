# Regresión, evidencias y defectos

## 1. Smoke crítico por entrega

Ejecutar en este orden. Detener la liberación ante el primer fallo P0.

- [ ] `/up` y SPA responden.
- [ ] Migraciones aplicadas sin error.
- [ ] Admin activo inicia sesión.
- [ ] Credenciales inválidas e inactivo reciben respuesta genérica.
- [ ] Logout invalida la sesión.
- [ ] Usuario con contraseña temporal queda limitado a cambio/logout.
- [ ] Viewer consulta libros, pero no crea, edita o elimina.
- [ ] Operator administra catálogo, pero no usuarios ni auditoría.
- [ ] Admin administra usuarios y consulta auditoría.
- [ ] Desactivar una cuenta revoca su sesión abierta.
- [ ] No es posible dejar cero administradores activos.
- [ ] Recuperación entrega enlace al buzón QA y el token funciona una sola vez.
- [ ] Un cambio sensible produce auditoría sin secretos.
- [ ] `X-Request-ID` coincide entre respuesta y auditoría.
- [ ] Suite backend, Pint y build frontend terminan correctamente.

## 2. Regresión según área modificada

| Cambio | Regresión mínima adicional |
|---|---|
| Login, sesión, middleware o Sanctum | AUTH completa, PWD-001, AZ completa, CORS/cookies |
| Password broker o correo | REC completa, PWD-002/003, auditoría asociada |
| User, roles, Policies o capacidades | AZ y USR completas, sesión abierta/inactiva, último admin |
| Libros o BookPolicy | CAT completa y matriz de escritura por rol |
| AuditService, eventos o middleware request ID | AUD completa y acciones sensibles de AUTH/USR |
| Migraciones o casts | MIG completa y suite sobre MySQL además de SQLite |
| Router, store de sesión o Axios | Smoke completo en navegador y estados 401/403/422 |
| Componentes/formularios | Flujo positivo, validación, carga, vacío, error y viewport móvil |
| Dependencias o build | Suite completa, build, smoke y revisión de avisos de seguridad |

## 3. Automatización existente

| Área | Ubicación principal |
|---|---|
| Login y sesión | `backend/tests/Feature/Auth/AuthenticationTest.php` |
| Cuenta activa y revocación | `backend/tests/Feature/Auth/ActiveUserSessionTest.php` |
| Contraseñas y recuperación | `backend/tests/Feature/Auth/PasswordLifecycleTest.php` |
| Capacidades y catálogo por rol | `backend/tests/Feature/Auth/AuthorizationTest.php` |
| Administración de usuarios | `backend/tests/Feature/Users/UserAdministrationApiTest.php` |
| Último administrador | `backend/tests/Feature/Users/UserAccessServiceTest.php` |
| Estado/migración de usuarios | `backend/tests/Feature/Users/UserSecurityStateTest.php` |
| Bootstrap | `backend/tests/Feature/Console/BootstrapAdminTest.php` |
| Auditoría | `backend/tests/Feature/Audit/` |
| CRUD de libros | `backend/tests/Feature/Books/BookCrudTest.php` |
| Servicios de catálogo | `backend/tests/Unit/Services/BookServiceTest.php` |

La automatización no sustituye el smoke de la interfaz. El frontend todavía no
tiene una suite de componentes; los flujos de navegación, formularios y estados
visuales deben comprobarse en navegador hasta incorporar esa capa.

## 4. Evidencia mínima

Registrar una ejecución con:

- Versión o commit.
- Ambiente y URL, sin credenciales.
- Fecha/hora UTC y responsable.
- Navegador/dispositivo.
- IDs de casos ejecutados y resultado.
- Comandos automáticos y conteo reportado por las herramientas.
- Defectos vinculados.
- Riesgos aceptados y aprobador.

Adjuntar evidencia únicamente cuando demuestra un resultado:

| Resultado | Evidencia recomendada |
|---|---|
| Control visual/capacidad | Captura con usuario/rol de QA identificable, sin secretos |
| Contrato HTTP | Método, ruta, estado y fragmento mínimo del JSON |
| Revocación de sesión | Secuencia antes/después y estados HTTP |
| Último administrador | Estado previo, solicitudes concurrentes y conteo final |
| Auditoría | Evento, actor, sujeto y request ID; ocultar PII no necesaria |
| Migración | Salida del comando y conteos/invariantes antes/después |

No adjuntar cookies, token CSRF, token de recuperación, contraseña, hash, `.env`,
dump completo o encabezado `Authorization`.

## 5. Reporte de defectos

Plantilla:

```text
Título: [Módulo] resultado incorrecto observable
Ambiente/versión:
Severidad: crítica | alta | media | baja
Caso QA relacionado:
Precondiciones y datos no sensibles:
Pasos exactos:
Resultado actual:
Resultado esperado:
Estado HTTP / X-Request-ID:
Frecuencia:
Evidencia sanitizada:
Impacto y alcance:
```

Severidades:

- **Crítica:** acceso no autorizado, exposición de secretos, pérdida/corrupción
  irreversible, cero administradores activos o indisponibilidad general.
- **Alta:** flujo principal imposible, revocación fallida, recuperación rota,
  auditoría sensible ausente o bypass de rol.
- **Media:** validación, filtro, paginación o estado de UI incorrecto con
  alternativa operativa.
- **Baja:** presentación, texto o accesibilidad sin pérdida funcional inmediata.

## 6. Cierre y limpieza

- Cerrar sesiones y revocar enlaces de recuperación pendientes.
- Eliminar correos capturados que contengan enlaces activos.
- Restaurar rate limits y reloj si fueron manipulados.
- Retirar cuentas/datos de QA o desactivarlos según política.
- Detener servicios temporales y borrar bases desechables.
- Confirmar que no quedaron logs o artefactos con secretos.
- Conservar solo evidencia sanitizada y el resultado consolidado.

## 7. Decisión de liberación

**Go:** criterios de salida cumplidos y sin defectos críticos/altos.

**Go condicionado:** solo defectos medios/bajos, impacto documentado, mitigación
operativa y aprobación explícita.

**No-Go:** cualquier P0 fallido, migración no reversible, diferencia de permisos
respecto de la matriz, enumeración de cuentas, secreto expuesto o evidencia
insuficiente sobre una invariante crítica.
