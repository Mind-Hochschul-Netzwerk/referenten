FROM mindhochschulnetzwerk/php-base

LABEL Maintainer="Henrik Gebauer <code@henrik-gebauer.de>" \
      Description="mind-hochschul-netzwerk.de"

COPY app/ /var/www/

RUN set -ex \
  && apk --no-cache add \
    php7-mysqli \
    php7-xml \
    php7-zip \
    php7-curl \
    php7-gd \
    php7-ldap \
    php7-session \
    php7-ctype \
    php7-simplexml \
    php7-xmlreader \
  && mkdir /var/www/vendor && chown www-data:www-data /var/www/vendor \
  && su www-data -s /bin/sh -c "composer install -d /var/www --optimize-autoloader --no-dev --no-interaction --no-progress --no-cache" \
  && chown -R nobody:nobody /var/www \
  # for profile picture upload (default limit: 1 MB)
  && echo "client_max_body_size 20m;" > /etc/nginx/conf.d/server-client_max_body_size \
  && mkdir -p /var/www/html/profilbilder && chown www-data:www-data /var/www/html/profilbilder

VOLUME /var/www/html/public/profilbilder
