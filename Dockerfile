
FROM teltek/pumukit-base

# default build for production
ARG APP_ENV=prod

# copy only specifically what we need
COPY --chown=www-data:www-data . ./
COPY --chown=www-data:www-data doc/docker/pumukit/parameters.yml app/config/parameters.yml

RUN set -eux; \
    composer install -a -n --no-scripts; \
    php app/console a:i; \
    composer clear-cache

COPY doc/docker/pumukit/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

## Add the wait script to the image
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.4.0/wait /wait
RUN chmod +x /wait

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
