<?php ($visit) ? ("") : (header('location: /'));?>
<?php
/*这是NPO的配置文件
**------------------
**不建议更改*/

/*缓存文件夹
**定义时请确保该文件夹有权访问修改*/
$tmpFolder = "_tmp";

/*站点根地址（可选）
**用于生成分享链接与二维码，如 "https://npo.example.com"
**留空则按请求 Host 头推断（不推荐，存在 Host 注入风险）*/
$siteURL = "";

/*分享链接签名密钥
**分享 token 用 HMAC-SHA256 签名，防止他人从 token 反推出文档名；
**为空时分享功能不可用（不会退化为固定弱密钥）。
**生产环境请通过环境变量 NPO_SHARE_SECRET 提供，密钥变更会让已发出的分享链全部失效*/
$shareSecret = getenv('NPO_SHARE_SECRET');
if ($shareSecret === false) $shareSecret = "";

/*站点名称
**显示在浏览器标题与导航栏品牌位，留空则回退为 "NPO"。
**Docker 部署可用环境变量 NPO_SITE_NAME 覆盖*/
$siteName = "NPO";
$npoEnvSiteName = getenv('NPO_SITE_NAME');
if ($npoEnvSiteName !== false && trim($npoEnvSiteName) !== "") $siteName = trim($npoEnvSiteName);

/*顶部广播
**标题加粗显示，正文跟在后面；两者都为空则整条广播不显示。
**Docker 部署可用环境变量 NPO_BROADCAST_TITLE / NPO_BROADCAST_TEXT 覆盖，
**只要变量被设置（哪怕是空值）就以环境变量为准，这样才能把默认广播置空关掉*/
$broadcastTitle = "广播DEMO";
$broadcastText = "这里是广播DEMO内容";
$npoEnvBroadcastTitle = getenv('NPO_BROADCAST_TITLE');
if ($npoEnvBroadcastTitle !== false) $broadcastTitle = trim($npoEnvBroadcastTitle);
$npoEnvBroadcastText = getenv('NPO_BROADCAST_TEXT');
if ($npoEnvBroadcastText !== false) $broadcastText = trim($npoEnvBroadcastText);

/*站点图标（favicon）
**填图片地址：站内相对路径（如 "/assets/image/favicon.ico"）或绝对 URL
**（如 "https://cdn.example.com/favicon.png"）都可以。
**留空则不输出图标链接，浏览器仍会自行请求 /favicon.ico。
**Docker 部署可用环境变量 NPO_FAVICON 覆盖*/
$faviconURL = "";
$npoEnvFavicon = getenv('NPO_FAVICON');
if ($npoEnvFavicon !== false) $faviconURL = trim($npoEnvFavicon);
/*只放行 http(s)、data:image/ 与站内相对路径，挡掉 javascript: 之类的伪协议*/
if ($faviconURL !== '' && preg_match('#^([a-z][a-z0-9+.\-]*):#i', $faviconURL, $npoFaviconScheme)) {
    $npoFaviconScheme = strtolower($npoFaviconScheme[1]);
    if ($npoFaviconScheme !== 'http' && $npoFaviconScheme !== 'https'
        && !($npoFaviconScheme === 'data' && stripos($faviconURL, 'data:image/') === 0)) {
        $faviconURL = "";
    }
}

/*页脚文字（可选）
**与固定的版权（© 年份 站点名）合成一行，整行水平居中。
**Docker 部署可用环境变量 NPO_FOOTER_TEXT 覆盖，
**只要变量被设置（哪怕是空值）就以环境变量为准，显式空串即不显示文字部分*/
$footerText = "";
$npoEnvFooterText = getenv('NPO_FOOTER_TEXT');
if ($npoEnvFooterText !== false) $footerText = trim($npoEnvFooterText);

/*外部 CDN 资源（可选）
**页面的 jquery / bootstrap / font-awesome / wangEditor / clipboard / FileSaver
**默认从 cdn.staticfile.net 加载。想换自建镜像或本地文件时有两种方式：
**1) NPO_CDN_BASE 换掉整站前缀，各库的相对路径保持不变；
**2) NPO_CDN_xxx 单独指定某个库的完整地址，优先级高于前缀，适合只给某个库加速。
**任何一项留空都回落到默认值，所以全部不设时页面输出与原来完全一致。
**只放行 http(s) 绝对地址和以单个 / 开头的站内路径，其余（javascript: 等伪协议）一律忽略*/
$cdnBase = "https://cdn.staticfile.net";
$npoEnvCdnBase = getenv('NPO_CDN_BASE');
if ($npoEnvCdnBase !== false && trim($npoEnvCdnBase) !== "") {
    $cdnBase = rtrim(trim($npoEnvCdnBase), "/");
}
/*前缀本身也只允许 http(s)，防止被写成伪协议*/
if (!preg_match('#^https?://[^/\s]+#i', $cdnBase)) $cdnBase = "https://cdn.staticfile.net";

/*库名 => array(覆盖变量名, 相对路径)；数组顺序即页面上 8 处引用的顺序*/
$npoCdnLibs = array(
    'jquery'         => array('NPO_CDN_JQUERY',          '/jquery/3.4.1/jquery.min.js'),
    'bootstrapCss'   => array('NPO_CDN_BOOTSTRAP_CSS',   '/twitter-bootstrap/4.4.1/css/bootstrap.min.css'),
    'bootstrapJs'    => array('NPO_CDN_BOOTSTRAP_JS',    '/twitter-bootstrap/4.4.1/js/bootstrap.bundle.min.js'),
    'fontawesomeCss' => array('NPO_CDN_FONTAWESOME_CSS', '/font-awesome/5.13.0/css/all.min.css'),
    'fontawesomeJs'  => array('NPO_CDN_FONTAWESOME_JS',  '/font-awesome/5.13.0/js/all.min.js'),
    'wangEditor'     => array('NPO_CDN_WANGEDITOR',      '/wangEditor/10.0.13/wangEditor.min.js'),
    'clipboard'      => array('NPO_CDN_CLIPBOARD',       '/clipboard.js/2.0.6/clipboard.min.js'),
    'fileSaver'      => array('NPO_CDN_FILESAVER',       '/FileSaver.js/1.3.8/FileSaver.min.js'),
);
$npoCdnUrl = array();
foreach ($npoCdnLibs as $npoCdnKey => $npoCdnLib) {
    $npoCdnUrl[$npoCdnKey] = $cdnBase . $npoCdnLib[1];
    $npoCdnRaw = getenv($npoCdnLib[0]);
    if ($npoCdnRaw === false) continue;
    $npoCdnRaw = trim($npoCdnRaw);
    if ($npoCdnRaw === "") continue;
    if (preg_match('#^([a-z][a-z0-9+.\-]*):#i', $npoCdnRaw, $npoCdnMatch)) {
        /*带协议头的只接受 http / https，其他伪协议保持默认值*/
        $npoCdnScheme = strtolower($npoCdnMatch[1]);
        if ($npoCdnScheme === 'http' || $npoCdnScheme === 'https') $npoCdnUrl[$npoCdnKey] = $npoCdnRaw;
    } else if (strpos($npoCdnRaw, '/') === 0 && strpos($npoCdnRaw, '//') !== 0) {
        /*单个 / 开头视为站内路径；//host/x 这种协议相对地址不接受*/
        $npoCdnUrl[$npoCdnKey] = $npoCdnRaw;
    }
}
unset($npoCdnLibs, $npoCdnKey, $npoCdnLib, $npoCdnRaw, $npoCdnMatch, $npoCdnScheme);

/*套用模板
**模板决定页面结构（header/show/footer），套用时请确保模板存在*/
$templateName = "default";
/*服务端 include 用绝对路径；URL 拼接另用 $templateUrl，
**否则相对路径会随请求路径（/xxx 或 /share/yyy）漂移*/
$templateFolder = __DIR__."/../content/templates/".$templateName."/";
$templateUrl = "/content/templates/".$templateName."/";
?>