server {
    listen 80;
    server_name b.[project].domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name b.[project].domain.com;

    ssl_certificate /etc/letsencrypt/live/b.[project].domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/b.[project].domain.com/privkey.pem;

    # Security headers - declared at server level so they survive certbot's
    # rewrite of the `location /` block on renewal. No frame or CSP directives
    # here: this host serves the API, it has no UI to frame. Raise HSTS to
    # max-age=31536000 once the domain is confirmed HTTPS-only for good.
    add_header Strict-Transport-Security "max-age=2592000" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    server_tokens off;

    client_max_body_size 20M;

    location / {
        proxy_pass http://localhost:[PROD_BACKEND_PORT];
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
