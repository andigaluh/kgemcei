<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once "SMSUnit.php";

class SMSPerson {

	public static $currentId = 444;

	public $id;
	public $no;
	public $name;

	public static function load($id) {
		return SMS::$instance->loadPerson($id);
	}

	public function __construct($id, $no, $name) {
		$this->id = $id;
		$this->no = $no;
		$this->name = $name;
	}

	public static function getInfoPanel($personId, $unitId, $pair_ = null) {
		// create panel
		$pair = $pair_ ? $pair_ : new LabelPair();
		SMSUnit::getInfoPanel($unitId, $pair);
		$person = SMSPerson::load($personId);
		$pair->addChild(t_("Employee ID"), new PlainText($person->no));
		$pair->addChild(t_("Employee Name"), new PlainText($person->name));
		if (!$pair_) return $pair->out();
	}

}

?>