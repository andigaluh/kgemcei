<?php
require_once('../../../config.php');
define('FPDF_FONTPATH',XOCP_DOC_ROOT.'/class/pdf/fpdf/font/');
require_once(XOCP_DOC_ROOT.'/class/pdf/fpdf/fpdf.php');
require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
require_once(XOCP_DOC_ROOT."/modules/hris/assessmentresult.php");

class _fpdf_AssessmentResult extends FPDF {
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
      $x0 = $x1 = 10;
      $y0 = $y1 = 5;
      //Set position
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
      $this->Cell( 0, 12, "Individual Competency Assessment Result",0,1,"R" );
      $this->Line($x0,25,$this->w-15,25);
      $this->Ln(4);
      
   }
   
   function incumbent() {
      global $picy,$datay;
      global $employee_id,$job_id;
      $db=&Database::getInstance();
      $this->Ln(4);
      $this->Ln(4);
      $x0 = $this->GetX();
      $y0 = $this->GetY();
      
      $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_class_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
           . " WHERE a.job_id = '$job_id'";
      $result = $db->query($sql);
      list($job_id,$job_nm,$job_cd,$job_abbr,$org_id,$job_descx,$job_summary,$job_class_nm,$org_nm,$org_class_nm,$org_class_id)=$db->fetchRow($result);

      $_SESSION["hris_org_parents"] = array();
      $_SESSION["hris_org_parents"][] = array($org_id,$org_class_nm,$org_nm);
      $this->getOrgsUp($org_id);
      
      $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm,a.gradeval,c.birth_dttm,c.birthplace,"
           . "c.adm_gender_cd,c.addr_txt,c.cell_phone,c.home_phone,c.marital_st,"
           . "b.entrance_dttm,a.start_dttm,a.stop_dttm,(TO_DAYS(now())-TO_DAYS(b.entrance_dttm)) as jobage,"
           . "c.person_id"
           . " FROM ".XOCP_PREFIX."employee_job a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.employee_id = '$employee_id'";
      $result = $db->query($sql);
      list($employee_id,$nip,$employee_nm,$gradeval,$dob,$pob,$gender,$addr,$cellphone,$phone,$marital,
           $entrance_dttm,$jobstart,$jobstop,$jobage,$person_id)=$db->fetchRow($result);
      
      //// detect image:
      if(file_exists(XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.JPG")) {
         $image = XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.JPG";
      } else if(file_exists(XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.GIF")) {
         $image = XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.GIF";
      } else if(file_exists(XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.PNG")) {
         $image = XOCP_DOC_ROOT."/modules/hris/data/person/PICTURE_${person_id}.PNG";
      } else {
         $image = XOCP_DOC_ROOT."/images/nopic.png";
      }
      
      $x1 = $x0;
      $y1 = $y0;
      
      //Set position
      $this->SetFont('Arial','B',9);
      $length1 = $this->GetStringWidth( $name1 );
      $img_size = getimagesize($image);
      
      $ry = 27;
      $padding = 1.5;
      $rx = $img_size[0] * ( $ry / $img_size[1] );
      $this->Image( $image, $x1+$padding, $y1+$padding, $rx,$ry );
      $this->Rect($x1,$y1,$rx+(2*$padding),$ry+(2*$padding));
      $picy = $y1+$ry+(2*$padding);
      
      $x1 = $x1 + $rx + 6;
      
      $w0 = 25;
      $w1 = 3;
      $w2 = 50;
      $h = 4;
      
      $this->SetXY( $x1, $y1 );
      $this->Cell($w0,$h,"Job Title",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$job_nm,0,1);
      
      foreach($_SESSION["hris_org_parents"] as $k=>$v) {
         list($org_idx,$org_class_nmx,$org_nmx,$org_class_idx)=$v;
         $this->SetX($x1);
         $this->Cell($w0,$h,"$org_class_nmx",0,0);
         $this->Cell($w1,$h,":",0,0);
         $this->Cell($w2,$h,$org_nmx,0,1);
      }
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"Incumbent",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$employee_nm,0,1);
      
      $this->SetX($x1);
      $this->Cell($w0,$h,"NIP",0,0);
      $this->Cell($w1,$h,":",0,0);
      $this->Cell($w2,$h,$nip,0,1);
      $datay = $this->GetY();
   }
   
   function competency_profile() {
      global $picy,$datay;
      global $employee_id,$job_id;
      $db=&Database::getInstance();
      
      /// competency fit
      $cf_compgroup = array();
      $cf_pass = array();
      
      $asid = $_GET["asid"];
      
      $x0 = $this->GetX();
      $y0 = max($picy,$datay);
      $this->SetXY($x0,$y0);
      $this->Ln(4);
      $this->SetFont('Arial','B',10);
      $this->Cell(0,4,"Competency Profile",0,1);
      $this->Ln(4);
      
      $col_comp_c = 17;
      $col_comp_nm = 90;
      $col_sep = 3;
      $col_val = 10;
      
      $ch = 5;
      
      $this->SetFillColor(240,240,240);
      
      $this->SetFont('Arial','',9);
      
      /////////////////////////////////////////////
      
      $sql = "SELECT b.compgroup_id,a.competency_id,a.rcl,a.itj,b.competency_nm,c.compgroup_nm,b.competency_class,d.ccl,(b.competency_class+0) as urcl,"
           . "f.person_nm,b.desc_en,b.desc_id"
           . " FROM ".XOCP_PREFIX."job_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup  c USING(compgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee_competency d ON d.employee_id = '$employee_id' AND d.competency_id = b.competency_id AND d.asid_update = '$asid'"
           . " LEFT JOIN ".XOCP_PREFIX."employee e ON e.employee_id = d.assessor_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons f ON f.person_id = e.person_id"
           . " WHERE a.job_id = '$job_id'"
           . " ORDER BY b.compgroup_id,urcl,b.competency_id";
      $result = $db->query($sql);
      _debuglog($sql);
      $oldcompgroup = "";
      if($db->getRowsNum($result)>0) {
         
         $this->Cell($col_comp_c,$ch,"",0,0);
         $this->Cell($col_comp_nm,$ch,"",0,0);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell(3*$col_val,$ch,"Total Value","TLR",0,"C");
         
         $this->Ln();
         $this->Cell($col_comp_c,$ch,"",0,0);
         $this->Cell($col_comp_nm,$ch,"",0,0);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"ITJ","TLR",0,"C");
         $this->Cell($col_val,$ch,"RCL","TLR",0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"CCL","TLR",0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"RCL","TLR",0,"C");
         $this->Cell($col_val,$ch,"CCL","TLR",0,"C");
         $this->Cell($col_val,$ch,"GAP","TLR",0,"C");
      
         
         
         while(list($compgroup_id,$competency_id,$rcl,$itj,$competency_nm,$compgroup_nm,$cc,$cclxxx,$urcl,$asr_nm,$desc_en,$desc_id)=$db->fetchRow($result)) {
            
            /// competency fit
            if($compgroup_id==1||$compgroup_id==2) {
               $cf_compgroup[$compgroup_id][$competency_id] = array($competency_id,$competency_nm,$compgroup_nm);
            }
            
            $cc = ucfirst($cc);
            //$ccl = $ccl+0;
            $arrccl = array();
            
            
            /// superior
            $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t,e.fulfilled FROM ".XOCP_PREFIX."employee_competency a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = '$asid'"
                 . " AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id"
                 . " AND d.status_cd = 'active'"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_level e ON e.employee_id = a.employee_id AND e.competency_id = a.competency_id AND e.assessor_id = a.assessor_id AND e.proficiency_lvl = CONVERT(FLOOR(a.ccl),CHAR)"
                 . " WHERE a.employee_id = '$employee_id'"
                 . " AND a.competency_id = '$competency_id'"
                 . " AND d.asid = '$asid'"
                 . " ORDER BY a.ccl DESC";
            $r360 = $db->query($sql);
            if($db->getRowsNum($r360)>0) {
               while(list($ccl360,$asr360_id,$asr360_nm,$assessor_t,$fulfilled)=$db->fetchRow($r360)) {
                  if($fulfilled==0) {
                     continue;
                  }
                  ///if($assessor_t=="superior") continue;
                  $ccl360 = $ccl360+0;
                  $arrccl[$asr360_id] = $ccl360;
               }
            }
            
            
            //// 360
            $sql = "SELECT a.ccl,a.assessor_id,c.person_nm,d.assessor_t,e.fulfilled FROM ".XOCP_PREFIX."employee_competency360 a"
                 . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.assessor_id"
                 . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."assessor_360 d ON d.asid = '$asid'"
                 . " AND d.employee_id = a.employee_id AND d.assessor_id = a.assessor_id"
                 . " AND d.status_cd = 'active'"
                 . " LEFT JOIN ".XOCP_PREFIX."employee_level360 e ON e.employee_id = a.employee_id AND e.competency_id = a.competency_id AND e.assessor_id = a.assessor_id AND e.proficiency_lvl = CONVERT(FLOOR(a.ccl),CHAR)"
                 . " WHERE a.employee_id = '$employee_id'"
                 . " AND a.competency_id = '$competency_id'"
                 . " AND d.asid = '$asid'"
                 . " ORDER BY a.ccl DESC";
            $r360 = $db->query($sql);
            if($db->getRowsNum($r360)>0) {
               while(list($ccl360,$asr360_id,$asr360_nm,$assessor_t,$fulfilled)=$db->fetchRow($r360)) {
                  if($fulfilled==0) {
                     continue;
                  }
                  if($assessor_t=="superior") continue;
                  $ccl360 = $ccl360+0;
                  $arrccl[$asr360_id] = $ccl360;
               }
            }
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            arsort($arrccl);
            $ascnt = count($arrccl);
            $xxccl = 4;
            $cnt = 0;
            $calc_ccl = 0;
            
            $r = 0;
            $old_r = $r;
            foreach($arrccl as $k=>$v) {
               if($cnt==0) {
                  $calc_ccl = $v;
               }
               $cnt++;
               $r = _bctrim(bcdiv($cnt,$ascnt));
               
               if(bccomp($old_r,0.75)>=0) {
               } else {
                  $calc_ccl = $v;
               }
               $old_r = $r;
            }
            
            
            
            
            
            ////
            if($oldcompgroup!=$compgroup_nm) {
               $this->Ln();
               $this->SetFont('Arial','B',9);
               $this->Cell($col_comp_c+$col_comp_nm+(3*$col_sep)+(6*$col_val),$ch,"$compgroup_nm",1,0,"L",1);
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
            $gapx = $calc_ccl*$itj-$rcl*$itj;
            if($gapx<0) {
               $gap_color = "color:red;font-weight:bold;";
               $competency_color = "color:red;";
            } else {
               $gap_color = "";
               $competency_color = "";
               if($compgroup_id==1||$compgroup_id==2) {
                  $cf_pass[$compgroup_id][$competency_id] = 1;
               }
            }
            $this->SetTextColor(80,80,80);
            
            $this->Ln();
            $this->Cell($col_comp_c,$ch,"$cctxt",$ccborder,0);
            
            if($gapx<0) {
               $this->SetTextColor(250,0,0);
            } else {
            
            }
            
            $this->Cell($col_comp_nm,$ch,"$competency_nm","TLR",0);
            $this->Cell($col_sep,$ch,"",0,0);
            $this->Cell($col_val,$ch,"$itj","TLR",0,"C");
            $this->Cell($col_val,$ch,"$rcl","TLR",0,"C");
            $this->Cell($col_sep,$ch,"",0,0);
            $this->Cell($col_val,$ch,(count($arrccl)>0?number_format("$calc_ccl",2,".",""):"-"),"TLR",0,"C");
            $this->Cell($col_sep,$ch,"",0,0);
            $this->Cell($col_val,$ch,($rcl*$itj),"TLR",0,"C");
            $this->Cell($col_val,$ch,(count($arrccl)>0?number_format(($calc_ccl*$itj),2,".",""):"-"),"TLR",0,"C");
            $this->Cell($col_val,$ch,(count($arrccl)>0?number_format($gapx,2,".",""):"-"),"TLR",0,"C");
            
            if(count($arrccl)>0) {
               $ttlccl += ($calc_ccl*$itj);
               $ttlrcl += ($rcl*$itj);
               $ttlgap += (($calc_ccl-$rcl)*$itj);
            }
            $this->SetTextColor(80,80,80);
         }
         
         if($ttlrcl==0) {
            $match = 0;
         } else {
            $match = toMoney(_bctrim(100*$ttlccl/$ttlrcl));
         }
         
         /////////////////////////////////////////////
         $this->Ln();
         $this->Cell($col_comp_c,$ch,"","TB",0);
         $this->Cell($col_comp_nm,$ch,"","TB",0);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"","T",0,"C");
         $this->Cell($col_val,$ch,"","T",0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"","T",0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"","T",0,"C");
         $this->Cell($col_val,$ch,"","T",0,"C");
         $this->Cell($col_val,$ch,"","T",0,"C");
         
         $this->SetFont('Arial','B',9);
         
         $this->Ln();
         $this->Cell($col_comp_c+$col_comp_nm,$ch,"Total :","TLR",0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,$ttlrcl,"TLR",0,"C");
         $this->Cell($col_val,$ch,number_format("$ttlccl",2,".",""),"TLR",0,"C");
         $this->Cell($col_val,$ch,number_format("$ttlgap",2,".",""),"TLR",0,"C");
         
         $this->SetFillColor(255,210,160);
         
         $this->Ln();
         $this->Cell($col_comp_c+$col_comp_nm,$ch,"Job Match :","TLR",0,"C",1);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell(3*$col_val,$ch,"$match%","TLR",0,"C",1);
         
         /// competency fit
         $cf_cnt = $cf_pass_cnt = 0;
         foreach($cf_compgroup as $cg=>$x) {
            $cf_cnt += count($cf_compgroup[$cg]);
            $cf_pass_cnt += count($cf_pass[$cg]);
         }
         
         $cf = toMoney(_bctrim(bcmul(100,bcdiv($cf_pass_cnt,$cf_cnt))));
      
         $this->Ln();
         $this->Cell($col_comp_c+$col_comp_nm,$ch,"Competency Fit :","TLRB",0,"C",1);
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell($col_val,$ch,"",0,0,"C");
         $this->Cell($col_sep,$ch,"",0,0);
         $this->Cell(3*$col_val,$ch,"$cf%","TLRB",0,"C",1);
         
         $this->Ln();
         
         $this->Ln();
         $this->SetFont('Arial','B',7);
         $this->Cell(0,3,"Remark : ",0,0,'L');
         
         $this->Ln();
         $this->SetFont('Arial','',7);
         $this->Cell(0,3,"Job Match = Total CCL / Total RCL",0,0,'L');
         
         $this->Ln();
         $this->SetFont('Arial','',7);
         $this->Cell(0,3,"Competency Fit = ( General + Managerial Fulfilled Competecy Count ) / ( General + Managerial Competency Count )",0,0,'L');
         
      } else {
         $this->SetTextColor(255,0,0);
         $this->Ln(4);
         $this->Ln(4);
         $this->Cell(0,4,"No competency found.",0,1);
         $this->Ln(4);
         
      }
   }
   
   function Footer() {
      global $login_nm,$user_nm,$user_nip;
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
   
} // end of class _fpdf_AssessmentResult

$db=&Database::getInstance();

global $employee_id,$job_id,$login_nm,$user_nm,$user_nip;

$user_id = getUserID();

$sql = "SELECT a.user_nm,b.person_nm,c.employee_ext_id,d.job_id"
     . " FROM ".XOCP_PREFIX."users a"
     . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
     . " LEFT JOIN ".XOCP_PREFIX."employee c ON c.person_id = b.person_id"
     . " LEFT JOIN ".XOCP_PREFIX."employee_job d USING(employee_id)"
     . " WHERE a.user_id = '$user_id'";
$result = $db->query($sql);
list($login_nm,$user_nm,$user_nip,$as_job_id)=$db->fetchRow($result);

$arr_print = array();

if(isset($_GET["as"])&&$_GET["as"]>0) {
   $sql = "SELECT a.job_id,a.job_nm,a.job_cd,a.job_abbr,a.org_id,a.description,a.summary,b.job_level"
        . " FROM ".XOCP_PREFIX."jobs a"
        . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
        . " WHERE a.assessor_job_id = '$as_job_id'"
        . " ORDER BY a.job_class_id";
   $result = $db->query($sql);
   if($db->getRowsNum($result)>0) {
      while(list($employee_job_idx)=$db->fetchRow($result)) {
         $sql = "SELECT employee_id FROM ".XOCP_PREFIX."employee_job"
              . " WHERE job_id = '$employee_job_idx'";
         $remp = $db->query($sql);
         if($db->getRowsNum($remp)>0) {
            while(list($employee_idx)=$db->fetchRow($remp)) {
               $arr_print[] = array($employee_idx,$employee_job_idx);
            }
         }
      }
   }
} else {
   $employee_id = $_GET["e"];
   $job_id = $_GET["j"];
   $arr_print[] = array($employee_id,$job_id);
}

$pdf = new _fpdf_AssessmentResult( 'P', 'mm', 'A4' );
$pdf->Open();

$pdf->SetMargins(15,15,15);
global $page_count;
$page_count = 0;
$pdf->SetDrawColor(90,90,90);
$pdf->SetTextColor(80,80,80);

if(count($arr_print)>0) {
   foreach($arr_print as $v) {
      list($employee_id,$job_id)=$v;
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(FALSE);
      $pdf->AliasNbPages();
      $pdf->incumbent();
      $pdf->competency_profile();
   }
} else {
   $this->AddPage();
}

$pdf->Output();




