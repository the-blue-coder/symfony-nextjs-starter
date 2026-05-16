#!/bin/bash
# Copy nginx configs and obtain SSL certificates via certbot.
# Run once after first-deploy.sh.
#
# Strategy: install HTTP-only configs first so nginx -t passes without certs,
# then run certbot which appends the HTTPS server block + cert references,
# then overwrite with the final HTTPS configs (which include the proxy headers
# that certbot's nginx installer would otherwise strip on renewal).
set -e

DOMAIN="[project].domain.com"
BACKEND_DOMAIN="b.[project].domain.com"
FRONTEND_PORT="[FRONTEND_PORT]"
PROD_BACKEND_PORT="[PROD_BACKEND_PORT]"
PROJECT_NAME="[project-name]"
LE_EMAIL="jd.rakotoarison@gmail.com"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Installing HTTP-only bootstrap configs..."
cat > "/etc/nginx/sites-available/$DOMAIN" <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    location / {
        proxy_pass http://localhost:$FRONTEND_PORT;
    }
}
EOF

cat > "/etc/nginx/sites-available/$BACKEND_DOMAIN" <<EOF
server {
    listen 80;
    server_name $BACKEND_DOMAIN;
    location / {
        proxy_pass http://localhost:$PROD_BACKEND_PORT;
    }
}
EOF

ln -sf "/etc/nginx/sites-available/$DOMAIN" /etc/nginx/sites-enabled/
ln -sf "/etc/nginx/sites-available/$BACKEND_DOMAIN" /etc/nginx/sites-enabled/

nginx -t
systemctl reload nginx

echo "==> Obtaining SSL certificates (separate cert per domain)..."
# Each domain gets its OWN certificate. Do NOT pass both domains to a single
# certbot --nginx call: that would create a SAN cert under a single
# /etc/letsencrypt/live/<first-domain>/ directory, and the second domain's
# nginx config - which references /etc/letsencrypt/live/<backend-domain>/ -
# would fail to load.
certbot --nginx -d "$DOMAIN" --cert-name "$DOMAIN" --non-interactive --agree-tos --email "$LE_EMAIL" --redirect
certbot --nginx -d "$BACKEND_DOMAIN" --cert-name "$BACKEND_DOMAIN" --non-interactive --agree-tos --email "$LE_EMAIL" --redirect

echo "==> Installing final HTTPS configs (with proxy headers)..."
cp "$SCRIPT_DIR/$DOMAIN" "/etc/nginx/sites-available/$DOMAIN"
cp "$SCRIPT_DIR/$BACKEND_DOMAIN" "/etc/nginx/sites-available/$BACKEND_DOMAIN"

nginx -t
systemctl reload nginx

echo "==> Installing certbot renewal hook to restore proxy headers..."
HOOK_PATH="/etc/letsencrypt/renewal-hooks/deploy/restore-${PROJECT_NAME}-proxy-headers.sh"
mkdir -p "$(dirname "$HOOK_PATH")"
cat > "$HOOK_PATH" <<HOOK
#!/bin/bash
# Re-apply the final HTTPS configs after certbot renewal,
# because certbot --installer=nginx rewrites location blocks
# and strips proxy_set_header directives.
set -e
SCRIPT_DIR="/home/www/${PROJECT_NAME}/infra/nginx"
cp "\$SCRIPT_DIR/$DOMAIN" /etc/nginx/sites-available/$DOMAIN
cp "\$SCRIPT_DIR/$BACKEND_DOMAIN" /etc/nginx/sites-available/$BACKEND_DOMAIN
nginx -t && systemctl reload nginx
HOOK
chmod +x "$HOOK_PATH"

echo "==> Nginx + SSL setup complete!"
