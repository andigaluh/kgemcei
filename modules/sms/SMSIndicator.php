<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once "SMSObjective.php";

class SMSIndicator {

	public $id;
	public $objectiveId;
	public $text;
	public $metric;
	public $targetValue;

	public static function loadAll($objectiveId = null) {
		return SMS::$instance->loadIndicators($objectiveId);
	}

	public static function load($id) {
		$indicator = SMS::$instance->loadIndicator($id);
		return $indicator;
	}

	public function __construct($id, $objectiveId, $text, $metric, $targetValue) {
		$this->id = $id;
		$this->objectiveId = $objectiveId;
		$this->text = $text;
		$this->metric = $metric;
		$this->targetValue = $targetValue;
	}

	public function save() {
		$id = SMS::$instance->saveIndicator($this);
		if ($id) $this->id = $id;
		return $id;
	}

	public static function getEditor($unitId, $request = array(), $editURL = "") {
		$output = "";
		$div = new Div();

		$id = $request["id"];
		$mode = $request["mode"];
		$objectiveId = $request["ObjectiveId"];
		if ($mode == "edit") {
			$id = $request["id"];
			$indicator = $id ? self::load($id) : new SMSIndicator(null, "", "", "", "");
			$text = $request["save"] ? $request["IndicatorText"] : $indicator->text;
			$targetValue = $request["save"] ? $request["IndicatorTargetValue"] : $indicator->targetValue;
			$metric = $request["save"] ? $request["IndicatorMetric"] : $indicator->metric;
			if ($request["save"]) {
				$errors = "";
				if (!$text) $errors .= t_("Description cannot be left blank<br>");
				if (!$targetValue) $errors .= t_("Target value cannot be left blank<br>");
				if (!$errors) {
					$indicator->objectiveId = $objectiveId;
					$indicator->text = $text;
					$indicator->targetValue = $targetValue;
					$indicator->metric = $metric;
					$ok = $indicator->save();
					if ($ok) return "";
					else $errors .= t_("Cannot save data, error occurred<br>");
				}
				if ($errors) {
					$output .= $errors;
				}
			}
			$formId = "SMSObjectiveForm";
			$form = new Form($formId, $editURL);
			$div->addChild($form);
			$form->addChild(new HiddenInput("", "ObjectiveId", $objectiveId));
			$form->addChild(new HiddenInput("", "id", $id));
			$form->addChild(new HiddenInput("", "mode", "edit"));
			$form->addChild(new HiddenInput("", "save", 1));
			$pair = new LabelPair();
			$form->addChild($pair);
			$pair->addChild(t_("Description"), new TextArea("IndicatorText", "IndicatorText", $text));
			$pair->addChilds(t_("Target"), array(new PlainText(t_("Metric: ")), new TextInput("IndicatorMetric", "IndicatorMetric", $metric, 20), new Hypertext("&nbsp;&nbsp;"), new PlainText("Value: "), new TextInput("IndicatorTargetValue", "IndicatorTargetValue", $targetValue ? $targetValue : "", 20)));
			$buttons = new Div("", "SMSEditorButtons");
			$form->addChild($buttons);
			$buttons->addChild(new JSButton("", t_("Save"), SMS::$instance->getIndicatorEditorSaveJS($formId)));
			$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
			$buttons->addChild(new JSButton("", t_("Cancel"), SMS::$instance->getIndicatorEditorCloseJS()));
			if ($indicator->id) {
				$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
				$buttons->addChild(new JSButton("", t_("Delete"), SMS::$instance->getIndicatorEditorDelJS($indicator->id), t_("Delete indicator?")));
			}
		}
		else if ($mode == "del") {
			SMS::$instance->delIndicator($id);
			return "";
		}
		else {
			$table = new DataTable("", "DataList");
			$div->addChild($table);
			$table->columns[] = new DataColumn(t_("Description"));
			$table->columns[] = new DataColumn(t_("Metric"));
			$table->columns[] = new DataColumn(t_("Target Value"), "SMSValue", "SMSValue");
			foreach (self::loadAll($objectiveId) as $indicator) {
				$table->rows[] = new DataRow(array($indicator->text, $indicator->metric, $indicator->targetValue), "", SMS::$instance->getIndicatorEditorOpenJS("edit", $indicator->id, $objectiveId));
			}
			$div->addChild(new LineBreak());
			$div->addChild(new JSButton("", t_("Add New KPI"), SMS::$instance->getIndicatorEditorOpenJS("edit", "", $objectiveId)));
		}

		$output .= $div->out();
		return $output;
	}

}

?>