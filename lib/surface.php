<?php ($visit) ? ("") : (header('location: /'));?>
<?php
/*这是NPO的操作文件
**------------------
**如果您不了解PHP或程序的功能实现方式及内容架构，请勿修改内容*/

$name = npoInput($_POST, 'f');
$isPost = ($name !== '');
if (!$isPost) $name = npoInput($_GET, 'f');
$esN = $es != 'e' ? 1 : 0;
$esL = $esN ? 'share/' : '';

/*站点根地址：优先使用 config.php 的 $siteURL，避免 Host 头注入；
**未配置时仅在 Host 形态合法时按请求推断，否则留空（页面内退化为相对链接）*/
$URL = '';
if (isset($siteURL) && is_string($siteURL) && $siteURL !== '') {
	$URL = rtrim($siteURL, '/');
} else {
	$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
	if (preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
		$URL = ($npoHttps ? 'https://' : 'http://').$host;
	}
}

function randomkeys($length = 6){
	/*获取指定位数 a-z 的随机字符串*/
	$output = '';
	for ($a = 0; $a < $length; $a++) {
		$output .= chr(random_int(97, 122));
	}
	return $output;
}

if ($name === '') {//未定义URL后缀时，随机获取并跳转
	$base = realpath(__DIR__.'/../'.$tmpFolder);
	$name = '';
	for ($i = 0; $i < 50; $i++) {
		$cand = randomkeys(10);
		if ($base === false or !file_exists($base.'/'.$cand.'.data')) { $name = $cand; break; }
	}
	header("Location: /".$esL.$name);
	die();
}

if ($es == 'e') {
	$namePed = str_replace(array("\\","/"," ",".","*",":","|","\"","'","<",">"), "", trim($name));
	if (function_exists('mb_substr')) {
		$namePed = mb_substr($namePed, 0, 12, 'UTF-8');
	} else {
		$namePed = substr($namePed, 0, 48);
	}
	if (!npoValidName($namePed)) { header('Location: /'); die(); }
	if ($isPost or $namePed !== $name) { header('Location: /'.$namePed); die(); }
} else {
	/*分享侧 f 是签名 token，保持 token 形态（页面内的分享链接与 es=s 请求都依赖它），
	**签名校验通过即说明明文名称合法*/
	if (npoShareParse($name) === false) { header('Location: /'); die(); }
}

/*展示与导出 Word 用的明文名称：分享侧 $name 是签名 token，
**解析失败时退回 token 本身；编辑侧直接用 $name*/
$npoDisplayName = $name;
if ($esN) {
	$npoParsedName = npoShareParse($name);
	if ($npoParsedName !== false) $npoDisplayName = $npoParsedName;
}
?>
