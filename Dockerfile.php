# Default Dockerfile

FROM hyperf/hyperf:8.0-alpine-v3.15-swoole

LABEL maintainer="MineManage Developers <root@mineadmin.com>" version="1.0" license="MIT" app.name="MineAdmin"

##
# ---------- env settings ----------
##
# --build-arg timezone=Asia/Shanghai
ARG timezone

ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    #    APP_ENV=dev \
    APP_SYSTEM_ENV=docker \
    SCAN_CACHEABLE=(true)

# update
RUN set -ex \
    # show php version and extensions
    && php -v \
    && php -m \
    && php --ri swoole \
    #  ---------- some config ----------
    && cd /etc/php8 \
    # - config PHP
    && { \
    echo "upload_max_filesize=1G"; \
    echo "post_max_size=1G"; \
    echo "memory_limit=2G"; \
    echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    # - config timezone
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    # ---------- clear works ----------
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"

RUN apk update \
    && apk add --no-cache php8-pear php8-dev php8-jpeg zlib-dev re2c gcc g++ make \
    && curl -fsSL "https://pecl.php.net/get/xlswriter-1.5.1.tgz" -o xlswriter.tgz \
    && mkdir -p /tmp/xlswriter \
    && tar -xf xlswriter.tgz -C /tmp/xlswriter --strip-components=1 \
    && rm xlswriter.tgz \
    && ln -s /usr/bin/phpize8 /usr/local/bin/phpize \
    && ln -s /usr/bin/php-config8 /usr/local/bin/php-config \
    && cd /tmp/xlswriter \
    && phpize && ./configure --enable-reader && make && make install

RUN echo "extension=xlswriter.so" >> /etc/php8/conf.d/xlswriter.ini

# fix aliyun oss wrong charset: https://github.com/aliyun/aliyun-oss-php-sdk/issues/101
# https://github.com/docker-library/php/issues/240#issuecomment-762438977

RUN apk --no-cache --allow-untrusted --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ add gnu-libiconv=1.15-r2
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so

WORKDIR /app-src

EXPOSE 9501 9502 9503
