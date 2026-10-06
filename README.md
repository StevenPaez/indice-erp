# Indice ERP

Mini ERP para gestión de compras y ventas de libros.

## Arquitectura

```text
Navegador
  └─ Netlify: Vue 3 SPA
       ├─ archivos estáticos y rutas del frontend
       └─ proxy /api/* y /sanctum/* (mismo origen)
            └─ Laravel Cloud: API Laravel
                 └─ Laravel MySQL
```

El proxy de Netlify es intencional. La autenticación SPA de Laravel Sanctum
requiere que frontend y API compartan el dominio superior. Los dominios
`netlify.app` y `laravel.cloud` no lo comparten; el proxy mantiene las cookies
de sesión y CSRF como cookies de primer nivel. En producción, el frontend no
debe definir `VITE_API_URL`.

| Capa | Tecnología |
|---|---|
| Frontend | Vue 3, Vite, Vue Router, Pinia, PrimeVue y Axios |
| Backend | Laravel 13, PHP 8.3+ y API REST bajo `/api` |
| Autenticación | Laravel Sanctum con sesión y protección CSRF |
| Base de datos | MySQL 8 |
| Desarrollo local | Docker Compose con Nginx, PHP-FPM, MySQL y Redis |

## Distribución

- `frontend/src/views`: pantallas de acceso, registro y libros.
- `frontend/src/stores`: estado de autenticación y libros.
- `frontend/src/api`: cliente Axios y servicios de la API.
- `backend/app/Http`: controladores, requests y resources HTTP.
- `backend/app/Services`: casos de uso del dominio.
- `backend/app/Models`: modelos Eloquent.
- `backend/routes/api.php`: contrato HTTP de la API.
- `backend/database/migrations`: esquema versionado.
- `netlify.toml`: compilación SPA, proxy hacia Laravel Cloud y fallback del router.

## Desarrollo local

Requisitos: Docker, Docker Compose y Node.js 22.12 o posterior.

```bash
cp .env.example .env
chmod +x startup.sh
./startup.sh
```

Servicios:

- Frontend: `http://localhost:5173`
- API: `http://localhost:8000/api`
- MySQL: `localhost:3390`, base `indice_db`

El servidor de Vite redirige `/api` y `/sanctum` al backend local.

## Despliegue del frontend en Netlify

La configuración está versionada en `netlify.toml`:

- Base directory: `frontend`
- Build command: `npm run build`
- Publish directory: `frontend/dist`
- Node.js: 22.12 o posterior

No definir `VITE_API_URL` en Netlify. Las solicitudes relativas pasan por los
proxies `/api/*` y `/sanctum/*` antes del fallback de la SPA. Si cambia el
dominio del backend, actualizar ambos destinos en `netlify.toml`.

## Despliegue del backend en Laravel Cloud

Configurar la aplicación como monorepo con **Root directory** `backend` y
runtime PHP 8.4.

```text
Build command:
composer install --no-dev --optimize-autoloader --no-interaction

Deploy command:
php artisan migrate --force
```

Variables del ambiente `production`:

```dotenv
APP_NAME="Indice ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://indice-erp-app-production-vjktrt.laravel.cloud
APP_KEY=base64:GENERAR_UN_VALOR_NUEVO

FRONTEND_URL=https://indice-erp.netlify.app
CORS_ALLOWED_ORIGINS=https://indice-erp.netlify.app
SANCTUM_STATEFUL_DOMAINS=indice-erp.netlify.app

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database

DB_CONNECTION=mysql
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
LOG_CHANNEL=stderr
```

Generar `APP_KEY` una sola vez y conservarla entre despliegues:

```bash
cd backend
php artisan key:generate --show
```

No definir `SESSION_DOMAIN`: la cookie debe ser host-only para funcionar a
través del proxy de Netlify.

## Base de datos en Laravel Cloud

Recomendación para desarrollo compartido:

1. Crear **Laravel MySQL** en la misma región del entorno.
2. Usar una instancia Flex con scale-to-zero y 5 GB mientras la carga sea baja.
3. Crear la base `indice_erp` y adjuntarla al ambiente `production`.
4. Mantener el endpoint público desactivado salvo durante una importación.
5. Activar respaldos diarios: 2 días para pruebas y al menos 7 días al pasar a producción real.
6. Redesplegar. Cloud inyecta `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
   `DB_USERNAME` y `DB_PASSWORD`; el deploy ejecuta las migraciones.

Las sesiones, caché y colas usan inicialmente MySQL, suficiente para un
ambiente de pruebas pequeño. Añadir Laravel Valkey y cambiar esos drivers a
`redis` cuando existan múltiples instancias, workers permanentes o más carga.

## Verificación

```bash
cd backend
php artisan test

cd ../frontend
npm ci
npm run build
```

Después de desplegar:

```bash
curl https://indice-erp-app-production-vjktrt.laravel.cloud/up
curl -i https://indice-erp.netlify.app/api/user
```

El segundo comando debe responder `401` JSON sin sesión, no `200 text/html`.
