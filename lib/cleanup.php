<?php ($visit) ? ("") : (header('location: /'));?>
<?php
/*这是NPO的库文件
**------------------
**如果您不了解PHP或程序的功能实现方式及内容架构，请勿修改内容*/

/*空笔记自动清理
**访客打开首页时会被随机分配一个笔记名（见 lib/surface.php），看一眼就走
**就会在 _tmp 里留下一个空笔记。这里把「超过 $ttlDays 天没写过、内容为空」
**的笔记文件删掉，由 lib/general.func.php 在请求路径上惰性触发。
**判定要点：
**1) 「空」有两种形态——lockAcquire 的 c+b 建出的 0 字节空壳，
**   和 JSON 完整但 data 为空串的正常空笔记，两者都要判；
**2) 「旧」优先看 JSON 里的 editTime（13 位毫秒），缺失或非法时退回文件 mtime；
**3) 已开分享（share=1）或设过密码（epw/spw 非空）的空笔记一律保留，
**   删掉会让已发出的分享链和别人加了密码的笔记直接失效；
**4) JSON 损坏的文件同样保留，与 noteChange「保留现场」的策略一致；
**5) 拿不到独占锁说明正好有人在写，这一轮先放过，下轮再说；
**6) 删除用「先 rename 再 unlink」：noteChange 是 fopen('c+b') 之后才 flock，
**   直接 unlink 会让那个已打开的旧 inode 变成孤儿，用户的保存静默丢失。
**   先 rename 能让之后才 fopen 的写入落到新文件上，再配合最近 60 秒内
**   改动过就跳过，把残留窗口压到可忽略*/

function npoCleanupEmptyNotes($baseDir, $ttlDays, $intervalHours = 1){
	$stat = array('scanned'=>0, 'deleted'=>0, 'kept'=>0, 'skipped'=>false);
	$ttlDays = (int)$ttlDays;
	if ($ttlDays <= 0) return $stat;                     //0 或不设 = 关闭清理
	$baseDir = is_string($baseDir) ? rtrim($baseDir, '/\\') : '';
	if ($baseDir === '' || !is_dir($baseDir)) return $stat;

	$intervalHours = (int)$intervalHours;
	if ($intervalHours < 1) $intervalHours = 1;

	/*节流：标记文件里存的是上次扫描时间（mtime），未到间隔就直接跳过，
	**避免每个请求都去 scandir 一遍数据目录*/
	$mark = $baseDir.'/.cleanup.ts';
	$now = time();
	$last = @filemtime($mark);
	if ($last !== false && $now - $last < $intervalHours * 3600) {
		$stat['skipped'] = true;
		return $stat;
	}
	/*先更新标记再扫描：即使扫描中途出错，也不会让下一个请求紧接着重扫*/
	@touch($mark);

	$entries = @scandir($baseDir);
	if (!is_array($entries)) return $stat;

	/*毫秒与秒统一到毫秒比较，避免 13 位时间戳被误当秒*/
	$deadlineMs = ($now - $ttlDays * 86400) * 1000;

	foreach ($entries as $file) {
		if (substr($file, -5) !== '.data') continue;      //只处理笔记文件，.htaccess / error.log 等一概不碰
		$path = $baseDir.'/'.$file;
		if (!is_file($path)) continue;
		$stat['scanned']++;

		$h = @fopen($path, 'rb');
		if ($h === false) { $stat['kept']++; continue; }
		/*非阻塞独占锁：写着的人还持有锁时立刻放弃，不动这个文件*/
		if (!flock($h, LOCK_EX | LOCK_NB)) { fclose($h); $stat['kept']++; continue; }

		$meta = fstat($h);
		$size = $meta ? (int)$meta['size'] : -1;
		$json = null;
		$empty = false;
		if ($size === 0) {
			$empty = true;                               //0 字节空壳
		} else {
			$raw = stream_get_contents($h);
			$json = is_string($raw) ? json_decode($raw, true) : null;
			if (!is_array($json)) {
				flock($h, LOCK_UN); fclose($h);
				$stat['kept']++; continue;               //损坏文件保留现场
			}
			if (isset($json['data']) && is_string($json['data']) && $json['data'] === '') $empty = true;
		}

		if ($empty && is_array($json)) {
			if (isset($json['share']) && (string)$json['share'] === '1') {
				flock($h, LOCK_UN); fclose($h);
				$stat['kept']++; continue;               //分享中的笔记不删
			}
			$epw = isset($json['epw']) && is_string($json['epw']) ? $json['epw'] : '';
			$spw = isset($json['spw']) && is_string($json['spw']) ? $json['spw'] : '';
			if ($epw !== '' || $spw !== '') {
				flock($h, LOCK_UN); fclose($h);
				$stat['kept']++; continue;               //设过密码的笔记不删
			}
		}
		if (!$empty) {
			flock($h, LOCK_UN); fclose($h);
			$stat['kept']++; continue;
		}

		/*判旧：editTime 只接受纯数字，越界或不合法一律退回 mtime*/
		$timeMs = 0;
		if (is_array($json) && isset($json['editTime']) && preg_match('/^\d{1,16}$/', (string)$json['editTime'])) {
			$timeMs = (int)$json['editTime'];
			if ($timeMs < 946684800000) $timeMs = 0;     //早于 2000-01-01 视为无效
		}
		if ($timeMs <= 0) {
			$mtime = $meta ? (int)$meta['mtime'] : $now;
			$timeMs = $mtime * 1000;
		}
		if ($timeMs > $deadlineMs) {
			flock($h, LOCK_UN); fclose($h);
			$stat['kept']++; continue;                   //还没到清理年龄
		}
		/*最近 60 秒内动过就放过：把「刚好有人在写」的残留窗口压到可忽略*/
		if ($now * 1000 - $timeMs < 60000) {
			flock($h, LOCK_UN); fclose($h);
			$stat['kept']++; continue;
		}

		/*先 rename 再 unlink：rename 是原子的，之后才 fopen 的写入会落到新路径，
		**不会写进即将被删掉的孤儿 inode。改名后的文件带 .purge. 后缀，
		**不会命中上面的 .data 过滤，即使本轮 unlink 失败也不会被下一轮当成笔记重扫*/
		$purge = $path.'.purge.'.uniqid('', true);
		$deleted = false;
		if (@rename($path, $purge)) {
			$deleted = @unlink($purge);
		}
		flock($h, LOCK_UN);
		fclose($h);
		if ($deleted) $stat['deleted']++; else $stat['kept']++;
	}
	return $stat;
}
?>
