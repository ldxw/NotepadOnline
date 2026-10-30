<?php //($visit) ? ("") : (header('location: /'));?>
<?php
/*这是NPO的操作文件
**------------------
**如果您不了解PHP或程序的功能实现方式及内容架构，请勿修改内容*/

header('Content-Type:application/json;charset=utf-8');
$visit = true;
include_once __DIR__."/config.php";
include_once __DIR__."/general.func.php";
include_once __DIR__."/ascii.class.php";
include_once __DIR__."/note.class.php";

/*只接受 POST 且必须携带合法 CSRF 令牌*/
if (!npoCsrfValid()) {
	echo json_encode(array("status"=>"paraError10"));
	die();
}

$name = npoInput($_POST, 'name');
$es = npoInput($_POST, 'es');
$do = npoInput($_POST, 'do');
$data = npoInput($_POST, 'data');
$opw = npoInput($_POST, 'opw');
$npw = npoInput($_POST, 'npw');
$pw = npoInput($_POST, 'pw');
$lastEditTime = npoInput($_POST, 'lastEditTime');

if ($es != 'e' and $es != 's') { echo json_encode(array("status"=>"paraError4")); die(); }
if ($name === '') { echo json_encode(array("status"=>"paraError5")); die(); }
if ($do === '') { echo json_encode(array("status"=>"paraError6")); die(); }
$esN = $es != 'e' ? 1 : 0;

/*判断 name 参数：编辑侧直接校验；分享侧校验签名并取出明文名称*/
if ($es == 'e') {
	if (!npoValidName($name)) { echo json_encode(array("status"=>"paraError6")); die(); }
} else {
	/*分享 token 形态受限（base64url + 点 + 16 位十六进制），超长串直接拒绝，
	**避免先解码再校验的无谓开销*/
	if (strlen($name) > 256) { echo json_encode(array("status"=>"paraError6")); die(); }
	$decoded = npoShareParse($name);
	if ($decoded === false) { echo json_encode(array("status"=>"paraError6")); die(); }
	$name = $decoded;
}

$noteE = new note($name,$esN);
switch($do){
	case 'noteRead':echo $noteE->noteRead();break;
	case 'noteChange':if($es=='s'){echo json_encode(array("status"=>"paraError7"));die();}echo $noteE->noteChange($data,$lastEditTime);break;
	case 'pwJudge':	$judge = $noteE->pwJudge($pw);
					if ($judge === 1) {
						echo json_encode(array("status"=>"OK"));
					} elseif ($judge === -1) {
						echo json_encode(array("status"=>"tooManyAttempts"));
					} else {
						echo json_encode(array("status"=>"permissionDenied"));
					}
					break;
	case 'pwChange':echo $noteE->pwChange($opw,$npw);break;
	case 'shareOpen':if($es=='s'){echo json_encode(array("status"=>"paraError8"));die();}echo $noteE->shareOperate("OPEN");break;
	case 'shareClose':if($es=='s'){echo json_encode(array("status"=>"paraError9"));die();}echo $noteE->shareOperate("CLOSE");break;
	case 'noteDelete':if($es=='s'){echo json_encode(array("status"=>"paraError1"));die();}echo $noteE->noteDelete();break;
	case 'isExist':	$perm = $noteE->readPermission();
					if ($perm === -1) { echo json_encode(array("status"=>"shareClosed")); break; }
					if ($perm !== 1) { echo json_encode(array("status"=>"permissionDenied")); break; }
					echo json_encode(array("status"=>"OK","result"=>$noteE->isExist() ? "1" : "0"));
					break;
	case 'getLastTime':	$perm = $noteE->readPermission();
					if ($perm === -1) { echo json_encode(array("status"=>"shareClosed")); break; }
					if ($perm !== 1) { echo json_encode(array("status"=>"permissionDenied")); break; }
					echo json_encode(array("status"=>"OK","result"=>$noteE->getLastTime()));
					break;
	default:echo json_encode(array("status"=>"paraError0"));break;
}
?>
