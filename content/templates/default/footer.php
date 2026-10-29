<?php ($visit) ? ("") : (header('location: /'));?>
<?php
/*分享链接：编辑侧由明文名称生成签名 token；分享侧自身已是 token，直接复用*/
$npoShareLink = $esN ? $name : npoShareToken($name);
$npoShareUrl = ($npoShareLink === '') ? '' : $URL."/share/".$npoShareLink;
/*版本号读根目录 VERSION，读不到时退回内置值，避免"关于"里写死过期版本*/
$npoVersionFile = __DIR__.'/../../../VERSION';
$npoVersion = is_readable($npoVersionFile) ? trim((string)file_get_contents($npoVersionFile)) : '';
if ($npoVersion === '') $npoVersion = '3.22';
?>
<div class="modal fade" id="keyJudgeWindow" tabindex="-1" data-backdrop="static" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">此笔记被加密，请输入密码</h5>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group">
            <label class="col-form-label">密码：</label>
            <input type="password" class="form-control" id="Pw" placeholder="">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <input type='submit' class="btn btn-success" id="pwJudge" value='好的'>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="versionWindow" tabindex="-1" role="dialog" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalScrollableTitle">关于</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h3>感谢捐助</h3>
        <p>Alex Lee</p>
        <h3>使用的开源程序</h3>
        <p>Bootstrap | Jquery | Popper | font-awesome | wangeditor | clipboard.js | QRCode.js | FileSaver.js | jquery.wordexport.js
        <h3>创意来源</h3>
        <p>Notepad_live | 石墨文档</p>
        <h3>关于NPO</h3>
        <p>项目地址：<a href="https://github.com/2613df/NotepadOnline" target="_blank">Github</a><br>当前版本：<?php echo htmlspecialchars($npoVersion, ENT_QUOTES, 'UTF-8'); ?><br>更新内容：<br>1 优化滚动条样式和显示逻辑<br>2 优化保存 / 同步逻辑，支持多终端同步<br>3 优化文档样式<br>4 新增顶部广播<br>5 顶栏不再能被选中<br>6 优化分享功能<br>7 支持导出Word<br>8 修复一定情况下Word导出失败的问题<br>9 捕获组合按键，优化体验<br>10 优化工具栏<br>11 进一步优化移动端(含iPad)体验</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">好</button>
      </div>
    </div>
  </div>
</div>





<div class="modal fade" id="shareWindow" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalScrollableTitle">分享</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
		<ul class="nav nav-pills mb-3" id="pills-share-tab" role="tablist">
		  <li class="nav-item">
		    <a class="nav-link active" id="pills-share-tab-std" data-toggle="pill" href="#pills-share-std" role="tab" aria-controls="pills-share-std" aria-selected="true">基本</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link" id="pills-share-tab-key" data-toggle="pill" href="#pills-share-key" role="tab" aria-controls="pills-share-key" aria-selected="false">密码</a>
		  </li>
		  <!-- <li class="nav-item">
		    <a class="nav-link" id="pills-contact-tab" data-toggle="pill" href="#pills-contact" role="tab" aria-controls="pills-contact" aria-selected="false">Contact</a>
		  </li> -->
		</ul>
		<div class="tab-content" id="pills-tabContent">
		  <div class="tab-pane fade show active" id="pills-share-std" role="tabpanel" aria-labelledby="pills-share-tab-std">
			<div id="shareStatus"></div>
		    <form>
			<b>分享开关：</b><div id="getShareDiv"><a href="#" id="getShareBtn">点此获取当前状态</a></div>
			<br>
			<b>分享权限：</b><div class="custom-control custom-switch switchDiv"><input type="checkbox" id="shareRight" class="custom-control-input" disabled><label class="custom-control-label" for="shareRight">只能阅读</label></div>&nbsp;<small>分享链内容无法编辑只能阅读</small>
			<br><small>开发日志：分享链暂时不支持编辑</small><br>
			<p><b>固定分享链：</b>
			<div class="input-group">
				<input type="text" class="form-control" id="shareUrl" aria-describedby="inputGroupAppend" value="<?php echo htmlspecialchars($npoShareUrl, ENT_QUOTES, 'UTF-8');?>" readonly>
				<div class="input-group-append">
				  <span class="input-group-text" id="inputGroupPrepend"><a href="#" class="copy" data-clipboard-target="#shareUrl"><i class="fas fa-clipboard-list"></i>&nbsp;复制链接</a></span>
				</div>
			</div>
			</p>
			<p><b>二维码：</b><br>

			<img class="npo-qr" data-qr="<?php echo htmlspecialchars($npoShareUrl, ENT_QUOTES, 'UTF-8');?>" alt="分享二维码"/></p>
			</form>


		  </div>
		  <div class="tab-pane fade" id="pills-share-key" role="tabpanel" aria-labelledby="pills-share-tab-key">
		  	<div id="shareKeyStatus"></div>
		  	<b class="text-danger">请您保管好密码，密码遗失后将无法通过分享链访问您的内容<br>即使关闭分享后再次打开，密码依然存在，直至您在此删除密码</b>
			  <div class="form-group">
			    <label class="col-form-label">旧密码：</label>
			    <input type="text" class="form-control" id="oldPwShare" placeholder="若无请留空">
			  </div>
			  <div class="form-group">
			    <label class="col-form-label">新密码：</label>
			    <input type="text" class="form-control" id="newPwShare" placeholder="删密请留空">
			  </div>
			  
			<button type="button" class="btn btn-danger" id="pwChangeShare">确认</button>



		  </div>
		  <!-- <div class="tab-pane fade" id="pills-contact" role="tabpanel" aria-labelledby="pills-contact-tab">..3.</div> -->
		</div>
      </div>
      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">知道了</button>
      </div> -->
    </div>
  </div>
</div>





<div class="modal fade" id="configWindow" tabindex="-1" role="dialog" data-backdrop="static" aria-labelledby="exampleModalScrollableTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalScrollableTitle">设定</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
		<ul class="nav nav-pills mb-3" id="pills-config-tab" role="tablist">
		  <li class="nav-item">
		    <a class="nav-link active" id="pills-config-tab-std" data-toggle="pill" href="#pills-config-std" role="tab" aria-controls="pills-config-std" aria-selected="true">基本</a>
		  </li>
		  <li class="nav-item">
		    <a class="nav-link" id="pills-config-tab-key" data-toggle="pill" href="#pills-config-key" role="tab" aria-controls="pills-config-key" aria-selected="false">密码</a>
		  </li>

		</ul>
		<div class="tab-content" id="pills-tabContent">
		  <div class="tab-pane fade show active" id="pills-config-std" role="tabpanel" aria-labelledby="pills-config-tab-std">
		    <form>
			<p><b>链接：</b>
			<div class="input-group">
				<input type="text" class="form-control" id="crtUrl" aria-describedby="inputGroupAppend" value="<?php echo htmlspecialchars($URL."/".$esL.$name, ENT_QUOTES, 'UTF-8');?>" readonly>
				<div class="input-group-append">
				  <span class="input-group-text" id="inputGroupPrepend"><a href="#" class="copy" data-clipboard-target="#crtUrl"><i class="fas fa-clipboard-list"></i>&nbsp;复制链接</a></span>
				</div>
			</div>
			</p>
			<p><b>二维码：</b><br>

			<img class="npo-qr" data-qr="<?php echo htmlspecialchars($URL."/".$esL.$name, ENT_QUOTES, 'UTF-8');?>" alt="当前链接二维码"/></p>
			</form>


		  </div>
		  <div class="tab-pane fade" id="pills-config-key" role="tabpanel" aria-labelledby="pills-config-tab-key">
		  	<div id="configKeyStatus"></div>
		  	<b class="text-danger">请您保管好密码，密码遗失后将无法访问您的文档</b><br>
		  	
	        <form>
	          <div class="form-group">
	            <label class="col-form-label">旧密码：</label>
	            <input type="text" class="form-control" id="oldPw" placeholder="若无请留空">
	          </div>
	          <div class="form-group">
	            <label class="col-form-label">新密码：</label>
	            <input type="text" class="form-control" id="newPw" placeholder="删密请留空">
	          </div>
	        <input type='submit' class="btn btn-danger" id="pwChange" value='确认'>
	        </form>
		  </div>
		</div>
      </div>
      <!-- <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">知道了</button>
      </div> -->
    </div>
  </div>
</div>

<div id="toastTop">
	<div id="toastTopTitle">
		<span id="toastTopTitleText"></span>
		<a href="#" id="toastTopClose">&times;</a></div>
	<div id="toastTopBody"></div>
</div>

<script src="<?php echo htmlspecialchars($npoCdnUrl['jquery'], ENT_QUOTES, 'UTF-8'); ?>" crossorigin="anonymous"></script>
<script src="<?php echo htmlspecialchars($npoCdnUrl['bootstrapJs'], ENT_QUOTES, 'UTF-8'); ?>" crossorigin="anonymous"></script>
<script crossorigin="anonymous" src="<?php echo htmlspecialchars($npoCdnUrl['fontawesomeJs'], ENT_QUOTES, 'UTF-8'); ?>"></script>
<script type="text/javascript">
var NPO_CSRF = <?php echo npoJson($csrfToken); ?>;
$.ajaxPrefilter(function(options){
	/*jQuery 在进入 prefilter 前已把 data 对象序列化成字符串，这里直接追加字段*/
	if (String(options.type).toUpperCase() !== 'POST') return;
	if (options.data === undefined || options.data === null) {
		options.data = 'csrf=' + encodeURIComponent(NPO_CSRF);
	} else if (typeof options.data === 'string') {
		options.data += (options.data === '' ? '' : '&') + 'csrf=' + encodeURIComponent(NPO_CSRF);
	}
});
<?php
$ajaxJs = $es=='e'? "edit":"share";
include_once $templateFolder."assets/js/ajax.".$ajaxJs.".js.php";
?>
</script>
<!-- <script src="<?php echo $templateUrl."assets/js/light.js";?>"></script> -->
<script src="<?php echo htmlspecialchars($npoCdnUrl['wangEditor'], ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($npoCdnUrl['clipboard'], ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars($npoCdnUrl['fileSaver'], ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="/assets/js/jquery.wordexport.js"></script>
<script src="/assets/js/qrcode.min.js"></script>
<script>
/*把页面上的 data-qr 占位图片就地渲染成本地二维码（data: URL），
**不依赖第三方二维码服务；已填过的跳过*/
function npoQrFill($root){
	$root.find('img[data-qr]').addBack('img[data-qr]').each(function(){
		if (this.getAttribute('data-qr-done') === '1') return;
		var text = this.getAttribute('data-qr');
		if (!text) return;
		try {
			var q = qrcode(0, 'M');
			q.addData(text);
			q.make();
			this.src = q.createDataURL(4, 8);
			this.setAttribute('data-qr-done', '1');
		} catch (e) {}
	});
}
//编辑器初始化
var E = window.wangEditor
var editor = new E('#toolDiv','#editorDiv')
editor.customConfig.zIndex = 1000;
/*该 CDN 构建(10.0.13)没有 fontSize / fontName / fullscreen 菜单，
**写进 menus 也只会被静默忽略，所以这里不列。全屏由页面上单独放的按钮实现*/
editor.customConfig.menus = [
'head',  // 标题
'bold',  // 粗体
'italic',  // 斜体
'underline',  // 下划线
'strikeThrough',  // 删除线
'foreColor',  // 文字颜色
'backColor',  // 背景颜色
'link',  // 插入链接
'list',  // 列表
'justify',  // 对齐方式
'quote',  // 引用
'emoticon',  // 表情
'image',  // 插入图片
'table',  // 表格
'code',  // 插入代码
'undo',  // 撤销
'redo'  // 重复
];
/*图片以 base64 直接嵌进正文，本程序没有上传后端；
**服务端只放行 data:image/ 的 src，其他 src 会被丢弃。
**单张上限 200KB：base64 后约 270KB，仍在笔记 512KB 原始体积上限之内*/
editor.customConfig.uploadImgShowBase64 = true;
editor.customConfig.uploadImgMaxSize = 200 * 1024;
editor.customConfig.uploadImgMaxLength = 1;
/*编辑器自带的 alert() 换成顶部 toast*/
editor.customConfig.customAlert = function(msg){
	var esc = String(msg).replace(/[&<>"']/g, function(c){
		return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
	});
	clearTimeout(closeToastSTO);
	$("#toastTop").attr("style","display:block");
	$("#toastTopTitleText").html('<i class="fas fa-exclamation-triangle"></i>&nbsp;插入失败');
	$("#toastTopBody").html(esc.replace(/\n/g,'<br>'));
	closeToastSTO = setTimeout(function(){$("#toastTop").attr("style","display:none");},5000);
};
editor.create();
$(function () {
  $('[data-toggle="popover"]').popover()
  $('[data-toggle="tooltip"]').tooltip()
  /*二维码就地渲染；popover 内容是插入时才进 DOM 的，需在 shown 事件里补一次*/
  npoQrFill($(document));
  $(document).on('shown.bs.popover', function(e){
    /*popover 内的图片被 Bootstrap sanitizer 剥掉了 data-qr，地址改存在触发元素自身上，
    **这里回填后再统一渲染*/
    var $trigger = $(e.target);
    var qrText = $trigger.attr('data-qr');
    var pop = $trigger.data('bs.popover');
    var $tip = (pop && pop.tip) ? $(pop.tip) : $trigger.next('.popover');
    if (qrText && $tip.length) {
      $tip.find('img.npo-qr').attr('data-qr', qrText);
    }
    npoQrFill($(document));
  });
  // $('.w-e-menu').eq(6).after('<div class="visible-md-block">');
  // $('.w-e-menu').eq(13).after('</div>');
  <?php if ($es == 'e') { ?>
  // 仅编辑页在窄屏隐藏工具栏；分享页必须隐藏，不能被 d-lg-block 覆盖
  $('.w-e-toolbar').attr("class","w-e-toolbar d-none d-lg-block")
  <?php } ?>
})






//复制剪切板初始化
new ClipboardJS('.copy');

//toast初始化
var closeToastSTO;

//导出word初始化
function exportWordDo(){
	clearTimeout(closeToastSTO);
	$("#toastTop").attr("style","display:block");
	$("#toastTopTitleText").html('<i class="far fa-check-circle"></i>&nbsp;文档生成完毕');
	$("#toastTopBody").html('您的文档已开始下载。<br>若未出现下载提示，请确认浏览器是否拦截，以及在文档中是否包含视频内容');
	$(".w-e-text").wordExport(<?php echo npoJson($esN ? $npoDisplayName."(只读)" : $name); ?>);
	closeToastSTO = setTimeout(function(){$("#toastTop").attr("style","display:none");},5000);
}
$("#exportBtnWord").click(function(){
	clearTimeout(closeToastSTO);
	$("#toastTop").attr("style","display:block");
	$("#toastTopTitleText").html('<i class="fas fa-sync-alt savingIcon"></i>&nbsp;文档生成中...');
	$("#toastTopBody").html('<span class="text-danger font-weight-bold">在文档中请勿包含视频，否则将生成失败或发生遗漏</span><br>文档正在生成导出中，请稍后');
	setTimeout(exportWordDo,2000);
});
$("#toastTopClose").click(function(){
	$("#toastTop").attr("style","display:none");
});

/*全屏编辑：只切换 .docDiv 上的类，不依赖 Fullscreen API，
**这样 Esc 之外任何方式退出都不会留下被浏览器锁定的元素；分享页没有这个按钮*/
$(function(){
	var $doc = $('.docDiv');
	var $btn = $('#npoFullBtn');
	if (!$btn.length) return;
	function setFull(on){
		$doc.toggleClass('npo-fullscreen', on);
		$('body').css('overflow', on ? 'hidden' : '');
		$btn.attr('title', on ? '退出全屏（Esc）' : '全屏编辑（Esc 退出）');
		/*FontAwesome 的 JS 版会把 <i> 换成 <svg>，改 class 不会改图形，
		**所以整块重建图标，再让 FA 自己把 <i> 转成 svg（未加载时 CSS 版照样能显示）*/
		$btn.html('<i class="fas fa-' + (on ? 'compress' : 'expand') + '"></i>');
		if (window.FontAwesome && FontAwesome.dom && FontAwesome.dom.i2svg) {
			FontAwesome.dom.i2svg({ node: $btn[0] });
		}
	}
	$btn.click(function(){
		setFull(!$doc.hasClass('npo-fullscreen'));
	});
	$(document).on('keydown.npoFullscreen', function(e){
		if (e.key === 'Escape' && $doc.hasClass('npo-fullscreen')) setFull(false);
	});
});

<?php if ($es == 'e') { ?>
/*--------- 编辑扩展工具条 ---------
**字数统计 / 粘贴 Markdown 自动转换
**全部在 wangEditor 之外直接改 DOM；wangEditor 每 500ms 读一次正文判断变化，
**所以这些改动最长约 2 秒就会随自动保存落库，不需要手工调保存接口*/
$(function(){
	if (!$('#npoExtraBar').length) return;

	function textEl(){ return document.querySelector('#editorDiv .w-e-text'); }
	function sel(){
		var s = window.getSelection();
		return (s && s.rangeCount) ? s : null;
	}

	/*记住最后一次落在正文里的选区：粘贴回退路径要把内容插回光标处*/
	var lastRange = null;
	function saveRange(){
		var s = sel(), t = textEl();
		if (!s || !t) return;
		var r = s.getRangeAt(0);
		if (t.contains(r.commonAncestorContainer)) lastRange = r.cloneRange();
	}
	$(document).on('mouseup keyup', '.w-e-text', saveRange);
	function useRange(){
		var t = textEl(), s = sel(), r;
		if (s) {
			r = s.getRangeAt(0);
			if (t && t.contains(r.commonAncestorContainer)) return s;
		}
		if (!lastRange || !t) return s;
		t.focus();
		s = window.getSelection();
		s.removeAllRanges();
		s.addRange(lastRange);
		return s;
	}

	/*---------- 字数统计 ----------*/
	function updateWordCount(){
		var t = '';
		try { t = editor.txt.text() || ''; } catch (e) { t = textEl() ? textEl().textContent : ''; }
		/*按非空白字符计数，中文场景比按词更直观*/
		t = t.replace(/[\s\u200b\ufeff]/g, '');
		$('#npoWordCount').text('字数 ' + t.length);
	}
	/*正文由 ajax 载入，DOM 就绪时还是空的，只算一次会一直显示 0。
	**盯住 #editorDiv 的结构变化，笔记加载完成后再补算一次；连续变动合并成一次*/
	var wcTimer = null;
	function scheduleWordCount(){
		if (wcTimer) return;
		wcTimer = setTimeout(function(){ wcTimer = null; updateWordCount(); }, 50);
	}
	if (window.MutationObserver) {
		new MutationObserver(scheduleWordCount).observe(document.getElementById('editorDiv'), {
			childList: true, subtree: true, characterData: true
		});
	}
	$(document).on('input keyup compositionend', '.w-e-text', updateWordCount);
	updateWordCount();

	/*---------- 粘贴 Markdown 自动转 HTML ----------*/
	function escHtml(s){
		return String(s).replace(/[&<>"]/g, function(c){
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
		});
	}
	function mdInline(s){
		s = escHtml(s);
		/*行内代码先处理，免得里面的 * _ 被当成强调*/
		s = s.replace(/`([^`\n]+)`/g, '<code>$1</code>');
		s = s.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
		s = s.replace(/__([^_\n]+)__/g, '<strong>$1</strong>');
		s = s.replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');
		s = s.replace(/(^|[^_\w])_([^_\n]+)_/g, '$1<em>$2</em>');
		s = s.replace(/~~([^~\n]+)~~/g, '<del>$1</del>');
		s = s.replace(/\[([^\]\n]+)\]\((https?:\/\/[^)\s]+)\)/g,
			'<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
		return s;
	}
	function mdToHtml(text){
		var lines = String(text).replace(/\r\n?/g, '\n').split('\n');
		var out = [], i, line, m, list = null, inCode = false, buf = [];
		function closeList(){ if (list) { out.push('</' + list + '>'); list = null; } }
		function openList(t){ if (list !== t) { closeList(); out.push('<' + t + '>'); list = t; } }
		for (i = 0; i < lines.length; i++) {
			line = lines[i];
			if (/^\s*```/.test(line)) {
				if (inCode) {
					out.push('<pre><code>' + escHtml(buf.join('\n')) + '</code></pre>');
					buf = []; inCode = false;
				} else { closeList(); inCode = true; }
				continue;
			}
			if (inCode) { buf.push(line); continue; }
			if (/^\s*$/.test(line)) { closeList(); continue; }
			if ((m = /^\s*(#{1,6})\s+(.*)$/.exec(line))) {
				closeList();
				out.push('<h' + m[1].length + '>' + mdInline(m[2]) + '</h' + m[1].length + '>');
				continue;
			}
			if (/^\s*([-*_])\1{2,}\s*$/.test(line)) { closeList(); out.push('<hr>'); continue; }
			if ((m = /^\s*[-*+]\s+(.*)$/.exec(line))) {
				openList('ul'); out.push('<li>' + mdInline(m[1]) + '</li>'); continue;
			}
			if ((m = /^\s*\d+[.)]\s+(.*)$/.exec(line))) {
				openList('ol'); out.push('<li>' + mdInline(m[1]) + '</li>'); continue;
			}
			if ((m = /^\s*>\s?(.*)$/.exec(line))) {
				closeList(); out.push('<blockquote>' + mdInline(m[1]) + '</blockquote>'); continue;
			}
			closeList();
			out.push('<p>' + mdInline(line) + '</p>');
		}
		closeList();
		if (inCode) out.push('<pre><code>' + escHtml(buf.join('\n')) + '</code></pre>');
		return out.join('');
	}
	function looksLikeMd(s){
		if (/(^|\n)\s*(#{1,6}\s|```|[-*+]\s|\d+[.)]\s|>\s)/.test(s)) return true;
		if (/\*\*[^*\n]+\*\*/.test(s)) return true;
		if (/`[^`\n]+`/.test(s)) return true;
		if (/\[[^\]\n]+\]\(https?:/.test(s)) return true;
		if (/(^|\n)\s*([-*_])\2{2,}\s*($|\n)/.test(s)) return true;
		return false;
	}
	$(document).on('paste', '.w-e-text', function(e){
		var ev = e.originalEvent || e;
		var cd = ev.clipboardData || window.clipboardData;
		if (!cd) return;
		var plain = cd.getData('text/plain') || '';
		/*从网页或 Word 复制来的内容自带 text/html，交回 wangEditor 原本的逻辑，
		**否则纯文本里的列表符号会被当成 Markdown，把原有格式丢掉*/
		var rich = cd.getData('text/html') || '';
		/*不像 Markdown 或是带格式的富文本就交回 wangEditor 原本的粘贴逻辑*/
		if (!plain || rich || !looksLikeMd(plain)) return;
		ev.preventDefault();
		var html = mdToHtml(plain), ok = false;
		try { ok = document.execCommand('insertHTML', false, html); } catch (err) { ok = false; }
		if (!ok) {
			var s = useRange();
			if (s) {
				var r = s.getRangeAt(0);
				r.deleteContents();
				var box = document.createElement('div');
				box.innerHTML = html;
				var frag = document.createDocumentFragment();
				while (box.firstChild) frag.appendChild(box.firstChild);
				r.insertNode(frag);
			}
		}
		updateWordCount();
	});
});
<?php } ?>

//主题切换
/*用户在页面上手选的主题存在 localStorage，head 里那段脚本会在页面渲染前抢先应用，
**这里只负责点击切换、把选择写回去，以及同步菜单上的高亮*/
$(function(){
	var npoThemeLinks = <?php echo npoJson($npoThemeList); ?>;
	function npoThemeMark(key){
		$('.npoThemeItem').removeClass('active').filter('[data-npo-theme="' + key + '"]').addClass('active');
	}
	function npoThemeApply(key){
		if (!Object.prototype.hasOwnProperty.call(npoThemeLinks, key)) key = 'default';
		var link = document.getElementById('npoThemeLink');
		if (link) link.href = npoThemeLinks[key];
		document.documentElement.setAttribute('data-npo-theme', key);
		npoThemeMark(key);
	}
	/*head 里已经按 localStorage 切过一次，这里只把菜单高亮补上*/
	npoThemeMark(document.documentElement.getAttribute('data-npo-theme') || 'default');
	$(document).on('click', '.npoThemeItem', function(e){
		e.preventDefault();
		var key = $(this).attr('data-npo-theme');
		npoThemeApply(key);
		/*无痕模式下 localStorage 会抛异常，写不进去就只在本次浏览里生效*/
		try { window.localStorage.setItem('npoTheme', key); } catch (err) {}
	});
});

//组合键捕获初始化
$(function(){
 
	document.addEventListener('keydown', function(e){
		e = window.event || e;
		var keycode = e.keyCode || e.which;     
 
		// if(e.ctrlKey && keycode == 87){   //屏蔽Ctrl+w  
  //           e.preventDefault();
  //           window.event.returnValue = false;  
  //        }
  //        if(e.ctrlKey && keycode == 82){   //Ctrl + R 
  //           e.preventDefault(); 
  //           window.event.returnValue= false; 
  //        }
         if(e.ctrlKey && keycode== 83){ //Ctrl + S  
         	clearTimeout(closeToastSTO);
            e.preventDefault();
            window.event.returnValue= false;
			$("#toastTop").attr("style","display:block");
			$("#toastTopTitleText").html('小贴士');
			$("#toastTopBody").html('文档会自动保存');
			closeToastSTO = setTimeout(function(){$("#toastTop").attr("style","display:none");},2000);
         }
 
         // if(e.ctrlKey && keycode == 72){   //Ctrl + H 
         //    e.preventDefault();
         //    window.event.returnValue= false; 
         // }
         // if(e.ctrlKey && keycode == 74){   //Ctrl + J
         //    e.preventDefault(); 
         //    window.event.returnValue= false; 
         // }
         // if(e.ctrlKey && keycode == 75){   //Ctrl + K 
         //    e.preventDefault();
         //    window.event.returnValue= false; 
         // }
         // if(e.ctrlKey && keycode == 78){   //Ctrl + N
         //    e.preventDefault();
         //    window.event.returnValue= false; 
         // }        
	});
});
</script>
</body>
</html>