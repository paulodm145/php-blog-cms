FROM php:7.2-apache
# A hospedagem de producao esta travada em PHP 7.2 (sem opcao de trocar) —
# o dev usa a mesma versao de proposito, pra pegar incompatibilidade de
# sintaxe aqui, nao so depois do deploy. Ver AGENTS.md.

# UID/GID do usuário do host, para que arquivos criados pelo container
# (uploads, .env gerado pelo instalador, etc.) fiquem com o dono certo
# no bind mount e não virem root.
ARG PUID=1000
ARG PGID=1000

# PHP 7.2 = Debian Buster, que ja saiu de suporte — os repositorios normais
# do apt.debian.org sumiram, precisa apontar pro arquivo.
RUN sed -i \
        -e 's|deb.debian.org/debian|archive.debian.org/debian|g' \
        -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' \
        -e '/buster-updates/d' \
        /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libfreetype6-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype-dir=/usr --with-jpeg-dir=/usr --with-webp-dir=/usr \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql mysqli mbstring dom zip opcache gd \
    && a2enmod rewrite headers \
    && groupmod -g "${PGID}" www-data \
    && usermod -u "${PUID}" -g "${PGID}" www-data

# Caminhos relativos ao contexto de build (raiz do projeto).
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-blog.ini
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
