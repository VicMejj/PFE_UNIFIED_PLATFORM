#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DJANGO_DIR="$ROOT_DIR/django-backend"
VUE_DIR="$ROOT_DIR/vue-project"

echo "Starting Unified Platform dev services..."
echo "Frontend: http://localhost:5173"
echo "Django AI: http://127.0.0.1:8001/api"
echo
echo "If you want the Laravel backend too, start it separately on port 8000."
echo

if command -v uv >/dev/null 2>&1; then
  echo "Launching Django AI service with uv..."
  (
    cd "$DJANGO_DIR"
    uv run python manage.py runserver 8001
  ) &
  DJANGO_PID=$!
else
  echo "Launching Django AI service with python..."
  (
    cd "$DJANGO_DIR"
    python manage.py runserver 8001
  ) &
  DJANGO_PID=$!
fi

echo "Launching Vue frontend..."
(
  cd "$VUE_DIR"
  npm run dev
) &
VUE_PID=$!

cleanup() {
  echo
  echo "Stopping dev services..."
  kill "$DJANGO_PID" "$VUE_PID" >/dev/null 2>&1 || true
}

trap cleanup EXIT INT TERM

wait
