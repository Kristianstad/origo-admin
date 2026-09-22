# =========================================================================
# Init
# =========================================================================
# ARGs (can be passed to Build/Final) <BEGIN>
ARG SaM_REPO=${SaM_REPO:-ghcr.io/kristianstad/secure_and_minimal}
ARG ALPINE_VERSION=${ALPINE_VERSION:-3.23}
ARG ORIGO_VERSION=${ORIGO_VERSION:-2.10.0-r1}
ARG NGINX_VERSION=${NGINX_VERSION:-1.28.3}
ARG POSTGRESQL_VERSION=${POSTGRESQL_VERSION:-18}
ARG PHP_VERSION=${PHP_VERSION:-85}
ARG IMAGETYPE="application"
ARG BASEIMAGE="ghcr.io/kristianstad/origo:$ORIGO_VERSION"
ARG BUILDDEPS="composer"
ARG BUILDCMDS=\
'   mkdir composerdir '\
'&& cd composerdir '\
'&& composer require --ignore-platform-reqs adldap2/adldap2 '\
'&& mkdir -p "$DESTDIR/composer" '\
'&& mv ./vendor "$DESTDIR/composer/adldap2" '\
'&& rm -rf * '\
'&& composer require --ignore-platform-reqs matthiasmullie/minify '\
'&& mv ./vendor "$DESTDIR/composer/minify" '\
'&& rm -rf * '\
'&& composer require --ignore-platform-reqs thenetworg/oauth2-azure '\
'&& mv ./vendor "$DESTDIR/composer/oauth2-azure" '\
'&& rm -rf * '\
'&& composer require --ignore-platform-reqs league/oauth2-client '\
'&& mv ./vendor "$DESTDIR/composer/oauth2-client"'
ARG RUNDEPS="\
        apache2-utils \
        postgresql$POSTGRESQL_VERSION \
        php$PHP_VERSION-fpm \
        php$PHP_VERSION-json \
        php$PHP_VERSION-opcache \
        php$PHP_VERSION-simplexml \
        php$PHP_VERSION-session \
        php$PHP_VERSION-openssl \
        php$PHP_VERSION-ldap \
        php$PHP_VERSION-pecl-brotli \
        php$PHP_VERSION-pgsql"
ARG MAKEDIRS="/etc/php$PHP_VERSION/conf.d /etc/php$PHP_VERSION/php-fpm.d /var/log/php$PHP_VERSION"
ARG FINALCMDS=\
"   cd /usr/local "\
"&& rm -rf share lib "\
"&& ln -s ../lib ../share ./ "\
"&& cd bin "\
"&& find ../../libexec/postgresql$POSTGRESQL_VERSION ! -type l ! -name postgres ! -name ../../libexec/postgresql$POSTGRESQL_VERSION -maxdepth 1 -exec ln -s {} ./ + "\
"&& chmod g+X /usr/bin/* "\
"&& ln -sf /www/demokarta/index.json /www/index.json "\
"&& ln -sf /www/demokarta/index.html /www/index.html "\
"&& ln -s /www/demokarta/index.json /www/demokarta.json "\
"&& ln -s /www/demokarta/index.html /www/demokarta.html "\
"&& ln -s /www/preview/index.json /www/preview.json "\
"&& ln -s /www/preview/index.html /www/preview.html "\
"&& mkdir -p /etc/origo-adm "\
"&& cp -a /www/adm/constants /etc/origo-adm/constants-defaults "
ARG REMOVEFILES="/etc/php$PHP_VERSION/php-fpm.d/www.conf"
ARG STARTUPEXECUTABLES="/usr/sbin/php-fpm$PHP_VERSION /usr/libexec/postgresql$POSTGRESQL_VERSION/postgres"
ARG LINUXUSEROWNED="/var/log/php$PHP_VERSION /www/demokarta /www/demokarta/index.json /www/demokarta/index.html /www/preview /www/preview/index.json /www/preview/index.html"
# ARGs (can be passed to Build/Final) </END>

# Generic template (don't edit) <BEGIN>
FROM ${CONTENTIMAGE1:-scratch} AS content1
FROM ${CONTENTIMAGE2:-scratch} AS content2
FROM ${CONTENTIMAGE3:-scratch} AS content3
FROM ${CONTENTIMAGE4:-scratch} AS content4
FROM ${CONTENTIMAGE5:-scratch} AS content5
FROM ${BASEIMAGE:-$SaM_REPO:base-$ALPINE_VERSION} AS base
FROM ${INITIMAGE:-scratch} AS init
# Generic template (don't edit) </END>

# =========================================================================
# Build
# =========================================================================
# Generic template (don't edit) <BEGIN>
FROM ${BUILDIMAGE:-$SaM_REPO:build-$ALPINE_VERSION} AS build
FROM ${BASEIMAGE:-$SaM_REPO:base-$ALPINE_VERSION} AS final
COPY --from=build /finalfs /
# Generic template (don't edit) </END>

# =========================================================================
# Final
# =========================================================================
# Re-declare ARGs
ARG ALPINE_VERSION
ARG ORIGO_VERSION
ARG NGINX_VERSION
ARG POSTGRESQL_VERSION
ARG PHP_VERSION

ARG POSTGRES_CONFIG_DIR="/etc/postgres"

ENV VAR_PHP_VERSION="$PHP_VERSION" \
    VAR_SOCKET_FILE="/run/php$PHP_VERSION-fpm/socket" \
    VAR_wwwconf_listen='$VAR_SOCKET_FILE' \
    VAR_wwwconf_pm="dynamic" \
    VAR_wwwconf_pm__max_children="5" \
    VAR_wwwconf_pm__min_spare_servers="1" \
    VAR_wwwconf_pm__max_spare_servers="3" \
    VAR_LINUX_USER="postgres" \
    VAR_INIT_CAPS="cap_chown,cap_dac_override" \
    VAR_POSTGRES_CONFIG_DIR="$POSTGRES_CONFIG_DIR" \
    VAR_POSTGRES_CONFIG_FILE="$POSTGRES_CONFIG_DIR/postgresql.conf" \
    VAR_LOCALE="en_US.UTF-8" \
    VAR_ENCODING="UTF8" \
    VAR_TEXT_SEARCH_CONFIG="english" \
    VAR_HBA="local all all trust, host all all 127.0.0.1/32 trust, host all all ::1/128 trust, host all all all md5" \
    VAR_param_data_directory="'/pgdata'" \
    VAR_param_hba_file="'$POSTGRES_CONFIG_DIR/pg_hba.conf'" \
    VAR_param_ident_file="'$POSTGRES_CONFIG_DIR/pg_ident.conf'" \
    VAR_param_unix_socket_directories="'/var/run/postgresql'" \
    VAR_param_listen_addresses="'*'" \
    VAR_param_timezone="'UTC'" \
    VAR_server16_index="index.html manage.php index.php" \
    VAR_serversub02_location="/adm { auth_basic 'Administrator’s Area'; auth_basic_user_file /etc/nginx/.htpasswd; }" \
    VAR_serversub03_location="~ \\.php\$ { fastcgi_param SCRIPT_FILENAME \\\$document_root\\\$fastcgi_script_name; fastcgi_param SCRIPT_NAME \\\$fastcgi_script_name; include fastcgi.conf; fastcgi_pass unix:\$VAR_SOCKET_FILE; fastcgi_buffers 32 32k; fastcgi_buffer_size 16k; fastcgi_busy_buffers_size 64k; }" \
    VAR_ADMUSER="origo" \
    VAR_ADMPASSWORD="origo" \
    VAR_FINAL_COMMAND="php-fpm$PHP_VERSION --force-stderr && postgres --config_file=\"\$VAR_POSTGRES_CONFIG_FILE\" & nginx -g 'daemon off; user \$VAR_LINUX_USER; error_log stderr \$VAR_LOG_LEVEL; worker_processes \$VAR_WORKER_PROCESSES; worker_rlimit_nofile \$VAR_WORKER_RLIMIT_NOFILE;'"

STOPSIGNAL SIGINT

# Generic template (don't edit) <BEGIN>
USER starter
ONBUILD USER root
# Generic template (don't edit) </END>

LABEL org.opencontainers.image.version="${ORIGO_VERSION}" \
      org.opencontainers.image.title="origo-admin" \
      org.opencontainers.image.description="Origo-admin ${ORIGO_VERSION} based on secure_and_minimal ${ALPINE_VERSION} + nginx ${NGINX_VERSION}"
