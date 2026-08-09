#!/bin/bash
set -e

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"

echo -e "${GREEN}=== Indice ERP - Iniciando entorno de desarrollo ===${NC}"

check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo -e "${RED}Error: $1 no esta instalado${NC}"
        exit 1
    fi
}

check_command docker
check_command node
check_command npm

# 1. Copiar archivos .env si no existen
echo -e "${YELLOW}[1/6] Configurando variables de entorno...${NC}"
cd "$PROJECT_ROOT"
if [ ! -f .env ]; then
    cp .env.example .env
fi
if [ ! -f backend/.env ]; then
    cp backend/.env.example backend/.env
fi

# 2. Levantar contenedores Docker (backend + mysql + redis)
echo -e "${YELLOW}[2/6] Levantando contenedores Docker...${NC}"
docker compose up -d --build

# 3. Esperar a que MySQL este listo
echo -e "${YELLOW}[3/6] Esperando a que MySQL este listo...${NC}"
until docker compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
    echo -n "."
    sleep 2
done
echo ""
echo -e "${GREEN}MySQL listo!${NC}"

# 4. Instalar dependencias PHP si no existen
echo -e "${YELLOW}[4/6] Verificando dependencias PHP...${NC}"
if ! docker compose exec -T app sh -c 'test -f /var/www/html/vendor/autoload.php' 2>/dev/null; then
    docker compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction --no-progress
    echo -e "${GREEN}Dependencias PHP instaladas!${NC}"
else
    echo -e "${GREEN}Dependencias PHP ya existentes.${NC}"
fi

# 5. Generar APP_KEY si esta vacia
echo -e "${YELLOW}[5/6] Verificando APP_KEY...${NC}"
if docker compose exec -T app sh -c 'grep -q "^APP_KEY=$" /var/www/html/.env' 2>/dev/null; then
    docker compose exec -T app php artisan key:generate --force
    echo -e "${GREEN}APP_KEY generada!${NC}"
else
    echo -e "${GREEN}APP_KEY ya configurada.${NC}"
fi

# 6. Ejecutar migraciones
echo -e "${YELLOW}[6/6] Ejecutando migraciones...${NC}"
docker compose exec -T app php artisan migrate --force
echo -e "${GREEN}Migraciones ejecutadas!${NC}"

# Iniciar frontend en local
echo -e "${GREEN}=== Entorno listo! ===${NC}"
echo -e "${GREEN}Backend API:  http://localhost:8000${NC}"
echo -e "${GREEN}Frontend:     http://localhost:5173${NC}"
echo -e "${GREEN}MySQL:        localhost:3390 (user: root, db: indice_db)${NC}"
echo ""

cd "$PROJECT_ROOT/frontend"
if [ ! -d node_modules ]; then
    echo -e "${YELLOW}Instalando dependencias del frontend...${NC}"
    npm install
fi

npm run dev
