#!/bin/bash

if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

if [ -z "$PROJECT_NAME" ]; then
  PROJECT_NAME="${DDEV_SITENAME}"
fi

if [ -z "$PROJECT_NAME" ]; then
  PROJECT_NAME=$(ddev describe | grep 'name:' | awk '{print $2}')
fi

if [ -z "$PROJECT_NAME" ]; then
  echo "Error: PROJECT_NAME is not set. Please set it in .env or ensure DDEV_SITENAME is available."
  exit 1
fi

MYSQL_USER="db"
MYSQL_HOST="db"
MYSQL_PASSWORD=""

SQL_DIR="./sql"

echo "Starting SQL migration for database ${PROJECT_NAME}..."

echo "Ensuring migrations_log table exists..."
MYSQL_PWD=$MYSQL_PASSWORD mysql -u "$MYSQL_USER" -h "$MYSQL_HOST" --database="$PROJECT_NAME" -e "
CREATE TABLE IF NOT EXISTS migrations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_file VARCHAR(255) NOT NULL,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);"

if [ ! -d "$SQL_DIR" ]; then
  echo "SQL directory ${SQL_DIR} does not exist. Exiting."
  exit 1
fi

SQL_FILES=$(find "$SQL_DIR" -type f -name '*.sql' | sort)

for FILE in $SQL_FILES; do
  RELATIVE_PATH=$(realpath --relative-to="$SQL_DIR" "$FILE")
  FULL_PATH=$(realpath "$FILE")

  if [[ "$(basename "$FILE")" == "init-db.sql" ]]; then
    echo "Skipping $FILE (init-db.sql already executed earlier)."
    continue
  fi

  MIGRATION_COUNT=$(MYSQL_PWD=$MYSQL_PASSWORD mysql -u "$MYSQL_USER" -h "$MYSQL_HOST" --database="$PROJECT_NAME" -N -e "SELECT COUNT(*) FROM migrations_log WHERE migration_file='$RELATIVE_PATH';")
  if [ "$MIGRATION_COUNT" -gt 0 ]; then
    echo "Skipping $RELATIVE_PATH (already executed)."
    continue
  fi

  echo "Processing SQL file: $RELATIVE_PATH..."

  SQL_COMMANDS=$(cat "$FULL_PATH")
  echo "Executing SQL commands from $RELATIVE_PATH"
  MYSQL_PWD=$MYSQL_PASSWORD mysql -u "$MYSQL_USER" -h "$MYSQL_HOST" --database="$PROJECT_NAME" -e "$SQL_COMMANDS" 2>/tmp/mysql_error.log

  if grep -q "ERROR" /tmp/mysql_error.log; then
    echo "Critical error while executing $RELATIVE_PATH. Check /tmp/mysql_error.log for details."
    exit 1
  fi

  MYSQL_PWD=$MYSQL_PASSWORD mysql -u "$MYSQL_USER" -h "$MYSQL_HOST" --database="$PROJECT_NAME" -e "
INSERT INTO migrations_log (migration_file) VALUES ('$RELATIVE_PATH');"

  echo "Successfully executed $RELATIVE_PATH."
done

echo "All SQL files have been processed successfully!"