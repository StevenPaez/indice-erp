# Plan de pruebas

## 1. Objetivo

Demostrar que Índice ERP cumple sus contratos funcionales y de seguridad sin
depender únicamente de controles visuales. La validación combina:

- Suite automatizada del backend.
- Build de producción del frontend.
- Ciclo de migraciones en una base aislada.
- Pruebas manuales en navegador.
- Verificación directa de respuestas HTTP cuando el caso lo requiera.

## 2. Alcance actual

| Área | Superficie |
|---|---|
| Autenticación | Login, logout, sesión, CSRF, rate limits y usuarios inactivos |
| Contraseñas | Cambio propio, temporal obligatoria, solicitud y consumo de recuperación |
| Autorización | Capacidades, Policies, navegación y acceso directo por API |
| Usuarios | Listado, filtros, alta, identidad, rol, activación y desactivación |
| Catálogo | Listado, búsqueda, paginación, alta, edición, baja lógica y stock bajo |
| Auditoría | Eventos, filtros, paginación, inmutabilidad, metadata y correlación |
| Persistencia | Migraciones hacia adelante, rollback y conservación de usuarios |

Fuera del alcance hasta que exista implementación: catálogo ampliado, almacenes,
movimientos de inventario, compras, ventas y reportes operativos.

## 3. Ambientes

### 3.1 Local recomendado

```bash
cp .env.example .env
cp backend/.env.example backend/.env
./startup.sh
```

Servicios predeterminados:

- SPA: `http://localhost:5173`
- API: `http://localhost:8000/api`
- MySQL: `localhost:3390`
- Redis: `localhost:6380`

Crear el primer administrador en una base nueva:

```bash
docker compose exec app php artisan app:bootstrap-admin
```

### 3.2 Requisitos del ambiente QA

- Base de datos exclusiva y reiniciable; nunca producción.
- Transporte de correo dirigido a un buzón o capturador exclusivo de QA.
- HTTPS y cookies seguras cuando se pruebe un ambiente publicado.
- Hora del sistema sincronizada.
- Acceso autorizado a logs técnicos y base de datos para evidencia, sin copiar
  secretos al reporte.
- Tres cuentas activas: una por cada rol.
- Una segunda cuenta `admin` para probar la invariante del último administrador.
- Una cuenta inactiva y una cuenta con contraseña temporal.

## 4. Datos de prueba

No reutilizar contraseñas reales. Un conjunto manual debe contener, como mínimo:

| Alias | Rol/estado | Propósito |
|---|---|---|
| `qa-admin-1` | admin activo | Administración principal |
| `qa-admin-2` | admin activo | Democión/desactivación segura y concurrencia |
| `qa-operator` | operator activo | Escritura de catálogo sin acceso administrativo |
| `qa-viewer` | viewer activo | Consulta sin escritura |
| `qa-inactive` | cualquier rol, inactivo | Rechazo de login y revocación |
| `qa-temporary` | viewer activo, cambio obligatorio | Frontera de contraseña temporal |

Crear libros con:

- ISBN único.
- Precios con dos decimales.
- Stock `0`, `10` y `11` para cubrir el límite de stock bajo.
- Títulos y autores que permitan distinguir resultados de búsqueda.

Los correos deben pertenecer al dominio reservado del ambiente QA. Eliminar o
anonimizar los datos al finalizar el ciclo según la política del despliegue.

## 5. Matriz de capacidades esperada

| Capacidad | admin | operator | viewer |
|---|:---:|:---:|:---:|
| `users.view` | Sí | No | No |
| `users.manage` | Sí | No | No |
| `audit.view` | Sí | No | No |
| `catalog.view` | Sí | Sí | Sí |
| `catalog.manage` | Sí | Sí | No |
| `inventory.view` | Sí | Sí | Sí |
| `inventory.manage` | Sí | Sí | No |
| `inventory.adjust` | Sí | No | No |

Las capacidades de inventario ya forman parte del contrato de sesión, pero no
tienen endpoints hasta implementar ese módulo.

## 6. Ejecución automatizada

Desde una instalación con dependencias:

```bash
cd backend
./vendor/bin/pint --test
php artisan test
php artisan route:list --path=api

cd ../frontend
npm ci
npm run build
```

La migración debe probarse contra una base desechable compatible con el motor del
ambiente. Secuencia mínima:

```bash
php artisan migrate:fresh --force
php artisan migrate:rollback --force
php artisan migrate --force
```

Nunca ejecutar `migrate:fresh` sobre una base compartida o con datos que deban
conservarse.

## 7. Ejecución manual

1. Registrar versión, commit, ambiente, navegador y responsable.
2. Preparar los datos definidos en la sección 4.
3. Ejecutar primero el smoke crítico de `REGRESSION.md`.
4. Ejecutar los casos afectados en `TEST-CASES.md`.
5. Ejecutar regresión completa para releases o cambios transversales.
6. Adjuntar evidencia solo en puntos de decisión: respuesta HTTP, transición de
   estado, control de acceso, evento de auditoría o defecto.
7. Limpiar sesiones, correos, tokens y datos temporales.

Navegadores mínimos para una liberación:

- Chromium actual, escritorio y viewport móvil.
- Firefox actual.
- Safari actual cuando el despliegue soporte dispositivos Apple.

## 8. Criterios de entrada

- Build desplegado e identificable.
- Migraciones terminadas sin error.
- `APP_URL`, `FRONTEND_URL`, CORS, Sanctum, sesión y correo apuntan al ambiente QA.
- Dependencias instaladas mediante lockfiles.
- Datos y cuentas disponibles.
- Defectos bloqueantes conocidos declarados.

## 9. Criterios de salida

- Suite backend, Pint y build frontend aprobados.
- Smoke crítico aprobado.
- Casos afectados y regresión exigida aprobados.
- Sin defectos abiertos de severidad crítica o alta.
- Defectos medios aceptados explícitamente y con impacto documentado.
- Evidencia adjunta para controles de acceso, recuperación, último administrador
  y auditoría.
- Migraciones y rollback comprobados en una base aislada.

## 10. Seguridad de la ejecución QA

- No adjuntar contraseñas, cookies, tokens CSRF, enlaces activos de recuperación,
  cadenas de conexión ni dumps completos.
- Ocultar datos personales no necesarios en capturas.
- Usar un buzón controlado para recuperación; no redirigir correo QA a usuarios
  reales.
- Destruir tokens y sesiones al finalizar.
- Tratar `X-Request-ID` como dato de correlación, no como autenticación.
- Confirmar que errores de login y recuperación no revelan existencia o estado
  de cuentas.
