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

/*站点主题（可选）
**主题是 content/themes/<主题名>.css 这样一份样式覆盖文件，
**在基础样式之后加载，只改配色不改布局。
**主题名会拼进 URL，所以只允许字母数字下划线连字符，并且必须存在对应文件，
**否则静默回退默认样式。Docker 部署用环境变量 NPO_THEME 指定*/
$themeName = "default";
$themeCssUrl = "";
$npoEnvTheme = trim((string)getenv('NPO_THEME'));
if ($npoEnvTheme !== "" && preg_match('/^[A-Za-z0-9_-]{1,32}$/', $npoEnvTheme)) {
    if (is_file(__DIR__."/../content/themes/".$npoEnvTheme.".css")) {
        $themeName = $npoEnvTheme;
        $themeCssUrl = "/content/themes/".$npoEnvTheme.".css";
    }
}

/*页脚文字（可选）
**显示在页面底部左侧，右侧固定为版权（© 年份 站点名）。
**Docker 部署可用环境变量 NPO_FOOTER_TEXT 覆盖，
**只要变量被设置（哪怕是空值）就以环境变量为准，显式空串即不显示文字部分*/
$footerText = "";
$npoEnvFooterText = getenv('NPO_FOOTER_TEXT');
if ($npoEnvFooterText !== false) $footerText = trim($npoEnvFooterText);

/*套用模板
**模板决定页面结构（header/show/footer），套用时请确保模板存在*/
$templateName = "default";
/*服务端 include 用绝对路径；URL 拼接另用 $templateUrl，
**否则相对路径会随请求路径（/xxx 或 /share/yyy）漂移*/
$templateFolder = __DIR__."/../content/templates/".$templateName."/";
$templateUrl = "/content/templates/".$templateName."/";
?>