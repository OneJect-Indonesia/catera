FROM catera_backend:latest AS backend-assets

FROM nginx:alpine

RUN apk add --no-cache openssl

RUN mkdir -p /etc/ssl/private /etc/ssl/certs \
    && openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/nginx-selfsigned.key \
    -out /etc/ssl/certs/nginx-selfsigned.crt \
    -subj "/C=ID/ST=Jakarta/L=Jakarta/O=Oneject/CN=192.104.220.248"

COPY --from=backend-assets /var/www/html/public /var/www/html/public

RUN rm -f /etc/nginx/conf.d/default.conf

COPY nginx.conf /etc/nginx/templates/nginx.conf.template
COPY conf.d/ /etc/nginx/conf.d/

EXPOSE 80 443
CMD ["sh", "-c", "envsubst '${SERVER_NAME}' < /etc/nginx/templates/nginx.conf.template > /etc/nginx/conf.d/default.conf && nginx -g 'daemon off;'"]
