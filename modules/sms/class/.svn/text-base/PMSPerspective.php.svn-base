<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once GAKRUWET_DOC_DIR."/Div.php";
require_once GAKRUWET_DOC_DIR."/DataTable.php";
require_once GAKRUWET_DOC_DIR."/Form.php";
require_once GAKRUWET_DOC_DIR."/LabelPair.php";
require_once GAKRUWET_DOC_DIR."/TextInput.php";
require_once GAKRUWET_DOC_DIR."/Button.php";
require_once GAKRUWET_DOC_DIR."/GlassBox.php";

class PMSPerspective {

	public $id;
	public $code;
	public $name;

	public static function loadAll() {
		return PMS::$instance->loadPerspectives();
	}

	public static function loadById($id) {
		return PMS::$instance->loadPerspectiveById($id);
	}

	public static function loadByCode($code) {
		return PMS::$instance->loadPerspectiveByCode($code);
	}

	public function __construct($id, $code, $name) {
		$this->id = $id;
		$this->code = $code;
		$this->name = $name;
	}

	public function save() {
		$id = PMS::$instance->savePerspective($this);
		if ($id) $this->id = $id;
		return $id;
	}

	public static function getEditor($request, $editURL = "") {
		$output = "";
		$div = new Div();

		$id = $request["id"];
		$mode = $request["mode"];
		if ($mode == "edit") {
			$perspective = $id ? self::loadById($id) : new PMSPerspective(null, "", "");
			$code = $request["save"] ? $request["PerspectiveCode"] : $perspective->code;
			$name = $request["save"] ? $request["PerspectiveName"] : $perspective->name;
			if ($request["save"]) {
				$errors = "";
				if (!$code) $errors .= t_("Code cannot be left blank<br>");
				if (!$name) $errors .= t_("Name cannot be left blank<br>");
				if (!$errors) {
					$perspective2 = self::loadByCode($code);
					if ($perspective2 && ($perspective2->id != $id)) {
						$errors .= t_("Perspective with same code already exists<br>");
					}
					else {
						$perspective->code = $code;
						$perspective->name = $name;
						if ($perspective->save()) return "";
						else $errors .= t_("Cannot save data, error occurred<br>");
					}
				}
				if ($errors) {
					$output .= $errors;
				}
			}
			$formId = "PMSPerspectiveForm";
			$form = new Form($formId, $editURL);
			$div->addChild($form);
			$form->addChild(new HiddenInput("", "id", $id));
			$form->addChild(new HiddenInput("", "mode", "edit"));
			$form->addChild(new HiddenInput("", "save", 1));
			$pair = new LabelPair();
			$form->addChild($pair);
			$pair->addChild(t_("Code"), new TextInput("PerspectiveCode", "PerspectiveCode", $code, 20));
			$pair->addChild(t_("Name"), new TextInput("PerspectiveName", "PerspectiveName", $name, 40));
			$buttons = new Div("", "PMSEditorButtons");
			$form->addChild($buttons);
			$buttons->addChild(new JSButton("", t_("Save"), PMS::$instance->getPerspectiveEditorSaveJS($formId)));
			$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
			$buttons->addChild(new JSButton("", t_("Cancel"), PMS::$instance->getPerspectiveEditorCloseJS()));
			if ($perspective->id) {
				$buttons->addChild(new Hypertext("&nbsp;&nbsp;"));
				$buttons->addChild(new JSButton("", t_("Delete"), PMS::$instance->getPerspectiveEditorDelJS($perspective->id), t_("Delete perspective?")));
			}
		}
		else if ($mode == "del") {
			PMS::$instance->delPerspective($id);
			return "";
		}
		else {
			$table = new DataTable("", "DataList");
			$div->addChild($table);
			$table->columns[] = new DataColumn(t_("Code"));
			$table->columns[] = new DataColumn(t_("Name"));
			foreach (self::loadAll() as $perspective) {
				$table->rows[] = new DataRow(array($perspective->code, $perspective->name), "", PMS::$instance->getPerspectiveEditorOpenJS("edit", $perspective->id));
			}
			$div->addChild(new LineBreak());
			$div->addChild(new JSButton("", t_("Add New Perspective"), PMS::$instance->getPerspectiveEditorOpenJS("edit", "")));
		}

		$output .= $div->out();
		return $output;
	}

}

//		// parse url to get base url
//		preg_match_all("/(.*)\?/", $_SERVER["REQUEST_URI"], $out, PREG_SET_ORDER);
//		$url = $out[0][1]."?";
//		preg_match_all("/[\?,\&]*(XP\_\w*_menu=\w*|menuid=\w*|mpid=\w*)\&*/", $_SERVER["REQUEST_URI"], $out, PREG_SET_ORDER);
//		foreach($out as $o) {
//			$url .= $o[1]."&";
//		}
//		$_SESSION["PMSPerspectiveURL"] = $url;



?>