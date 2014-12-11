<?php
require_once('../../../config.php');
define('FPDF_FONTPATH',XOCP_DOC_ROOT.'/class/pdf/fpdf/font/');
require_once(XOCP_DOC_ROOT.'/class/pdf/fpdf/fpdf.php');
require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/idp/idp.php");
require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
require_once(XOCP_DOC_ROOT.'/class/pdf/fpdf/scripts/roundedrect.php');

class _fpdf_IDPRequest extends FPDF {
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
   
   function NbLines($w, $txt) {
      //Computes the number of lines a MultiCell of width w will take
      $cw=&$this->CurrentFont['cw'];
      if($w==0) $w=$this->w-$this->rMargin-$this->x;
      $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
      $s=str_replace("\r", '', $txt);
      $nb=strlen($s);
      if($nb>0 and $s[$nb-1]=="\n") $nb--;
      $sep=-1;
      $i=0;
      $j=0;
      $l=0;
      $nl=1;
      while($i<$nb) {
         $c=$s[$i];
         if($c=="\n") {
            $i++;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
            continue;
         }
         if($c==' ') $sep=$i;
         $l+=$cw[$c];
         if($l>$wmax) {
            if($sep==-1) {
               if($i==$j) $i++;
            } else $i=$sep+1;
            $sep=-1;
            $j=$i;
            $l=0;
            $nl++;
         } else $i++;
      }
      return $nl;
   }
   
   
   function formInit() { // setup extensions
      // rounded rectangle
      $this->mRoundedRect = new _fpdf_RoundedRect($this);
   }
   
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
      $this->Cell( 0, 12, "Assessment Result Chart",0,1,"R" );
      $this->Line($x0,25,$this->w-15,25);
      $this->Ln(4);
      $this->Ln(4);
   }
   
   function incumbent() {
      global $picy,$datay;
      global $division_id,$section_id,$position_level;
      $db=&Database::getInstance();
      $sql = "SELECT employee_id,current_job_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id,$job_id)=$db->fetchRow($result);
      $x0 = $this->GetX();
      $y0 = $this->GetY();
      
      $y1 = $y0;
      $x1 = $x0;
      
      if($division_id>0) {
         $sql = "SELECT a.org_nm,b.org_class_nm"
              . " FROM ".XOCP_PREFIX."orgs a"
              . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
              . " WHERE a.org_id = '$division_id'";
         $result = $db->query($sql);
         list($division_nm,$division_class_nm)=$db->fetchRow($result);
      } else {
         $division_nm = "All";
      }
      
      if($section_id>0) {
         $sql = "SELECT a.org_nm,b.org_class_nm"
              . " FROM ".XOCP_PREFIX."orgs a"
              . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
              . " WHERE a.org_id = '$section_id'";
         $result = $db->query($sql);
         list($section_nm,$section_class_nm)=$db->fetchRow($result);
      } else {
         $section_nm = "All";
      }
      
      if($position_level>0) {
         $sql = "SELECT a.job_class_nm"
              . " FROM ".XOCP_PREFIX."job_class a"
              . " WHERE a.job_class_id = '$position_level'";
         $result = $db->query($sql);
         list($job_class_nm)=$db->fetchRow($result);
      } else {
         $job_class_nm = "All";
      }
      
      $x1 = $x1 + $rx + 6;
      
      $w0 = 25;
      $w1 = 3;
      $w2 = 50;
      $h = 4;
      
      $this->SetFont("Arial","",9);
      $this->SetXY( $x1, $y1 );
      
      $this->Cell($w0,$h,"Division",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$division_nm,0,1);
      
      $this->SetX( $x1 );
      $this->Cell($w0,$h,"Section/Unit",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$section_nm,0,1);
      
      $this->SetX( $x1 );
      $this->Cell($w0,$h,"Position Level",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$job_class_nm,0,1);
      
      $datay = $this->GetY();
      
   }
   
   function idp_request() {
      global $picy,$datay;
      global $employee_id,$job_id,$employee_nm;
      $db=&Database::getInstance();
      require_once(XOCP_DOC_ROOT."/modules/hris/include/hris.php");
      $user_id = getUserID();
      $assessor_job_count = 0;
      global $proficiency_level_name;
      
      $x0 = $this->GetX();
      $y0 = max($picy,$datay);
      $bottom_y = 190;
      $this->SetXY($x0,$y0);
      $this->Ln(4);
      $this->SetFont('Arial','B',10);
      $x0 = $this->GetX();
      $ch = 4;
      
      $myw = $this->w-30;
      $cw = floor($myw/2);
      $myw = 2*$cw;
      ///////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////
      
      list($orgs,$param,$divarr)=$_SESSION["var_print"];
      
      _dumpvar($_SESSION["tmp_file"]);
      
      $no = 0;
      $yy = $this->GetY();
      foreach($orgs as $nourut=>$division_org_idx) {
         if(!isset($param[$division_org_idx])) continue;
         $div = $param[$division_org_idx];
         list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id)=$divarr[$division_org_idx];
         foreach($div as $job_class_id=>$div2) {
            if($no>=4) {
               $this->AddPage();
               $yy = $this->GetY();
               $no=0;
            }
            if($no%2==1) {
               $this->SetX($this->GetX()+$cw);
            } else {
               $this->Ln($ch);
               if($no>0) {
                  $this->SetY($this->GetY()+$cw);
                  $yy = $this->GetY();
               }
            }
            
            $x1 = $this->GetX();
            $this->SetY($yy);
            $this->SetX($x1);
            $this->Cell($cw-3,$ch,$division_org_nm,1,1,"C");
            $this->Ln($ch);
            $y1 = $this->GetY();
            $this->SetX($x1);
            
            
            $ret .= "<div style='margin-bottom:10px;text-align:center;padding-top:0px;height:328px;width:330px;border:1px solid #888;-moz-border-radius:5px;-moz-box-shadow:0px 1px 5px #888;position:relative;left:50%;margin-left:-165px;'>"
                 . "<div style='padding:5px;text-align:center;background-color:#eee;-moz-border-radius:5px 5px 0 0;border-bottom:1px solid #bbb;'>$division_org_nm</div>"
                 . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/spider_chart_pos.php?division_org_id=${division_org_idx}&job_class_id=${job_class_id}&i=".uniqid()."'/>"
                 . "</div>";
            if(isset($_SESSION["tmp_file"][$division_org_idx][$job_class_id])) {
               $file = $_SESSION["tmp_file"][$division_org_idx][$job_class_id];
               $this->Image( $file, $x1+5, $y1, $cw-10 );
            }
            $no++;
         }
      }
      
      ///////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////
      
      
      
   }
   
   function Footer() {
      global $employee_nm,$nip;
      global $login_nm,$user_nm,$user_nip;
      global $xrequest_id;
      //Position at 1.5 cm from bottom
      $this->SetY(-15);
      //Arial italic 8
      $this->SetFont('Arial','',8);
      //Page number
      $this->Cell(100,10,"Generated: HRIS ".sql2ind(getSQLDate())." by $user_nm ($user_nip)",0,0,'L');
      //$this->Cell(0,10,"Page ".$this->PageNo().'/{nb}',0,0,'R');
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
   
} // end of class _fpdf_IDPRequest

$db=&Database::getInstance();

global $employee_id,$job_id,$login_nm,$user_nm,$user_nip;
global $division_id,$section_id,$position_level;

$division_id = $_GET["d"];
$section_id = $_GET["s"];
$position_level = $_GET["p"];

$user_id = getUserID();

$sql = "SELECT a.user_nm,b.person_nm,c.employee_ext_id,d.job_id"
     . " FROM ".XOCP_PREFIX."users a"
     . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
     . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.person_id = b.person_id"
     . " LEFT JOIN ".XOCP_PREFIX."employee_job d USING(employee_id)"
     . " WHERE a.user_id = '$user_id'";
$result = $db->query($sql);
list($login_nm,$user_nm,$user_nip,$as_job_id)=$db->fetchRow($result);

$pdf = new _fpdf_IDPRequest( 'P', 'mm', 'A4' );
$pdf->Open();
$pdf->formInit();

$pdf->SetMargins(15,15,15);
global $page_count;
$page_count = 0;
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(255,255,255);
$pdf->SetTextColor(0,0,0);

global $xrequest_id;

$pdf->AddPage();
$pdf->SetAutoPageBreak(TRUE,15);
$pdf->AliasNbPages();
$pdf->incumbent();
$pdf->idp_request();

$pdf->Output();




