<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once "PMSObjective.php";
require_once "PMSUnit.php";

class PMSAction {

	public $id;
	public $objectiveId;
	public $unitId;
	public $text;
	public $weight;
	public $begin;
	public $end;

	public static function loadAll($objectiveId) {
		return PMS::$instance->loadActions($objectiveId);
	}

	public static function load($id) {
		$action = PMS::$instance->loadAction($id);
		return $action;
	}

	public function __construct($id, $objectiveId, $unitId, $text, $weight, $begin, $end) {
		$this->id = $id;
		$this->objectiveId = $objectiveId;
		$this->unitId = $unitId;
		$this->text = $text;
		$this->weight = $weight;
		$this->begin = $begin;
		$this->end = $end;
	}

	public function save() {
		$id = PMS::$instance->saveAction($this);
		if ($id) $this->id = $id;
		return $id;
	}

	public static function getSelector($url, $objectiveId, $unitId) {
		$output = "";
		$div = new Div();

		$div->addChild(new Hypertext(PMSObjective::getSimpleInfoPanel($objectiveId, $unitId)));
		$div->addChild(new LineBreak());

		$div->addChild(new ContentDiv("", t_("Select Action"), "PMSSubtitle"));
		$table = new DataTable("", "DataList");
		$div->addChild($table);
		$table->columns[] = new DataColumn(t_("Description"));
		$table->columns[] = new DataColumn(t_("Weight"));
		$table->columns[] = new DataColumn(t_("Timeframe"));
		$table->columns[] = new DataColumn(t_("Unit"));
		foreach (self::loadAll($objectiveId) as $action) {
			$table->rows[] = new DataRow(array($action->text, $action->weight, t_("From: ").DateUtil::unixToUIFull($action->begin)."&nbsp;&nbsp;".t_("To: ").DateUtil::unixToUIFull($action->end), PMSUnit::load($action->unitId)->name), "", "document.location='".$url."&obj_id={$objectiveId}&act_id={$action->id}';");
		}

		$output .= $div->out();
		return $output;
	}

	public static function getEditor($unitId) {
		$output = "";
		$div = new Div();

		// parse url to get base url
		preg_match_all("/(.*)\?/", $_SERVER["REQUEST_URI"], $out, PREG_SET_ORDER);
		$url = $out[0][1]."?";
		preg_match_all("/[\?,\&]*(XP\_\w*_menu=\w*|menuid=\w*|mpid=\w*)\&*/", $_SERVER["REQUEST_URI"], $out, PREG_SET_ORDER);
		foreach($out as $o) {
			$url .= $o[1]."&";
		}
		$_SESSION["PMSActionURL"] = $url;

		$objectiveId = $_REQUEST["obj_id"];
		if (!$objectiveId) {
			return PMSObjective::getSelector($_SESSION["PMSActionURL"], $unitId);
		}

		$id = $_REQUEST["id"];
		$action = $id ? self::load($id) : new PMSAction(null, $objectiveId, $unitId, "", "", "", "");

		if ($_REQUEST["edit"]) {
			$text = $_REQUEST["save"] ? $_REQUEST["ActionText"] : $action->text;
			$weight = $_REQUEST["save"] ? $_REQUEST["ActionWeight"] * 1 : $action->weight;
			$begin = $_REQUEST["save"] ? DatePicker::toUnix($_REQUEST["ActionBegin"]) : $action->begin;
			$end = $_REQUEST["save"] ? DatePicker::toUnix($_REQUEST["ActionEnd"]) : $action->end;
			if ($_REQUEST["save"]) {
				$errors = "";
				if (!$text) $errors .= t_("Description cannot be left blank<br>");
				if ($weight <= 0) $errors .= t_("Weight cannot be left blank, zero or negative<br>");
				if (!$begin) $errors .= t_("Timeframe start cannot be left blank<br>");
				if (!$end) $errors .= t_("Timeframe end cannot be left blank<br>");
				if (!$errors) {
					$action->text = $text;
					$action->weight = $weight;
					$action->begin = $begin;
					$action->end = $end;
					$ok = $action->save();
					if ($ok) redirect_($_SESSION["PMSActionURL"]."&obj_id={$objectiveId}");
					else $errors .= t_("Cannot save data, error occurred<br>");
				}
				if ($errors) {
					$dlg = new GlassBoxDialog("PMSError", t_("Errors"), $errors);
					$output .= $dlg->out();
					$output .= Widget::script($dlg->appear());
				}
			}
			$div->addChild(new ContentDiv("", t_("Edit Action"), "PMSSubtitle"));
			$form = new Form("", $_SESSION["PMSActionURL"]);
			$div->addChild($form);
			$form->addChild(new HiddenInput("", "obj_id", $objectiveId));
			$form->addChild(new HiddenInput("", "id", $id));
			$form->addChild(new HiddenInput("", "edit", 1));
			$form->addChild(new HiddenInput("", "save", 1));
			$pair = new LabelPair();
			$form->addChild($pair);
			PMSObjective::getSimpleInfoPanel($objectiveId, $unitId, $pair);
			$pair->addChild(t_("Description"), new TextArea("ActionText", "ActionText", $text));
			$pair->addChilds(t_("Weight"), array(new NumberInput("ActionWeight", "ActionWeight", $weight ? $weight : "", 10, 0, 100), new PlainText("%")));
			$pair->addChilds(t_("Timeframe"), array(new PlainText(t_("From: ")), new DatePicker("ActionBegin", "ActionBegin", $begin), new Hypertext("&nbsp;&nbsp;"), new PlainText(t_("To: ")), new DatePicker("ActionEnd", "ActionEnd", $end)));
			$form->addChild(new LineBreak());
			$form->addChild(new SubmitButton("", t_("Save")));
			$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
			$form->addChild(new JSButton("", t_("Cancel"), "document.location='".$_SESSION["PMSActionURL"]."&obj_id={$objectiveId}';"));
			if ($action->id) {
				$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
				$form->addChild(new JSButton("", t_("Delete"), "document.location='".$_SESSION["PMSActionURL"]."&obj_id={$objectiveId}&del=1&id={$action->id}';", t_("Delete action?")));
			}
		}

		else if ($_REQUEST["del"]) {
			PMS::$instance->delAction($id);
			redirect_($_SESSION["PMSActionURL"]);
		}

		else {
			$div->addChild(new Hypertext(PMSObjective::getSimpleInfoPanel($objectiveId, $unitId)));
			$div->addChild(new LineBreak());
			$table = new DataTable("", "DataList");
			$div->addChild($table);
			$table->columns[] = new DataColumn(t_("Description"));
			$table->columns[] = new DataColumn(t_("Weight"));
			$table->columns[] = new DataColumn(t_("Timeframe"));
			$table->columns[] = new DataColumn(t_("Unit"));
			foreach (self::loadAll($objectiveId) as $action) {
				$table->rows[] = new DataRow(array($action->text, $action->weight, t_("From: ").DateUtil::unixToUIFull($action->begin)."&nbsp;&nbsp;".t_("To: ").DateUtil::unixToUIFull($action->end), PMSUnit::load($action->unitId)->name), "", "document.location='".$_SESSION["PMSActionURL"]."&obj_id={$objectiveId}&edit=1&id={$action->id}';");
			}
			$div->addChild(new LineBreak());
			$div->addChild(new Hyperlink($_SESSION["PMSActionURL"]."&obj_id={$objectiveId}&edit=1", t_("Add new action")));
			$div->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
			$div->addChild(new Hyperlink($_SESSION["PMSActionURL"], t_("Select other objective")));
		}

		$output .= $div->out();
		return $output;
	}

}

?>