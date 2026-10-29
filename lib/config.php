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

/*套用主题
**套用主题时请确保主题存在*/
$templateName = "default";
/*服务端 include 用绝对路径；URL 拼接另用 $templateUrl，
**否则相对路径会随请求路径（/xxx 或 /share/yyy）漂移*/
$templateFolder = __DIR__."/../content/templates/".$templateName."/";
$templateUrl = "/content/templates/".$templateName."/";
?>