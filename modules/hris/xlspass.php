<?php
require_once("../../config.php");
require_once(XOCP_DOC_ROOT."/class/PEAR/Spreadsheet/Excel/Writer.php");

$db=&Database::getInstance();

$asid = $_GET["asid"];

$sql = "SELECT session_nm,session_periode FROM ".XOCP_PREFIX."assessment_session WHERE asid = '$asid'";
$result = $db->query($sql);
list($session_nm,$session_periode)=$db->fetchRow($result);

//////////////////////////////////////////////////////////////////////////////////
$workbook = new Spreadsheet_Excel_Writer();

$workbook->setCustomColor(12, 192, 255, 200);
$workbook->setCustomColor(11, 253, 255, 142);

$format_bold =& $workbook->addFormat();
$format_bold->setBold();

$format_title_merge =& $workbook->addFormat();
$format_title_merge->setBold();
$format_title_merge->setColor('black');
$format_title_merge->setPattern(1);
$format_title_merge->setFgColor("white");
$format_title_merge->setAlign("center");
$format_title_merge->setAlign("vcenter");
$format_title_merge->setAlign('merge');
$format_title_merge->setSize(8);

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
$sheet =& $workbook->addWorksheet("Assessor's Password");


   $sheet->write(0, 0, "Assessor Password for : $session_nm / $session_periode", $format_title_merge);
   $sheet->write(0, 1, "", $format_title_merge);
   $sheet->write(0, 2, "", $format_title_merge);
   $sheet->write(0, 3, "", $format_title_merge);
   $sheet->write(0, 4, "", $format_title_merge);
   $sheet->write(0, 5, "", $format_title_merge);
   $sheet->write(0, 6, "", $format_title_merge);
   $sheet->write(0, 7, "", $format_title_merge);
   
   $sheet->write(3, 0, "No", $format_title);
   $sheet->write(3, 1, "NIP", $format_title);
   $sheet->write(3, 2, "Name", $format_title);
   $sheet->write(3, 3, "Job Title", $format_title);
   $sheet->write(3, 4, "Position Level", $format_title);
   $sheet->write(3, 5, "Division/Section", $format_title);
   $sheet->write(3, 6, "Login", $format_title);
   $sheet->write(3, 7, "Password", $format_title);
   
   $sql = "SELECT b.employee_ext_id,c.person_nm,e.job_nm,h.job_class_nm,f.org_nm,g.org_class_nm,a.pwd1"
        . " FROM ".XOCP_PREFIX."assessor_pass a"
        . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
        . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
        . " LEFT JOIN ".XOCP_PREFIX."employee_job d ON d.employee_id = b.employee_id"
        . " LEFT JOIN ".XOCP_PREFIX."jobs e USING(job_id)"
        . " LEFT JOIN ".XOCP_PREFIX."orgs f USING(org_id)"
        . " LEFT JOIN ".XOCP_PREFIX."org_class g USING(org_class_id)"
        . " LEFT JOIN ".XOCP_PREFIX."job_class h ON h.job_class_id = e.job_class_id"
        . " WHERE a.asid = '$asid'"
        . " ORDER BY g.order_no,h.gradeval_bottom DESC";
   $result = $db->query($sql);
   $no = 1;
   $rowno = 4;
   if($db->getRowsNum($result)>0) {
      while(list($nip,$name,$job_nm,$job_class_nm,$org_nm,$org_class_nm,$pwd1)=$db->fetchRow($result)) {
         if($job_nm=="") continue;
         $sheet->write($rowno,0,$no,$format_value_left);
         $sheet->writeString($rowno,1,$nip,$format_value_left);
         $sheet->writeString($rowno,2,$name,$format_value_left);
         $sheet->writeString($rowno,3,$job_nm,$format_value_left);
         $sheet->writeString($rowno,4,$job_class_nm,$format_value_left);
         $sheet->writeString($rowno,5,"$org_nm $org_class_nm",$format_value_left);
         $sheet->writeString($rowno,6,$nip,$format_value_left);
         $sheet->writeString($rowno,7,"$pwd1",$format_value_left);
         $rowno++;
         $no++;
      }
   }
   

$sheet->setColumn(4,0,3);
$sheet->setColumn(4,1,8);
$sheet->setColumn(4,2,30);
$sheet->setColumn(4,3,25);
$sheet->setColumn(4,4,15);
$sheet->setColumn(4,5,15);
$sheet->setColumn(4,6,10);
$sheet->setColumn(4,7,10);

$sheet->setSelection(0,0,0,0);

$workbook->send("pass_${asid}.xls");
$workbook->close();

//////////////////////////////////////////////////////////////////////////////////

