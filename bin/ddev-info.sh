#!/bin/bash

# Získání URL projektu
PROJECT_URL=$(ddev describe | grep -Eo "https://[a-zA-Z0-9.-]+:[0-9]+" | head -n 1)

# Výstup informací
echo -e "\033[32mAnd we are ready!\033[0m"
echo -e "\033[36mNow you can connect to container: \033[33mddev ssh\033[0m"
echo -e "\033[36mRun Vite server use: \033[33mnpm start\033[0m"
echo -e "\033[36mBuild assets use: \033[33mnpm run build\033[0m"
echo -e "\033[36mProject is served on: \033[4m$PROJECT_URL\033[0m"
echo -e "\033[36mDatabase connection details:\033[0m"
echo -e "\033[36m  Host: \033[33mhttp://127.0.0.1:8081/\033[0m"
echo -e "\033[36m  User: \033[33mdb\033[0m"
echo -e "\033[36m  Password: \033[33mdb\033[0m"
echo -e "\033[36mTo run ddev container again use: \033[4mddev start\033[0m"