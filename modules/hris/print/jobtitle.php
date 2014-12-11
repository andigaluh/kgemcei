<?php
require_once('../../../config.php');
define('FPDF_FONTPATH',XOCP_DOC_ROOT.'/class/pdf/fpdf/font/');
require_once(XOCP_DOC_ROOT.'/class/pdf/fpdf/html2pdf2.php');
require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
require_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");

class _fpdf_JobTitle extends HTML2FPDF {
   // private variables
   var $columns;
   var $format;
   var $angle=0;
   var $page_c = 1;
   var $pages_ttl = 0;
   var $cols;
   var $obj_nm;
   var $mWriteTag;
   var $mBarcode;
   var $mRoundedRect;
   var $mWatermark;
   var $res;
   var $cntx;
   
   function Header() {
      global $custom_nama_rs,$custom_nama_unit,$custom_alamat,$custom_logo,$page_count;
      $this->company();
      $page_count++;
   }
   
   // Company
   function company() {
      $logo_image = XOCP_DOC_ROOT."/images/logo.png";
      $x0 = $x1 = 15;
      $y0 = $y1 = 5;
      // Set position
      $this->SetFont('Arial','B',10);
      $length1 = $this->GetStringWidth( $name1 );
      $img_size = getimagesize($logo_image);
      
      $ry = 16;
      $rx = $img_size[0] * ( $ry / $img_size[1] );
      $this->Image( $logo_image, $x1, $y1 + ((23-$ry)/2), $rx,$ry );
      $x1 = $x1 + $rx;
      
      $y1 += 4;
      $x1 = $x1 + 2;
      
      $this->SetXY( $x1, $y1 );
      $this->SetFont('Helvetica','',14);
      $this->Cell( 0, 12, "Job Description",0,1,"R" );
      $this->Line($x0,25,$this->w-15,25);
      $this->Ln(4);
      $this->Ln(4);
      
   }
   
   function toc($arr_comp) {
      $db=&Database::getInstance();
      $this->AddPage();
      $this->SetAutoPageBreak(TRUE,20);
      $x0 = $this->GetX();
      $y0 = $this->GetY();
      
      $this->Ln(3);
      $this->SetFont('Arial','B',10);
      $this->Cell(0,$th,"Table of Content",0,1,"L");
      $this->Ln(3);
      
      $old_group = "";
      $pagex = 3;
      foreach($arr_comp as $job_id) {
         //// select competency title / header
         $sql = "SELECT competency_nm,competency_abbr,competency_cd,compgroup_id,competency_class,desc_en,desc_id"
              . " FROM ".XOCP_PREFIX."competency WHERE job_id = '$job_id'";
         $result = $db->query($sql);
         list($competency_nm,$competency_abbr,$competency_cd,$compgroup_id,$competency_class,$desc_en,$desc_id)=$db->fetchRow($result);
         
         $sql = "SELECT compgroup_nm FROM ".XOCP_PREFIX."compgroup WHERE compgroup_id = '$compgroup_id'";
         $result = $db->query($sql);
         list($compgroup_nm)=$db->fetchRow($result);
         
         $x1 = $x0;
         $y1 = $y0;
         
         $w0 = 15;
         $w1 = 3;
         $w2 = 155;
         $h = 5;
         $th = 6;

         if($compgroup_id!=$old_group) {
            $this->Ln(3);
            $this->SetFont('Arial','B',9);
            $this->SetFillColor(220,220,220);
            $this->Cell(0,$th,$compgroup_nm,0,1,"L",1);
            $old_group = $compgroup_id;
            $old_class = "";
         }
         
         if($competency_class!=$old_class) {
            $this->Ln(2);
            $this->SetFont('Arial','B',9);
            $this->Cell(0,$h,ucfirst($competency_class),0,1,"L");
            $old_class = $competency_class;
            $this->Ln(2);
         }
         
         $this->SetFont('Arial','',9);
         $wx = $this->GetStringWidth($competency_nm)+2;
         $this->SetX($x1);
         $this->Cell($w0,$h,"",0,0);
         $this->Cell($w1,$h,"$competency_abbr",0,0,"R");
         $this->Cell($wx,$h,$competency_nm,0,0);
         $wt = $w2-$wx;
         for($i=$wt;$i>0;) {
            $this->Cell(1,$h,".","",0);
            $i-=1;
         }
         $this->Cell(0,$h,$pagex,0,0,"R");
         $pagex++;
         $this->Ln();
      }
      $this->SetAutoPageBreak(FALSE);
      
   }
   
   function getOrgsUp($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_id)=$db->fetchRow($result);
         $sql = "SELECT b.org_class_nm,a.org_class_id,a.org_nm FROM ".XOCP_PREFIX."orgs a LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id) WHERE a.org_id = '$parent_id'";
         $rs = $db->query($sql);
         list($org_class_nm,$org_class_id,$org_nm)=$db->fetchRow($rs);
         if($parent_id>0) {
            $_SESSION["hris_org_parents"][] = array($parent_id,$org_class_nm,$org_nm,$org_class_id);
            $this->getOrgsUp($parent_id);
         }
      }
   }
   
   function job_title($job_id) {
      $db=&Database::getInstance();
      $x0 = $this->GetX();
      $y0 = $this->GetY();
      
      //// select competency title / header
      $sql = "SELECT a.org_id,a.job_nm,a.job_cd,a.job_abbr,h.org_class_nm,b.org_nm,c.job_class_nm,c.job_class_abbr,"
           . "d.workarea_cd,d.workarea_nm,e.job_abbr,e.job_nm as 'superior',f.job_abbr,f.job_nm as 'assessor',g.peer_group_nm"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."orgs b USING (org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."job_class c USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."workarea d USING(workarea_id)"
           . " LEFT JOIN ".XOCP_PREFIX."jobs e ON e.job_id = a.upper_job_id"
           . " LEFT JOIN ".XOCP_PREFIX."jobs f ON f.job_id = a.assessor_job_id"
           . " LEFT JOIN ".XOCP_PREFIX."peer_group g ON g.peer_group_id = a.peer_group_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class h ON h.org_class_id = b.org_class_id"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      list($org_id,$job_nm,$job_cd,$job_abbr,$org_class_nm,$org_nm,$job_class_nm,$job_class_abbr,$workarea_cd,$workarea_nm,$superior_abbr,$superior_job,$assessor_abbr,
            $assessor_job,$peer_group_nm)=$db->fetchRow($result);
      
      
      $_SESSION["hris_org_parents"] = array();
      $_SESSION["hris_org_parents"][] = array($org_id,$org_class_nm,$org_nm);
      $this->getOrgsUp($org_id);
      
      $x1 = $x0;
      $y1 = $y0;
      
      //// Set position
      $this->SetFont('Arial','B',9);
      
      $w0 = 42;
      $w1 = 3;
      $w2 = 50;
      $h = 4;
      
      $this->SetXY( $x1, $y1 );
      $this->Cell($w0,$h,"Job Title",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$job_nm,0,1);
      
      /*
      $this->SetX($x1);
      $this->Cell($w0,$h,"Code",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$job_cd,0,1);
      */
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Abbreviation",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$job_abbr,0,1);
      
      krsort($_SESSION["hris_org_parents"]);
      foreach($_SESSION["hris_org_parents"] as $k=>$v) {
         list($org_id,$org_class_nm,$org_nm) = $v;
         $this->SetX($x1);
         $this->Cell($w0,$h,"$org_class_nm",0,0);
         $this->Cell($w1,$h,":",0,0);
         $this->Cell($w2,$h,"$org_nm",0,1);
      }
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Position Level",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,"$job_class_abbr - $job_class_nm",0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Work Area",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,"$workarea_cd - $workarea_nm",0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Superior",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,"$superior_abbr - $superior_job",0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Assessor",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,"$assessor_abbr - $assessor_job",0,1);
      
      $this->Ln();

   }
   
   function job_structure($job_id) {
      include_once(XOCP_DOC_ROOT."/modules/hris/class/mydiagram.php");
      $db=&Database::getInstance();
      $filename = "jobstruct_${job_id}.png";
      $xx = new DiagramX($job_id);
      $xx->render(XOCP_DOC_ROOT."/tmp/${filename}");
      $image = XOCP_DOC_ROOT."/tmp/${filename}";
      $x1 = $x0 = $this->GetX();
      $h = 4;
      
      $this->Line($x0,$this->GetY(),$this->w-15,$this->GetY());
      $this->Ln($h);
      $this->SetFont('Arial','B',9);
      $this->Cell(0,4,"Organization Structure",0,1);
      $this->Ln(4);
      $x = $this->GetX();
      $y = $this->GetY();
      $x0 = $x1 = $x;
      $y0 = $y1 = $y;
      
      $img_size = getimagesize($image);
      $max_x = $this->w-$this->lMargin-$this->rMargin;
      $rx = $img_size[0] * 0.23;
      if($rx>$max_x) $rx = $max_x;
      $x1 = $x + ($max_x-$rx)/2;
      $ry = $img_size[1] * ($rx / $img_size[0]);
      $this->Image( $image, $x1, $y1, $rx,$ry );
      $this->SetY($y+$ry);
      $this->Ln($h);
   }
   
   function job_description($job_id) {
      $db=&Database::getInstance();
      $sql = "SELECT summary,summary_id_txt,description,description_id_txt FROM ".XOCP_PREFIX."jobs WHERE job_id = '$job_id'";
      $result = $db->query($sql);
      list($summary,$summary_id_txt,$description,$description_id_txt)=$db->fetchRow($result);
      
      $x1 = $x0 = $this->GetX();
      $h = 4;
      
      $this->Line($x0,$this->GetY(),$this->w-15,$this->GetY());
      $this->Ln();
     
      $this->SetX($x1);
      $this->Cell(0,$h,"Summary :",0,1);
      $this->Ln();
      $this->SetFont('Helvetica','',9);
      $summary = str_replace("\n","",$summary);
      $summary = str_replace("\r","",$summary);
      $summary = str_replace("\t","",$summary);
      
      $this->pgwidth = $this->fw - $this->lMargin - $this->rMargin;
      
      $this->pjustfinished = true;
      $this->WriteHTML($summary,TRUE);
      
      $this->Ln();
      $this->Line($x0,$this->GetY(),$this->w-15,$this->GetY());
      $this->Ln();
      
      $this->SetTextColor(0);
      $this->SetX($x1);
      $this->SetFont('Helvetica','B',9);
      $this->Cell($w0,$h,"Duties and Responsibilities :",0,1);
      $this->Ln();
      $this->SetFont('Helvetica','',9);
      $description = str_replace("\n","",$description);
      $description = str_replace("\r","",$description);
      $description = str_replace("\t","",$description);
      
      $this->pjustfinished = true;
      $this->WriteHTML($description,TRUE);
      
      $this->Ln();
      
   }
   
   function competency_profile($job_id) {
      $db=&Database::getInstance();
      $this->SetTextColor(0);
      
      $x0 = $this->GetX();
      $y0 = $this->GetY();
      
      $x1 = $x0 = $this->GetX();
      $h = 4;
      
      $this->Line($x0,$this->GetY(),$this->w-15,$this->GetY());
      $this->Ln($h);
     
      $this->SetFont('Arial','B',9);
      $this->Cell(0,4,"Competency Profile",0,1);
      //$this->Ln(4);
      
      $col_comp_c = 17;
      $col_comp_nm = 120;
      $col_sep = 3;
      $col_val = 10;
      
      $ch = 5;
      
      $this->SetFillColor(240,240,240);
      
      $this->SetFont('Arial','',9);
      
      /////////////////////////////////////////////
      
      $this->SetLeftMargin(25);
      
      $sql = "SELECT a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,(b.competency_class+0) as urcl"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " WHERE a.job_id = '$job_id'"
           . " ORDER BY b.compgroup_id,urcl";
      $result = $db->query($sql);
      $oldcompgroup = "";
      if($db->getRowsNum($result)>0) {
         
         $this->Cell($col_comp_c,$ch,"",0,0);
         $this->Cell($col_comp_nm,$ch,"",0,0);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0);
         
         $this->Ln();
         $this->Cell($col_comp_c,$ch,"",0,0);
         $this->Cell($col_comp_nm,$ch,"",0,0);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"ITJ","TLR",0,"C");
         $this->Cell($col_val,$ch,"RCL","TLR",0,"C");
         
         
         while(list($competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$urcl)=$db->fetchRow($result)) {
            $cc = ucfirst($cc);
            
            ////
            if($oldcompgroup!=$compgroup_nm) {
               $this->Ln();
               $this->SetFont('Arial','B',9);
               $this->Cell($col_comp_c+$col_comp_nm+(1*$col_sep)+(2*$col_val),$ch,"$compgroup_nm",1,0,"L",1);
               $this->SetFont('Arial','',9);
               $oldcompgroup = $compgroup_nm;
               $oldcc = "";
            }
            if($oldcc!=$cc) {
               $cctxt = $cc;
               $ccborder = "TL";
               $oldcc = $cc;
            } else {
               $cctxt = "";
               $ccborder = "L";
            }
            $this->SetTextColor(0);
            
            $this->Ln();
            $this->Cell($col_comp_c,$ch," $cctxt",$ccborder,0);
            
            $this->Cell($col_comp_nm,$ch," $competency_nm","TLR",0);
            $this->Cell($col_sep,$ch,"",0,0);
            $this->Cell($col_val,$ch,"$itj","TLR",0,"C");
            $this->Cell($col_val,$ch,"$rcl","TLR",0,"C");
            
            $ttlrcl += ($rcl*$itj);
            $this->SetTextColor(0);
         }
         
         /////////////////////////////////////////////
         $this->Ln();
         $this->Cell($col_comp_c,$ch,"","T",0);
         $this->Cell($col_comp_nm,$ch,"","T",0);
         $this->Cell($col_sep,$ch,"","T",0);
         $this->Cell($col_val,$ch,"","T",0,"C");
         $this->Cell($col_val,$ch,"","T",0,"C");
         
         
      } else {
         $this->SetTextColor(255,0,0);
         $this->Ln(4);
         $this->Ln(4);
         $this->Cell(0,4,"No competency found.",0,1);
         $this->Ln(4);
         $this->SetTextColor(0);
         
      }
      $this->SetLeftMargin(15);
      $this->SetFillColor(255,255,255);
   }
   
   
   function Footer() {
      global $login_nm,$user_nm,$user_nip;
      //Position at 1.5 cm from bottom
      $this->SetY(-15);
      //Arial italic 8
      $this->SetFont('Arial','',8);
      //Page number
      $this->Cell(100,10,"Generated : HRIS ".sql2ind(getSQLDate())." by $user_nm ($user_nip)",0,0,'L');
      $this->Cell(0,10,"Page ".$this->PageNo().'/{nb}',0,0,'R');
   }
   
} // end of class _fpdf_JobTitle

$db=&Database::getInstance();

global $login_nm,$user_nm,$user_nip;

$user_id = getUserID();

$sql = "SELECT a.user_nm,b.person_nm,c.employee_ext_id"
     . " FROM ".XOCP_PREFIX."users a"
     . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
     . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.person_id = b.person_id"
     . " WHERE a.user_id = '$user_id'";
$result = $db->query($sql);
list($login_nm,$user_nm,$user_nip)=$db->fetchRow($result);



$arr_print = array();

if(isset($_GET["all"])&&$_GET["all"]>0) {
   $sql = "SELECT a.job_id FROM ".XOCP_PREFIX."jobs a"
        . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
        . " WHERE a.status_cd = 'normal'"
        . " ORDER BY b.job_class_level";
   $result = $db->query($sql);
   if($db->getRowsNum($result)>0) {
      while(list($job_id)=$db->fetchRow($result)) {
         $arr_print[] = $job_id;
      }
   }
} else {
   $job_id = $_GET["jid"];
   $arr_print[] = $job_id;
}

$pdf = new _fpdf_JobTitle( 'P', 'mm', 'A4' );
$pdf->Open();

$pdf->SetMargins(15,15,15,15);
global $page_count;
$page_count = 0;
$pdf->SetDrawColor(0);
$pdf->SetTextColor(0);
$pdf->SetAutoPageBreak(TRUE,20);
$pdf->AliasNbPages();

if(count($arr_print)>0) {
   if(isset($_GET["all"])) {
      //$pdf->toc($arr_print);
   }
   foreach($arr_print as $job_id) {
      $pdf->AddPage();
      $pdf->job_title($job_id);
      $pdf->job_structure($job_id);
      $pdf->job_description($job_id);
      $pdf->competency_profile($job_id);
   }
} else {
   $this->AddPage();
}

$pdf->Output();




