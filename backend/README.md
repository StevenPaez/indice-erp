# API de Indice ERP

API REST Laravel para autenticación y gestión de libros.

## Capas

- `app/Http/Controllers/Api`: entrada HTTP.
- `app/Http/Requests`: validación.
- `app/Http/Resources`: representación JSON.
- `app/Services`: operaciones de aplicación.
- `app/Models`: persistencia Eloquent.
- `routes/api.php`: rutas públicas y protegidas por Sanctum.

## Comandos

```bash
composer install
php artisan migrate
php artisan test
```

La configuración completa de desarrollo y Laravel Cloud está en el
[`README.md` raíz](../README.md).
