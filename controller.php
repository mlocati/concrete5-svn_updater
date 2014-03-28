<?php defined('C5_EXECUTE') or die('Access denied.');

/** The ProgePack package controller. */
class SvnUpdaterPackage extends Package {

	protected $pkgHandle = 'svn_updater';
	protected $appVersionRequired = '5.6.1';
	protected $pkgVersion = '0.9.0';

	public function getPackageName() {
		return t('SVN Updater');
	}

	public function getPackageDescription() {
		return t('Update the website and/or installed packages by svn update');
	}

	public function install() {
		$pkg = parent::install();
		$this->installReal('', $pkg);
	}

	public function upgrade() {
		$currentVersion = $this->getPackageVersion();
		parent::upgrade();
		$this->installReal($currentVersion, $this);
	}

	private function installReal($fromVersion, $pkg) {
		$fromScratch = strlen($fromVersion) ? false : true;
		if($fromScratch || version_compare($fromVersion, '0.9.0', '<')) {
			self::addSinglePage($pkg, '/dashboard/system/backup_restore/svn_updater', t('SVN Updater'), t('Update the website and/or installed packages by svn update'));
		}
	}
	private static function getSinglePage($path) {
		$c = Page::getByPath($path);
		if((!is_object($c)) || $c->isError()) {
			return null;
		}
		$sp = SinglePage::getByID($c->getCollectionID());
		if((!is_object($sp)) || $sp->isError()) {
			return null;
		}
		return $sp;
	}
	private static function addSinglePage($pkg, $path, $name, $description, $icon = '') {
		static $iconHandle;
		if(!(is_object($iconHandle) || ($iconHandle === false))) {
			$iconHandle = CollectionAttributeKey::getByHandle('icon_dashboard');
			if(!is_object($iconHandle)) {
				$iconHandle = false;
			}
		}
		if(!($sp = self::getSinglePage($path))) {
			$sp = SinglePage::add($path, $pkg);
		}
		$sp->update(array('cName' => $name, 'cDescription' => $description));
		if(strlen($icon) && $iconHandle) {
			$sp->setAttribute('icon_dashboard', "icon-$icon");
		}
		return $sp;
	}
}
