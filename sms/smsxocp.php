<?php

include_once(XOCP_DOC_ROOT."/gakruwetxocp.php");

include_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");


define("SMS_DOC_DIR", XOCP_DOC_ROOT."/modules/sms/class");
define("SMS_URL_DIR", XOCP_SERVER_SUBDIR."/modules/sms/class");

include_once(SMS_DOC_DIR."/SMS.php");
include_once(XOCP_DOC_ROOT."/modules/sms/ajax/ajax_sms.php");

require_once GAKRUWET_DOC_DIR."/DateUtil.php";

class SMSXOCP extends SMS {

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
		$editor = new GlassBoxDialog("SMS{$type}Editor", $title, "", "", false, false);
		$output .= $editor->out();
		$output .= Widget::script("

			function sms{$type}EditorLoad(data) {
				".$editor->init()."
				var cnt = document.getElementById('SMS{$type}EditorContent');
				cnt.innerHTML = data;
				".$editor->appear()."
			}

			function sms{$type}EditorOpen(mode, id, extra) {
				data = 'mode^^' + mode + '@@id^^' + id + '@@' + extra;
				sms_app_smsAjax('{$type}', data,
					function(_data) {
						sms{$type}EditorLoad(_data);
					}
				);
			}

			function sms{$type}EditorClose() {
				".$editor->fade()."
			}

			var SMS{$type}EditorSaveTmp;

			function sms{$type}EditorSave(formId) {
				data = _parseForm(formId);
				sms{$type}EditorClose();
				sms_app_smsAjax('{$type}', data,
					function(_data) {
						if (_data) {
							SMS{$type}EditorSaveTmp = _data;
							setTimeout('sms{$type}EditorLoad(SMS{$type}EditorSaveTmp)', 1000);
						}
						else {
							{$afterSave}
						}
					}
				);			
			}

			function sms{$type}EditorDel(id, extra) {
				data = 'mode^^del@@id^^' + id + '@@' + extra;
				sms{$type}EditorClose();
				sms_app_smsAjax('{$type}', data,
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
		$ajax = new _sms_class_Ajax("sms");
		$output .= $ajax->getJs();
		$list = new Div("SMS{$type}Div");
		$output .= $list->out();
		$output .= Widget::script("

			function sms{$type}ListOpen() {
				SMS{$type}DetailId = '';
				sms_app_smsAjax('{$type}', '',
					function(_data) {
						var div = document.getElementById('SMS{$type}Div');
						div.innerHTML = _data;
					}
				);
			}

			function sms{$type}DetailOpen(id) {
				if (id) SMS{$type}DetailId = id;
				if (SMS{$type}DetailId) {
					sms_app_smsAjax('{$type}', 'mode^^detail@@id^^' + SMS{$type}DetailId,
						function(_data) {
							var div = document.getElementById('SMS{$type}Div');
							div.innerHTML = _data;
						}
					);
				}
				else {
					sms{$type}ListOpen();
				}
			}

		");
		return $output;
	}

	public function getPerspectiveListOpenJS() {
		return "smsPerspectiveListOpen();";
	}

	public function getPerspectiveEditorOpenJS($mode, $id) {
		return "smsPerspectiveEditorOpen('{$mode}', '{$id}', '');";
	}

	public function getPerspectiveEditorSaveJS($formId) {
		return "smsPerspectiveEditorSave('{$formId}');";
	}

	public function getPerspectiveEditorDelJS($id) {
		return "smsPerspectiveEditorDel('{$id}');";
	}

	public function getPerspectiveEditorCloseJS() {
		return "smsPerspectiveEditorClose();";
	}

	public function getObjectiveListOpenJS() {
		return "smsObjectiveListOpen();";
	}

	public function getObjectiveDetailOpenJS($id) {
		return "smsObjectiveDetailOpen('$id');";
	}

	public function getObjectiveEditorOpenJS($mode, $id) {
		return "smsObjectiveEditorOpen('{$mode}', '{$id}');";
	}

	public function getObjectiveEditorSaveJS($formId) {
		return "smsObjectiveEditorSave('{$formId}');";
	}

	public function getObjectiveEditorDelJS($id) {
		return "smsObjectiveEditorDel('{$id}');";
	}

	public function getObjectiveEditorCloseJS() {
		return "smsObjectiveEditorClose();";
	}

	public function getIndicatorEditorOpenJS($mode, $id, $objectiveId) {
		return "smsIndicatorEditorOpen('{$mode}', '{$id}', 'ObjectiveId^^".rawurlencode($objectiveId)."');";
	}

	public function getIndicatorEditorSaveJS($formId) {
		return "smsIndicatorEditorSave('{$formId}');";
	}

	public function getIndicatorEditorDelJS($id) {
		return "smsIndicatorEditorDel('{$id}');";
	}

	public function getIndicatorEditorCloseJS() {
		return "smsIndicatorEditorClose();";
	}

	public function getObjectivePerspectiveEditorOpenJS($mode, $id, $objectiveId) {
		return "smsObjectivePerspectiveEditorOpen('{$mode}', '{$id}', 'ObjectiveId^^".rawurlencode($objectiveId)."');";
	}

	public function getObjectivePerspectiveEditorSaveJS($formId) {
		return "smsObjectivePerspectiveEditorSave('{$formId}');";
	}

	public function getObjectivePerspectiveEditorDelJS($id, $objectiveId) {
		return "smsObjectivePerspectiveEditorDel('{$id}', 'ObjectiveId^^".rawurlencode($objectiveId)."');";
	}

	public function getObjectivePerspectiveEditorCloseJS() {
		return "smsObjectivePerspectiveEditorClose();";
	}

	public function loadUnits() {
		$result = array();
		$q = $this->db->query("select a.org_id, a.org_nm, b.org_class_id, b.org_class_nm, b.order_no from hris_orgs a"
		   . " left join hris_org_class b USING(org_class_id)"
		   . " order by b.org_class_id, a.org_nm");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSUnit($data['org_id'], $data['org_nm'], $data['org_class_nm'], $data['order_no']);
		}
		return $result;
	}

	public function loadUnit($unitId) {
		$q = $this->db->query("select a.org_id, a.org_nm, b.org_class_nm, b.order_no from hris_orgs a"
		   . " left join hris_org_class b USING(org_class_id)"
		   . " where a.org_id = '".self::escapeString($unitId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSUnit($data['org_id'], $data['org_nm'], $data['org_class_nm'], $data['order_no']);
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
			$result[] = new SMSUnit($data['org_id'], $data['org_nm'], $data['org_class_nm'], $data['order_no']);
		}
		return $result;
	}

	public function loadPositions($unitId = null, $titleSort = false) {
		$result = array();
		$q = $this->db->query("select * from hris_jobs j join hris_job_class c on c.job_class_id = j.job_class_id ".($unitId ? "where j.org_id = '".self::escapeString($unitId)."'" : "")." order by ".($titleSort ? "" : "c.job_class_level, ")."j.job_nm");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSPosition($data['job_id'], $data['job_nm'], $data['job_class_level']);
		}
		return $result;
	}

	public function loadPosition($positionId) {
		$q = $this->db->query("select * from hris_jobs j join hris_job_class c on c.job_class_id = j.job_class_id where j.job_id = '".self::escapeString($positionId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSPosition($data['job_id'], $data['job_nm'], $data['job_class_level']);
		}
		return null;
	}

	public function loadPerson($personId) {
		$q = $this->db->query("select * from hris_employee e join hris_persons p on p.person_id = e.person_id where p.person_id = '".self::escapeString($personId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSPerson($data['person_id'], $data['employee_ext_id'], $data['person_nm']);
		}
		return null;
	}

	public function loadPerspectives() {
		$result = array();
		$q = $this->db->query("select * from sms_section_perspective order by id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSPerspective($data['id'], $data['sms_perspective_code'], $data['sms_perspective_name']);
		}
		return $result;
	}

	public function loadPerspectiveById($perspectiveId) {
		$q = $this->db->query("select * from sms_section_perspective 
									where id = '".self::escapeString($perspectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSPerspective($data['id'], $data['sms_perspective_code'], $data['sms_perspective_name']);
		}
		return null;
	}

	public function loadPerspectiveByCode($perspectiveCode) {
		$q = $this->db->query("select * from sms_section_perspective 
									where code = '".self::escapeString($perspectiveCode)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSPerspective($data['id'], $data['sms_perspective_code'], $data['sms_perspective_name']);
		}
		return null;
	}

	public function delPerspective($perspectiveId) {
		return $this->db->query("delete from sms_section_perspective where id = '".self::escapeString($perspectiveId)."'");
	}

	public function savePerspective(SMSPerspective $perspective) {
		$id = false;
		if ($perspective->id) {
			$ok = $this->db->query("update sms_section_perspective set 
										code = '".self::escapeString($perspective->code)."', 
										title = '".self::escapeString($perspective->name)."'
									id = '".self::escapeString($perspective->id)."'");
			if ($ok) $id = $perspective->id;
		}
		else {
			$ok = $this->db->query("insert into sms_section_perspective (code, title)
									values ('".self::escapeString($perspective->code)."',
									 		'".self::escapeString($perspective->name)."')");
			if ($ok) $id = $this->db->getInsertId();
		}
		return $id;
	}

	public function loadObjectives() {
		$result = array();
		$q = $this->db->query("select * from sms_section_objective order by start, stop");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSObjective($data['sms_objective_id'], $data['sms_objective_text'], $data['sms_objective_weight'], DateUtil::sqlToUnix($data['sms_objective_begin']), DateUtil::sqlToUnix($data['sms_objective_end']), $data['sms_pic_position_id']);
		}
		return $result;
	}

	public function loadTotalObjectiveWeight() {
		$q = $this->db->query("select sum(weight) as weight from sms_section_objective");
		if ($data = $this->db->fetchArray($q)) {
			return $data['sms_objective_weight'] * 1;
		}
		return 0;
	}

	public function loadObjectivesByUnit($unitId) {
		$result = array();
		$q = $this->db->query("select o.* from sms_section_objective o
								join sms_objective_perspective p on p.sms_objective_id = o.sms_objective_id
								where p.sms_unit_id = '".self::escapeString($unitId)."'
								order by o.sms_objective_begin, o.sms_objective_end");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSObjective($data['sms_objective_id'], $data['sms_objective_text'], $data['sms_objective_weight'], DateUtil::sqlToUnix($data['sms_objective_begin']), DateUtil::sqlToUnix($data['sms_objective_end']), $data['sms_pic_position_id']);
		}
		return $result;
	}

	public function loadObjective($objectiveId) {
		$q = $this->db->query("select * from sms_section_objective where id = '".self::escapeString($objectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSObjective($data['sms_objective_id'], $data['sms_objective_text'], $data['sms_objective_weight'], DateUtil::sqlToUnix($data['sms_objective_begin']), DateUtil::sqlToUnix($data['sms_objective_end']), $data['sms_pic_position_id']);
		}
		return null;
	}

	public function delObjective($objectiveId) {
		$ok = $this->db->query("delete from sms_objective_perspective where sms_objective_id = '".self::escapeString($objectiveId)."'");
		if ($ok) $ok = $this->db->query("delete from sms_objective where sms_objective_id = '".self::escapeString($objectiveId)."'");
		return $ok;
	}

	public function saveObjective(SMSObjective $objective) {
		$id = false;
		if ($objective->id) {
			$ok = $this->db->query("update sms_section_objective set 
										section_objective_desc = '".self::escapeString($objective->text)."', 
										weight = '".self::escapeString($objective->weight)."', 
										start = '".DateUtil::unixToSQL($objective->begin)."', 
										stop = '".DateUtil::unixToSQL($objective->end)."', 
										pic_job_id = '".self::escapeString($objective->picId)."'
									where sms_objective_id = '".self::escapeString($objective->id)."'");
			if ($ok) $id = $objective->id;
		}
		else {
			$ok = $this->db->query("insert into sms_objective (section_objective_desc, weight, 
										start, stop, pic_job_id)
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
		$q = $this->db->query("select * from sms_objective_perspective where sms_objective_id = '".self::escapeString($objectiveId)."' and is_default = 1");
		if ($data = $this->db->fetchArray($q)) {
			return array("unit" => $data['sms_unit_id'], "perspective" => $data['sms_perspective_id'], "no" => $data['objective_perspective_no'], "weight" => $data['unit_weight']);
		}
		return null;
	}

	public function loadObjectiveOtherPerspectives($objectiveId) {
		$result = array();
		$q = $this->db->query("select * from sms_objective_perspective where sms_objective_id = '".self::escapeString($objectiveId)."' and is_default = 0");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = array("unit" => $data['sms_unit_id'], "perspective" => $data['sms_perspective_id'], "no" => $data['objective_perspective_no'], "weight" => $data['unit_weight']);
		}
		return $result;
	}

	public function loadObjectivePerspective($objectiveId, $unitId) {
		$q = $this->db->query("select * from sms_objective_perspective where sms_objective_id = '".self::escapeString($objectiveId)."' and sms_unit_id = '".self::escapeString($unitId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return array("unit" => $data['sms_unit_id'], "perspective" => $data['sms_perspective_id'], "no" => $data['objective_perspective_no'], "weight" => $data['unit_weight']);
		}
		return null;
	}

	public function loadTotalObjectivePerspectiveWeight($objectiveId) {
		$q = $this->db->query("select sum(unit_weight) as unit_weight from sms_objective_perspective where sms_objective_id = '".self::escapeString($objectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return $data['unit_weight'] * 1;
		}
		return 0;
	}

	public function delObjectivePerspective($objectiveId, $unitId) {
		return $this->db->query("delete from sms_objective_perspective where sms_objective_id = '".self::escapeString($objectiveId)."' and sms_unit_id = '".self::escapeString($unitId)."'");
	}

	public function saveObjectivePerspective($objectiveId, $unitId, $perspectiveId, $no, $default, $weight = 0) {
		if ($this->delObjectivePerspective($objectiveId, $unitId)) {
			return $this->db->query("insert into sms_objective_perspective (sms_objective_id, sms_unit_id, 
											sms_perspective_id, objective_perspective_no, unit_weight, is_default)
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
		$q = $this->db->query("select * from sms_objective_source where sms_objective_id = '".self::escapeString($objectiveId)."'");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = array("source" => $data['sms_source_id'], "weight" => $data['sms_source_weight']);
		}
		return $result;
	}

	public function loadObjectiveSource($objectiveId, $sourceId) {
		$result = array();
		$q = $this->db->query("select * from sms_objective_source where sms_objective_id = '".self::escapeString($objectiveId)."' and sms_source_id = '".self::escapeString($sourceId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return array("source" => $data['sms_source_id'], "weight" => $data['sms_source_weight']);
		}
		return null;
	}

	public function loadTotalObjectiveSourceWeight($objectiveId) {
		$q = $this->db->query("select sum(sms_source_weight) as sms_source_weight from sms_objective_source where sms_objective_id = '".self::escapeString($objectiveId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return $data['sms_source_weight'] * 1;
		}
		return 0;
	}

	public function delObjectiveSource($objectiveId, $sourceId) {
		return $this->db->query("delete from sms_objective_source where sms_objective_id = '".self::escapeString($objectiveId)."' and sms_source_id = '".self::escapeString($sourceId)."'");
	}

	public function saveObjectiveSource($objectiveId, $sourceId, $weight) {
		if ($this->delObjectiveSource($objectiveId, $sourceId)) {
			return $this->db->query("insert into sms_objective_source (sms_objective_id, sms_source_id, sms_source_weight)
										values ('".self::escapeString($objectiveId)."',
												'".self::escapeString($sourceId)."',
												'".self::escapeString($weight)."')");
		}
		return false;
	}

	public function loadIndicators($objectiveId = null) {
		$result = array();
		$q = $this->db->query("select * from sms_indicator ".($objectiveId ? "where sms_objective_id = '".self::escapeString($objectiveId)."'" : "")." order by sms_indicator_id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSIndicator($data['sms_indicator_id'], $data['sms_objective_id'], $data['sms_indicator_text'], $data['sms_indicator_metric'], $data['sms_indicator_target_value']);
		}
		return $result;
	}

	public function loadIndicator($indicatorId) {
		$q = $this->db->query("select * from sms_indicator 
									where sms_indicator_id = '".self::escapeString($indicatorId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSIndicator($data['sms_indicator_id'], $data['sms_objective_id'], $data['sms_indicator_text'], $data['sms_indicator_metric'], $data['sms_indicator_target_value']);
		}
		return null;
	}

	public function saveIndicator(SMSIndicator $indicator) {
		$id = false;
		if ($indicator->id) {
			$ok = $this->db->query("update sms_indicator set 
										sms_indicator_text = '".self::escapeString($indicator->text)."', 
										sms_indicator_metric = '".self::escapeString($indicator->metric)."', 
										sms_indicator_target_value = '".self::escapeString($indicator->targetValue)."', 
										sms_objective_id = '".self::escapeString($indicator->objectiveId)."'
									where sms_indicator_id = '".self::escapeString($indicator->id)."'");
			if ($ok) $id = $indicator->id;
		}
		else {
			$ok = $this->db->query("insert into sms_indicator (sms_indicator_text, sms_indicator_metric, 
										sms_indicator_target_value, sms_objective_id)
									values ('".self::escapeString($indicator->text)."',
									 		'".self::escapeString($indicator->metric)."',
									 		'".self::escapeString($indicator->targetValue)."',
									 		'".self::escapeString($indicator->objectiveId)."')");
			if ($ok) $id = $this->db->getInsertId();
		}
		return $id;
	}

	public function delIndicator($indicatorId) {
		return $this->db->query("delete from sms_indicator where sms_indicator_id = '".self::escapeString($indicatorId)."'");
	}

	public function loadActions($objectiveId) {
		$result = array();
		$q = $this->db->query("select * from sms_action where sms_objective_id = '".self::escapeString($objectiveId)."' order by sms_action_id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSAction($data['sms_action_id'], $data['sms_objective_id'], $data['sms_unit_id'], $data['sms_action_text'], $data['sms_action_weight'], DateUtil::sqlToUnix($data['sms_action_begin']), DateUtil::sqlToUnix($data['sms_action_end']));
		}
		return $result;
	}

	public function loadAction($actionId) {
		$q = $this->db->query("select * from sms_action where sms_action_id = '".self::escapeString($actionId)."'");
		if ($data = $this->db->fetchArray($q)) {
			return new SMSAction($data['sms_action_id'], $data['sms_objective_id'], $data['sms_unit_id'], $data['sms_action_text'], $data['sms_action_weight'], DateUtil::sqlToUnix($data['sms_action_begin']), DateUtil::sqlToUnix($data['sms_action_end']));
		}
		return null;
	}

	public function saveAction(SMSAction $action) {
		$id = false;
		if ($action->id) {
			$ok = $this->db->query("update sms_action set 
										sms_objective_id = '".self::escapeString($action->objectiveId)."',
										sms_unit_id = '".self::escapeString($action->unitId)."',
										sms_action_text = '".self::escapeString($action->text)."', 
										sms_action_weight = '".self::escapeString($action->weight)."', 
										sms_action_begin = '".DateUtil::unixToSQL($action->begin)."', 
										sms_action_end = '".DateUtil::unixToSQL($action->end)."'
									where sms_action_id = '".self::escapeString($action->id)."'");
			if ($ok) $id = $action->id;
		}
		else {
			$ok = $this->db->query("insert into sms_action (sms_objective_id, sms_unit_id, 
										sms_action_text, sms_action_weight, sms_action_begin, sms_action_end)
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
		return $this->db->query("delete from sms_action where sms_action_id = '".self::escapeString($actionId)."'");
	}

	public function loadProgresses($personId) {
		$result = array();
		$q = $this->db->query("select * from sms_progress where sms_person_id = '".self::escapeString($personId)."' order by sms_progress_id");
		while ($data = $this->db->fetchArray($q)) {
			$result[] = new SMSProgress($data['sms_progress_id'], $data['sms_action_id'], $data['sms_indicator_id'], $data['sms_person_id'], $data['sms_progress_text'], $data['sms_progress_weight'], DateUtil::sqlToUnix($data['sms_progress_target_begin']), DateUtil::sqlToUnix($data['sms_progress_target_end']), $data['sms_progress_target_value'], DateUtil::sqlToUnix($data['sms_progress_actual_begin']), DateUtil::sqlToUnix($data['sms_progress_actual_end']), $data['sms_progress_actual_value']);
		}
		return $result;
	}

	public function loadProgress($progressId) {
		$q = $this->db->query("select * from sms_progress where sms_progress_id = '".self::escapeString($progressId)."'");
		while ($data = $this->db->fetchArray($q)) {
			return new SMSProgress($data['sms_progress_id'], $data['sms_action_id'], $data['sms_indicator_id'], $data['sms_person_id'], $data['sms_progress_text'], $data['sms_progress_weight'], DateUtil::sqlToUnix($data['sms_progress_target_begin']), DateUtil::sqlToUnix($data['sms_progress_target_end']), $data['sms_progress_target_value'], DateUtil::sqlToUnix($data['sms_progress_actual_begin']), DateUtil::sqlToUnix($data['sms_progress_actual_end']), $data['sms_progress_actual_value']);
		}
		return null;
	}

	public function saveProgressTarget(SMSProgress $progress) {
		$id = false;
		if ($progress->id) {
			$ok = $this->db->query("update sms_progress set 
										sms_action_id = '".self::escapeString($progress->actionId)."',
										sms_indicator_id = '".self::escapeString($progress->indicatorId)."',
										sms_person_id = '".self::escapeString($progress->personId)."',
										sms_progress_text = '".self::escapeString($progress->text)."', 
										sms_progress_weight = '".self::escapeString($progress->weight)."', 
										sms_progress_target_begin = '".DateUtil::unixToSQL($progress->targetBegin)."', 
										sms_progress_target_end = '".DateUtil::unixToSQL($progress->targetEnd)."',
										sms_progress_target_value = '".self::escapeString($progress->targetValue)."'
									where sms_progress_id = '".self::escapeString($progress->id)."'");
			if ($ok) $id = $progress->id;
		}
		else {
			$ok = $this->db->query("insert into sms_progress (sms_action_id, sms_indicator_id, sms_person_id, 
										sms_progress_text, sms_progress_weight, sms_progress_target_begin, 
										sms_progress_target_end, sms_progress_target_value)
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

	public function saveProgressActual(SMSProgress $progress) {
		$id = false;
		$ok = $this->db->query("update sms_progress set 
									sms_progress_actual_begin = '".DateUtil::unixToSQL($progress->actualBegin)."', 
									sms_progress_actual_end = '".DateUtil::unixToSQL($progress->actualEnd)."',
									sms_progress_actual_value = '".self::escapeString($progress->actualValue)."'
								where sms_progress_id = '".self::escapeString($progress->id)."'");
		if ($ok) $id = $progress->id;
		return $id;
	}

	public function delProgress($progressId) {
		return $this->db->query("delete from sms_progress where sms_progress_id = '".self::escapeString($progressId)."'");
	}

}

SMS::$instance = new SMSXOCP();

$userinfo = _hris_getinfobyuserid(getUserID());
if ($userinfo) {
	$db = &Database::getInstance();
	$q = $db->query("select * from hris_jobs where job_id = '".SMSXOCP::escapeString($userinfo[0])."'");
	$data = $db->fetchArray($q);
	if ($data) SMSUnit::$currentId = $data['org_id'];
}

if ($_SESSION["html"]) {
	if (file_exists(XOCP_DOC_ROOT."/themes/{$theme}/style/sms.css")) {
		$_SESSION["html"]->addStyleSheet("<link href=\"".XOCP_SERVER_SUBDIR."/themes/{$theme}/style/sms.css"."\" type=\"text/css\" rel=\"stylesheet\"/>");
	}
	else {
		$_SESSION["html"]->addStyleSheet("<link href=\"".SMS_URL_DIR."/sms.css"."\" type=\"text/css\" rel=\"stylesheet\"/>");
	}
}

?>