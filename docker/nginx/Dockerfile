FROM nginx:1.27-alpine

# Установка logrotate для ротации логов по суткам
RUN apk add --no-cache logrotate

# Конфигурация logrotate для nginx-логов приложения
RUN mkdir -p /etc/logrotate.d && \
    echo '/var/log/nginx/app/*.log {\n\
    daily\n\
    missingok\n\
    rotate 14\n\
    compress\n\
    delaycompress\n\
    notifempty\n\
    dateext\n\
    dateformat -%Y-%m-%d\n\
    create 0644 nginx nginx\n\
    sharedscripts\n\
    postrotate\n\
        [ -f /var/run/nginx.pid ] && kill -USR1 $(cat /var/run/nginx.pid) || true\n\
    endscript\n\
}' > /etc/logrotate.d/nginx-app

# Cron-задача для ежесуточной ротации
RUN echo '0 0 * * * /usr/sbin/logrotate /etc/logrotate.d/nginx-app' > /var/spool/cron/crontabs/root

# Создание директории для логов приложения
RUN mkdir -p /var/log/nginx/app

# Запуск crond в фоне + nginx на переднем плане
CMD ["sh", "-c", "crond && nginx -g 'daemon off;'"]
