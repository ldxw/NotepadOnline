<?php ($visit) ? ("") : (header('location: /'));?>
<div class="container docDiv<?php if ($es == 'e') { echo ' has-fullbtn'; } ?>">
	<div id="toolDiv"></div>
	<?php if ($es == 'e') { ?>
	<div id="npoFullWrap" class="d-none d-lg-block">
		<button type="button" id="npoFullBtn" class="btn btn-light" title="全屏编辑（Esc 退出）"><i class="fas fa-expand"></i></button>
	</div>
	<?php } ?>
	<div id="toolNone" class="d-lg-none d-xl-none"><i class="fas fa-exclamation-triangle"></i>&nbsp;工具栏仅在大屏设备上可用</div>
	<?php if ($es == 'e') { ?>
	<div id="npoExtraBar" class="d-none d-lg-flex">
		<span id="npoWordCount">单词: 0, 字符: 0, 行数: 0</span>
	</div>
	<?php } ?>
	<div id="editorDiv"></div>
</div>
<footer class="npoFooter">
	<div class="npoFooterInner">
		<?php if ($footerText !== '') { ?>
		<span class="npoFooterText"><?php echo htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8'); ?></span>
		<?php } ?>
		<span class="npoFooterCopy">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></span>
	</div>
</footer>