FROM trafex/php-nginx:3.5.0

LABEL Maintainer="Henrik Gebauer <code@henrik-gebauer.de>" \
      Description="mind-hochschul-netzwerk.de"

HEALTHCHECK --interval=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping

COPY --from=composer /usr/bin/composer /usr/bin/composer

USER root

# apply (security) updates
RUN set -x \
  && apk upgrade --no-cache

# install packages
RUN set -x \
  && apk --no-cache add \
      php83-ldap \
      php83-zip \
      php83-pdo_mysql \
  && chown nobody:nobody /var/www

USER nobody

COPY config/nginx/ /etc/nginx
COPY config/php-custom.ini /etc/php83/conf.d/custom.ini
COPY --chown=nobody app/ /var/www

RUN composer install -d "/var/www/" --optimize-autoloader --no-dev --no-interaction --no-progress --no-cache

VOLUME /var/www/html/profilbilder
