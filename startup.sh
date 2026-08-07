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

# 1. Copiar .env si no existe
echo -e "${YELLOW}[1/4] Configurando variables de entorno...${NC}"
cd "$PROJECT_ROOT"
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${YELLOW}IMPORTANTE: Edita el archivo .env con tus credenciales${NC}"
fi

# 2. Levantar contenedores Docker (backend + mysql + redis)
echo -e "${YELLOW}[2/4] Levantando contenedores Docker...${NC}"
docker compose up -d --build

# 3. Esperar a que MySQL este listo
echo -e "${YELLOW}[3/4] Esperando a que MySQL este listo...${NC}"
until docker compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
    echo -n "."
    sleep 2
done
echo ""
echo -e "${GREEN}MySQL listo!${NC}"

# 4. Ejecutar migraciones
echo -e "${YELLOW}[4/4] Ejecutando migraciones...${NC}"
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
