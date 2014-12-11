<?php
require_once("../../config.php");
require_once(XOCP_DOC_ROOT."/class/PEAR/Spreadsheet/Excel/Writer.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/assessment.php");
//////////////////////////////////////////////////////////////////////////////////
$asid = $_GET["asid"];
$poslevel_id = $_GET["poslevel_id"];
$division = $_GET["division"];
$subdivision = $_GET["subdivision"];

//////////////////////////////////////////////////////////////////////////////////
$workbook = new Spreadsheet_Excel_Writer();

$workbook->setCustomColor(12, 192, 255, 200);
$workbook->setCustomColor(11, 253, 255, 142);
$workbook->setCustomColor(10, 192, 255, 200);

$format_bold =& $workbook->addFormat();
$format_bold->setBold();

$format_title_merge =& $workbook->addFormat();
$format_title_merge->setBold();
$format_title_merge->setColor('black');
$format_title_merge->setPattern(1);
$format_title_merge->setFgColor("white");
$format_title_merge->setAlign("left");
$format_title_merge->setAlign("vcenter");
$format_title_merge->setAlign('merge');
$format_title_merge->setSize(8);

$format_jobclass =& $workbook->addFormat();
$format_jobclass->setBold();
$format_jobclass->setColor('black');
$format_jobclass->setPattern(1);
$format_jobclass->setFgColor(11);
$format_jobclass->setAlign("left");
$format_jobclass->setAlign("vcenter");
$format_jobclass->setAlign('merge');
$format_jobclass->setSize(8);
$format_jobclass->setBorder(2);

$format_title =& $workbook->addFormat();
$format_title->setBold();
$format_title->setColor('black');
$format_title->setPattern(1);
$format_title->setFgColor(12);
$format_title->setAlign("center");
$format_title->setAlign("vcenter");
$format_title->setSize(8);
$format_title->setBorder(2);
$format_title->setBorderColor("black");

$fmt_compgroup[1] =& $workbook->addFormat();
$fmt_compgroup[1]->setBold();
$fmt_compgroup[1]->setColor('black');
$fmt_compgroup[1]->setPattern(1);
$fmt_compgroup[1]->setFgColor(12);
$fmt_compgroup[1]->setAlign("left");
$fmt_compgroup[1]->setAlign("vcenter");
$fmt_compgroup[1]->setAlign('merge');
$fmt_compgroup[1]->setSize(8);
$fmt_compgroup[1]->setBorder(2);

$fmt_compgroup[2] =& $workbook->addFormat();
$fmt_compgroup[2]->setBold();
$fmt_compgroup[2]->setColor('black');
$fmt_compgroup[2]->setPattern(1);
$fmt_compgroup[2]->setFgColor(12);
$fmt_compgroup[2]->setAlign("left");
$fmt_compgroup[2]->setAlign("vcenter");
$fmt_compgroup[2]->setAlign('merge');
$fmt_compgroup[2]->setSize(8);
$fmt_compgroup[2]->setBorder(2);

$fmt_compgroup[3] =& $workbook->addFormat();
$fmt_compgroup[3]->setBold();
$fmt_compgroup[3]->setColor('black');
$fmt_compgroup[3]->setPattern(1);
$fmt_compgroup[3]->setFgColor(12);
$fmt_compgroup[3]->setAlign("left");
$fmt_compgroup[3]->setAlign("vcenter");
$fmt_compgroup[3]->setAlign('merge');
$fmt_compgroup[3]->setSize(8);
$fmt_compgroup[3]->setBorder(2);

$fmt_compclass[1]["soft"] =& $workbook->addFormat();
$fmt_compclass[1]["soft"]->setBold();
$fmt_compclass[1]["soft"]->setColor('black');
$fmt_compclass[1]["soft"]->setPattern(1);
$fmt_compclass[1]["soft"]->setFgColor(12);
$fmt_compclass[1]["soft"]->setAlign("left");
$fmt_compclass[1]["soft"]->setAlign("vcenter");
$fmt_compclass[1]["soft"]->setAlign('merge');
$fmt_compclass[1]["soft"]->setSize(8);
$fmt_compclass[1]["soft"]->setBorder(2);

$fmt_compclass[1]["technical"] =& $workbook->addFormat();
$fmt_compclass[1]["technical"]->setBold();
$fmt_compclass[1]["technical"]->setColor('black');
$fmt_compclass[1]["technical"]->setPattern(1);
$fmt_compclass[1]["technical"]->setFgColor(12);
$fmt_compclass[1]["technical"]->setAlign("left");
$fmt_compclass[1]["technical"]->setAlign("vcenter");
$fmt_compclass[1]["technical"]->setAlign('merge');
$fmt_compclass[1]["technical"]->setSize(8);
$fmt_compclass[1]["technical"]->setBorder(2);

$fmt_compclass[2]["soft"] =& $workbook->addFormat();
$fmt_compclass[2]["soft"]->setBold();
$fmt_compclass[2]["soft"]->setColor('black');
$fmt_compclass[2]["soft"]->setPattern(1);
$fmt_compclass[2]["soft"]->setFgColor(12);
$fmt_compclass[2]["soft"]->setAlign("left");
$fmt_compclass[2]["soft"]->setAlign("vcenter");
$fmt_compclass[2]["soft"]->setAlign('merge');
$fmt_compclass[2]["soft"]->setSize(8);
$fmt_compclass[2]["soft"]->setBorder(2);

$fmt_compclass[2]["technical"] =& $workbook->addFormat();
$fmt_compclass[2]["technical"]->setBold();
$fmt_compclass[2]["technical"]->setColor('black');
$fmt_compclass[2]["technical"]->setPattern(1);
$fmt_compclass[2]["technical"]->setFgColor(12);
$fmt_compclass[2]["technical"]->setAlign("left");
$fmt_compclass[2]["technical"]->setAlign("vcenter");
$fmt_compclass[2]["technical"]->setAlign('merge');
$fmt_compclass[2]["technical"]->setSize(8);
$fmt_compclass[2]["technical"]->setBorder(2);

$fmt_compclass[3]["soft"] =& $workbook->addFormat();
$fmt_compclass[3]["soft"]->setBold();
$fmt_compclass[3]["soft"]->setColor('black');
$fmt_compclass[3]["soft"]->setPattern(1);
$fmt_compclass[3]["soft"]->setFgColor(12);
$fmt_compclass[3]["soft"]->setAlign("left");
$fmt_compclass[3]["soft"]->setAlign("vcenter");
$fmt_compclass[3]["soft"]->setAlign('merge');
$fmt_compclass[3]["soft"]->setSize(8);
$fmt_compclass[3]["soft"]->setBorder(2);

$fmt_compclass[3]["technical"] =& $workbook->addFormat();
$fmt_compclass[3]["technical"]->setBold();
$fmt_compclass[3]["technical"]->setColor('black');
$fmt_compclass[3]["technical"]->setPattern(1);
$fmt_compclass[3]["technical"]->setFgColor(12);
$fmt_compclass[3]["technical"]->setAlign("left");
$fmt_compclass[3]["technical"]->setAlign("vcenter");
$fmt_compclass[3]["technical"]->setAlign('merge');
$fmt_compclass[3]["technical"]->setSize(8);
$fmt_compclass[3]["technical"]->setBorder(2);



$format_count =& $workbook->addFormat();
$format_count->setColor('black');
$format_count->setAlign("left");
$format_count->setAlign("vcenter");
$format_count->setSize(8);

$format_value_center =& $workbook->addFormat();
$format_value_center->setBold();
$format_value_center->setColor('black');
$format_value_center->setPattern(1);
$format_value_center->setFgColor("white");
$format_value_center->setAlign("center");
$format_value_center->setAlign("vcenter");
$format_value_center->setSize(8);
$format_value_center->setBorder(1);
$format_value_center->setBorderColor("black");

$format_value_left =& $workbook->addFormat();
$format_value_left->setBold();
$format_value_left->setColor('black');
$format_value_left->setPattern(1);
$format_value_left->setFgColor("white");
$format_value_left->setAlign("left");
$format_value_left->setAlign("vcenter");
$format_value_left->setSize(8);
$format_value_left->setBorder(1);
$format_value_left->setBorderColor("black");

$format_value_assessor =& $workbook->addFormat();
//$format_value_assessor->setBold();
$format_value_assessor->setItalic();
$format_value_assessor->setColor('black');
$format_value_assessor->setPattern(1);
$format_value_assessor->setFgColor("white");
$format_value_assessor->setAlign("left");
$format_value_assessor->setAlign("vcenter");
$format_value_assessor->setSize(8);
$format_value_assessor->setBorder(1);
$format_value_assessor->setBorderColor("black");

$format_value_avg =& $workbook->addFormat();
//$format_value_avg->setBold();
$format_value_avg->setColor('black');
$format_value_avg->setPattern(1);
$format_value_avg->setFgColor("white");
$format_value_avg->setAlign("right");
$format_value_avg->setAlign("vcenter");
$format_value_avg->setSize(8);
$format_value_avg->setBorder(1);
$format_value_avg->setBorderColor("black");

$format_value_right =& $workbook->addFormat();
$format_value_right->setBold();
$format_value_right->setColor('black');
$format_value_right->setPattern(1);
$format_value_right->setFgColor("white");
$format_value_right->setAlign("right");
$format_value_right->setAlign("vcenter");
$format_value_right->setSize(8);
$format_value_right->setBorder(1);
$format_value_right->setBorderColor("black");

////////////// INIT WORKSHEET FORM 1A ////////////////////////
$sheet =& $workbook->addWorksheet("Result");


$db=&Database::getInstance();

/// write title
$sheet->write(0, 0, "Assessment Result Matrix", $format_title_merge);
$sheet->write(0, 1, "", $format_title_merge);
$sheet->write(0, 2, "", $format_title_merge);
$sheet->write(0, 3, "", $format_title_merge);
$sheet->write(0, 4, "", $format_title_merge);

////////////////// header information ///////////////////////
/// asid
$sql = "SELECT session_nm,session_periode FROM ".XOCP_PREFIX."assessment_session"
     . " WHERE asid = '$asid'";
$result = $db->query($sql);
if($db->getRowsNum($result)>0) {
   list($session_nm,$session_periode)=$db->fetchRow($result);
   $asid_nm = "$session_periode $session_nm";
} else {
   $asid_nm = "";
}
$sheet->write(2, 0, "Assessment Session :",$format_count);
$sheet->write(2, 1, "$asid_nm",$format_count);
/// division
$division_nm = "All";
if($division>0) {
   $sql = "SELECT org_nm FROM ".XOCP_PREFIX."orgs WHERE org_id = '$division'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)>0) {
      list($division_nm)=$db->fetchRow($result);
   }
}
$sheet->write(3, 0, "Division :",$format_count);
$sheet->write(3, 1, "$division_nm",$format_count);
/// subdivision
$subdivision_nm = "All";
if($subdivision>0) {
   $sql = "SELECT org_nm FROM ".XOCP_PREFIX."orgs WHERE org_id = '$subdivision'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)>0) {
      list($subdivision_nm)=$db->fetchRow($result);
   }
}
$sheet->write(4, 0, "Section :",$format_count);
$sheet->write(4, 1, "$subdivision_nm",$format_count);
/// poslevel
$poslevel_nm = "All";
if($poslevel_id>0) {
   $sql = "SELECT job_class_nm FROM ".XOCP_PREFIX."job_class WHERE job_class_id = '$poslevel_id'";
   $result = $db->query($sql);
   if($db->getRowsNum($result)>0) {
      list($poslevel_nm)=$db->fetchRow($result);
   }
}
$sheet->write(5, 0, "Position Level :",$format_count);
$sheet->write(5, 1, "$poslevel_nm",$format_count);

$rowno = 8;

/// tblheader
$sheet->write($rowno,0,"Employee",$format_title);
$sheet->write($rowno,1,"NIP",$format_title);
$sheet->write($rowno,2,"Job\nTitle",$format_title);
$sheet->write($rowno,3,"Section",$format_title);
$sheet->write($rowno,4,"Grade",$format_title);
$sheet->write($rowno,5,"Job\nMatch\n%",$format_title);
$sheet->write($rowno,6,"Comp.\nFit\n%",$format_title);
$sheet->setRow($rowno,40);

$sql = "SELECT a.competency_id,a.competency_abbr,a.competency_nm,a.compgroup_id,b.compgroup_nm,a.competency_class"
     . " FROM ".XOCP_PREFIX."competency a"
     . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
     . " WHERE a.status_cd = 'normal'"
     . " ORDER BY a.compgroup_id,a.competency_class,a.xurut01";
$result = $db->query($sql);
$colx = 7;
$arrcomp = array();
if($db->getRowsNum($result)>0) {
   while(list($competency_id,$competency_abbr,$competency_nm,$compgroup_id,$compgroup_nm,$competency_class)=$db->fetchRow($result)) {
      $sheet->write($rowno,$colx,$competency_abbr,$format_title);
      $sheet->setColumn($rowno,$colx,6);
      $arrcomp[$competency_id] = array($competency_abbr,$competency_nm,$compgroup_id,$compgroup_nm,$competency_class);
      $colx++;
   }
}

$xrow = 6;
$colx = 7;
$oldcomp = 0;
foreach($arrcomp as $competency_id=>$v) {
   list($competency_abbr,$competency_nm,$compgroup_id,$compgroup_nm,$competency_class)=$v;
   if($oldcomp!=$compgroup_id) {
      $sheet->write($xrow,$colx,$compgroup_nm,$fmt_compgroup[$compgroup_id]);
   } else {
      $sheet->write($xrow,$colx,"",$fmt_compgroup[$compgroup_id]);
   }
   $oldcomp = $compgroup_id;
   $colx++;
}
$sheet->setRow($xrow,24);

$xrow = 7;
$colx = 7;
$oldcomp = 0;
$oldcc = "";
foreach($arrcomp as $competency_id=>$v) {
   list($competency_abbr,$competency_nm,$compgroup_id,$compgroup_nm,$competency_class)=$v;
   if($oldcomp!=$compgroup_id||$oldcc!=$competency_class) {
      $sheet->write($xrow,$colx,ucfirst($competency_class),$fmt_compclass[$compgroup_id][$competency_class]);
   } else {
      $sheet->write($xrow,$colx,"",$fmt_compclass[$compgroup_id][$competency_class]);
   }
   $oldcomp = $compgroup_id;
   $oldcc = $competency_class;
   $colx++;
}
$sheet->setRow($xrow,24);

$rowno++;
$old_job_class_id = 0;
$ass_type = array("superior","peer","customer","subordinat");
if(isset($_SESSION["result_matrix"])&&is_array($_SESSION["result_matrix"])) {
   foreach($_SESSION["result_matrix"] as $k=>$v) {
      list($job_class_id,$job_class_nm,$job_id,$job_nm,$job_abbr,$jlvl,$summary,$org_nm,$org_abbr,$org_class_nm,$nip,$employee_id,$employee_nm,$person_id,$cf_pass_cnt,$cf_cnt,$pr_value,$ttlccl,$ttlrcl,$match,$cf)=$v;
      if($employee_id<=0) continue;
      
      
      if($old_job_class_id!=$job_class_id) {
         $sheet->write($rowno,0,$job_class_nm,$format_jobclass);
         
         $sheet->write($rowno,1,"",$format_jobclass);
         $sheet->write($rowno,2,"",$format_jobclass);
         $sheet->write($rowno,3,"",$format_jobclass);
         $sheet->write($rowno,4,"",$format_jobclass);
         $sheet->write($rowno,5,"",$format_jobclass);
         $sheet->write($rowno,6,"",$format_jobclass);
         $sheet->setRow($rowno,20);
         $old_job_class_id = $job_class_id;
         $rowno++;
      }
      if($employee_nm=="-") {
         $match = "-";
         $cf = "-";
      }
      
      $sql = "SELECT gradeval FROM ".XOCP_PREFIX."employee_job WHERE employee_id = '$employee_id' AND job_id = '$job_id'";
      $result = $db->query($sql);
      list($grade)=$db->fetchRow($result);
      
      
      /////////////// employee data /////////////////////////////////
      $sheet->write($rowno,0,$employee_nm,$format_value_left);
      $sheet->write($rowno,1,$nip,$format_value_center);
      $sheet->write($rowno,2,$job_nm,$format_value_left);
      $sheet->write($rowno,3,$org_abbr,$format_value_left);
      $sheet->write($rowno,4,$grade,$format_value_center);
      $sheet->write($rowno,5,$match,$format_value_right);
      $sheet->write($rowno,6,$cf,$format_value_right);
      
      $colx = 7;
      foreach($arrcomp as $competency_id=>$v) {
         list($competency_abbr,$competency_nm,$compgroup_id,$compgroup_nm,$competency_class)=$v;
         if(isset($_SESSION["mtrx_ccl"][$employee_id][$job_id][$competency_id])) {
            list($employee_idx,$job_id,$competency_idx,$ccl,$rcl,$itj,$gap,$job_class_id)=$_SESSION["mtrx_ccl"][$employee_id][$job_id][$competency_id];
         } else {
            $ccl = "";
         }
         $sheet->write($rowno,$colx,$ccl,$format_value_right);
         $colx++;
      }
      $rowno++;
      
      ////////////// assessor data ///////////////////////////////
      
      $last_rowass = 0;
      $rowass = 0;
      foreach($ass_type as $assessor_t) {
         if($assessor_t=="subordinat") {
            $assessor_tx = "subordinate";
         } else {
            $assessor_tx = $assessor_t;
         }
         $sheet->write($rowno+$rowass,0,$assessor_tx,$format_value_assessor);
         $rowass++;
      }
      $last_rowass = max($rowass,$last_rowass);
      
      $colx = 7;
      foreach($arrcomp as $competency_id=>$v) {
         //list($arr0,$arr1,$arr2,$arr3,$arravg) = _get_arrccl($asid,$employee_id,$competency_id,$job_id); /// metode hitung yang lama
         list($arrccl_,$arrasr_,$calc_ccl_,$original_calc_ccl_,$arravg,$arrcclxxx,$calc_cclxxx,$arravgxxx,$arrasrxxx)=_get_arrccl($asid,$employee_id,$competency_id,$job_id);
         $rowass = 0;
         foreach($ass_type as $assessor_t) {
            $ccl_avg = $arravgxxx[$assessor_t];
            $sheet->write($rowno+$rowass,$colx,$ccl_avg,$format_value_avg);
            $rowass++;
         }
         $colx++;
      }
      $rowno+=$last_rowass;
      
   }
}

$sheet->setColumn(8,0,17);
$sheet->setColumn(8,1,9);
$sheet->setColumn(8,2,8);
$sheet->setColumn(8,3,6);
$sheet->setColumn(8,4,6);

$sheet->setSelection(0,0,0,0);

$workbook->send("resultmatrix.xls");
$workbook->close();

//////////////////////////////////////////////////////////////////////////////////

?>