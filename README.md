# Indice ERP

Mini ERP para gestion de compras y ventas de libros.

## Stack

| Capa | Tecnologia |
|------|-----------|
| Backend | Laravel 12 + PHP 8.4 |
| Frontend | Vue 3 + Vite + Pinia + PrimeVue |
| Base de datos | MySQL 8.0 |
| Cache/Sesiones | Redis 7 |
| Autenticacion | Laravel Sanctum (SPA) |

## Requisitos

- Docker y Docker Compose
- Node.js 18+

## Arranque rapido

```bash
cd indice-erp
cp .env.example .env
chmod +x startup.sh
./startup.sh
```

El script levanta los contenedores (backend, MySQL, Redis), ejecuta migraciones y arranca el frontend.

**URLs:**
- Frontend: http://localhost:5173
- API: http://localhost:8000
- MySQL: localhost:3390 (user: root, db: indice_db)

## Arranque manual

```bash
# Backend + MySQL + Redis
docker compose up -d --build
docker compose exec app php artisan migrate

# Frontend (en otra terminal)
cd frontend
npm install
npm run dev
```

## Tests

```bash
cd backend
php artisan test
```
