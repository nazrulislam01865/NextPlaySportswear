#!/usr/bin/env bash
set -euo pipefail

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
    echo "Run this script as root (or with sudo)." >&2
    exit 1
fi

PROJECT_ROOT="${1:-}"

if [[ -z "$PROJECT_ROOT" || ! -f "$PROJECT_ROOT/artisan" ]]; then
    echo "Usage: sudo bash deploy/nginx/install-performance-snippets.sh /absolute/path/to/project" >&2
    exit 1
fi

PROJECT_ROOT="$(cd "$PROJECT_ROOT" && pwd)"
SNIPPET_DIR="/etc/nginx/snippets"
mkdir -p "$SNIPPET_DIR"

sed "s#/var/www/nextplay#$PROJECT_ROOT#g" \
    "$PROJECT_ROOT/deploy/nginx/nextplay-static-cache.conf.example" \
    > "$SNIPPET_DIR/nextplay-static-cache.conf"

cp "$PROJECT_ROOT/deploy/nginx/nextplay-compression-gzip.conf.example" \
    "$SNIPPET_DIR/nextplay-compression.conf"

if nginx -V 2>&1 | grep -qi brotli; then
    cat "$PROJECT_ROOT/deploy/nginx/nextplay-compression-brotli.conf.example" \
        >> "$SNIPPET_DIR/nextplay-compression.conf"
    echo "Brotli support detected and enabled in the generated compression snippet."
else
    echo "Brotli module was not detected; Gzip is enabled. Install/load the Brotli module, then append the Brotli example snippet."
fi

cat <<OUT
Generated:
  $SNIPPET_DIR/nextplay-static-cache.conf
  $SNIPPET_DIR/nextplay-compression.conf

Add these lines inside your existing Nginx server block:
  include $SNIPPET_DIR/nextplay-static-cache.conf;
  include $SNIPPET_DIR/nextplay-compression.conf;

Then validate and reload:
  nginx -t
  systemctl reload nginx
OUT
