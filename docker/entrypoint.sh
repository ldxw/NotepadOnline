#!/bin/sh
# 密钥缺失时分享功能完全不可用，但界面只会显示"开启失败"，
# 启动阶段在日志里给出明确提示，省去用户排查配置的时间
if [ -z "${NPO_SHARE_SECRET:-}" ]; then
	echo "[NotePadOnline] 警告：环境变量 NPO_SHARE_SECRET 为空，分享功能不可用。" >&2
	echo "[NotePadOnline] 请在 .env 或 docker run -e 中设置一个足够长的随机密钥后重启容器。" >&2
fi

exec docker-php-entrypoint "$@"
