<?php ($visit) ? ("") : (header('location: /'));?>
<?php
/*这是NPO的库文件
**------------------
**如果您不了解PHP或程序的功能实现方式及内容架构，请勿修改内容*/

class note{
	public $name;
	public $mode;
	public $pathData;
	function __construct($name,$mode){//share的name应该经过处理
		$this->name = (string)$name;
		$this->mode = $mode;//0为编辑页,1为分享页
		$this->pathData = null;
		//名称非法或数据目录不可用时直接禁用本实例，避免路径穿越
		if (!function_exists('npoValidName') || !npoValidName($this->name)) return;
		$base = realpath(__DIR__.'/../'.$GLOBALS['tmpFolder']);
		if ($base === false) {
			@mkdir(__DIR__.'/../'.$GLOBALS['tmpFolder'], 0775, true);
			$base = realpath(__DIR__.'/../'.$GLOBALS['tmpFolder']);
		}
		if ($base === false) return;
		$this->pathData = $base.'/'.$this->name.'.data';
	}
	private $lockHandle = null;

	private function loadJson(){
		/*共享锁读取，避免读到写入中途被截断的内容*/
		if ($this->pathData === null or !file_exists($this->pathData)) return null;
		$h = @fopen($this->pathData, 'rb');
		if ($h === false) return null;
		flock($h, LOCK_SH);
		$raw = stream_get_contents($h);
		flock($h, LOCK_UN);
		fclose($h);
		$json = json_decode((string)$raw, true);
		return is_array($json) ? $json : null;
	}
	/*独占锁：把「读 → 判定 → 改 → 写」整段串行化，避免并发丢更新*/
	private function lockAcquire(){
		if ($this->pathData === null) return false;
		$h = @fopen($this->pathData, 'c+b');
		if ($h === false) return false;
		if (!flock($h, LOCK_EX)) { fclose($h); return false; }
		$this->lockHandle = $h;
		return true;
	}
	private function lockRead(){
		if ($this->lockHandle === null) return null;
		rewind($this->lockHandle);
		$raw = stream_get_contents($this->lockHandle);
		$json = json_decode((string)$raw, true);
		return is_array($json) ? $json : null;
	}
	/*把 $raw 完整写入持锁句柄；写满后按实际长度截断，
	**短写或 ftruncate 失败都返回 false，由调用方回滚*/
	private function rawWrite($raw){
		$len = strlen($raw);
		rewind($this->lockHandle);
		$written = 0;
		while ($written < $len) {
			$n = @fwrite($this->lockHandle, substr($raw, $written));
			if ($n === false or $n === 0) { fflush($this->lockHandle); return false; }
			$written += $n;
		}
		$ok = @ftruncate($this->lockHandle, $len);
		fflush($this->lockHandle);
		return (bool)$ok;
	}
	private function lockWrite($json){
		if ($this->lockHandle === null) return false;
		$raw = json_encode($json);
		if ($raw === false) return false;
		/*先读完旧内容做回滚备份，避免写到一半失败把笔记截断成残缺 JSON*/
		rewind($this->lockHandle);
		$backup = stream_get_contents($this->lockHandle);
		if ($backup === false) $backup = '';
		if ($this->rawWrite($raw)) return true;
		$this->rawWrite($backup);
		return false;
	}
	private function lockRelease(){
		if ($this->lockHandle === null) return;
		flock($this->lockHandle, LOCK_UN);
		fclose($this->lockHandle);
		$this->lockHandle = null;
	}
	/*权限判定：与具体读取方式解耦，便于在持锁状态下复用*/
	private function permissionFromJson($json){
		if (!is_array($json)) return 0;
		$espw = $this->mode ? "spw" : "epw";
		$es = $this->mode ? "s" : "e";
		if($es=='s' and (!isset($json['share']) or $json['share']=='0')) return -1;
		//只有“确实设置过密码”才要求鉴权，"0" 也是合法密码
		if(!isset($json[$espw]) or !is_string($json[$espw]) or $json[$espw]==='') return 1;
		$key = $es.dencrypt(1,$this->name);
		if(isset($_SESSION[$key]) and $_SESSION[$key]==="1") return 1;
		return 0;
	}
	private function throttleKey(){
		return $this->name.':'.($this->mode ? 's' : 'e');
	}
	public function noteRead(){
		if($this->pathData === null) return json_encode(array("status"=>"paraError6"));
		if(!$this->isExist()) return json_encode(array("status"=>"notExist"));
		$json = $this->loadJson();
		if ($json === null or !isset($json["data"]) or !is_string($json["data"])) return json_encode(array("status"=>"error"));
		/*先判权限再解码：无权限的请求不承担解码开销，避免被当作放大放大器*/
		$perm = $this->permissionFromJson($json);
		if ($perm === -1) return json_encode(array("status"=>"shareClosed"));
		if ($perm !== 1) return json_encode(array("status"=>"permissionDenied"));
		$asciiProcess = new ascii;
		$result = $asciiProcess->decode($json["data"]);
		//历史数据可能残留危险结构，返回前再清洗一次
		if (npoLooksSuspiciousHtml($result)) $result = npoSanitizeHtml($result);
		return json_encode(array("status"=>"OK","result"=>$result));
	}
	function noteChange($data,$lastEditTime='00000000'){
		if($this->pathData === null) return json_encode(array("status"=>"paraError6"));
		if(!is_string($data)) $data = '';
		if (strlen($data) > NPO_MAX_NOTE_BYTES) return json_encode(array("status"=>"tooLarge"));
		$lastEditTime = npoEditTime($lastEditTime);
		$data = npoSanitizeHtml($data);
		$asciiProcess = new ascii;
		$data = $asciiProcess->encode($data);
		if (strlen($data) > NPO_MAX_NOTE_ENCODED) return json_encode(array("status"=>"tooLarge"));

		/*整段加独占锁，避免多终端并发保存互相覆盖。
		**先记录加锁前的状态：lockAcquire 用 c+b 打开文件会顺手建出空文件，
		**所以必须在加锁之前判断「文件本来不存在」或「本来是空文件」，
		**否则损坏的 JSON 会被误判为首次创建并被整份覆盖*/
		$existed = $this->isExist();
		$emptyFile = false;
		if ($existed) {
			clearstatcache(true, $this->pathData);
			$emptyFile = (@filesize($this->pathData) === 0);
		}
		if (!$this->lockAcquire()) return json_encode(array("status"=>"error"));
		$json = $this->lockRead();
		$ok = false;
		if ($json === null) {
			if ($existed && !$emptyFile) {
				//文件存在但 JSON 损坏：拒绝写入，保留现场供人工处理
				$this->lockRelease();
				return json_encode(array("status"=>"error"));
			}
			//初次创建
			$ok = $this->lockWrite(array("name"=>$this->name,"creatTime"=>$lastEditTime,"editTime"=>$lastEditTime,"epw"=>"","spw"=>"","share"=>'0',"data"=>$data));
			$this->lockRelease();
			return json_encode(array("status"=>$ok ? "OK" : "error"));
		}
		if ($this->permissionFromJson($json) !== 1) {
			$this->lockRelease();
			return json_encode(array("status"=>"permissionDenied"));
		}
		$json["data"] = $data;
		$json["editTime"] = $lastEditTime;
		$ok = $this->lockWrite($json);
		$this->lockRelease();
		return json_encode(array("status"=>$ok ? "OK" : "error"));
	}
	public function isExist(){
		if ($this->pathData === null) return false;
		return file_exists($this->pathData);
	}
	public function readPermission(){
		/*只读类查询的权限判定：文件不存在视为可读（新笔记首访 / notExist 流程）
		**返回 -1 分享已关闭 / 0 需密码 / 1 可读*/
		if ($this->pathData === null) return 0;
		if (!$this->isExist()) return 1;
		$json = $this->loadJson();
		if ($json === null) return 0;
		return $this->permissionFromJson($json);
	}
	function pwJudge($pw, $rotate = true){
		/*返回：1 通过 / 0 失败 / -1 失败次数过多（暂时锁定）*/
		if($this->pathData === null) return 0;
		$tkey = $this->throttleKey();
		$throttled = function_exists('npoPwThrottle');
		if ($throttled && npoPwThrottle($tkey) > 0) return -1;
		if(!$this->isExist()){
			if ($throttled) npoPwThrottle($tkey, false);
			return 1;
		}
		$espw = $this->mode ? "spw" : "epw";
		$json = $this->loadJson();
		if (!is_string($pw) or $pw === '' or $json === null or !isset($json[$espw]) or !is_string($json[$espw])) {
			if ($throttled) npoPwThrottle($tkey, true);
			return 0;
		}
		//严格比较，避免 0e 开头的“魔术哈希”绕过
		if(!hash_equals($json[$espw], $pw)){
			if ($throttled) npoPwThrottle($tkey, true);
			return 0;
		}
		if ($throttled) npoPwThrottle($tkey, false);
		$es = $this->mode ? "s" : "e";$_SESSION[$es.dencrypt(1,$this->name)] = "1";
		/*权限提升后更换会话 ID 与 CSRF 令牌（调用方会重新加载页面，
		**所以不会让页面里缓存的旧令牌失效）*/
		if ($rotate && function_exists('npoCsrfRotate')) npoCsrfRotate();
		return 1;
	}
	function shareOperate($oc="OPEN"){
		//先判断文件存在，不存在则创建
		if($this->pathData === null) return json_encode(array("status"=>"paraError6"));
		if(!$this->isExist()){
			$this->noteChange('');
		}
		if (!$this->lockAcquire()) return json_encode(array("status"=>"error"));
		$json = $this->lockRead();
		if ($json === null) { $this->lockRelease(); return json_encode(array("status"=>"error")); }
		if ($this->permissionFromJson($json) !== 1) {
			$this->lockRelease();
			return json_encode(array("status"=>"permissionDenied"));
		}
		$json["share"] = $oc =="OPEN" ? 1 : 0;
		$ok = $this->lockWrite($json);
		$this->lockRelease();
		return json_encode(array("status"=>$ok ? "OK" : "error"));
	}
	function pwChange($opw,$npw){
		//先判断文件存在，不存在则创建
		if($this->pathData === null) return json_encode(array("status"=>"paraError6"));
		if(!is_string($opw)) $opw = '';
		if(!is_string($npw)) $npw = '';
		if(strlen($npw) > 128) return json_encode(array("status"=>"permissionDenied"));
		if(!$this->isExist()){
			$this->noteChange('');
		}
		/*持锁状态下不能再用 loadJson 这类会自行加锁的读取（会二次加锁导致自我阻塞），
		**这里全部基于同一份加锁快照判断*/
		$tkey = $this->throttleKey();
		$throttled = function_exists('npoPwThrottle');
		if ($throttled && npoPwThrottle($tkey) > 0) return json_encode(array("status"=>"tooManyAttempts"));
		if (!$this->lockAcquire()) return json_encode(array("status"=>"error"));
		$json = $this->lockRead();
		if ($json === null) { $this->lockRelease(); return json_encode(array("status"=>"error")); }
		$espw = $this->mode ? "spw" : "epw";
		$hasPw = isset($json[$espw]) && is_string($json[$espw]) && $json[$espw] !== '';
		//有无权限均可，只要旧密码正确；已有密码时不允许错误旧密码覆盖
		$pass = false;
		if(!$hasPw){
			$pass = ($opw === '');
		}elseif($opw !== '' && hash_equals($json[$espw], $opw)){
			$pass = true;
		}
		if (!$pass) {
			$this->lockRelease();
			if ($throttled && $hasPw) npoPwThrottle($tkey, true);
			return json_encode(array("status"=>"permissionDenied1"));
		}
		if ($throttled) npoPwThrottle($tkey, false);
		$json["editTime"]=(int)round(microtime(true) * 1000);
		$json[$espw]=$npw;
		$ok = $this->lockWrite($json);
		$this->lockRelease();
		$this->logout();
		return json_encode(array("status"=>$ok ? "OK" : "error"));
	}
	function logout(){
		if($this->pathData === null) return;
		$es = $this->mode ? "s" : "e";
		$_SESSION[$es.dencrypt(1,$this->name)]=0;
	}
	function getLastTime(){
		if($this->pathData === null) return '00000000';
		if(!$this->isExist()) return '00000000';
		$json = $this->loadJson();
		if ($json === null or !isset($json['editTime'])) return '00000000';
		$t = (string)$json['editTime'];
		return preg_match('/^\d{1,16}$/', $t) ? $t : '00000000';
	}
}
?>
