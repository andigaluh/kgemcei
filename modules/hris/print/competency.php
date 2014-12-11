<?php
require_once('../../../config.php');
define('FPDF_FONTPATH',XOCP_DOC_ROOT.'/class/pdf/fpdf/font/');
require_once(XOCP_DOC_ROOT.'/class/pdf/fpdf/fpdf.php');
require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
require_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");

class _fpdf_CompetencyDictionary extends FPDF {
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
      $this->Cell( 0, 12, "Competency Dictionary",0,1,"R" );
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
      foreach($arr_comp as $competency_id) {
         //// select competency title / header
         $sql = "SELECT competency_nm,competency_abbr,competency_cd,compgroup_id,competency_class,desc_en,desc_id"
              . " FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
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
   
   function competency_title($competency_id) {
      $db=&Database::getInstance();
      $x0 = $this->GetX();
      $y0 = $this->GetY();
      
      //// select competency title / header
      $sql = "SELECT competency_nm,competency_abbr,competency_cd,compgroup_id,competency_class,desc_en,desc_id"
           . " FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($competency_nm,$competency_abbr,$competency_cd,$compgroup_id,$competency_class,$desc_en,$desc_id)=$db->fetchRow($result);
      
      $sql = "SELECT compgroup_nm FROM ".XOCP_PREFIX."compgroup WHERE compgroup_id = '$compgroup_id'";
      $result = $db->query($sql);
      list($compgroup_nm)=$db->fetchRow($result);
      
      $x1 = $x0;
      $y1 = $y0;
      
      //// Set position
      $this->SetFont('Arial','B',9);
      
      $w0 = 42;
      $w1 = 3;
      $w2 = 50;
      $h = 4;
      
      $this->SetXY( $x1, $y1 );
      $this->Cell($w0,$h,"Competency Name",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$competency_nm,0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Competency Abbreviation",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$competency_abbr,0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Competency Group",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$compgroup_nm,0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Competency Class",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,ucfirst($competency_class),0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Description",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->MultiCell(0,$h,$desc_en,0,"L");
      
      $this->Ln();
      
      $datay = $this->GetY();
   }
   
   function competency_definition($competency_id) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      global $proficiency_level_name;
      $db=&Database::getInstance();
      
      $h0 = 6;
      $h = 4;
      $w0 = 10;
      $w1 = 30;
      $w2 = 140;
      $wb0 = 8;
      $x0 = $this->GetX();
      $y0 = $this->GetY();
      $g = 2;
      
      $this->SetFont('Arial','B',10);
      $this->Cell($w0+$w1,$h0,"Proficiency Level","TLR",0,"C");
      $this->Cell($w2,$h0,"Behaviour Indicator","TLR",0,"C");
      $this->Ln($h0);
      
      $y0 = $this->GetY();
      $this->SetFont('Arial','',9);
      
      for($pl=4;$pl>=0;$pl--) {
         $sql = "SELECT behaviour_id,behaviour_en_txt,behaviour_id_txt"
              . " FROM ".XOCP_PREFIX."compbehaviour"
              . " WHERE competency_id = '$competency_id'"
              . " AND proficiency_lvl = '$pl'"
              . " ORDER BY proficiency_lvl DESC, behaviour_id";
         $result = $db->query($sql);
         $old_level = "";
         $bh_no = 1;
         if($db->getRowsNum($result)>0) {
            while(list($behaviour_id,$behaviour_en_txt,$behaviour_id_txt)=$db->fetchRow($result)) {
               if($old_level!=$pl) {
                  if($pl!=4) {
                     $this->Ln($g);
                  }
                  $this->Line($x0,$this->GetY(),$x0+$w0+$w1+$w2,$this->GetY());
                  $this->Ln($g);
                  $this->Cell($w0,$h,"$pl","",0,"C");
                  $this->Cell($w1,$h,$proficiency_level_name[$pl],"",0,"L");
                  $old_level = $pl;
                  $bh_no = 1;
                  $this->Cell($wb0,$h,"$bh_no.","",0,"R");
                  $this->MultiCell($w2-$wb0,$h,$behaviour_en_txt,"","L");
               } else {
                  $this->Ln($g);
                  $this->Cell($w0,$h,"","",0,"R");
                  $this->Cell($w1,$h,"","",0,"R");
                  $this->Cell($wb0,$h,"$bh_no.","",0,"R");
                  $this->MultiCell($w2-$wb0,$h,$behaviour_en_txt,"","L");
               }
               $bh_no++;
               
               /*
               $sql = "SELECT ca_id,q_en_txt,q_id_txt"
                    . " FROM ".XOCP_PREFIX."compbehaviour_qa"
                    . " WHERE competency_id = '$competency_id'"
                    . " AND proficiency_lvl = '$pl'"
                    . " AND behaviour_id = '$behaviour_id'"
                    . " ORDER BY ca_id";
               $rca = $db->query($sql);
               if($db->getRowsNum($rca)>0) {
                  $this->SetFont('Arial','B',9);
                  $this->Ln($h);
                  $this->Cell($w0,$h,"",0,0,"R");
                  $this->Cell(0,$h,"Competency Assessment :",0,1,"L");
                  $ca_no = 1;
                  $this->SetFont('Arial','',9);
                  while(list($ca_id,$q_en_txt,$q_id_txt)=$db->fetchRow($rca)) {
                     $this->Ln($h);
                     $this->Cell($w0,$h,"",0,0,"R");
                     $this->Cell(4,$h,"$ca_no.",0,0,"L");
                     $this->MultiCell(0,$h,$q_en_txt,0,"L");
                     $ca_no++;
                  }
               }
               */
            }
         } else {
            if($pl!=4) {
               $this->Ln($g);
            }
            $this->Line($x0,$this->GetY(),$x0+$w0+$w1+$w2,$this->GetY());
            $this->Ln($g);
            $this->Cell($w0,$h,"$pl","",0,"C");
            $this->Cell($w1,$h,$proficiency_level_name[$pl],"",0,"L");
            $old_level = $pl;
            $this->Cell($wb0,$h,"","",1,"R");
         }
         
         
      }
      
      $this->Ln($g);
      $y1 = $this->GetY();
      //// clean up line
      
      $this->Line($x0,$y0,$x0,$y1);
      $this->Line($x0+$w0,$y0,$x0+$w0,$y1);
      $this->Line($x0+$w0+$w1,$y0,$x0+$w0+$w1,$y1);
      $this->Line($x0+$w0+$w1+$w2,$y0,$x0+$w0+$w1+$w2,$y1);
      $this->Line($x0,$y1,$x0+$w0+$w1+$w2,$y1);
      
      
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
   
} // end of class _fpdf_CompetencyDictionary

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
   $sql = "SELECT competency_id,competency_class,compgroup_id FROM ".XOCP_PREFIX."competency"
        . " ORDER BY compgroup_id,competency_class,competency_abbr";
   $result = $db->query($sql);
   if($db->getRowsNum($result)>0) {
      while(list($competency_id,$competency_class,$compgroup_id)=$db->fetchRow($result)) {
         $arr_print[] = $competency_id;
      }
   }
} else {
   $competency_id = $_GET["cid"];
   $arr_print[] = $competency_id;
}

$pdf = new _fpdf_CompetencyDictionary( 'P', 'mm', 'A4' );
$pdf->Open();

$pdf->SetMargins(15,15,15,15);
global $page_count;
$page_count = 0;
$pdf->SetDrawColor(90,90,90);
$pdf->SetTextColor(80,80,80);
$pdf->SetAutoPageBreak(FALSE);
$pdf->AliasNbPages();

if(count($arr_print)>0) {
   if(isset($_GET["all"])) {
      $pdf->toc($arr_print);
   }
   foreach($arr_print as $competency_id) {
      $pdf->AddPage();
      $pdf->competency_title($competency_id);
      $pdf->competency_definition($competency_id);
   }
} else {
   $this->AddPage();
}

$pdf->Output();




