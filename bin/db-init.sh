#!/bin/bash

PROJECT_NAME="${DDEV_SITENAME}"

echo "Creating database ${PROJECT_NAME}..."

mysql -u root -proot -e "
CREATE DATABASE IF NOT EXISTS \`${PROJECT_NAME}\`;
GRANT ALL PRIVILEGES ON \`${PROJECT_NAME}\`.* TO 'db'@'%';
FLUSH PRIVILEGES;"

TABLE_COUNT=$(mysql -u root -proot -N -e "
SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${PROJECT_NAME}';
")

if [ "$TABLE_COUNT" -eq 0 ]; then
  echo "No tables found in database ${PROJECT_NAME}. Importing SQL dump..."

  SQL_FILE="./sql/init-db.sql"

  if [ -f "$SQL_FILE" ]; then
    mysql --database=${PROJECT_NAME} < "$SQL_FILE"
    echo "SQL dump imported successfully from ${SQL_FILE}."
  else
    echo "SQL file ${SQL_FILE} not found. Skipping init import."
  fi
else
  echo "Database ${PROJECT_NAME} already contains tables. Skipping init import."
fi