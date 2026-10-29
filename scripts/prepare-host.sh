#!/usr/bin/env bash
# Run from any directory: bash scripts/prepare-host.sh
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
for command_name in php composer npm; do
  command -v "$command_name" >/dev/null || { echo "Missing required command: $command_name" >&2; exit 1; }
done
php -r '
if (PHP_VERSION_ID < 80400) { fwrite(STDERR, "PHP 8.4+ is required.\n"); exit(1); }
$required = ["pdo", "pdo_mysql", "mbstring", "openssl", "tokenizer", "json", "curl", "fileinfo", "gd", "bcmath", "xml", "zip"];
$missing = array_filter($required, fn($extension) => !extension_loaded($extension));
if ($missing) { fwrite(STDERR, "Missing PHP extensions: ".implode(", ", $missing)."\n"); exit(1); }
'
if [[ ! -f .env ]]; then
  (umask 077; cp .env.example .env)
  echo "Created private .env. Configure database and APP_URL before using the installer."
fi
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
if [[ ! -w storage || ! -w bootstrap/cache ]]; then
  echo "The deployment user needs write access to storage and bootstrap/cache." >&2
  exit 1
fi
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm install --include=dev
npm run production
echo "Dependencies and assets prepared. No migrations or seeds were run."
echo "Follow docs/HOSTING.md to finish installation in a protected environment."
