FROM prooph/php:7.2-cli-xdebug

ARG UID=1000

RUN mv /usr/local/etc/php/conf.d/xdebug-cli.ini /usr/local/etc/php/conf.d/xdebug-cli.ini--disabled && \
	apk --no-cache add shadow autoconf gcc musl-dev alpine-sdk && \
	usermod -u $UID www-data && \
	pecl install ast && \
	docker-php-ext-enable ast && \
	# install runkit
    wget https://github.com/runkit7/runkit7/releases/download/2.0.3/runkit7-2.0.3.tgz -O /tmp/runkit.tgz && \
    pecl install /tmp/runkit.tgz && \
    echo -e 'extension=runkit.so\nrunkit.internal_override=On' > /usr/local/etc/php/conf.d/docker-php-ext-runkit.ini && \
    mv /usr/local/etc/php/conf.d/xdebug-cli.ini--disabled /usr/local/etc/php/conf.d/xdebug-cli.ini

ENV WORKDIR=/var/www
WORKDIR $WORKDIR

ENV PATH=$WORKDIR/vendor/bin:$PATH

COPY --chown=www-data . $WORKDIR
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN chown www-data $WORKDIR

USER www-data

RUN composer install

CMD ["php"]
