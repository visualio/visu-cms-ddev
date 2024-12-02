#!/bin/bash

set -e  # Ukončí skript při jakékoliv chybě

# Kontrola, zda existuje .env soubor
if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
else
  echo -e "\033[31mError: .env file not found.\033[0m"
  exit 1
fi

# Kontrola, zda je HTTPS_PORT definován
if [ -z "$HTTPS_PORT" ]; then
  echo -e "\033[31mError: HTTPS_PORT is not defined in .env.\033[0m"
  exit 1
fi

# Restart DDEV
echo -e "\033[36mStopping all DDEV containers...\033[0m"
ddev poweroff
ddev restart

# Generování SSL certifikátu
echo -e "\033[36mGenerating SSL certificate...\033[0m"
ddev auth ssh

# Spuštění Composer install
echo -e "\033[36mRunning composer install...\033[0m"
ddev exec "composer install" | stdbuf -oL cat
echo -e "\033[32mComposer install completed.\033[0m"

# Spuštění npm install
echo -e "\033[36mRunning npm install...\033[0m"
ddev exec "npm install" | stdbuf -oL cat
echo -e "\033[32mNpm install completed.\033[0m"

# Build front-end assets
echo -e "\033[36mBuilding assets...\033[0m"
ddev exec "npm run build" | stdbuf -oL cat
echo -e "\033[32mAssets built successfully.\033[0m"