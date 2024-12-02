#!/bin/bash

if [ ! -f .env ]; then
  echo ".env file not found. Please create it with the required variables."
  exit 1
fi

export $(grep -v '^#' .env | xargs)

if [ -z "$PROJECT_NAME" ] || [ -z "$PHP_VERSION" ] || [ -z "$NODE_VERSION" ] || [ -z "$HTTP_PORT" ] || [ -z "$HTTPS_PORT" ] || [ -z "$VITE_PORT" ]; then
  echo "Some required variables are missing in .env. Please define PROJECT_NAME, PHP_VERSION, NODE_VERSION, HTTP_PORT, HTTPS_PORT, or VITE_PORT."
  exit 1
fi

if [ ! -d .ddev ]; then
  mkdir .ddev
  echo "Directory .ddev created."
fi

### config.yaml
cat > .ddev/config.yaml <<EOL
name: $PROJECT_NAME
type: php
docroot: "www"
php_version: "$PHP_VERSION"
webserver_type: nginx-fpm
xdebug_enabled: false
additional_hostnames: [ ]
additional_fqdns: [ ]
database:
  type: mariadb
  version: "10.11"
use_dns_when_possible: true
composer_version: "2"
web_environment:
  - "PROJECT_NAME=$PROJECT_NAME"
  - "DDEV_NO_HTTPS=false"
corepack_enable: false
disable_upload_dirs_warning: true
router_http_port: "$HTTP_PORT"
router_https_port: "$HTTPS_PORT"

hooks:
  post-start:
    - exec: './bin/db-init.sh'
    - exec: './bin/db-migrations.sh'
EOL

echo "config.yaml created in .ddev."

### docker-compose.override.yaml
cat > .ddev/docker-compose.override.yaml <<EOL
services:
  web:
    environment:
      - PROJECT_NAME=${PROJECT_NAME}
      - NODE_VERSION=${NODE_VERSION}
    ports:
      - "${VITE_PORT}:${VITE_PORT}"
  db:
    container_name: ${PROJECT_NAME}_db
  adminer:
    image: adminer:latest
    ports:
      - "8081:8080"
EOL

echo "docker-compose.override.yaml created in .ddev."

### .mutagen.yaml
mkdir -p .ddev/mutagen
cat > .ddev/mutagen/mutagen.yml <<EOL
sync:
  defaults:
    mode: "two-way-resolved"
    stageMode: "neighboring"
    ignore:
      paths:
        - "/.git"
        - "/.tarballs"
        - "/.ddev/db_snapshots"
        - "/.ddev/.importdb*"
        - ".DS_Store"
        - ".idea"
        - "node_modules"
        - "package.lock.json"
    symlink:
      mode: "posix-raw"
EOL

echo ".mutagen.yaml created in .ddev."


echo "Configuration files have been generated successfully!"