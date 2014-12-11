<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once "SMSObjective.php";
require_once "SMSAction.php";
require_once "SMSIndicator.php";
require_once "SMSUnit.php";
require_once "SMSPerson.php";

class SMSProgress {

	public $id;
	public $actionId;
	public $indicatorId;
	public $personId;
	public $text;
	public $weight;
	public $targetBegin;
	public $targetEnd;
	public $targetValue;
	public $actualBegin;
	public $actualEnd;
	public $actualValue;

	public static function loadAll($personId) {
		return SMS::$instance->loadProgresses($personId);
	}

	public static function load($id) {
		return SMS::$instance->loadProgress($id);
	}

	public function __construct($id, $actionId, $indicatorId, $personId, $text, $weight, 
			$targetBegin, $targetEnd, $targetValue, $actualBegin = null, $actualEnd = null, $actualValue = null) {
		$this->id = $id;
		$this->actionId = $actionId;
		$this->indicatorId = $indicatorId;
		$this->personId = $personId;
		$this->text = $text;
		$this->weight = $weight;
		$this->targetBegin = $targetBegin;
		$this->targetEnd = $targetEnd;
		$this->targetValue = $targetValue;
		$this->actualBegin = $actualBegin;
		$this->actualEnd = $actualEnd;
		$this->actualValue = $actualValue;
	}

	public function saveTarget() {
		$id = SMS::$instance->saveProgressTarget($this);
		if ($id) $this->id = $id;
		return $id;
	}

	public function saveActual() {
		$id = SMS::$instance->saveProgressActual($this);
		if ($id) $this->id = $id;
		return $id;
	}

	public static function getEditor($unitId, $personId) {
		$output = "";
		$div = new Div();

		// parse url to get base url
		preg_match_all("/(.*)\?/", $_SERVER["REQUEST_URI"], $out, PREG_SET_ORDER);
		$url = $out[0][1]."?";
		preg_match_all("/[\?,\&]*(XP\_\w*_menu=\w*|menuid=\w*|mpid=\w*)\&*/", $_SERVER["REQUEST_URI"], $out, PREG_SET_ORDER);
		foreach($out as $o) {
			$url .= $o[1]."&";
		}
		$_SESSION["SMSProgressURL"] = $url;

		$id = $_REQUEST["id"];
		$progress = $id ? self::load($id) : new SMSProgress(null, "", "", $personId, "", "", "", "", "");
		$objectiveId = SMSAction::load($progress->actionId)->objectiveId;

		// edit objective
		if ($_REQUEST["edit"]) {
			if (!$objectiveId) $objectiveId = $_REQUEST["obj_id"];
			if (!$objectiveId) {
				return SMSObjective::getSelector($_SESSION["SMSProgressURL"]."&edit=1", $unitId);
			}
			$actionId = $_REQUEST["save"] ? $_REQUEST["ProgressAction"] : $progress->actionId;
			$indicatorId = $_REQUEST["save"] ? $_REQUEST["ProgressIndicator"] : $progress->indicatorId;
			$text = $_REQUEST["save"] ? $_REQUEST["ProgressText"] : $progress->text;
			$weight = $_REQUEST["save"] ? $_REQUEST["ProgressWeight"] * 1 : $progress->weight;
			$targetBegin = $_REQUEST["save"] ? DatePicker::toUnix($_REQUEST["ProgressTargetBegin"]) : $progress->targetBegin;
			$targetEnd = $_REQUEST["save"] ? DatePicker::toUnix($_REQUEST["ProgressTargetEnd"]) : $progress->targetEnd;
			$targetValue = $_REQUEST["save"] ? $_REQUEST["ProgressTargetValue"] * 1 : $progress->targetValue;
			if ($_REQUEST["save"]) {
				$errors = "";
				if (!$actionId) $errors .= t_("Action cannot be left blank<br>");
				if (!$indicatorId) $errors .= t_("Indicator cannot be left blank<br>");
				if (!$text) $errors .= t_("Description cannot be left blank<br>");
				if ($weight <= 0) $errors .= t_("Weight cannot be left blank, zero or negative<br>");
				if (!$targetBegin) $errors .= t_("Target timeframe start cannot be left blank<br>");
				if (!$targetEnd) $errors .= t_("Target timeframe end cannot be left blank<br>");
				if (!$targetValue) $errors .= t_("Target value cannot be left blank or zero<br>");
				if (!$errors) {
					$progress->actionId = $actionId;
					$progress->indicatorId = $indicatorId;
					$progress->text = $text;
					$progress->weight = $weight;
					$progress->targetBegin = $targetBegin;
					$progress->targetEnd = $targetEnd;
					$progress->targetValue = $targetValue;
					$ok = $progress->saveTarget();
					if ($ok) redirect_($_SESSION["SMSProgressURL"]);
					else $errors .= t_("Cannot save data, error occurred<br>");
				}
				if ($errors) {
					$dlg = new GlassBoxDialog("SMSError", t_("Errors"), $errors);
					$output .= $dlg->out();
					$output .= Widget::script($dlg->appear());
				}
			}
			$div->addChild(new ContentDiv("", t_("Edit Progress Target"), "SMSSubtitle"));
			$form = new Form("", $_SESSION["SMSProgressURL"]);
			$div->addChild($form);
			$form->addChild(new HiddenInput("", "id", $id));
			$form->addChild(new HiddenInput("", "obj_id", $objectiveId));
			$form->addChild(new HiddenInput("", "edit", 1));
			$form->addChild(new HiddenInput("", "save", 1));
			$pair = new LabelPair();
			$form->addChild($pair);
			SMSPerson::getInfoPanel($personId, $unitId, $pair);
			SMSObjective::getSimpleInfoPanel($objectiveId, $unitId, $pair, false);
			$as = array("" => "");
			foreach (SMSAction::loadAll($objectiveId) as $a) {
				$as[$a->id] = $a->text;
			}
			$pair->addChild("Action", new DropDown("ProgressAction", "ProgressAction", $as, $actionId));
			$is = array("" => "");
			foreach (SMSIndicator::loadAll($objectiveId) as $i) {
				$is[$i->id] = $i->text;
			}
			$pair->addChild("Indicator", new DropDown("ProgressIndicator", "ProgressIndicator", $is, $indicatorId));
			$pair->addChild("Description", new TextArea("ProgressText", "ProgressText", $text));
			$pair->addChilds("Weight", array(new NumberInput("ProgressWeight", "ProgressWeight", $weight ? $weight : "", 10, 0, 100), new PlainText("%")));
			$pair->addChilds("Target Timeframe", array(new PlainText("From: "), new DatePicker("ProgressTargetBegin", "ProgressTargetBegin", $targetBegin), new Hypertext("&nbsp;&nbsp;"), new PlainText("To: "), new DatePicker("ProgressTargetEnd", "ProgressTargetEnd", $targetEnd)));
			$pair->addChild("Target Value", new NumberInput("ProgressTargetValue", "ProgressTargetValue", $targetValue ? $targetValue : "", 10));
			$form->addChild(new LineBreak());
			$form->addChild(new SubmitButton("", t_("Save")));
			$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
			$form->addChild(new JSButton("", t_("Cancel"), "document.location='".$_SESSION["SMSProgressURL"]."';"));
			if ($progress->id) {
				$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
				$form->addChild(new JSButton("", t_("Delete"), "document.location='".$_SESSION["SMSProgressURL"]."&del=1&id={$progress->id}';", t_("Delete progress?")));
			}
		}

		// enter actual progress
		else if ($_REQUEST["actual"]) {
			$actualBegin = $_REQUEST["save"] ? DatePicker::toUnix($_REQUEST["ProgressActualBegin"]) : $progress->actualBegin;
			$actualEnd = $_REQUEST["save"] ? DatePicker::toUnix($_REQUEST["ProgressActualEnd"]) : $progress->actualEnd;
			$actualValue = $_REQUEST["save"] ? $_REQUEST["ProgressActualValue"] * 1 : $progress->actualValue;
			if ($_REQUEST["save"]) {
				$errors = "";
				if (!$actualBegin) $errors .= t_("Actual timeframe start cannot be left blank<br>");
				if (!$actualEnd) $errors .= t_("Actual timeframe end cannot be left blank<br>");
				if (!$actualValue) $errors .= t_("Actual value cannot be left blank or zero<br>");
				if (!$errors) {
					$progress->actualBegin = $actualBegin;
					$progress->actualEnd = $actualEnd;
					$progress->actualValue = $actualValue;
					$ok = $progress->saveActual();
					if ($ok) redirect_($_SESSION["SMSProgressURL"]);
					else $errors .= t_("Cannot save data, error occurred<br>");
				}
				if ($errors) {
					$dlg = new GlassBoxDialog("SMSError", t_("Errors"), $errors);
					$output .= $dlg->out();
					$output .= Widget::script($dlg->appear());
				}
			}
			$div->addChild(new ContentDiv("", t_("Edit Progress Actual"), "SMSSubtitle"));
			$form = new Form("", $_SESSION["SMSProgressURL"]);
			$div->addChild($form);
			$form->addChild(new HiddenInput("", "id", $id));
			$form->addChild(new HiddenInput("", "actual", 1));
			$form->addChild(new HiddenInput("", "save", 1));
			$pair = new LabelPair();
			$form->addChild($pair);
			SMSPerson::getInfoPanel($personId, $unitId, $pair);
			SMSObjective::getSimpleInfoPanel($objectiveId, $unitId, $pair, false);
			$pair->addChild(t_("Action"), new PlainText(SMSAction::load($progress->actionId)->text));
			$pair->addChild(t_("Indicator"), new PlainText(SMSIndicator::load($progress->indicatorId)->text));
			$pair->addChild(t_("Description"), new PlainText($progress->text));
			$pair->addChilds(t_("Weight"), array(new PlainText($progress->weight), new PlainText("%")));
			$pair->addChilds(t_("Target Timeframe"), array(new PlainText(t_("From: ")), new PlainText(DateUtil:: unixToUIFull($progress->targetBegin)), new Hypertext("&nbsp;&nbsp;"), new PlainText(t_("To: ")), new PlainText(DateUtil:: unixToUIFull($progress->targetEnd))));
			$pair->addChild(t_("Target Value"), new PlainText($progress->targetValue));
			$pair->addChilds(t_("Actual Timeframe"), array(new PlainText(t_("From: ")), new DatePicker("ProgressActualBegin", "ProgressActualBegin", $actualBegin), new Hypertext("&nbsp;&nbsp;"), new PlainText(t_("To: ")), new DatePicker("ProgressActualEnd", "ProgressActualEnd", $actualEnd)));
			$pair->addChild(t_("Actual Value"), new NumberInput("ProgressActualValue", "ProgressActualValue", $actualValue ? $actualValue : "", 10));
			$form->addChild(new LineBreak());
			$form->addChild(new SubmitButton("", t_("Save")));
			$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
			$form->addChild(new JSButton("", t_("Cancel"), "document.location='".$_SESSION["SMSProgressURL"]."';"));
			$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
			$form->addChild(new JSButton("", t_("Delete"), "document.location='".$_SESSION["SMSProgressURL"]."&actual=1&del=1&id={$progress->id}';", t_("Delete progress actual?")));
			$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
			$form->addChild(new JSButton("", t_("Edit Target"), "document.location='".$_SESSION["SMSProgressURL"]."&edit=1&id={$progress->id}';"));
		}

		else {
			$div->addChild(new Hypertext(SMSPerson::getInfoPanel($personId, $unitId)));
			$div->addChild(new LineBreak());

			$div->addChild(new ContentDiv("", t_("Progresses"), "SMSSubtitle"));
			$table = new DataTable("", "DataList");
			$div->addChild($table);
			$table->columns[] = new DataColumn(t_("Description"));
			$table->columns[] = new DataColumn(t_("Weight"));
			$table->columns[] = new DataColumn(t_("Target Timeframe"));
			$table->columns[] = new DataColumn(t_("Target Value"));
			$table->columns[] = new DataColumn(t_("Actual Value"));
			$table->columns[] = new DataColumn(t_("Status"));
			foreach (self::loadAll($personId) as $progress) {
				$table->rows[] = new DataRow(array($progress->text, $progress->weight, t_("From: ").DateUtil::unixToUIFull($progress->targetBegin)."&nbsp;&nbsp;".t_("To: ").DateUtil::unixToUIFull($progress->targetEnd), $progress->targetValue, $progress->actualValue, $progress->actualValue ? ($progress->actualEnd > $progress->targetEnd ? t_("Late") : t_("Done")) : (time() > $progress->targetEnd ? t_("Overdue") : t_("Undone"))), "", "document.location='".$_SESSION["SMSProgressURL"]."&actual=1&id={$progress->id}';");
			}
			$div->addChild(new LineBreak());
			$div->addChild(new Hyperlink($_SESSION["SMSProgressURL"]."&edit=1", t_("Add new progress")));
		}

		$output .= $div->out();
		return $output;
	}


}

?>