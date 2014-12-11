<?php

include_once(XOCP_DOC_ROOT."/gakruwetxocp.php");

include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");


define("PMS_DOC_DIR", XOCP_DOC_ROOT."/modules/pms/class");
define("PMS_URL_DIR", XOCP_SERVER_SUBDIR."/modules/pms/class");

include_once(PMS_DOC_DIR."/PMS.php");
include_once(XOCP_DOC_ROOT."/modules/pms/ajax/ajax_pms.php");

require_once GAKRUWET_DOC_DIR."/DateUtil.php";

class PMSXOCP extends PMS {

	public $db;

	function __construct() {
		$this->db = &Database::getInstance();
	}

	public static function escapeString($s) {
		return addslashes($s);
	}

	public static function initEditorDialog($type, $title, $afterSave, $afterDel = "") {
		if (!$afterDel) $afterDel = $afterSave; 
		$output = "";
		$editor = new GlassBoxDialog("PMS{$type}Editor", $title, "", "", false, false);
		$output .= $editor->out();
		$output .= Widget::script("

			function pms{$type}EditorLoad(data) {
				".$editor->init()."
				var cnt = document.getElementById('PMS{$type}EditorContent');
				cnt.innerHTML = data;
				".$editor->appear()."
			}

			function pms{$type}EditorOpen(mode, id, extra) {
				data = 'mode^^' + mode + '@@id^^' + id + '@@' + extra;
				pms_app_pmsAjax('{$type}', data,
					function(_data) {
						pms{$type}EditorLoad(_data);
					}
				);
			}

			function pms{$type}EditorClose() {
				".$editor->fade()."
			}

			var PMS{$type}EditorSaveTmp;

			function pms{$type}EditorSave(formId) {
				data = _parseForm(formId);
				pms{$type}EditorClose();
				pms_app_pmsAjax('{$type}', data,
					function(_data) {
						if (_data) {
							PMS{$type}EditorSaveTmp = _data;
							setTimeout('pms{$type}EditorLoad(PMS{$type}EditorSaveTmp)', 1000);
						}
						else {
							{$afterSave}
						}
					}
				);			
			}

			function pms{$type}EditorDel(id, extra) {
				data = 'mode^^del@@id^^' + id + '@@' + extra;
				pms{$type}EditorClose();
				pms_app_pmsAjax('{$type}', data,
					function(_data) {
						{$afterDel}
					}
				);
			}

		");
		return $output;
	}

	public static function initEditor($type) {
		$output = "";
		$ajax = new _pms_class_Ajax("pms");
		$output .= $ajax->getJs();
		$list = new Div("PMS{$type}Div");
		$output .= $list->out();
		$output .= Widget::script("

			function pms{$type}ListOpen() {
				PMS{$type}DetailId = '';
				pms_app_pmsAjax('{$type}', '',
					function(_data) {
						var div = document.getElementById('PMS{$type}Div');
						div.innerHTML = _data;
					}
				);
			}

			function pms{$type}DetailOpen(id) {
				if (id) PMS{$type}DetailId = id;
				if (PMS{$type}DetailId) {
					pms_app_pmsAjax('{$type}', 'mode^^detail@@id^^' + PMS{$type}DetailId,
						function(_data) {
							var div = document.getElementById('PMS{$type}Div');
							div.innerHTML = _data;
						}
					);
				}
				else {
					pms{$type}ListOpen();
				}
			}

		");
		return $output;
	}

	public function getPerspectiveListOpenJS() {
		return "pmsPerspectiveListOpen();";
	}

	public function getPerspectiveEditorOpenJS($mode, $id) {
		return "pmsPerspectiveEditorOpen('{$mode}', '{$id}', '');";
	}

	public function getPerspectiveEditorSaveJS($formId) {
		return "pmsPerspectiveEditorSave('{$formId}');";
	}

	public function getPerspectiveEditorDelJS($id) {
		return "pmsPerspectiveEditorDel('{$id}');";
	}

	public function getPerspectiveEditorCloseJS() {
		return "pmsPerspectiveEditorClose();";
	}

	public function getObjectiveListOpenJS() {
		return "pmsObjectiveListOpen();";
	}

	public function getObjectiveDetailOpenJS($id) {
		return "pmsObjectiveDetailOpen('$id');";
	}

	public function getObjectiveEditorOpenJS($mode, $id) {
		return "pmsObjectiveEditorOpen('{$mode}', '{$id}');";
	}

	public function getObjectiveEditorSaveJS($formId) {
		return "pmsObjectiveEditorSave('{$formId}');";
	}

	public function getObjectiveEditorDelJS($id) {
		return "pmsObjectiveEditorDel('{$id}');";
	}

	public function getObjectiveEditorCloseJS() {
		return "pmsObjectiveEditorClose();";
	}

	public function getIndicatorEditorOpenJS($mode, $id, $objectiveId) {
		return "pmsIndicatorEditorOpen('{$mode}', '{$id}', 'ObjectiveId^^".rawurlencode($objectiveId)."');";
	}

	public function getIndicatorEditorSaveJS($formId) {
		return "pmsIndicatorEditorSave('{$formId}');";
	}

	public function getIndicatorEditorDelJS($id) {
		return "pmsIndicatorEditorDel('{$id}');";
	}

	public function getIndicatorEditorCloseJS() {
		return "pmsIndicatorEditorClose();";
	}

	public function getObjectivePerspectiveEditorOpenJS($mode, $id, $objectiveId) {
		return "pmsObjectivePerspectiveEditorOpen('{$mode}', '{$id}', 'ObjectiveId^^".rawurlencode($objectiveId)."');";
	}

	public function getObjectivePerspectiveEditorSaveJS($formId) {
		return "pmsObjectivePerspectiveEditorSave('{$formId}');";
	}

	public function getObjectivePerspectiveEditorDelJS($id, $objectiveId) {
		return "pmsObjectivePerspectiveEditorDel('{$id}', 'ObjectiveId^^".rawurlencode($objectiveId)."');";
	}

	public function getObjectivePerspectiveEditorCloseJS() {
		return "pmsObjectivePerspectiveEditorClose();";
	}

	public function loadUnits() {
		$result = array();
		$q = $this->db->query("select a.org_id, a.org_nm, b.org_class_id, b.org_class_nm, b.order_no from hris_orgs a"
		   . " left join hris_org_class b USING(org_class_id)"
		   . " order by b.org_class_id, a.org_nm");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSUnit($data['org_id'], $data['org_nm'], $data['org_class_nm'], $data['order_no']);
		}
		return $result;
	}

	public function loadUnit($unitId) {
		$q = $this->db->query("select a.org_id, a.org_nm, b.org_class_nm, b.order_no from hris_orgs a"
		   . " left join hris_org_class b USING(org_class_id)"
		   . " where a.org_id = '".self::escapeString($unitId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSUnit($data['org_id'], $data['org_nm'], $data['org_class_nm'], $data['order_no']);
		}
		return null;
	}

	public function loadSubUnits($unitId) {
		$result = array();
		$q = $this->db->query("select a.org_id, a.org_nm, b.org_class_id, b.org_class_nm, b.order_no from hris_orgs a"
		   . " left join hris_org_class b USING(org_class_id)"
		   . " where a.parent_id = '".self::escapeString($unitId)."'"
		   . " order by b.org_class_id, a.org_nm");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSUnit($data['org_id'], $data['org_nm'], $data['org_class_nm'], $data['order_no']);
		}
		return $result;
	}

	public function loadPositions($unitId = null, $titleSort = false) {
		$result = array();
		$q = $this->db->query("select * from hris_jobs j join hris_job_class c on c.job_class_id = j.job_class_id ".($unitId ? "where j.org_id = '".self::escapeString($unitId)."'" : "")." order by ".($titleSort ? "" : "c.job_class_level, ")."j.job_nm");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSPosition($data['job_id'], $data['job_nm'], $data['job_class_level']);
		}
		return $result;
	}

	public function loadPosition($positionId) {
		$q = $this->db->query("select * from hris_jobs j join hris_job_class c on c.job_class_id = j.job_class_id where j.job_id = '".self::escapeString($positionId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSPosition($data['job_id'], $data['job_nm'], $data['job_class_level']);
		}
		return null;
	}

	public function loadPerson($personId) {
		$q = $this->db->query("select * from hris_employee e join hris_persons p on p.person_id = e.person_id where p.person_id = '".self::escapeString($personId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSPerson($data['person_id'], $data['employee_ext_id'], $data['person_nm']);
		}
		return null;
	}

	public function loadPerspectives() {
		$result = array();
		$q = $this->db->query("select * from pms_perspective order by pms_perspective_id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSPerspective($data['pms_perspective_id'], $data['pms_perspective_code'], $data['pms_perspective_name']);
		}
		return $result;
	}

	public function loadPerspectiveById($perspectiveId) {
		$q = $this->db->query("select * from pms_perspective 
									where pms_perspective_id = '".self::escapeString($perspectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSPerspective($data['pms_perspective_id'], $data['pms_perspective_code'], $data['pms_perspective_name']);
		}
		return null;
	}

	public function loadPerspectiveByCode($perspectiveCode) {
		$q = $this->db->query("select * from pms_perspective 
									where pms_perspective_code = '".self::escapeString($perspectiveCode)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSPerspective($data['pms_perspective_id'], $data['pms_perspective_code'], $data['pms_perspective_name']);
		}
		return null;
	}

	public function delPerspective($perspectiveId) {
		return $this->db->query("delete from pms_perspective where pms_perspective_id = '".self::escapeString($perspectiveId)."'");
	}

	public function savePerspective(PMSPerspective $perspective) {
		$id = false;
		if ($perspective->id) {
			$ok = $this->db->query("update pms_perspective set 
										pms_perspective_code = '".self::escapeString($perspective->code)."', 
										pms_perspective_name = '".self::escapeString($perspective->name)."'
									where pms_perspective_id = '".self::escapeString($perspective->id)."'");
			if ($ok) $id = $perspective->id;
		}
		else {
			$ok = $this->db->query("insert into pms_perspective (pms_perspective_code, pms_perspective_name)
									values ('".self::escapeString($perspective->code)."',
									 		'".self::escapeString($perspective->name)."')");
			if ($ok) $id = $this->db->getInsertId();
		}
		return $id;
	}

	public function loadObjectives() {
		$result = array();
		$q = $this->db->query("select * from pms_objective order by pms_objective_begin, pms_objective_end");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSObjective($data['pms_objective_id'], $data['pms_objective_text'], $data['pms_objective_weight'], DateUtil::sqlToUnix($data['pms_objective_begin']), DateUtil::sqlToUnix($data['pms_objective_end']), $data['pms_pic_position_id']);
		}
		return $result;
	}

	public function loadTotalObjectiveWeight() {
		$q = $this->db->query("select sum(pms_objective_weight) as pms_objective_weight from pms_objective");
		if ($data = $this->db->fetchArray($q)) {
			return $data['pms_objective_weight'] * 1;
		}
		return 0;
	}

	public function loadObjectivesByUnit($unitId) {
		$result = array();
		$q = $this->db->query("select o.* from pms_objective o
								join pms_objective_perspective p on p.pms_objective_id = o.pms_objective_id
								where p.pms_unit_id = '".self::escapeString($unitId)."'
								order by o.pms_objective_begin, o.pms_objective_end");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSObjective($data['pms_objective_id'], $data['pms_objective_text'], $data['pms_objective_weight'], DateUtil::sqlToUnix($data['pms_objective_begin']), DateUtil::sqlToUnix($data['pms_objective_end']), $data['pms_pic_position_id']);
		}
		return $result;
	}

	public function loadObjective($objectiveId) {
		$q = $this->db->query("select * from pms_objective where pms_objective_id = '".self::escapeString($objectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSObjective($data['pms_objective_id'], $data['pms_objective_text'], $data['pms_objective_weight'], DateUtil::sqlToUnix($data['pms_objective_begin']), DateUtil::sqlToUnix($data['pms_objective_end']), $data['pms_pic_position_id']);
		}
		return null;
	}

	public function delObjective($objectiveId) {
		$ok = $this->db->query("delete from pms_objective_perspective where pms_objective_id = '".self::escapeString($objectiveId)."'");
		if ($ok) $ok = $this->db->query("delete from pms_objective where pms_objective_id = '".self::escapeString($objectiveId)."'");
		return $ok;
	}

	public function saveObjective(PMSObjective $objective) {
		$id = false;
		if ($objective->id) {
			$ok = $this->db->query("update pms_objective set 
										pms_objective_text = '".self::escapeString($objective->text)."', 
										pms_objective_weight = '".self::escapeString($objective->weight)."', 
										pms_objective_begin = '".DateUtil::unixToSQL($objective->begin)."', 
										pms_objective_end = '".DateUtil::unixToSQL($objective->end)."', 
										pms_pic_position_id = '".self::escapeString($objective->picId)."'
									where pms_objective_id = '".self::escapeString($objective->id)."'");
			if ($ok) $id = $objective->id;
		}
		else {
			$ok = $this->db->query("insert into pms_objective (pms_objective_text, pms_objective_weight, 
										pms_objective_begin, pms_objective_end, pms_pic_position_id)
									values ('".self::escapeString($objective->text)."',
									 		'".self::escapeString($objective->weight)."',
									 		'".DateUtil::unixToSQL($objective->begin)."',
									 		'".DateUtil::unixToSQL($objective->end)."',
											'".self::escapeString($objective->picId)."')");
			if ($ok) $id = $this->db->getInsertId();
		}
		return $id;
	}

	public function loadObjectiveDefaultPerspective($objectiveId) {
		$q = $this->db->query("select * from pms_objective_perspective where pms_objective_id = '".self::escapeString($objectiveId)."' and is_default = 1");
		if ($data = $this->db->fetchArray($q)) {
			return array("unit" => $data['pms_unit_id'], "perspective" => $data['pms_perspective_id'], "no" => $data['objective_perspective_no'], "weight" => $data['unit_weight']);
		}
		return null;
	}

	public function loadObjectiveOtherPerspectives($objectiveId) {
		$result = array();
		$q = $this->db->query("select * from pms_objective_perspective where pms_objective_id = '".self::escapeString($objectiveId)."' and is_default = 0");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = array("unit" => $data['pms_unit_id'], "perspective" => $data['pms_perspective_id'], "no" => $data['objective_perspective_no'], "weight" => $data['unit_weight']);
		}
		return $result;
	}

	public function loadObjectivePerspective($objectiveId, $unitId) {
		$q = $this->db->query("select * from pms_objective_perspective where pms_objective_id = '".self::escapeString($objectiveId)."' and pms_unit_id = '".self::escapeString($unitId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return array("unit" => $data['pms_unit_id'], "perspective" => $data['pms_perspective_id'], "no" => $data['objective_perspective_no'], "weight" => $data['unit_weight']);
		}
		return null;
	}

	public function loadTotalObjectivePerspectiveWeight($objectiveId) {
		$q = $this->db->query("select sum(unit_weight) as unit_weight from pms_objective_perspective where pms_objective_id = '".self::escapeString($objectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return $data['unit_weight'] * 1;
		}
		return 0;
	}

	public function delObjectivePerspective($objectiveId, $unitId) {
		return $this->db->query("delete from pms_objective_perspective where pms_objective_id = '".self::escapeString($objectiveId)."' and pms_unit_id = '".self::escapeString($unitId)."'");
	}

	public function saveObjectivePerspective($objectiveId, $unitId, $perspectiveId, $no, $default, $weight = 0) {
		if ($this->delObjectivePerspective($objectiveId, $unitId)) {
			return $this->db->query("insert into pms_objective_perspective (pms_objective_id, pms_unit_id, 
											pms_perspective_id, objective_perspective_no, unit_weight, is_default)
										values ('".self::escapeString($objectiveId)."',
												'".self::escapeString($unitId)."',
												'".self::escapeString($perspectiveId)."',
												'".self::escapeString($no)."',
												'".self::escapeString($weight)."',
												'".($default ? 1 : 0)."')");
		}
		return false;
	}

	public function loadObjectiveSources($objectiveId) {
		$result = array();
		$q = $this->db->query("select * from pms_objective_source where pms_objective_id = '".self::escapeString($objectiveId)."'");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = array("source" => $data['pms_source_id'], "weight" => $data['pms_source_weight']);
		}
		return $result;
	}

	public function loadObjectiveSource($objectiveId, $sourceId) {
		$result = array();
		$q = $this->db->query("select * from pms_objective_source where pms_objective_id = '".self::escapeString($objectiveId)."' and pms_source_id = '".self::escapeString($sourceId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return array("source" => $data['pms_source_id'], "weight" => $data['pms_source_weight']);
		}
		return null;
	}

	public function loadTotalObjectiveSourceWeight($objectiveId) {
		$q = $this->db->query("select sum(pms_source_weight) as pms_source_weight from pms_objective_source where pms_objective_id = '".self::escapeString($objectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return $data['pms_source_weight'] * 1;
		}
		return 0;
	}

	public function delObjectiveSource($objectiveId, $sourceId) {
		return $this->db->query("delete from pms_objective_source where pms_objective_id = '".self::escapeString($objectiveId)."' and pms_source_id = '".self::escapeString($sourceId)."'");
	}

	public function saveObjectiveSource($objectiveId, $sourceId, $weight) {
		if ($this->delObjectiveSource($objectiveId, $sourceId)) {
			return $this->db->query("insert into pms_objective_source (pms_objective_id, pms_source_id, pms_source_weight)
										values ('".self::escapeString($objectiveId)."',
												'".self::escapeString($sourceId)."',
												'".self::escapeString($weight)."')");
		}
		return false;
	}

	public function loadIndicators($objectiveId = null) {
		$result = array();
		$q = $this->db->query("select * from pms_indicator ".($objectiveId ? "where pms_objective_id = '".self::escapeString($objectiveId)."'" : "")." order by pms_indicator_id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSIndicator($data['pms_indicator_id'], $data['pms_objective_id'], $data['pms_indicator_text'], $data['pms_indicator_metric'], $data['pms_indicator_target_value']);
		}
		return $result;
	}

	public function loadIndicator($indicatorId) {
		$q = $this->db->query("select * from pms_indicator 
									where pms_indicator_id = '".self::escapeString($indicatorId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSIndicator($data['pms_indicator_id'], $data['pms_objective_id'], $data['pms_indicator_text'], $data['pms_indicator_metric'], $data['pms_indicator_target_value']);
		}
		return null;
	}

	public function saveIndicator(PMSIndicator $indicator) {
		$id = false;
		if ($indicator->id) {
			$ok = $this->db->query("update pms_indicator set 
										pms_indicator_text = '".self::escapeString($indicator->text)."', 
										pms_indicator_metric = '".self::escapeString($indicator->metric)."', 
										pms_indicator_target_value = '".self::escapeString($indicator->targetValue)."', 
										pms_objective_id = '".self::escapeString($indicator->objectiveId)."'
									where pms_indicator_id = '".self::escapeString($indicator->id)."'");
			if ($ok) $id = $indicator->id;
		}
		else {
			$ok = $this->db->query("insert into pms_indicator (pms_indicator_text, pms_indicator_metric, 
										pms_indicator_target_value, pms_objective_id)
									values ('".self::escapeString($indicator->text)."',
									 		'".self::escapeString($indicator->metric)."',
									 		'".self::escapeString($indicator->targetValue)."',
									 		'".self::escapeString($indicator->objectiveId)."')");
			if ($ok) $id = $this->db->getInsertId();
		}
		return $id;
	}

	public function delIndicator($indicatorId) {
		return $this->db->query("delete from pms_indicator where pms_indicator_id = '".self::escapeString($indicatorId)."'");
	}

	public function loadActions($objectiveId) {
		$result = array();
		$q = $this->db->query("select * from pms_action where pms_objective_id = '".self::escapeString($objectiveId)."' order by pms_action_id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSAction($data['pms_action_id'], $data['pms_objective_id'], $data['pms_unit_id'], $data['pms_action_text'], $data['pms_action_weight'], DateUtil::sqlToUnix($data['pms_action_begin']), DateUtil::sqlToUnix($data['pms_action_end']));
		}
		return $result;
	}

	public function loadAction($actionId) {
		$q = $this->db->query("select * from pms_action where pms_action_id = '".self::escapeString($actionId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new PMSAction($data['pms_action_id'], $data['pms_objective_id'], $data['pms_unit_id'], $data['pms_action_text'], $data['pms_action_weight'], DateUtil::sqlToUnix($data['pms_action_begin']), DateUtil::sqlToUnix($data['pms_action_end']));
		}
		return null;
	}

	public function saveAction(PMSAction $action) {
		$id = false;
		if ($action->id) {
			$ok = $this->db->query("update pms_action set 
										pms_objective_id = '".self::escapeString($action->objectiveId)."',
										pms_unit_id = '".self::escapeString($action->unitId)."',
										pms_action_text = '".self::escapeString($action->text)."', 
										pms_action_weight = '".self::escapeString($action->weight)."', 
										pms_action_begin = '".DateUtil::unixToSQL($action->begin)."', 
										pms_action_end = '".DateUtil::unixToSQL($action->end)."'
									where pms_action_id = '".self::escapeString($action->id)."'");
			if ($ok) $id = $action->id;
		}
		else {
			$ok = $this->db->query("insert into pms_action (pms_objective_id, pms_unit_id, 
										pms_action_text, pms_action_weight, pms_action_begin, pms_action_end)
									values ('".self::escapeString($action->objectiveId)."',
									 		'".self::escapeString($action->unitId)."',
									 		'".self::escapeString($action->text)."',
									 		'".self::escapeString($action->weight)."',
									 		'".DateUtil::unixToSQL($action->begin)."',
									 		'".DateUtil::unixToSQL($action->end)."')");
			if ($ok) $id = $this->db->getInsertId();
		}
		return $id;
	}

	public function delAction($actionId) {
		return $this->db->query("delete from pms_action where pms_action_id = '".self::escapeString($actionId)."'");
	}

	public function loadProgresses($personId) {
		$result = array();
		$q = $this->db->query("select * from pms_progress where pms_person_id = '".self::escapeString($personId)."' order by pms_progress_id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new PMSProgress($data['pms_progress_id'], $data['pms_action_id'], $data['pms_indicator_id'], $data['pms_person_id'], $data['pms_progress_text'], $data['pms_progress_weight'], DateUtil::sqlToUnix($data['pms_progress_target_begin']), DateUtil::sqlToUnix($data['pms_progress_target_end']), $data['pms_progress_target_value'], DateUtil::sqlToUnix($data['pms_progress_actual_begin']), DateUtil::sqlToUnix($data['pms_progress_actual_end']), $data['pms_progress_actual_value']);
		}
		return $result;
	}

	public function loadProgress($progressId) {
		$q = $this->db->query("select * from pms_progress where pms_progress_id = '".self::escapeString($progressId)."'");
		while ($data = $this->db->fetchArray($q)) {
			return new PMSProgress($data['pms_progress_id'], $data['pms_action_id'], $data['pms_indicator_id'], $data['pms_person_id'], $data['pms_progress_text'], $data['pms_progress_weight'], DateUtil::sqlToUnix($data['pms_progress_target_begin']), DateUtil::sqlToUnix($data['pms_progress_target_end']), $data['pms_progress_target_value'], DateUtil::sqlToUnix($data['pms_progress_actual_begin']), DateUtil::sqlToUnix($data['pms_progress_actual_end']), $data['pms_progress_actual_value']);
		}
		return null;
	}

	public function saveProgressTarget(PMSProgress $progress) {
		$id = false;
		if ($progress->id) {
			$ok = $this->db->query("update pms_progress set 
										pms_action_id = '".self::escapeString($progress->actionId)."',
										pms_indicator_id = '".self::escapeString($progress->indicatorId)."',
										pms_person_id = '".self::escapeString($progress->personId)."',
										pms_progress_text = '".self::escapeString($progress->text)."', 
										pms_progress_weight = '".self::escapeString($progress->weight)."', 
										pms_progress_target_begin = '".DateUtil::unixToSQL($progress->targetBegin)."', 
										pms_progress_target_end = '".DateUtil::unixToSQL($progress->targetEnd)."',
										pms_progress_target_value = '".self::escapeString($progress->targetValue)."'
									where pms_progress_id = '".self::escapeString($progress->id)."'");
			if ($ok) $id = $progress->id;
		}
		else {
			$ok = $this->db->query("insert into pms_progress (pms_action_id, pms_indicator_id, pms_person_id, 
										pms_progress_text, pms_progress_weight, pms_progress_target_begin, 
										pms_progress_target_end, pms_progress_target_value)
									values ('".self::escapeString($progress->actionId)."',
									 		'".self::escapeString($progress->indicatorId)."',
									 		'".self::escapeString($progress->personId)."',
									 		'".self::escapeString($progress->text)."',
									 		'".self::escapeString($progress->weight)."',
									 		'".DateUtil::unixToSQL($progress->targetBegin)."',
									 		'".DateUtil::unixToSQL($progress->targetEnd)."',
									 		'".self::escapeString($progress->targetValue)."')");
			if ($ok) $id = $this->db->getInsertId();
		}
		return $id;
	}

	public function saveProgressActual(PMSProgress $progress) {
		$id = false;
		$ok = $this->db->query("update pms_progress set 
									pms_progress_actual_begin = '".DateUtil::unixToSQL($progress->actualBegin)."', 
									pms_progress_actual_end = '".DateUtil::unixToSQL($progress->actualEnd)."',
									pms_progress_actual_value = '".self::escapeString($progress->actualValue)."'
								where pms_progress_id = '".self::escapeString($progress->id)."'");
		if ($ok) $id = $progress->id;
		return $id;
	}

	public function delProgress($progressId) {
		return $this->db->query("delete from pms_progress where pms_progress_id = '".self::escapeString($progressId)."'");
	}

}

PMS::$instance = new PMSXOCP();

$userinfo = _hris_getinfobyuserid(getUserID());
if ($userinfo) {
	$db = &Database::getInstance();
	$q = $db->query("select * from hris_jobs where job_id = '".PMSXOCP::escapeString($userinfo[0])."'");
	$data = $db->fetchArray($q);
	if ($data) PMSUnit::$currentId = $data['org_id'];
}

if ($_SESSION["html"]) {
	if (file_exists(XOCP_DOC_ROOT."/themes/{$theme}/style/pms.css")) {
		$_SESSION["html"]->addStyleSheet("<link href=\"".XOCP_SERVER_SUBDIR."/themes/{$theme}/style/pms.css"."\" type=\"text/css\" rel=\"stylesheet\"/>");
	}
	else {
		$_SESSION["html"]->addStyleSheet("<link href=\"".PMS_URL_DIR."/pms.css"."\" type=\"text/css\" rel=\"stylesheet\"/>");
	}
}

?>