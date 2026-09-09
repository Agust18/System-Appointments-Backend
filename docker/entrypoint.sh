#!/bin/bash
set -e

echo "🚀 Iniciando contenedor CodeIgniter 4..."

# ── 1. Reinstalar VirtualHost (el volumen puede haberlo pisado) ──────────────
cat > /etc/apache2/sites-available/000-default.conf << 'EOF'
<VirtualHost *:80>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

echo "✅ VirtualHost configurado → /var/www/html/public"

# ── 2. Verificar si CodeIgniter está instalado ───────────────────────────────
if [ ! -f "/var/www/html/public/index.php" ]; then
    echo "📦 CodeIgniter no encontrado. Instalando via Composer..."

    # Limpiar directorio temporal si quedó de un intento anterior
    rm -rf /tmp/ci4

    composer create-project codeigniter4/appstarter /tmp/ci4 --no-interaction

    # Copiar archivos al directorio de trabajo (sin sobreescribir .env si existe)
    cp -rn /tmp/ci4/. /var/www/html/ 2>/dev/null || true
    cp -r /tmp/ci4/app /var/www/html/
    cp -r /tmp/ci4/public /var/www/html/
    cp -r /tmp/ci4/vendor /var/www/html/
    cp -r /tmp/ci4/writable /var/www/html/
    cp /tmp/ci4/composer.json /var/www/html/
    cp /tmp/ci4/composer.lock /var/www/html/
    [ ! -f /var/www/html/.env ] && cp /tmp/ci4/env /var/www/html/.env

    rm -rf /tmp/ci4
    echo "✅ CodeIgniter instalado correctamente."
else
    echo "✅ CodeIgniter ya está instalado."
fi

# ── 3. Instalar dependencias si hay composer.json pero no vendor/ ────────────
if [ -f "/var/www/html/composer.json" ] && [ ! -d "/var/www/html/vendor" ]; then
    echo "📦 Instalando dependencias de Composer..."
    cd /var/www/html && composer install --no-interaction --optimize-autoloader
    echo "✅ Dependencias instaladas."
fi

# ── 4. Ajustar permisos en writable/ ────────────────────────────────────────
if [ -d "/var/www/html/writable" ]; then
    chown -R www-data:www-data /var/www/html/writable
    chmod -R 775 /var/www/html/writable
    echo "✅ Permisos de writable/ ajustados."
fi

echo "🌐 Apache iniciando en puerto 80 (mapeado a 8086)..."

# ── 5. Ejecutar el comando original (apache2-foreground) ────────────────────
exec "$@"
