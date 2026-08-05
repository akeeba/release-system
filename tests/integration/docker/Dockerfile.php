# PHP-FPM container that runs Joomla and the Joomla CLI tools.
#
# The web root is bind-mounted from ./www, so the host (which runs PHPUnit) and
# this container share exactly the same files. To keep file ownership sane across
# that bind mount we remap the www-data user/group to the host's UID/GID.

ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm

ARG PUID=1000
ARG PGID=1000

# Reliable, batteries-included extension installer. Pulls in the system libraries
# each extension needs, so we do not have to manage them by hand.
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

# Extensions required by Joomla 5/6 and by ARS.
RUN install-php-extensions \
        mysqli \
        pdo_mysql \
        gd \
        zip \
        intl \
        exif \
        sodium

# Remap www-data to the host user so bind-mounted files are owned consistently.
RUN (groupmod -o -g "${PGID}" www-data || true) \
 && (usermod  -o -u "${PUID}" -g "${PGID}" www-data || true)

COPY config/php.ini /usr/local/etc/php/conf.d/zz-e2e.ini

WORKDIR /var/www/html
