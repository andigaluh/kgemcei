<?php
if ( !defined('HRIS_VOCABDOMAIN_DEFINED') ) {
   define('HRIS_VOCABDOMAIN_DEFINED', TRUE);

// Defines vocab standard

global $img_collapsed_url,$img_expanded_url,$img_bullet_url;
$img_collapsed_url = XOCP_SERVER_SUBDIR."/images/collapsed.gif";
$img_expanded_url = XOCP_SERVER_SUBDIR."/images/expanded.gif";
$img_bullet_url = XOCP_SERVER_SUBDIR."/images/bullet.gif";

global $arr_marital;
$arr_marital["maried"] = "Married";
$arr_marital["single"] = "Single";

global $proficiency_level_name;

$proficiency_level_name[0] = "Unskilled";
$proficiency_level_name[1] = "Semiskilled";
$proficiency_level_name[2] = "Skilled";
$proficiency_level_name[3] = "Very Skilled";
$proficiency_level_name[4] = "Expert";

global $arr_rel;
$arr_rel = array();
//$arr_rel["parent"] = "has parent";
$arr_rel["customer"] = "has customer";
//$arr_rel["sibling"] = "has sibling";

global $arr_edu;
$arr_edu[0] = "Unknown";
$arr_edu[1] = "Pre School";
$arr_edu[2] = "Elementary School";
$arr_edu[3] = "Junior High School";
$arr_edu[4] = "Senior High School";
$arr_edu[5] = "Diploma";
$arr_edu[6] = "Under Graduate";
$arr_edu[7] = "Graduate";
$arr_edu[8] = "Master";
$arr_edu[9] = "Doctoral";

global $family_relation;
$family_relation["spouse"] = "Spouse";
$family_relation["child"] = "Child";
$family_relation["parent"] = "Parent";
$family_relation["other_family"] = "Other Family";
$family_relation["sister"] = "Sister";
$family_relation["brother"] = "Brother";
$family_relation["step_parent"] = "Step Parent";
$family_relation["step_child"] = "Step Child";



} // HRIS_VOCABDOMAIN_DEFINED
?>