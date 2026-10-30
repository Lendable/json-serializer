FROM public.ecr.aws/docker/library/php:8.4-cli-alpine@sha256:d8784155c4d37986be9ddd7a8950df7e78998cc48e35472cd69e2b9371424a65 AS base

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN addgroup --gid "${GROUP_ID}" "app" && \
    adduser \
    --disabled-password \
    --gecos "" \
    --home "/app-home" \
    --ingroup "app" \
    --uid "${USER_ID}" \
    -s "/bin/sh" \
    "app"

COPY --from=composer/composer:2.10.3-bin@sha256:016c41066558559142758092f7ef286d66ae113df23d49e086fdda0166dd5f01 /composer /usr/bin/composer

RUN apk add --no-cache git

WORKDIR /app

FROM base AS dev

ARG XDEBUG_VERSION="3.5.1"
RUN apk add --no-cache autoconf file g++ gcc libc-dev make pkgconf re2c linux-headers \
  && apk add --no-cache bash git \
  && pecl install "xdebug-${XDEBUG_VERSION}" pcov \
  && docker-php-ext-enable pcov \
  && apk del autoconf file g++ gcc libc-dev make pkgconf re2c linux-headers

COPY xdebug-entrypoint.sh /usr/local/bin/docker-php-xdebug-entrypoint

RUN touch /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/bin/noxdebug \
    && chown app:app /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini /usr/local/bin/noxdebug

USER app
