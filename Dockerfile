FROM pensiero/apache-php-mysql

# Labels
LABEL maintainer "oscar.fanelli@gmail.com"

# php.ini configs
RUN sed -i "s/display_errors = .*/display_errors = On/" $PHP_INI && \
    sed -i "s/display_startup_errors = .*/display_startup_errors = On/" $PHP_INI && \
    sed -i "s/error_reporting = .*/error_reporting = E_ALL | E_STRICT/" $PHP_INI

# VirtualHost
COPY config/docker/apache-virtualhost.conf /etc/apache2/sites-available/000-default.conf

# Start apache
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]