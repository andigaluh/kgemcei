<?php
if ( !defined('HRIS_MODULECONSTS_DEFINED') ) {
   define('HRIS_MODULECONSTS_DEFINED', TRUE);

define("_HRIS_CATCH_VAR","X_hris");   

// Defines block registration number
define("_HRIS_ORGCLASS_BLOCK","1a");
define("_HRIS_ORGS_BLOCK","1b");
define("_HRIS_PGROUP2ORG_BLOCK","oa");
define("_HRIS_ASSESSMENT_BLOCK","asm");
define("_HRIS_ASSESSMENTSESSION_BLOCK","asms");
define("_HRIS_ASSESSMENTSESSIONCHECK_BLOCK","asmsck");
define("_HRIS_PERSON_DATADIR","/modules/hris/data/person");
define("_HRIS_IDPDEVLIB_BLOCK","idpdevlib");
define("_HRIS_IDPEVENTMANAGEMENT_BLOCK","idpeventm");

//define("_HRIS_MAX_ASSESSOR_LEVEL",70); //// set to supervisor level order, as minimum level of assessor
define("_HRIS_MAX_ASSESSOR_LEVEL",170); //// set to group leader level, as minimum level of assessor - per 2010-11-30
define("_HRIS_MAX_SUPERIOR_LEVEL",40); //// set to max direct superior can be assessed : division manager = 40

//// Anchor datetime for schedule
/// define("_HRIS_SCHEDULE_ANCHOR","2010-02-01 00:00:00"); /// original anchor
define("_HRIS_SCHEDULE_ANCHOR_DTTM","1988-02-01 00:00:00");
define("_HRIS_SCHEDULE_ANCHOR_DAY",726133);

} // HRIS_MODULECONSTS_DEFINED
?>