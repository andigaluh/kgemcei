<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once "SMSPerspective.php";
require_once "SMSObjective.php";
require_once "SMSIndicator.php";
require_once "SMSObjectivePerspective.php";
require_once "SMSAction.php";
require_once "SMSProgress.php";
require_once "SMSPerson.php";
require_once "SMSUnit.php";
require_once "SMSPosition.php";

abstract class SMS {

	public static $instance = null;

	abstract public function getPerspectiveListOpenJS();

	abstract public function getPerspectiveEditorOpenJS($mode, $id);

	abstract public function getPerspectiveEditorSaveJS($formId);

	abstract public function getPerspectiveEditorDelJS($id);

	abstract public function getPerspectiveEditorCloseJS();

	abstract public function getObjectiveListOpenJS();

	abstract public function getObjectiveDetailOpenJS($id);

	abstract public function getObjectiveEditorOpenJS($mode, $id);

	abstract public function getObjectiveEditorSaveJS($formId);

	abstract public function getObjectiveEditorDelJS($id);

	abstract public function getObjectiveEditorCloseJS();

	abstract public function getIndicatorEditorOpenJS($mode, $id, $objectiveId);

	abstract public function getIndicatorEditorSaveJS($formId);

	abstract public function getIndicatorEditorDelJS($id);

	abstract public function getIndicatorEditorCloseJS();

	abstract public function getObjectivePerspectiveEditorOpenJS($mode, $id, $objectiveId);

	abstract public function getObjectivePerspectiveEditorSaveJS($formId);

	abstract public function getObjectivePerspectiveEditorDelJS($id, $objectiveId);

	abstract public function getObjectivePerspectiveEditorCloseJS();

	abstract public function loadUnits();
		// return array(SMSUnit)

	abstract public function loadUnit($unitId);
		// return SMSUnit

	abstract public function loadSubUnits($unitId);
		// return array(SMSUnit)

	abstract public function loadPositions($unitId = null, $titleSort = false);
		// return array(SMSPosition)

	abstract public function loadPosition($positionId);
		// return SMSPosition

	abstract public function loadPerson($personId);
		// return SMSPerson

	abstract public function loadPerspectives();
		// return array(SMSPerspective)

	abstract public function loadPerspectiveById($perspectiveId);
		// return SMSPerspective

	abstract public function loadPerspectiveByCode($perspectiveCode);
		// return SMSPerspective

	abstract public function delPerspective($perspectiveId);
		// return state

	abstract public function savePerspective(SMSPerspective $perspective);
		// return SMSPerspective->id

	abstract public function loadObjectives();
		// return array(SMSObjective)

	abstract public function loadObjectivesByUnit($unitId);
		// return array(SMSObjective)

	abstract public function loadTotalObjectiveWeight();
		// return total objective weight

	abstract public function loadObjective($objectiveId);
		// return SMSObjective

	abstract public function delObjective($objectiveId);
		// return state

	abstract public function saveObjective(SMSObjective $objective);
		// return SMSObjective->id

	abstract public function loadObjectiveDefaultPerspective($objectiveId);
		// return array(unit => SMSUnit->id, perspective => SMSPerspective->id, no => no, weight => weight)

	abstract public function loadObjectiveOtherPerspectives($objectiveId);
		// return array(array(unit => SMSUnit->id, perspective => SMSPerspective->id, no => no, weight => weight))

	abstract public function loadObjectivePerspective($objectiveId, $unitId);
		// return array(unit => SMSUnit->id, perspective => SMSPerspective->id, no => no, weight => weight)

	abstract public function loadTotalObjectivePerspectiveWeight($objectiveId);
		// return total objective perspective weight

	abstract public function delObjectivePerspective($objectiveId, $unitId);
		// return state

	abstract public function saveObjectivePerspective($objectiveId, $unitId, $perspectiveId, $no, $default);
		// return state

	abstract public function loadObjectiveSources($objectiveId);
		// return array(array(source => SMSObjective->id, weight => %))

	abstract public function loadTotalObjectiveSourceWeight($objectiveId);
		// return total objective source weight

	abstract public function loadObjectiveSource($objectiveId, $sourceId);
		// return array(source => SMSObjective->id, weight => %)

	abstract public function saveObjectiveSource($objectiveId, $sourceId, $weight);
		// return true/false

	abstract public function delObjectiveSource($objectiveId, $sourceId);
		// return state

	abstract public function loadIndicators($objectiveId = null);
		// return array(SMSIndicator)

	abstract public function loadIndicator($indicatorId);
		// return SMSIndicator

	abstract public function saveIndicator(SMSIndicator $indicator);
		// return SMSIndicator->id

	abstract public function delIndicator($indicatorId);
		// return state

	abstract public function loadActions($objectiveId);
		// return array(SMSAction)

	abstract public function loadAction($actionId);
		// return SMSAction

	abstract public function saveAction(SMSAction $action);
		// return SMSAction->id

	abstract public function delAction($actionId);
		// return state

	abstract public function loadProgresses($personId);
		// return array(SMSProgress)

	abstract public function loadProgress($progressId);
		// return SMSProgress

	abstract public function saveProgressTarget(SMSProgress $progress);
		// return SMSProgress->id

	abstract public function saveProgressActual(SMSProgress $progress);
		// return SMSProgress->id

	abstract public function delProgress($progressId);
		// return state

}

?>