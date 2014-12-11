<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once "SMSObjective.php";

class SMSObjectivePerspective {

	public static function getEditor($unitId, $request = array(), $editURL = "") {
		$output = "";
		$div = new Div();

		$mode = $request["mode"];
		$id = $request["id"];
		$contribId = $request["ContribId"];
		$objective = SMSObjective::load($request["ObjectiveId"]);
		$p = SMS::$instance->loadObjectivePerspective($objective->id, $id);
		if ($mode == "edit") {
			$pd = SMS::$instance->loadObjectivePerspective($objective->id, $unitId);
			$weight = $request["save"] ? $request["ContribWeight"] : $p["weight"];
			// save source
			if ($request["save"]) {
				$errors = "";
				if (!$id && !$contribId) $errors .= t_("Contributed unit cannot be left blank<br>");
				if ($weight <= 0) $errors .= t_("Contributed weight cannot be left blank, zero or negative<br>");
				if (!$errors) {
					$ok = SMS::$instance->saveObjectivePerspective($objective->id, $id ? $id : $contribId, $p["perspective"] ? $p["perspective"] : $pd["perspective"], $p["no"] ? $p["no"] : $pd["no"], 0, $weight);
					if ($ok) return "";
					else $errors .= t_("Cannot save data, error occurred<br>");
				}
				if ($errors) {
					$output .= $errors;
				}
			}
			// editor
			$formId = "SMSObjectivePerspectiveForm";
			$form = new Form($formId, $editURL);
			$div->addChild($form);
			$form->addChild(new HiddenInput("", "ObjectiveId", $objective->id));
			$form->addChild(new HiddenInput("", "id", $id));
			$form->addChild(new HiddenInput("", "mode", "edit"));
			$form->addChild(new HiddenInput("", "save", 1));
			$pair = new LabelPair();
			$form->addChild($pair);
			$us = array("" => "");
			$class_name = t_("Contributed Unit");
			foreach (SMS::$instance->loadSubUnits($unitId) as $u) {
				$us[$u->id] = $u->name;
				$class_name = $u->class_name;
			}
			$pair->addChild($class_name, $id ? new PlainText($us[$id]) : new DropDown("ContribId", "ContribId", $us, $contribId));
			$weightUsed = SMS::$instance->loadTotalObjectivePerspectiveWeight($objective->id);
			$pair->addChilds(t_("Weight"), array(new NumberInput("ContribWeight", "ContribWeight", $weight ? $weight : "", 10, 0, $objective->weight - $weightUsed), new PlainText("%")));
			$buttons = new Div("", "SMSEditorButtons");
			$form->addChild($buttons);
			$buttons->addChild(new JSButton("", t_("Save"), SMS::$instance->getObjectivePerspectiveEditorSaveJS($formId)));
			$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
			$buttons->addChild(new JSButton("", t_("Cancel"), SMS::$instance->getObjectivePerspectiveEditorCloseJS()));
			if ($id) {
				$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
				$buttons->addChild(new JSButton("", t_("Delete"), SMS::$instance->getObjectivePerspectiveEditorDelJS($id, $objective->id), t_("Delete contribution?")));
			}
		}
		else if ($mode == "del") {
			SMS::$instance->delObjectivePerspective($objective->id, $p["unit"]);
			return "";
		}
		else {
			$table = new DataTable("", "DataList");
			$div->addChild($table);
			$units = SMS::$instance->loadSubUnits($unitId);
			$table->columns[] = new DataColumn($units[0] ? $units[0]->class_name : t_("Unit"));
			$table->columns[] = new DataColumn(t_("Weight"), "SMSWeight", "SMSWeight");
			foreach (SMS::$instance->loadObjectiveOtherPerspectives($objective->id) as $p) {
				$table->rows[] = new DataRow(array(SMSUnit::load($p["unit"])->name, $p["weight"]." %"), "", SMS::$instance->getObjectivePerspectiveEditorOpenJS("edit", $p["unit"], $objective->id));
			}
			$table->rows[] = new DataRow(array("<b>".t_("Total")."</b>", "<b>".SMS::$instance->loadTotalObjectivePerspectiveWeight($objective->id)." %"."</b>"), "SMSTotalRow");
			if (SMS::$instance->loadTotalObjectivePerspectiveWeight($objective->id) < $objective->weight) {
				$div->addChild(new LineBreak());
				$div->addChild(new JSButton("", t_("Add New Contribution"), SMS::$instance->getObjectivePerspectiveEditorOpenJS("edit", '', $objective->id)));
			}
		}

		$output .= $div->out();
		return $output;
	}

}

?>