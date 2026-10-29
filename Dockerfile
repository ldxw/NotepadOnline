FROM php:8.3-apache

ARG APP_VERSION=dev

LABEL org.opencontainers.image.title="NotePadOnline" \
      org.opencontainers.image.description="轻量、无数据库的在线协同笔记" \
      org.opencontainers.image.source="https://github.com/ldxw/NotepadOnline" \
      org.opencontainers.image.licenses="MIT" \
      org.opencontainers.image.version="${APP_VERSION}"

RUN a2enmod rewrite \
 && apt-get update \
 && apt-get install -y --no-install-recommends libonig-dev curl \
 && docker-php-ext-install mbstring \
 && apt-get purge -y libonig-dev \
 && rm -rf /var/lib/apt/lists/*
# 注意：purge 不能加 --auto-remove，mbstring 运行时依赖的 libonig5 会被一并删除

# 文件名排在 security.conf 之后，否则默认的 ServerTokens OS 会把这里的设置覆盖回去
COPY docker/apache.conf /etc/apache2/conf-available/zz-npo.conf
RUN a2enconf zz-npo && apache2ctl -t

COPY . /var/www/html/

# 笔记数据目录，建议挂载为独立数据卷
RUN mkdir -p /var/www/html/_tmp \
 && chown -R www-data:www-data /var/www/html/_tmp \
 && chmod 775 /var/www/html/_tmp

ENV APP_VERSION=${APP_VERSION}

# 启动时检查分享密钥并在缺失时告警，然后交回基础镜像的 entrypoint 链
COPY docker/entrypoint.sh /usr/local/bin/npo-entrypoint.sh
RUN chmod +x /usr/local/bin/npo-entrypoint.sh
ENTRYPOINT ["npo-entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80

# 探 PHP 入口而不是静态资源，静态资源 200 无法反映 PHP 是否正常
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -fsS http://127.0.0.1/index.php >/dev/null || exit 1
