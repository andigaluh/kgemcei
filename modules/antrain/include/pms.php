<?php
if ( !defined('HRIS_INCLUDEPMS_DEFINED') ) {
   define('HRIS_INCLUDEPMS_DEFINED', TRUE);

global $pms_ach_range;

$pms_ach_range = array(0,82,90,100,120);

global $allow_deploy,$allow_add_objective,$allow_add_initiative;
global $allow_actionplan;

$allow_add_objective[1] = 1;
$allow_add_objective[2] = 1;
$allow_add_objective[3] = 0;
$allow_add_objective[4] = 0;
$allow_add_objective[5] = 0;

$allow_add_initiative[1] = 1;
$allow_add_initiative[2] = 1;
$allow_add_initiative[3] = 1;
$allow_add_initiative[4] = 0;
$allow_add_initiative[5] = 0;

$allow_actionplan[1] = 0;
$allow_actionplan[2] = 0;
$allow_actionplan[3] = 0;
$allow_actionplan[4] = 1;
$allow_actionplan[5] = 0;

} // HRIS_INCLUDEPMS_DEFINED
?>