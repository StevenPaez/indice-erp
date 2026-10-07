# AGENTS.md

## Propósito

Este archivo define las reglas de trabajo para personas y agentes que modifiquen
Índice ERP. Aplica a todo el repositorio, salvo que un directorio incorpore un
`AGENTS.md` más específico.

Las palabras **DEBE**, **NO DEBE**, **DEBERÍA** y **PUEDE** expresan requisitos,
no sugerencias informales.

## Objetivo del producto

Índice ERP es primero un mini-ERP para la operación real de una librería
cristiana. Debe crecer de forma incremental sin convertirse prematuramente en
un SaaS genérico.

Principios:

1. Resolver primero un flujo real de Librería Índice.
2. Modelar el dominio con conceptos comerciales reutilizables.
3. Favorecer un monolito modular antes que microservicios.
4. Elegir implementaciones simples que permitan migraciones futuras.
5. No añadir abstracciones, configurabilidad o módulos sin un caso de uso.

## Idioma y nombres

- El código, nombres de tablas, columnas, clases, métodos, rutas, eventos y
  claves JSON DEBEN escribirse en inglés.
- Los textos visibles, mensajes de validación y documentación funcional para el
  usuario DEBEN escribirse en español.
- Los identificadores DEBEN representar conceptos del dominio: `Product`,
  `Category`, `Warehouse`, `InventoryMovement`, no abreviaturas ambiguas.
- El dominio NO DEBE incorporar el nombre comercial de la librería en clases o
  tablas; la marca pertenece a configuración y presentación.

## Límites tecnológicos

- Backend: Laravel 13, PHP 8.3 o posterior, Sanctum y Eloquent.
- Frontend: Vue 3 con Composition API, Vite, Pinia, Vue Router y PrimeVue 4.
- Persistencia principal: MySQL 8.
- El proyecto DEBE mantenerse como monolito modular mientras sus límites sean
  suficientes.
- No introducir otro framework, ORM, gestor de estado o librería visual sin una
  decisión documentada.

## Organización del backend

Flujo HTTP esperado:

```text
Route
  → Controller
  → Form Request + Policy
  → Service/Action
  → Eloquent Model
  → API Resource
```

### Responsabilidades

- `routes/api.php`: declarar rutas y middleware; no contener lógica de negocio.
- `Controllers`: coordinar la solicitud y respuesta; deben permanecer delgados.
- `Requests`: validar y normalizar entradas; pueden autorizar mediante políticas.
- `Policies`: decidir si un usuario puede ejecutar una acción sobre un recurso.
- `Services` o `Actions`: contener casos de uso y límites transaccionales.
- `Models`: relaciones, casts, scopes e invariantes locales; no orquestar flujos
  HTTP.
- `Resources`: definir el contrato JSON; no retornar modelos sin una decisión
  explícita.

### Reglas backend

- Toda entrada externa DEBE validarse en el servidor.
- Las operaciones con varias escrituras relacionadas DEBEN usar una transacción.
- La autorización DEBE ejecutarse en el backend en cada solicitud protegida.
- Ocultar botones en Vue es UX, nunca un control de seguridad.
- Los controladores NO DEBEN consultar o persistir múltiples agregados
  directamente si la operación constituye un caso de uso.
- No crear una capa Repository genérica sobre Eloquent sin una necesidad real.
- Evitar eventos, listeners y jobs para trabajo que deba confirmarse en la misma
  transacción.
- Los jobs DEBEN ser idempotentes cuando puedan reintentarse.
- Las fechas se almacenan en UTC y se presentan en la zona horaria configurada.
- Los importes monetarios NO DEBEN representarse con `float`.
- Las migraciones desplegadas NO DEBEN editarse; crear una nueva migración.
- Las eliminaciones físicas de información operativa requieren una decisión
  explícita. Preferir desactivación, anulación o soft delete según el dominio.

## Organización del frontend

Flujo esperado:

```text
View / Component
  → Pinia Store o composable
  → API Service
  → Axios client
  → Laravel API
```

### Reglas frontend

- Usar componentes Vue SFC con `<script setup>` y Composition API.
- Las vistas coordinan pantallas; la lógica reutilizable pertenece a
  composables, stores o servicios.
- Los servicios API son la única capa que conoce endpoints HTTP.
- Pinia mantiene sesión y estado compartido; el estado local permanece en el
  componente cuando no necesita compartirse.
- Cada flujo asíncrono DEBE representar carga, éxito, vacío y error.
- Los errores del backend deben traducirse a mensajes en español sin perder los
  errores por campo.
- No usar `v-html` con contenido no confiable.
- No construir plantillas, JavaScript, estilos o URLs ejecutables desde datos del
  usuario.
- Las capacidades recibidas del backend pueden controlar la interfaz, pero no
  sustituyen las Policies.
- Los componentes compartidos deben extraerse cuando exista reutilización real,
  no anticipada.

## API y errores

- La API usa JSON bajo `/api`.
- `401` significa usuario no autenticado.
- `403` significa usuario autenticado sin permiso.
- `404` puede utilizarse para ocultar recursos que el usuario no debe descubrir.
- `422` representa validación fallida.
- Los mensajes de autenticación DEBEN ser genéricos para evitar enumeración de
  cuentas.
- Las respuestas paginadas DEBEN mantener una estructura consistente.
- Los cambios incompatibles del contrato API requieren migrar todos los
  consumidores en el mismo cambio o versionar explícitamente.

## Autenticación y autorización

- Sanctum con cookies y CSRF es el mecanismo de la SPA propia.
- El registro público permanecerá deshabilitado durante el MVP interno.
- Los usuarios serán creados o invitados por un administrador.
- Roles iniciales: `admin`, `operator`, `viewer`.
- La ausencia de una regla explícita DEBE denegar acceso.
- Aplicar mínimo privilegio y verificar permisos en cada solicitud.
- Usar Policies para recursos y Gates solo para capacidades no ligadas a un
  modelo.
- No dispersar comparaciones de roles por controladores y vistas. Las reglas
  pertenecen a Policies o capacidades centralizadas.
- Un usuario inactivo NO DEBE iniciar sesión ni conservar acceso operativo.
- Login, recuperación de contraseña y acciones sensibles DEBEN tener rate limit.
- Login y reset DEBEN evitar revelar si una cuenta existe.
- Al iniciar sesión se regenera la sesión; al cerrar sesión se invalida y rota el
  token CSRF.
- Las contraseñas se procesan únicamente mediante el hasher de Laravel.
- Nunca registrar contraseñas, tokens, cookies, secretos ni cadenas de conexión.

## Roles MVP

| Capacidad | admin | operator | viewer |
|---|:---:|:---:|:---:|
| Ver panel y catálogos | Sí | Sí | Sí |
| Administrar usuarios | Sí | No | No |
| Crear y editar catálogo | Sí | Sí | No |
| Registrar movimientos | Sí | Sí | No |
| Ejecutar ajustes sensibles | Sí | No | No |
| Consultar auditoría | Sí | No | No |

Toda nueva capacidad DEBE añadirse primero a esta matriz y luego implementarse.

## Auditoría y logging

Distinguir:

- **Auditoría de negocio:** quién realizó una operación y sobre qué entidad.
- **Logging técnico y de seguridad:** diagnóstico, autenticación y fallos.

Para el MVP:

- Las entidades relevantes tendrán `created_by` y `updated_by` cuando aporte
  trazabilidad.
- Las operaciones sensibles generarán un registro de auditoría con actor,
  acción, recurso, fecha y metadatos mínimos.
- Los movimientos confirmados no se reescriben; se compensan con otro
  movimiento.
- Registrar éxitos/fallos de autenticación, denegaciones de autorización,
  administración de usuarios y cambios de rol o estado.
- No guardar payloads completos por defecto.
- No guardar contraseñas, tokens, cookies, claves, credenciales, ni datos
  personales innecesarios.
- Los valores procedentes del usuario deben sanitizarse antes de incorporarlos a
  logs para evitar log injection.

## Base de datos

- Usar claves foráneas e índices para invariantes y consultas conocidas.
- Las restricciones únicas críticas DEBEN existir en la base de datos, no solo
  en validación PHP.
- Las columnas de estado o rol deben tener una representación explícita y
  validada mediante enums de aplicación cuando corresponda.
- No duplicar datos derivados salvo que exista una estrategia transaccional de
  consistencia.
- Los seeders no deben contener credenciales reales.
- El primer administrador no debe crearse con una contraseña conocida y
  versionada.

## Estilo y calidad

### PHP

- Seguir el estilo Laravel y ejecutar Pint.
- Usar tipos de parámetros y retornos cuando sean claros y compatibles con el
  framework.
- Preferir inyección de dependencias y clases finales cuando no haya herencia
  prevista.
- Evitar comentarios que repitan el código; documentar decisiones e invariantes.

### JavaScript y Vue

- Usar módulos ES.
- Preferir `const`; usar `let` solo cuando exista reasignación.
- Mantener funciones pequeñas y nombres descriptivos.
- No ignorar promesas ni errores de red.
- Evitar watchers cuando un valor pueda ser `computed`.

## Pruebas

- Cada cambio de comportamiento DEBE incluir una prueba que pueda fallar por un
  defecto visible al usuario o una violación de seguridad.
- Las Policies DEBEN probar casos permitidos y denegados por rol.
- Probar `401`, `403`, validación, usuarios inactivos y escalamiento de
  privilegios.
- Las pruebas deben ser deterministas, aisladas y no depender de servicios
  externos reales.
- Usar factories y `RefreshDatabase` para pruebas de persistencia.
- No probar detalles internos, texto fuente, getters triviales ni forwarding.
- Después de cambios significativos, ejecutar el flujo real además de la suite.

Comandos mínimos:

```bash
cd backend
php artisan test
./vendor/bin/pint --test

cd ../frontend
npm run build
```

El frontend no tiene todavía una suite automatizada. No añadir pruebas
superficiales para compensarlo; incorporar una herramienta cuando existan
comportamientos de componentes que justifiquen mantenimiento permanente.

## Seguridad y secretos

- Nunca versionar `.env`, respaldos, tokens o credenciales.
- No imprimir secretos en logs, errores, fixtures o documentación.
- Usar HTTPS fuera de desarrollo local.
- Mantener dependencias soportadas y revisar alertas de seguridad.
- Cualquier endpoint público nuevo requiere análisis de abuso y rate limiting.
- Las URLs suministradas por usuarios deben validarse en backend.
- Todo acceso a archivos debe validar autorización y tipo/tamaño del contenido.

## Documentación y decisiones

- `README.md`: instalación y uso público del proyecto.
- `ARCHITECTURE.md`: arquitectura lógica, límites y decisiones técnicas.
- `.private/`: runbooks locales no versionados; nunca referenciarlos como
  requisito de compilación.
- Las decisiones difíciles de revertir DEBEN registrarse en
  `ARCHITECTURE.md` antes de implementar.
- Si el código contradice la documentación, el cambio debe corregir ambos en la
  misma entrega.

## Flujo de cambio

1. Confirmar el caso de uso y criterios de aceptación.
2. Revisar arquitectura, código relacionado y referencias de símbolos.
3. Definir migración y compatibilidad de datos.
4. Implementar verticalmente: base de datos, backend, autorización, frontend.
5. Probar comportamiento permitido y denegado.
6. Ejecutar formatter, tests y smoke test.
7. Actualizar documentación y eliminar caminos obsoletos.

## Definición de terminado

Un cambio está terminado únicamente cuando:

- Funciona de extremo a extremo.
- Valida y autoriza en backend.
- Presenta estados de carga, error y vacío en frontend cuando aplican.
- Tiene pruebas de comportamiento y seguridad relevantes.
- Migra los datos existentes sin pérdida.
- No introduce rutas alternativas obsoletas.
- La documentación pública no contiene secretos ni detalles privados de
  operación.

## Fuentes normativas

- [Laravel 13: Authorization](https://laravel.com/docs/13.x/authorization)
- [Laravel 13: Password Reset](https://laravel.com/docs/13.x/passwords)
- [Laravel 13: Hashing](https://laravel.com/docs/13.x/hashing)
- [Laravel 13: Rate Limiting](https://laravel.com/docs/13.x/rate-limiting)
- [Laravel 13: Validation](https://laravel.com/docs/13.x/validation)
- [Laravel Pint](https://laravel.com/docs/13.x/pint)
- [Vue: Security](https://vuejs.org/guide/best-practices/security.html)
- [Vue: State Management](https://vuejs.org/guide/scaling-up/state-management.html)
- [Vue: Testing](https://vuejs.org/guide/scaling-up/testing.html)
- [OWASP: Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP: Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
- [OWASP: Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)
