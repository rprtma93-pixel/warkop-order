#!/bin/sh
# Bangkitkan APP_KEY otomatis bila belum diisi (isi APP_KEY tetap agar sesi login
# tidak hilang saat container restart).
if [ -z "$APP_KEY" ]; then
  export APP_KEY="$(php artisan key:generate --show)"
fi

exec php -S 0.0.0.0:8000 -t public
