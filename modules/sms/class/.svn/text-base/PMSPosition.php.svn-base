<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

class PMSPosition {

	public $id;
	public $title;
	public $level;

	public static function loadAll($unitId = null, $titleSort = false) {
		return PMS::$instance->loadPositions($unitId, $titleSort);
	}

	public static function load($id) {
		return PMS::$instance->loadPosition($id);
	}

	public function __construct($id, $title, $level) {
		$this->id = $id;
		$this->title = $title;
		$this->level = $level;
	}

}

?>