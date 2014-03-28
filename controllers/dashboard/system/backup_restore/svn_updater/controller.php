<?php defined('C5_EXECUTE') or die('Access denied.');

class DashboardSystemBackupRestoreSvnUpdaterController extends DashboardBaseController {
	public function view() {
		$this->set('svnFolders', $this->getSvnFolders());
	}
	public function do_svn($folder) {
		$ah = Loader::helper('ajax');
		/* @var $ah AjaxHelper */
		try {
			$operation = $this->post('operation');
			$lines = self::callSvn($this->post('folder'), $operation);
			$result = implode("\n", $lines);
			$ah->sendResult($result);
		}
		catch(Exception $x) {
			$ah->sendError($x);
		}
	}
	private static function getSvnFolders() {
		$folders = array();
		$folders = array_merge($folders, self::getFolders('', 0));
		$folders = array_merge($folders, self::getFolders('packages', 1));
		$folders = array_unique($folders);
		$svnFolders = array();
		foreach($folders as $folder) {
			if($folder == '.svn') {
				$svnFolders[] = '/';
			}
			elseif(substr($folder, -5) === '/.svn') {
				$svnFolders[] = '/' . substr($folder, 0, -4);
			}
		}
		return $svnFolders;
	}
	private static function getFolders($rel, $level) {
		$folders = array();
		$abs = realpath(DIR_BASE . "/$rel");
		if(($abs !== false) && is_dir($abs)) {
			foreach(scandir($abs) as $item) {
				switch($item) {
					case '.':
					case '..':
						break;
					default:
						if(is_dir("$abs/$item")) {
							$folders[] = strlen($rel) ? "$rel/$item" : $item;
						}
						break;
				}
			}
		}
		if($level > 0) {
			$subFolders = array();
			foreach($folders as $folder) {
				$subFolders = array_merge($subFolders, self::getFolders($folder, $level - 1));
			}
			$folders = array_merge($folders, $subFolders);
		}
		return $folders;
	}
	private static function callSvn($folder, $operation) {
		$absFolder = realpath(DIR_BASE . "/$folder");
		if(!is_dir($absFolder)) {
			throw new Exception(t('Invalid svn folder: %s', $folder));
		}
		if(!(is_string($folder) && in_array($folder, self::getSvnFolders()))) {
			throw new Exception(t('Invalid svn folder: %s', $folder));
		}
		$options = '';
		switch($operation) {
			case 'status':
				$options = '--non-interactive';
				break;
			case 'update':
				$options = '--non-interactive --trust-server-cert';
				if(defined('SVNUPDATER_USERNAME') && strlen(SVNUPDATER_USERNAME) && defined('SVNUPDATER_PASSWORD') && strlen(SVNUPDATER_PASSWORD)) {
					$options .= ' --no-auth-cache --username ' . escapeshellarg(SVNUPDATER_USERNAME) . ' --password ' . escapeshellarg(SVNUPDATER_PASSWORD);
				}
				break;
			default:
				throw new Exception(t('Invalid svn operation: %s', $operation));
		}
		$prevCurDir = getcwd();
		if(@chdir($absFolder) === false) {
			throw new Exception(t('Unable to enter folder %s', $folder));
		}
		try {
			$output = array();
			@exec("svn $operation $options 2>&1", $output, $rc);
			if(!@is_int($rc)) {
				$rc = -1;
			}
			if(!is_array($output)) {
				$output = array();
			}
			if($rc !== 0) {
				$result = trim(implode("\n", $output));
				if(!strlen($result)) {
					$err = t('Program failed with return code %s', $rc);
				}
				throw new Exception($result);
			}
			@chdir($prevCurDir);
			return $output;
		}
		catch(Exception $x) {
			@chdir($prevCurDir);
			throw $x;
		}
	}
}
