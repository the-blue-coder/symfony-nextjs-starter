server {
    listen 80;
    server_name [project].domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name [project].domain.com;

    ssl_certificate /etc/letsencrypt/live/[project].domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/[project].domain.com/privkey.pem;

    location / {
        proxy_pass http://localhost:[FRONTEND_PORT];
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
