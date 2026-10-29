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
		<button type="button" class="btn btn-light" id="npoHrBtn" title="插入水平分割线"><i class="fas fa-minus"></i></button>
		<select class="form-control npoExSel" id="npoFontSize" title="字号">
			<option value="">字号</option>
			<option value="12">12px</option>
			<option value="14">14px</option>
			<option value="16">16px</option>
			<option value="18">18px</option>
			<option value="20">20px</option>
			<option value="24">24px</option>
			<option value="28">28px</option>
			<option value="32">32px</option>
			<option value="36">36px</option>
		</select>
		<select class="form-control npoExSel" id="npoLineHeight" title="行距">
			<option value="">行距</option>
			<option value="1">1.0</option>
			<option value="1.2">1.2</option>
			<option value="1.5">1.5</option>
			<option value="1.8">1.8</option>
			<option value="2">2.0</option>
			<option value="2.5">2.5</option>
			<option value="3">3.0</option>
		</select>
		<div class="dropdown">
			<button type="button" class="btn btn-light dropdown-toggle" id="npoTableBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">表格</button>
			<div class="dropdown-menu" aria-labelledby="npoTableBtn">
				<button type="button" class="dropdown-item" data-npo-tbl="rowAbove">上方插入行</button>
				<button type="button" class="dropdown-item" data-npo-tbl="rowBelow">下方插入行</button>
				<button type="button" class="dropdown-item" data-npo-tbl="rowDel">删除当前行</button>
				<button type="button" class="dropdown-item" data-npo-tbl="colLeft">左侧插入列</button>
				<button type="button" class="dropdown-item" data-npo-tbl="colRight">右侧插入列</button>
				<button type="button" class="dropdown-item" data-npo-tbl="colDel">删除当前列</button>
				<div class="dropdown-divider"></div>
				<button type="button" class="dropdown-item" data-npo-tbl="cellMerge">合并选中的单元格</button>
				<button type="button" class="dropdown-item" data-npo-tbl="cellSplit">拆分合并过的单元格</button>
				<div class="dropdown-divider"></div>
				<button type="button" class="dropdown-item text-danger" data-npo-tbl="tableDel">删除整个表格</button>
			</div>
		</div>
		<select class="form-control npoExSel" id="npoImgWidth" title="图片宽度">
			<option value="">宽度</option>
			<option value="25%">25%</option>
			<option value="50%">50%</option>
			<option value="75%">75%</option>
			<option value="100%">100%</option>
			<option value="auto">原始大小</option>
		</select>
		<select class="form-control npoExSel" id="npoImgAlign" title="图片对齐">
			<option value="">对齐</option>
			<option value="left">左对齐</option>
			<option value="center">居中</option>
			<option value="right">右对齐</option>
		</select>
		<button type="button" class="btn btn-light" id="npoImgReset" title="清除图片的宽度与对齐">还原图片</button>
		<span id="npoWordCount">字数 0</span>
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