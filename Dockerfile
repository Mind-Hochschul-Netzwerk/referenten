FROM trafex/php-nginx:3.1.0

LABEL Maintainer="Henrik Gebauer <code@henrik-gebauer.de>" \
      Description="mind-hochschul-netzwerk.de"

HEALTHCHECK --interval=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping

COPY --from=composer /usr/bin/composer /usr/bin/composer

USER root

RUN apk --no-cache add php81-ldap php81-zip php81-pdo_mysql \
  && chown nobody:nobody /var/www

USER nobody

COPY config/nginx/ /etc/nginx
COPY config/php-custom.ini /etc/php81/conf.d/custom.ini
COPY --chown=nobody app/ /var/www

RUN composer install -d "/var/www/" --optimize-autoloader --no-dev --no-interaction --no-progress --no-cache

VOLUME /var/www/html/profilbilder
