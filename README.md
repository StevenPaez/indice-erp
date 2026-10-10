# Índice ERP

Aplicación web para administrar un catálogo de libros, inventario y precios.
El repositorio puede usarse como proyecto completo, punto de partida para otro
ERP o ejemplo de una SPA autenticada con Laravel.

## Funcionalidades

- Inicio, cierre, cambio y recuperación de contraseña.
- Cuentas internas sin registro público y bootstrap seguro del primer administrador.
- Autenticación SPA mediante Laravel Sanctum con cookies y CSRF.
- Autorización por capacidades para los roles `admin`, `operator` y `viewer`.
- Administración de usuarios con búsqueda, filtros, roles, activación y contraseñas temporales.
- Auditoría append-only con filtros, actor, recurso e identificador de solicitud.
- Consulta paginada de libros.
- Búsqueda por título, autor o ISBN.
- Alta, edición y eliminación lógica de libros.
- Control de existencias y consulta de stock bajo.
- Validación del backend con respuestas JSON.
- Interfaz adaptable construida con componentes PrimeVue.

## Tecnologías

| Área | Herramientas |
|---|---|
| Frontend | Vue 3, Vite, Vue Router, Pinia, PrimeVue y Axios |
| Backend | Laravel 13 y PHP 8.3 o posterior |
| Persistencia | MySQL 8 |
| Autenticación | Laravel Sanctum |
| Desarrollo local | Docker Compose, Nginx y Redis |

## Requisitos

Para el arranque recomendado:

- Docker y Docker Compose.
- Node.js 22.12 o posterior.
- npm.

Para ejecutar el backend sin Docker también se necesita PHP 8.3 o posterior,
Composer y las extensiones requeridas por Laravel.

## Instalación rápida

```bash
git clone <URL-DE-TU-FORK-O-COPIA> indice-erp
cd indice-erp

cp .env.example .env
cp backend/.env.example backend/.env

chmod +x startup.sh
./startup.sh
```

El script:

1. Construye los contenedores.
2. Inicia PHP-FPM, Nginx, MySQL y Redis.
3. Genera `APP_KEY` cuando sea necesario.
4. Ejecuta las migraciones.
5. Instala las dependencias del frontend.
6. Inicia Vite.

Después del primer arranque, crear o promover el administrador inicial desde
otra terminal:

```bash
docker compose exec app php artisan app:bootstrap-admin
```

Servicios predeterminados:

| Servicio | Dirección |
|---|---|
| Aplicación web | `http://localhost:5173` |
| API | `http://localhost:8000/api` |
| MySQL | `localhost:3390` |
| Redis | `localhost:6380` |

## Instalación manual

### Backend y servicios

```bash
cp .env.example .env
cp backend/.env.example backend/.env

docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan app:bootstrap-admin
```

### Frontend

```bash
cd frontend
npm ci
npm run dev
```

Durante el desarrollo, Vite reenvía las solicitudes `/api` y `/sanctum` al
backend local.

## Variables de entorno

No versionar archivos `.env`. Los archivos incluidos en el repositorio son
plantillas y no contienen credenciales reales.

### Backend

Las variables principales están en `backend/.env.example`:

| Variable | Uso |
|---|---|
| `APP_URL` | URL pública del backend |
| `APP_KEY` | Clave de cifrado de Laravel |
| `DB_*` | Conexión a MySQL |
| `REDIS_*` | Conexión a Redis |
| `FRONTEND_URL` | URL permitida para el frontend |
| `CORS_ALLOWED_ORIGINS` | Orígenes CORS separados por comas |
| `SANCTUM_STATEFUL_DOMAINS` | Dominios SPA, sin protocolo |
| `SESSION_*` | Persistencia y atributos de la cookie |
| `MAIL_*` | Transporte de los enlaces de recuperación |

Generar una clave:

```bash
cd backend
php artisan key:generate
```

### Frontend

La plantilla `frontend/.env.example` acepta:

```dotenv
VITE_APP_NAME="Indice ERP"
VITE_API_URL=http://localhost:8000
```

`VITE_API_URL` puede omitirse cuando frontend y API se publican bajo el mismo
origen o cuando existe un proxy para `/api` y `/sanctum`.

## API

Las rutas se encuentran bajo `/api`.

### Públicas

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/login` | Iniciar sesión |
| `POST` | `/api/forgot-password` | Solicitar recuperación de contraseña |
| `POST` | `/api/reset-password` | Restablecer contraseña con un token |

No existe registro público. El primer administrador se establece con
`app:bootstrap-admin`; las demás cuentas serán creadas por administradores.

### Autenticadas

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/user` | Obtener el usuario actual |
| `POST` | `/api/logout` | Cerrar sesión |
| `PUT` | `/api/user/password` | Cambiar la contraseña propia |
| `GET` | `/api/users` | Listar y filtrar usuarios (`users.view`) |
| `POST` | `/api/users` | Crear un usuario con contraseña temporal (`users.manage`) |
| `GET` | `/api/users/{id}` | Consultar un usuario (`users.view`) |
| `PUT/PATCH` | `/api/users/{id}` | Editar nombre y correo (`users.manage`) |
| `PATCH` | `/api/users/{id}/role` | Cambiar el rol (`users.manage`) |
| `PATCH` | `/api/users/{id}/status` | Activar o desactivar (`users.manage`) |
| `GET` | `/api/audit-logs` | Consultar auditoría paginada (`audit.view`) |
| `GET` | `/api/books` | Listar y filtrar libros |
| `POST` | `/api/books` | Crear un libro |
| `GET` | `/api/books/{id}` | Consultar un libro |
| `PUT/PATCH` | `/api/books/{id}` | Actualizar un libro |
| `DELETE` | `/api/books/{id}` | Eliminar un libro |
| `GET` | `/api/books/low-stock` | Consultar stock bajo |

La autenticación web usa cookies de sesión y un token CSRF. Antes de una
operación que modifica datos, el cliente solicita `/sanctum/csrf-cookie`.

Login y recuperación están limitados por email normalizado e IP. Las respuestas
no confirman si una cuenta existe o está inactiva. Una cuenta con contraseña
temporal solo puede consultar su sesión, cambiar esa contraseña o cerrar sesión.
Los enlaces de recuperación usan `FRONTEND_URL`; para entregarlos debe
configurarse un transporte `MAIL_*` real.

La sesión incluye la lista de capacidades efectivas. `admin` administra usuarios
y catálogo; `operator` administra el catálogo; `viewer` tiene acceso de
consulta. Las Policies del backend son la fuente de verdad y la SPA usa esas
capacidades únicamente para navegación y presentación.

Cada respuesta incluye `X-Request-ID`, generado por el servidor. Los eventos de
auditoría asociados conservan ese identificador para correlación técnica. La
consulta administrativa permite filtrar por evento, actor, solicitud y rango de
fechas.

Los registros de auditoría no se actualizan ni eliminan desde la aplicación. El
responsable de cada despliegue debe definir fuera del repositorio su plazo de
retención, acceso a respaldos, exportación y eliminación controlada conforme a
sus obligaciones operativas y legales.

## Comandos útiles

### Frontend

```bash
cd frontend
npm run dev
npm run build
npm run preview
```

### Backend

```bash
cd backend
php artisan migrate
php artisan migrate:fresh
php artisan app:bootstrap-admin
php artisan route:list
php artisan test
```

`app:bootstrap-admin` crea o promueve de forma interactiva el primer
administrador. Debe ejecutarse después de las migraciones. No acepta la
contraseña como argumento y no efectúa cambios si ya existe un administrador
activo.

Con Docker, anteponer:

```bash
docker compose exec app
```

Ejemplo:

```bash
docker compose exec app php artisan test
```

## Pruebas

```bash
cd backend
php artisan test

cd ../frontend
npm ci
npm run build
```

Los tests del backend usan SQLite en memoria y no modifican la base MySQL de
desarrollo.

### QA funcional y de seguridad

La documentación viva de QA se encuentra en [`docs/qa/`](docs/qa/README.md):

- [Plan de pruebas](docs/qa/TEST-PLAN.md)
- [Casos funcionales y de seguridad](docs/qa/TEST-CASES.md)
- [Regresión, evidencias y defectos](docs/qa/REGRESSION.md)
- [Plantilla QA para funcionalidades nuevas](docs/qa/FEATURE-TEMPLATE.md)

Cada funcionalidad nueva debe actualizar sus casos y la regresión afectada en la
misma entrega.

## Personalización

Para adaptar el proyecto:

1. Cambiar `APP_NAME` y `VITE_APP_NAME`.
2. Reemplazar colores, textos e iconos del frontend.
3. Crear nuevas migraciones; no editar migraciones que ya fueron aplicadas.
4. Añadir modelos, servicios, controladores y rutas para nuevos módulos.
5. Actualizar la matriz de capacidades y sus Policies al añadir acciones protegidas.
6. Configurar correo, colas, almacenamiento y respaldos según el entorno.

## Publicación

El proyecto no depende de un proveedor específico. Para publicar una copia:

1. Compilar `frontend` con `npm ci && npm run build`.
2. Servir `frontend/dist` como SPA con fallback a `index.html`.
3. Publicar `backend` con PHP 8.3 o posterior.
4. Configurar MySQL y ejecutar `php artisan migrate --force`.
5. Definir una `APP_KEY` persistente y desactivar `APP_DEBUG`.
6. Configurar HTTPS, CORS, dominios stateful de Sanctum y cookies seguras.
7. Ejecutar workers separados si se habilitan colas asíncronas.
8. Mantener respaldos automatizados de la base de datos.

Para autenticación Sanctum basada en cookies, frontend y backend deben compartir
el mismo dominio superior o exponerse mediante un proxy de mismo origen.

## Seguridad

- No publicar `.env`, claves, contraseñas ni respaldos.
- Mantener `APP_DEBUG=false` fuera del desarrollo local.
- Usar HTTPS y cookies `Secure`.
- Restringir el acceso público a MySQL.
- Usar usuarios de base de datos con privilegios mínimos.
- Revisar autorización, roles y políticas antes de manejar datos reales.
