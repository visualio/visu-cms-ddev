#!/bin/bash

if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
else
  echo -e "\e[31mError: .env file not found.\e[0m"
  exit 1
fi

if [ -z "$HTTPS_PORT" ]; then
  echo -e "\e[31mError: HTTPS_PORT is not defined in .env.\e[0m"
  exit 1
fi

# Restart ddev
echo "Stopping all DDEV containers..."
ddev poweroff
ddev restart

# Spuštění příkazů s reálným výstupem
echo -e "\033[36mRunning composer install...\033[0m"
ddev exec bash -c "composer install"

echo -e "\033[36mRunning npm install...\033[0m"
ddev exec bash -c "npm install"

echo -e "\033[36mBuilding assets...\033[0m"
ddev exec bash -c "npm run build"

# Získání jedinečné URL na základě HTTPS portu
PROJECT_URL=$(ddev describe | grep -Eo "https://[a-zA-Z0-9.-]+:$HTTPS_PORT" | head -n 1)

# Získání údajů o databázi
DB_HOST=$(ddev describe | grep "DB Hostname:" | awk '{print $3}')
DB_PORT=$(ddev describe | grep "DB Port:" | awk '{print $3}')
DB_USER=$(ddev describe | grep "Username:" | awk '{print $2}')
DB_PASSWORD=$(ddev describe | grep "Password:" | awk '{print $2}')

# Výstup informací
echo -e "\033[32mAnd we are ready!\033[0m"
echo -e "\033[36mTo run Vite server use: \033[33mnpm start\033[0m"
echo -e "\033[36mTo build assets use: \033[33mnpm run build\033[0m"
echo -e "\033[36mProject is served on: \033[4m$PROJECT_URL\033[0m"
echo -e "\033[36mDatabase connection details:\033[0m"
echo -e "\033[36m  Host: \033[33m$DB_HOST\033[0m"
echo -e "\033[36m  Port: \033[33m$DB_PORT\033[0m"
echo -e "\033[36m  User: \033[33m$DB_USER\033[0m"
echo -e "\033[36m  Password: \033[33m$DB_PASSWORD\033[0m"
echo -e "\033[36mNext time just use: \033[4mddev start\033[0m"

# Připojení do kontejneru
ddev ssh