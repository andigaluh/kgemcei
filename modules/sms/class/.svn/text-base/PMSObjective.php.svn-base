<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once GAKRUWET_DOC_DIR."/Div.php";
require_once GAKRUWET_DOC_DIR."/DataTable.php";
require_once GAKRUWET_DOC_DIR."/Form.php";
require_once GAKRUWET_DOC_DIR."/LabelPair.php";
require_once GAKRUWET_DOC_DIR."/TextInput.php";
require_once GAKRUWET_DOC_DIR."/TextArea.php";
require_once GAKRUWET_DOC_DIR."/Button.php";
require_once GAKRUWET_DOC_DIR."/GlassBox.php";
require_once GAKRUWET_DOC_DIR."/DatePicker.php";
require_once GAKRUWET_DOC_DIR."/DateUtil.php";
require_once GAKRUWET_DOC_DIR."/DropDown.php";

require_once "PMSPerspective.php";
require_once "PMSUnit.php";
require_once "PMSPosition.php";

class PMSObjective {

	public $id;
	public $text;
	public $weight;
	public $begin;
	public $end;
	public $defaultPerspective;
	public $picId;

	public static function loadAll() {
		return PMS::$instance->loadObjectives();
	}

	public static function load($id) {
		$objective = PMS::$instance->loadObjective($id);
		$objective->defaultPerspective = PMS::$instance->loadObjectiveDefaultPerspective($objective->id);
		return $objective;
	}

	public function __construct($id, $text, $weight, $begin, $end, $picId) {
		$this->id = $id;
		$this->text = $text;
		$this->weight = $weight;
		$this->begin = $begin;
		$this->end = $end;
		$this->picId = $picId;
	}

	public function save() {
		$id = PMS::$instance->saveObjective($this);
		if ($id) {
			$ok = true;
			$this->id = $id;
			if ($ok) $ok = PMS::$instance->saveObjectivePerspective($id, $this->defaultPerspective["unit"], $this->defaultPerspective["perspective"], $this->defaultPerspective["no"], 1);
		}
		return $ok;
	}

	// create info panel
	public static function getInfoPanel($objective, $p, $unitId, $pair_ = null) {
		// create panel
		$pair = $pair_ ? $pair_ : new LabelPair();
		PMSUnit::getInfoPanel($unitId, $pair);
		$pair->addChilds(t_("Perspective"), array(new PlainText(PMSPerspective::loadById($p["perspective"])->name), new Hypertext("&nbsp;"), new PlainText($p["no"])));
		$pair->addChild(t_("Description"), new PlainText($objective->text));
		$pair->addChilds(t_("Weight"), array(new PlainText($objective->weight), new PlainText("%")));
		$pair->addChilds(t_("Timeframe"), array(new PlainText(t_("From: ")), new PlainText(DateUtil:: unixToUIFull($objective->begin)), new Hypertext("&nbsp;&nbsp;"), new PlainText(t_("To: ")), new PlainText(DateUtil:: unixToUIFull($objective->end))));
		$pair->addChild(t_("In Charge"), new PlainText(PMSPosition::load($objective->picId)->title));
		if (!$pair_) return $pair->out();
	}

	// create info panel
	public static function getSimpleInfoPanel($objectiveId, $unitId, $pair_ = null, $showUnit = true) {
		// create panel
		$pair = $pair_ ? $pair_ : new LabelPair();
		if ($showUnit) PMSUnit::getInfoPanel($unitId, $pair);
		$objective = PMSObjective::load($objectiveId);
		$p = PMS::$instance->loadObjectivePerspective($objective->id, $unitId);
		$pair->addChild(t_("Objective"), new PlainText(PMSPerspective::loadById($p["perspective"])->code.$p["no"]." - ".$objective->text));
		if (!$pair_) return $pair->out();
	}

	public static function getSelector($url, $unitId) {
		$output = "";
		$div = new Div();

		$div->addChild(new Hypertext(PMSUnit::getInfoPanel($unitId)));
		$div->addChild(new LineBreak());

		$div->addChild(new ContentDiv("", t_("Select Objective"), "PMSSubtitle"));
		$table = new DataTable("", "DataList");
		$div->addChild($table);
		$table->columns[] = new DataColumn(t_("Perspective"));
		$table->columns[] = new DataColumn(t_("Description"));
		$table->columns[] = new DataColumn(t_("Weight"));
		$table->columns[] = new DataColumn(t_("Timeframe"));
		foreach (self::loadAll() as $objective) {
			$p = PMS::$instance->loadObjectivePerspective($objective->id, $unitId);
			$table->rows[] = new DataRow(array($p["perspective"] ? PMSPerspective::loadById($p["perspective"])->code.$p["no"] : t_("(n/a)"), $objective->text, $objective->weight, t_("From: ").DateUtil::unixToUIFull($objective->begin)."&nbsp;&nbsp;".t_("To: ").DateUtil::unixToUIFull($objective->end)), "", "document.location='".$url."&obj_id={$objective->id}';");
		}

		$output .= $div->out();
		return $output;
	}

	public static function getEditor($unitId, $request, $editURL = "") {
		$output = "";
		$div = new Div();

		$mode = $request["mode"];
		$id = $request["id"];
		$objective = $id ? self::load($id) : new PMSObjective(null, "", "");
		$p = PMS::$instance->loadObjectivePerspective($objective->id, $unitId);
		$assignOnly = $id && ($objective->defaultPerspective["unit"] != $unitId);

		// edit objective
		if ($mode == "edit") {
			$perspective = $request["save"] ? $request["ObjectivePerspective"] : $p["perspective"];
			$no = $request["save"] ? $request["ObjectiveNo"] : $p["no"];
			$text = $request["save"] ? $request["ObjectiveText"] : $objective->text;
			$weight = $request["save"] ? $request["ObjectiveWeight"] * 1 : $objective->weight;
			$begin = $request["save"] ? DatePicker::toUnix($request["ObjectiveBegin"]) : $objective->begin;
			$end = $request["save"] ? DatePicker::toUnix($request["ObjectiveEnd"]) : $objective->end;
			$picId = $request["save"] ? $request["ObjectivePIC"] : $objective->picId;
			// save objective
			if ($request["save"]) {
				$errors = "";
				if (!$perspective) $errors .= t_("Perspective cannot be left blank<br>");
				if (!$no) $errors .= t_("No. cannot be left blank<br>");
				if (!$assignOnly) {
					if (!$text) $errors .= t_("Description cannot be left blank<br>");
					if ($weight <= 0) $errors .= t_("Weight cannot be left blank, zero or negative<br>");
					if (!$begin) $errors .= t_("Timeframe start cannot be left blank<br>");
					if (!$end) $errors .= t_("Timeframe end cannot be left blank<br>");
				}
				if (!$errors) {
					if ($assignOnly) {
						$ok = PMS::$instance->delObjectivePerspective($id, $unitId);
						if ($ok) $ok = PMS::$instance->saveObjectivePerspective($id, $unitId, $perspective, $no, 0);
					}
					else {
						$objective->text = $text;
						$objective->weight = $weight;
						$objective->begin = $begin;
						$objective->end = $end;
						$objective->picId = $picId;
						$objective->defaultPerspective = array("unit" => $unitId, "perspective" => $perspective, "no" => $no);
						$ok = $objective->save();
					}
					if ($ok) return "";
					else $errors .= t_("Cannot save data, error occurred<br>");
				}
				if ($errors) {
					$output .= $errors;
				}
			}
			// editor
			$formId = "PMSObjectiveForm";
			$form = new Form($formId, $editURL);
			$div->addChild($form);
			$form->addChild(new HiddenInput("", "id", $id));
			$form->addChild(new HiddenInput("", "mode", "edit"));
			$form->addChild(new HiddenInput("", "save", 1));
			$pair = new LabelPair();
			$form->addChild($pair);
			$ps = array("" => "");
			foreach (PMSPerspective::loadAll() as $p) {
				$ps[$p->id] = $p->name;
			}
			$pair->addChilds(t_("Perspective"), array(new DropDown("ObjectivePerspective", "ObjectivePerspective", $ps, $perspective), new Hypertext("&nbsp;&nbsp;"), new PlainText("No: "), new TextInput("ObjectiveNo", "ObjectiveNo", $no ? $no : "", 10)));
			$pair->addChild(t_("Description"), $assignOnly ? new PlainText($text) : new TextArea("ObjectiveText", "ObjectiveText", $text));
			$weightMin = PMS::$instance->loadTotalObjectivePerspectiveWeight($objective->id);
			$weightUsed = PMS::$instance->loadTotalObjectiveWeight();
			$pair->addChilds(t_("Weight"), array($assignOnly ? new PlainText($weight) : new NumberInput("ObjectiveWeight", "ObjectiveWeight", $weight ? $weight : "", 10, $weightMin, 100 - $weightUsed), new PlainText("%")));
			$pair->addChilds(t_("Timeframe"), array(new PlainText("From: "), $assignOnly ? new PlainText(DateUtil:: unixToUIFull($begin)) : new DatePicker("ObjectiveBegin", "ObjectiveBegin", $begin), new Hypertext("&nbsp;&nbsp;"), new PlainText("To: "), $assignOnly ? new PlainText(DateUtil:: unixToUIFull($end)) : new DatePicker("ObjectiveEnd", "ObjectiveEnd", $end)));
			$cs = array("" => "");
			foreach (PMSPosition::loadAll(null, true) as $c) {
				$cs[$c->id] = $c->title;
			}
			$pair->addChild(t_("In Charge"), $assignOnly ? new PlainText($cs[$picId]) : new DropDown("ObjectivePIC", "ObjectivePIC", $cs, $picId));
			$buttons = new Div("", "PMSEditorButtons");
			$form->addChild($buttons);
			$buttons->addChild(new JSButton("", t_("Save"), PMS::$instance->getObjectiveEditorSaveJS($formId)));
			$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
			$buttons->addChild(new JSButton("", t_("Cancel"), PMS::$instance->getObjectiveEditorCloseJS()));
			if ($objective->id && !$assignOnly) {
				$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
				$buttons->addChild(new JSButton("", t_("Delete"), PMS::$instance->getObjectiveEditorDelJS($objective->id), t_("Delete objective?")));
			}
		}

		// delete objective
		else if ($mode == "del") {
			if (!$assignOnly) PMS::$instance->delObjective($id);
			return "";
		}
		// show objective detail
		else if ($mode == "detail") {
/* ----------------
			// edit source
			if ($_REQUEST["edit_src"]) {
				$src = PMS::$instance->loadObjectiveSource($objective->id, $_REQUEST["src_id"]);
				$sourceId = $src["source"] ? $src["source"] : $_REQUEST["SourceId"];
				$sourceWeight = $_REQUEST["save"] ? $_REQUEST["SourceWeight"] : $src["weight"];
				// save source
				if ($_REQUEST["save"]) {
					$errors = "";
					if (!$sourceId) $errors .= t_("Source objective cannot be left blank<br>");
					if ($sourceWeight <= 0) $errors .= t_("Weight cannot be left blank, zero or negative<br>");
					if (!$errors) {
						$ok = PMS::$instance->saveObjectiveSource($objective->id, $sourceId, $sourceWeight);
						if ($ok) redirect_($_SESSION["PMSObjectiveURL"]."&show=1&id={$objective->id}");
						else $errors .= t_("Cannot save data, error occurred<br>");
					}
					if ($errors) {
						$dlg = new GlassBoxDialog("PMSError", t_("Errors"), $errors);
						$output .= $dlg->out();
						$output .= Widget::script($dlg->appear());
					}
				}
				// source editor
				$div->addChild(new ContentDiv("", t_("Edit Source Objective"), "PMSSubtitle"));
				$form = new Form("", $_SESSION["PMSObjectiveURL"]."&show=1&id={$objective->id}");
				$div->addChild($form);
				$form->addChild(new HiddenInput("", "src_id", $src["source"]));
				$form->addChild(new HiddenInput("", "edit_src", 1));
				$form->addChild(new HiddenInput("", "save", 1));
				$pair = new LabelPair();
				$form->addChild($pair);
				$os = array("" => "");
				foreach (PMS::$instance->loadObjectivesByUnit($unitId) as $o) {
					$p = PMS::$instance->loadObjectivePerspective($o->id, $unitId);
					$os[$o->id] = PMSPerspective::loadById($p["perspective"])->code.$p["no"]." - ".$o->text;
				}
				unset($os[$objective->id]);
				foreach (PMS::$instance->loadObjectiveSources($objective->id) as $s) {
					if ($s["source"] != $sourceId) unset($os[$s["source"]]);
				}
				self::getSimpleInfoPanel($objective->id, $unitId, $pair);
				$pair->addChild(t_("Source"), $src["source"] ? new PlainText($os[$sourceId]) : new DropDown("SourceId", "SourceId", $os, $sourceId));
				$sourceWeightUsed = PMS::$instance->loadTotalObjectiveSourceWeight($objective->id);
				$pair->addChilds(t_("Weight"), array(new NumberInput("SourceWeight", "SourceWeight", $sourceWeight ? $sourceWeight : "", 10, 0, 100 - $sourceWeightUsed), new PlainText("%")));
				$form->addChild(new LineBreak());
				$form->addChild(new SubmitButton("", t_("Save")));
				$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
				$form->addChild(new JSButton("", t_("Cancel"), "document.location='".$_SESSION["PMSObjectiveURL"]."&show=1&id={$objective->id}';"));
				if ($src["source"]) {
					$form->addChild(new Hypertext("&nbsp;&nbsp;&nbsp;"));
					$form->addChild(new JSButton("", t_("Delete"), "document.location='".$_SESSION["PMSObjectiveURL"]."&show=1&id={$objective->id}&del_src=1&src_id={$sourceId}';", t_("Delete source objective?")));
				}
			}
			// del source
			else if ($_REQUEST["del_src"]) {
				PMS::$instance->delObjectiveSource($objective->id, $_REQUEST["src_id"]);
				redirect_($_SESSION["PMSObjectiveURL"]."&show=1&id={$objective->id}");
			}
------------ */
/*
			// edit indicator
			else if ($_REQUEST["edit_idc"]) {
				$div->addChild(new ContentDiv("", t_("Edit Indicator"), "PMSSubtitle"));
				$div->addChild(new Hypertext(PMSIndicator::getIndicatorEditForm($unitId, $_SESSION["PMSObjectiveURL"]."&show=1&id={$objective->id}", $objective->id)));
			}
			// del indicator
			else if ($_REQUEST["del_idc"]) {
				PMS::$instance->delIndicator($_REQUEST["idc_id"]);
				redirect_($_SESSION["PMSObjectiveURL"]."&show=1&id={$objective->id}");
			}
			// objective detail with sources
			else {
*/
			// info panel
			$div->addChild(new ContentDiv("", t_("Objective Detail"), "PMSSubtitle"));
			$div->addChild(new Hypertext(self::getInfoPanel($objective, $p, $unitId)));
			$div->addChild(new LineBreak());
			// edit objective button
			$div->addChild(new JSButton("", t_("Edit"), PMS::$instance->getObjectiveEditorOpenJS("edit", $id)));
			$div->addChild(new Hypertext("&nbsp;&nbsp;"));
			$div->addChild(new JSButton("", t_("Delete"), PMS::$instance->getObjectiveEditorDelJS($id), t_("Delete objective?")));
			$div->addChild(new Hypertext("&nbsp;&nbsp;"));
			$div->addChild(new JSButton("", t_("Return"), PMS::$instance->getObjectiveListOpenJS()));
			$div->addChild(new LineBreak());

			// indicators
			$div->addChild(new LineBreak());
			$div->addChild(new ContentDiv("", t_("KPIs"), "PMSSubtitle"));
			$div->addChild(new Hypertext(PMSIndicator::getEditor($unitId, array("ObjectiveId" => $objective->id))));

			// contributed units
			$div->addChild(new LineBreak());
			$div->addChild(new ContentDiv("", t_("Contribution"), "PMSSubtitle"));
			$div->addChild(new Hypertext(PMSObjectivePerspective::getEditor($unitId, array("ObjectiveId" => $objective->id))));
		}

		// objective list
		else {
			$table = new DataTable("", "DataList");
			$div->addChild($table);
			$table->columns[] = new DataColumn(t_("Perspective"));
			$table->columns[] = new DataColumn(t_("Description"));
			$table->columns[] = new DataColumn(t_("Weight"), "PMSWeight", "PMSWeight");
			$table->columns[] = new DataColumn(t_("Timeframe"));
			$table->columns[] = new DataColumn(t_("In Charge"));
			foreach (PMS::$instance->loadObjectivesByUnit($unitId) as $objective) {
				$p = PMS::$instance->loadObjectivePerspective($objective->id, $unitId);
				$table->rows[] = new DataRow(array(PMSPerspective::loadById($p["perspective"])->code.$p["no"], $objective->text, $objective->weight." %", DateUtil::unixToUIFull($objective->begin)." - ".DateUtil::unixToUIFull($objective->end), PMSPosition::load($objective->picId)->title), "", PMS::$instance->getObjectiveDetailOpenJS($objective->id));
			}
			$table->rows[] = new DataRow(array("<b>".t_("Total")."</b>", "", "<b>".PMS::$instance->loadTotalObjectiveWeight()." %"."</b>", ""), "PMSTotalRow");
			$div->addChild(new LineBreak());
			$div->addChild(new JSButton("", t_("Add New Objective"), PMS::$instance->getObjectiveEditorOpenJS("edit", "")));
		}

		$output .= $div->out();
		return $output;
	}

}

?>