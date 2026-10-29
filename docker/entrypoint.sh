#!/bin/sh
# 密钥缺失时分享功能完全不可用，但界面只会显示"开启失败"，
# 启动阶段在日志里给出明确提示，省去用户排查配置的时间
if [ -z "${NPO_SHARE_SECRET:-}" ]; then
	echo "[NotePadOnline] 警告：环境变量 NPO_SHARE_SECRET 为空，分享功能不可用。" >&2
	echo "[NotePadOnline] 请在 .env 或 docker run -e 中设置一个足够长的随机密钥后重启容器。" >&2
fi

# 绑定挂载宿主目录时，宿主侧的目录和文件属主通常不是 www-data（uid 33）。
# PHP 以 www-data 运行，写不进 _tmp 就只在页面上显示"保存失败…失败原因:error"，
# 真正的 Permission denied 藏在 _tmp/error.log 里，很难排查。
# 容器此刻还是 root，统一修正属主，省去用户手工 chown 这一步。
NPO_DATA_DIR=/var/www/html/_tmp
mkdir -p "$NPO_DATA_DIR" 2>/dev/null
if ! chown -R www-data:www-data "$NPO_DATA_DIR" 2>/dev/null; then
	echo "[NotePadOnline] 警告：无法修正 $NPO_DATA_DIR 的属主，笔记可能无法保存。" >&2
	echo "[NotePadOnline] 请在宿主机执行：sudo chown -R 33:33 <挂载到 _tmp 的宿主目录>" >&2
fi
chmod 775 "$NPO_DATA_DIR" 2>/dev/null

exec docker-php-entrypoint "$@"
