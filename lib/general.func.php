<?php ($visit) ? ("") : (header('location: /'));?>
<?php
/*这是NPO的库文件
**------------------
**如果您不了解PHP或程序的功能实现方式及内容架构，请勿修改内容*/

/*安全基线：错误信息不回显，避免泄露路径与版本*/
ini_set('display_errors', '0');
//屏蔽新笔记因找不到笔记缓存而导致的错误；deprecation 只落盘无价值，一并关掉
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
/*错误落盘：页面不回显，但留档便于排查；_tmp 已被 .htaccess/nginx 拒绝直访*/
$npoErrorLog = __DIR__.'/../_tmp/error.log';
/*超过 2MB 轮转一份，避免长期运行把磁盘写满*/
if (@filesize($npoErrorLog) > 2097152) {
	@rename($npoErrorLog, $npoErrorLog.'.1');
}
ini_set('log_errors', '1');
ini_set('error_log', $npoErrorLog);

/*会话加固：Cookie 仅 HTTP、SameSite=Lax、严格模式、仅用 Cookie
**HTTPS 判断兼容反向代理透传的 X-Forwarded-Proto*/
$npoHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
	|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
if (PHP_VERSION_ID >= 70300) {
	session_set_cookie_params(array('lifetime'=>0,'path'=>'/','secure'=>$npoHttps,'httponly'=>true,'samesite'=>'Lax'));
} else {
	session_set_cookie_params(0, '/; samesite=Lax', '', $npoHttps, true);
}
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
session_start();//必要session启动器

/*基础安全响应头*/
if (!headers_sent()) {
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: SAMEORIGIN');
	header('Referrer-Policy: no-referrer');
	header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
	header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');
	/*笔记内容走 XHR 返回，禁止浏览器与中间代理缓存*/
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	if ($npoHttps) {
		header('Strict-Transport-Security: max-age=31536000');
	}
	header_remove('X-Powered-By');
}

/*CSRF 令牌：每个会话一个，前端在每个 ajax 请求中带上*/
if (empty($_SESSION['npoCsrf']) || !is_string($_SESSION['npoCsrf'])) {
	try {
		$_SESSION['npoCsrf'] = bin2hex(random_bytes(32));
	} catch (Throwable $e) {
		$_SESSION['npoCsrf'] = hash('sha256', uniqid((string)mt_rand(), true));
	}
}
$csrfToken = $_SESSION['npoCsrf'];

function dencrypt($mode,$data,$key = "94joy"){
	/*加密解密函数
	**原有加解密函数因长度过长已被弃用;
	**勿改key值,若base64编码后首字母为数字可能会导致session变量无法找到;
	**可逆加密	mode=1
	**可逆解密	mode=-1
	**不可逆加密	mode=2*/
	switch($mode){
		case 1:return str_replace("/","-",base64_encode($key.$data));
		case -1:return substr(base64_decode(str_replace("-","/",$data)),strlen($key));
		case 2:return md5(md5($data).sha1($data));
	}
}

/*单篇笔记的体积上限：原文 512KB，编码后（实体膨胀）4MB，
**防止单次请求把数据目录写满*/
define('NPO_MAX_NOTE_BYTES', 524288);
define('NPO_MAX_NOTE_ENCODED', 4194304);

function npoEditTime($v){
	/*编辑时间戳只接受合理的 13 位毫秒值：位数不对、早于 2000-01-01
	**或晚于当前时间 1 天以上的一律替换为服务端毫秒，避免客户端写入
	**异常值把同步逻辑带偏（例如永远"未来"的时间戳会让本机永远不保存）*/
	$now = (int)round(microtime(true) * 1000);
	if (is_string($v) && preg_match('/^\d{13}$/', $v)) {
		$t = (int)$v;
		if ($t >= 946684800000 && $t <= $now + 86400000) return $t;
	}
	return $now;
}

function npoPwThrottle($key, $fail = null){
	/*密码尝试节流：按会话记录失败次数，达到阈值后指数退避锁定。
	**$fail = null 查询剩余锁定秒数；true 记一次失败；false 清除记录
	**注意：攻击者丢弃 Cookie 可重置计数，这里只提高爆破成本，不是绝对防护*/
	if (!isset($_SESSION['npoPwFail']) || !is_array($_SESSION['npoPwFail'])) {
		$_SESSION['npoPwFail'] = array();
	}
	if (count($_SESSION['npoPwFail']) > 50) $_SESSION['npoPwFail'] = array();

	$now = time();
	$st = (isset($_SESSION['npoPwFail'][$key]) && is_array($_SESSION['npoPwFail'][$key]))
		? $_SESSION['npoPwFail'][$key] : array('n'=>0,'until'=>0);
	$st['n'] = isset($st['n']) ? (int)$st['n'] : 0;
	$st['until'] = isset($st['until']) ? (int)$st['until'] : 0;

	if ($fail === null) {
		return $st['until'] > $now ? ($st['until'] - $now) : 0;
	}
	if ($fail) {
		$st['n'] = min(13, $st['n'] + 1);
		if ($st['n'] >= 5) {
			$st['until'] = $now + min(300, 1 << ($st['n'] - 5));
		}
		$_SESSION['npoPwFail'][$key] = $st;
		usleep(200000);//统一失败延迟，降低在线爆破速率
		return 0;
	}
	unset($_SESSION['npoPwFail'][$key]);
	return 0;
}

function npoInput($arr, $key){
	/*只接受字符串型请求参数，数组/对象等一律视为空*/
	if (!is_array($arr) || !isset($arr[$key]) || !is_string($arr[$key])) return '';
	return $arr[$key];
}

function npoValidName($name){
	/*笔记名的唯一校验入口：
	**必须是合法 UTF-8、长度不超过 12 个字符、首尾无空白、
	**且不含控制字符、空白、路径分隔符与 HTML/JS 元字符*/
	if (!is_string($name) || $name === '') return false;
	if (!preg_match('//u', $name)) return false;
	if (function_exists('mb_strlen')) {
		if (mb_strlen($name, 'UTF-8') > 12) return false;
	} elseif (strlen($name) > 48) {
		return false;
	}
	if (trim($name) !== $name) return false;
	if (preg_match('/[\x00-\x1f\x7f\s\/\\\\\.\*:\|"\'<>]/u', $name)) return false;
	return true;
}

function npoCsrfRotate(){
	/*权限提升（密码校验通过）后更换会话 ID 并轮换 CSRF 令牌，
	**防止会话固定与令牌长期复用*/
	if (session_status() === PHP_SESSION_ACTIVE) {
		@session_regenerate_id(true);
	}
	try {
		$_SESSION['npoCsrf'] = bin2hex(random_bytes(32));
	} catch (Throwable $e) {
		$_SESSION['npoCsrf'] = hash('sha256', uniqid((string)mt_rand(), true));
	}
	return $_SESSION['npoCsrf'];
}

function npoCsrfValid(){
	/*写操作必须带合法 CSRF 令牌*/
	if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') return false;
	if (empty($_SESSION['npoCsrf']) || !is_string($_SESSION['npoCsrf'])) return false;
	$token = npoInput($_POST, 'csrf');
	if ($token === '') return false;
	return hash_equals($_SESSION['npoCsrf'], $token);
}

function npoJson($v){
	/*把值安全地嵌入 <script> 内的 JS 上下文，避免 </script> 与引号逃逸*/
	$out = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
	return ($out === false) ? '""' : $out;
}

function npoLooksSuspiciousHtml($html){
	/*快速判断内容中是否含有可执行/可外链的危险结构*/
	return (bool)preg_match('/<\s*(script|iframe|object|embed|svg|math|base|form|meta|link|style|frame|frameset|applet)\b|on[a-z]+\s*=|javascript\s*:|vbscript\s*:|data\s*:\s*text\/html|srcdoc\s*=/i', (string)$html);
}

function npoRebuildNode($doc, $node, $dropTags, $allowedTags, $allowedAttrs){
	/*把源文档节点重建到目标文档中：dropTags 整段丢弃，
	**白名单外的标签去壳保留内容，属性按白名单过滤*/
	if ($node->nodeType === XML_TEXT_NODE) {
		return $doc->createTextNode($node->nodeValue);
	}
	if ($node->nodeType !== XML_ELEMENT_NODE) {
		return null;//注释、处理指令、CDATA 等一律丢弃
	}
	$tag = strtolower($node->nodeName);
	if (in_array($tag, $dropTags, true)) return null;

	$children = array();
	for ($c = $node->firstChild; $c !== null; $c = $c->nextSibling) {
		$built = npoRebuildNode($doc, $c, $dropTags, $allowedTags, $allowedAttrs);
		if ($built !== null) $children[] = $built;
	}

	if (!in_array($tag, $allowedTags, true)) {
		$frag = $doc->createDocumentFragment();
		foreach ($children as $c) $frag->appendChild($c);
		return $frag;
	}

	$el = $doc->createElement($tag);
	if ($node->attributes !== null) {
		for ($i = 0; $i < $node->attributes->length; $i++) {
			$attr = $node->attributes->item($i);
			if ($attr === null) continue;
			$an = strtolower($attr->nodeName);
			if (strpos($an, 'on') === 0 || !in_array($an, $allowedAttrs, true)) continue;
			$val = (string)$attr->nodeValue;
			if ($an === 'style') {
				if (preg_match('/expression\s*\(|javascript\s*:|vbscript\s*:|behavior\s*:|-moz-binding/i', $val)) continue;
				$el->setAttribute('style', $val);
				continue;
			}
			if ($an === 'href' || $an === 'src') {
				$probe = preg_replace('/[\x00-\x20]+/', '', $val);
				if ($an === 'src' && preg_match('/^data:image\//i', $probe)) {
					$el->setAttribute('src', $val);
					continue;
				}
				if (preg_match('/^(javascript|vbscript|data|file|blob):/i', $probe)) continue;
				$el->setAttribute($an, $val);
				if ($an === 'href') $el->setAttribute('rel', 'noopener noreferrer');
				continue;
			}
			if ($an === 'target') {
				$el->setAttribute('target', ($val === '_blank') ? '_blank' : '_self');
				continue;
			}
			$el->setAttribute($an, $val);
		}
	}
	foreach ($children as $c) $el->appendChild($c);
	return $el;
}

function npoSanitizeHtml($html){
	/*白名单清洗：只保留编辑器能产生的安全标签与属性，杜绝存储型 XSS*/
	$html = (string)$html;
	if ($html === '') return '';

	$dropTags = array('script','style','iframe','frame','frameset','object','embed','applet','form','input','button','textarea','select','option','link','meta','base','svg','math','noscript','template','video','audio','source','canvas');
	$allowedTags = array('p','br','span','div','b','strong','i','em','u','s','strike','del','ins','sub','sup','h1','h2','h3','h4','h5','h6','ul','ol','li','blockquote','pre','code','hr','table','thead','tbody','tfoot','tr','th','td','a','img','font','small','big','center','section','article','figure','figcaption','label','dl','dt','dd');
	$allowedAttrs = array('style','class','href','src','alt','title','width','height','colspan','rowspan','align','valign','color','size','face','target','rel','border','cellpadding','cellspacing');

	if (!class_exists('DOMDocument')) {
		//没有 DOM 扩展时退化为黑名单过滤
		$html = preg_replace('#<\s*/?\s*(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta|base|svg|math|frame|frameset|applet|noscript)\b[^>]*>#i', '', $html);
		$html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
		$html = preg_replace('/(href|src)\s*=\s*("|\')\s*(javascript|vbscript|data\s*:\s*text\/html)[^"\']*("|\')/i', '$1="#"', $html);
		return $html;
	}

	$doc = new DOMDocument('1.0', 'UTF-8');
	$prev = libxml_use_internal_errors(true);
	$doc->loadHTML('<?xml encoding="UTF-8"?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	libxml_clear_errors();
	libxml_use_internal_errors($prev);

	$new = new DOMDocument('1.0', 'UTF-8');
	for ($c = $doc->firstChild; $c !== null; $c = $c->nextSibling) {
		$built = npoRebuildNode($new, $c, $dropTags, $allowedTags, $allowedAttrs);
		if ($built !== null) $new->appendChild($built);
	}

	$out = '';
	for ($c = $new->firstChild; $c !== null; $c = $c->nextSibling) {
		$out .= $new->saveHTML($c);
	}
	return $out;
}

function npoShareSecret(){
	/*分享 token 的 HMAC 密钥：优先取 config.php 的 $shareSecret，
	**其次读环境变量 NPO_SHARE_SECRET；都为空时返回空串，
	**调用方据此停用分享（不退化为固定的弱密钥）*/
	if (isset($GLOBALS['shareSecret']) && is_string($GLOBALS['shareSecret']) && $GLOBALS['shareSecret'] !== '') {
		return $GLOBALS['shareSecret'];
	}
	$env = getenv('NPO_SHARE_SECRET');
	return (is_string($env) && $env !== '') ? $env : '';
}

function npoShareToken($name){
	/*生成分享 token：base64url(明文名称) + "." + HMAC-SHA256 前 16 位十六进制。
	**笔记名不含点号，所以点号可以安全地作为分隔符；
	**名称非法或密钥缺失时返回空串*/
	if (!npoValidName($name)) return '';
	$secret = npoShareSecret();
	if ($secret === '') return '';
	$b64 = rtrim(strtr(base64_encode($name), '+/', '-_'), '=');
	return $b64.'.'.substr(hash_hmac('sha256', $name, $secret), 0, 16);
}

function npoShareParse($token){
	/*校验分享 token 并取出明文名称，失败返回 false。
	**签名用 hash_equals 比较，避免时序侧信道*/
	if (!is_string($token) || !preg_match('/^([A-Za-z0-9_-]+)\.([0-9a-f]{16})$/', $token, $m)) return false;
	$secret = npoShareSecret();
	if ($secret === '') return false;
	$raw = base64_decode(strtr($m[1], '-_', '+/'), true);
	if ($raw === false || $raw === '') return false;
	$expect = substr(hash_hmac('sha256', $raw, $secret), 0, 16);
	if (!hash_equals($expect, $m[2])) return false;
	return npoValidName($raw) ? $raw : false;
}
?>
