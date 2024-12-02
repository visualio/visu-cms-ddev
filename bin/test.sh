#!/bin/bash

if [ -t 1 ]; then
  FORCE_COLOR=1
fi

echo -e "\033[32mAnd we are ready!\033[0m"
echo -e "\033[36mTo run Vite server use: \033[33mnpm start\033[0m"
echo -e "\033[36mProject is served on: \033[4mhovno\033[0m"
