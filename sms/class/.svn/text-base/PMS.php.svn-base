<?php

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	die("You do not have any grant to access this script directly.");
}

require_once "PMSPerspective.php";
require_once "PMSObjective.php";
require_once "PMSIndicator.php";
require_once "PMSObjectivePerspective.php";
require_once "PMSAction.php";
require_once "PMSProgress.php";
require_once "PMSPerson.php";
require_once "PMSUnit.php";
require_once "PMSPosition.php";

abstract class PMS {

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
		// return array(PMSUnit)

	abstract public function loadUnit($unitId);
		// return PMSUnit

	abstract public function loadSubUnits($unitId);
		// return array(PMSUnit)

	abstract public function loadPositions($unitId = null, $titleSort = false);
		// return array(PMSPosition)

	abstract public function loadPosition($positionId);
		// return PMSPosition

	abstract public function loadPerson($personId);
		// return PMSPerson

	abstract public function loadPerspectives();
		// return array(PMSPerspective)

	abstract public function loadPerspectiveById($perspectiveId);
		// return PMSPerspective

	abstract public function loadPerspectiveByCode($perspectiveCode);
		// return PMSPerspective

	abstract public function delPerspective($perspectiveId);
		// return state

	abstract public function savePerspective(PMSPerspective $perspective);
		// return PMSPerspective->id

	abstract public function loadObjectives();
		// return array(PMSObjective)

	abstract public function loadObjectivesByUnit($unitId);
		// return array(PMSObjective)

	abstract public function loadTotalObjectiveWeight();
		// return total objective weight

	abstract public function loadObjective($objectiveId);
		// return PMSObjective

	abstract public function delObjective($objectiveId);
		// return state

	abstract public function saveObjective(PMSObjective $objective);
		// return PMSObjective->id

	abstract public function loadObjectiveDefaultPerspective($objectiveId);
		// return array(unit => PMSUnit->id, perspective => PMSPerspective->id, no => no, weight => weight)

	abstract public function loadObjectiveOtherPerspectives($objectiveId);
		// return array(array(unit => PMSUnit->id, perspective => PMSPerspective->id, no => no, weight => weight))

	abstract public function loadObjectivePerspective($objectiveId, $unitId);
		// return array(unit => PMSUnit->id, perspective => PMSPerspective->id, no => no, weight => weight)

	abstract public function loadTotalObjectivePerspectiveWeight($objectiveId);
		// return total objective perspective weight

	abstract public function delObjectivePerspective($objectiveId, $unitId);
		// return state

	abstract public function saveObjectivePerspective($objectiveId, $unitId, $perspectiveId, $no, $default);
		// return state

	abstract public function loadObjectiveSources($objectiveId);
		// return array(array(source => PMSObjective->id, weight => %))

	abstract public function loadTotalObjectiveSourceWeight($objectiveId);
		// return total objective source weight

	abstract public function loadObjectiveSource($objectiveId, $sourceId);
		// return array(source => PMSObjective->id, weight => %)

	abstract public function saveObjectiveSource($objectiveId, $sourceId, $weight);
		// return true/false

	abstract public function delObjectiveSource($objectiveId, $sourceId);
		// return state

	abstract public function loadIndicators($objectiveId = null);
		// return array(PMSIndicator)

	abstract public function loadIndicator($indicatorId);
		// return PMSIndicator

	abstract public function saveIndicator(PMSIndicator $indicator);
		// return PMSIndicator->id

	abstract public function delIndicator($indicatorId);
		// return state

	abstract public function loadActions($objectiveId);
		// return array(PMSAction)

	abstract public function loadAction($actionId);
		// return PMSAction

	abstract public function saveAction(PMSAction $action);
		// return PMSAction->id

	abstract public function delAction($actionId);
		// return state

	abstract public function loadProgresses($personId);
		// return array(PMSProgress)

	abstract public function loadProgress($progressId);
		// return PMSProgress

	abstract public function saveProgressTarget(PMSProgress $progress);
		// return PMSProgress->id

	abstract public function saveProgressActual(PMSProgress $progress);
		// return PMSProgress->id

	abstract public function delProgress($progressId);
		// return state

}

?>