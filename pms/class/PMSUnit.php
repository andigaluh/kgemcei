<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

class PMSUnit {

	public static $currentId = 1;

	public $id;
	public $name;
	public $class_name;
	public $level;

	public static function loadAll() {
		return PMS::$instance->loadUnits();
	}

	public static function load($id) {
		return PMS::$instance->loadUnit($id);
	}

	public function __construct($id, $name, $class_name, $level) {
		$this->id = $id;
		$this->name = $name;
		$this->class_name = $class_name;
		$this->level = $level;
	}

	public static function getInfoPanel($unitId, $pair_ = null) {
		// create panel
		$pair = $pair_ ? $pair_ : new LabelPair();
		$pair->addChild(PMSUnit::load($unitId)->class_name, new PlainText(PMSUnit::load($unitId)->name));
		if (!$pair_) return $pair->out();
	}

}

?>