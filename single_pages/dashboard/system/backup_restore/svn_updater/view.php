<?php defined('C5_EXECUTE') or die('Access denied.');

/* @var $this View */

$cdh = Loader::helper('concrete/dashboard');
/* @var $cdh ConcreteDashboardHelper */

$cih = Loader::helper('concrete/interface');
/* @var $cih ConcreteInterfaceHelper */

$jh = Loader::helper('json');
/* @var $jh JsonHelper */

echo $cdh->getDashboardPaneHeaderWrapper(t('SVN Updater'), false, 'span12', false); ?>

<div class="ccm-pane-body">
	<?php
	if(empty($svnFolders)) {
		?><div class="alert alert-warning"><?php echo t('No folder under version control has been found.'); ?></div><?php
	}
	else {
		?><table class="table" id="repos">
			<thead><tr>
				<th style="width: 280px"><?php echo t('Folder'); ?></th>
				<th><?php echo t('Status'); ?></th>
			</tr></thead>
			<tbody></tbody>
		</table><?php
	}
	?>
</div>

<?php if(!empty($svnFolders)) { ?>
	<div class="ccm-pane-footer">
		<?php echo $cih->button_js(t('Select all'), '$(\'.svn-repo-check\').prop(\'checked\', true)', 'left'); ?>
		<?php echo $cih->button_js(t('Select none'), '$(\'.svn-repo-check\').prop(\'checked\', false)', 'left'); ?>
		<?php echo $cih->button_js(t('Update'), 'svnUpdater.update()', 'right', 'danger'); ?>
		<?php echo $cih->button_js(t('Show status'), 'svnUpdater.showStatus()', 'right', 'info'); ?>
	</div>
<?php } ?>

<script>
(function() {

function setWorking(b) {
	if(b) {
		$('.ccm-pane-footer').find('button,input[type="button"],a').attr('disabled', true);
		$.fn.dialog.showLoader();
	}
	else {
		$('.ccm-pane-footer').find('button,input[type="button"],a').removeAttr('disabled');
		$.fn.dialog.hideLoader();
	}
}

function Repo(folder) {
	var me = this;
	me.folder = folder;
	$('#repos>tbody').append(me.$row = $('<tr />')
		.append($('<td />')
			.append($('<label class="checkbox" />')
				.text(' ' + this.folder)
				.prepend(me.$checkbox = $('<input type="checkbox" checked class="svn-repo-check" />'))
			)
		)
		.append(me.$status = $('<td />'))
	);
	Repo.all.push(me);
}
Repo.all = [];
Repo.getSelected = function() {
	var selected = [];
	$.each(Repo.all, function() {
		if(this.$checkbox.is(':checked')) {
			selected.push(this);
		}
	});
	return selected;
};
Repo.prototype = {
	updateStatus: function(cb) {
		var me = this;
		me.$status.html('<img src="<?php echo DIR_REL . '/' . DIRNAME_PACKAGES . '/svn_updater/images/loading.gif'; ?>" />');
		me.$status.css({'white-space': '', 'font-family': '', 'color': ''});
		style="white-space: pre; font-family: monospace"
		svnDo(me.folder, 'status', function(ok, r) {
			me.$status.empty();
			if(ok) {
				if(r.length) {
					me.$status.text(r).css({'white-space': 'pre', 'font-family': 'monospace'});
				}
				else {
					me.$status.text(<?php echo $jh->encode(tc('Status', 'Clean')); ?>).css('color', 'green');
				}
			}
			else {
				me.$status.text(r).css({'color': 'red', 'white-space': 'pre-line'});
			}
			cb();
		});
	},
	update: function(cb) {
		var me = this;
		me.$status.html('<img src="<?php echo DIR_REL . '/' . DIRNAME_PACKAGES . '/svn_updater/images/loading.gif'; ?>" />');
		me.$status.css({'white-space': '', 'font-family': '', 'color': ''});
		style="white-space: pre; font-family: monospace"
		svnDo(me.folder, 'update', function(ok, r) {
			me.$status.empty();
			if(ok) {
				if(r.length) {
					me.$status.text(r).css({'white-space': 'pre', 'font-family': 'monospace'});
				}
				else {
					me.$status.text(<?php echo $jh->encode(t('Done')); ?>).css('color', 'green');
				}
			}
			else {
				me.$status.text(r).css({'color': 'red', 'white-space': 'pre-line'});
			}
			cb();
		});
	}
};

function svnDo(folder, operation, cb) {
	$.ajax({
		cache: false,
		type: 'POST',
		url: <?php echo $jh->encode($this->action('do_svn')); ?>,
		data: {folder: folder, operation: operation},
		dataType: 'json'
	})
	.done(function(result) {
		if(result === null) {
			cb(false, <?php echo $jh->encode(t('No result from server')); ?>);
		}
		else {
			cb(true, result);
		}
	})
	.fail(function(xhr) {
		var msg = '?';
		try {
			if(!xhr.getResponseHeader('Content-Type').indexOf('text/plain')) {
				msg = xhr.responseText;
			}
			else {
				msg = xhr.status + ': ' + xhr.statusText;
			}
		}
		catch(e) {
		}
		cb(false, msg);
	});
}

window.svnUpdater = {
	showStatus: function() {
		var repos = Repo.getSelected();
		if(!repos.length) {
			alert(<?php echo $jh->encode(t('No folder selected')); ?>);
			return;
		}
		setWorking(true);
		function updateNext(i, cb) {
			if(i >= repos.length) {
				cb();
				return;
			}
			repos[i].updateStatus(function() {
				updateNext(i + 1, cb);
			});
		}
		updateNext(0, function() {
			setWorking(false);
		});
	},
	update: function() {
		var repos = Repo.getSelected();
		if(!repos.length) {
			alert(<?php echo $jh->encode(t('No folder selected')); ?>);
			return;
		}
		setWorking(true);
		function updateNext(i, cb) {
			if(i >= repos.length) {
				cb();
				return;
			}
			repos[i].update(function() {
				updateNext(i + 1, cb);
			});
		}
		updateNext(0, function() {
			setWorking(false);
		});
	}
};

$(document).ready(function() {
	<?php if(!empty($svnFolders)) { ?>
		$.each(<?php echo $jh->encode($svnFolders); ?>, function(_, folder) {
			new Repo(folder);
		});
	<?php } ?>
});
		
})();
</script>
<?php
echo $cdh->getDashboardPaneFooterWrapper(false);

