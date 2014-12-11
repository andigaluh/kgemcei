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
      $this->Cell( 0, 12, "Individual Development Program - HR Monitor",0,1,"R" );
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
   
   function recurseDivision($parent_id) {
      $db=&Database::getInstance();
      $sql = "SELECT a.org_id,a.org_nm,a.org_abbr,b.org_class_nm,a.org_class_id"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . ($parent_id=="all"?"":" WHERE a.parent_id = '$parent_id'");
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr,$org_class_nm,$org_class_id)=$db->fetchRow($result)) {
            if($org_class_id>=3) {
               $_SESSION["hris_subdiv"][$org_class_id][$org_id] = array($org_id,$org_nm,$org_abbr,$org_class_nm);
            }
            $this->recurseDivision($org_id);
         }
      }
   }
   
   function getAllJobs() {
      $db=&Database::getInstance();
      $division_id = $_SESSION["hris_posmatrix_division"];
      $subdiv_id = $_SESSION["hris_posmatrix_subdivision"];
      
      $_SESSION["hris_subdiv"] = array();
      if($division_id=="all") {
         foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
            $this->recurseDivision($division_org_id);
         }
      } else {
         $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
      }
      
      ksort($_SESSION["hris_subdiv"]);
      
      $_SESSION["hris_poslevel"] = array();
      $_SESSION["hris_jobs"] = array();
      
      /// subdivision jobs
      if($subdiv_id>0) {
         $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
              . " FROM ".XOCP_PREFIX."jobs a"
              . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
              . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
              . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
              . " WHERE a.org_id = '$subdiv_id'";
         $result = $db->query($sql);
         
         if($db->getRowsNum($result)>0) {
            while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
               $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
               $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
            }
         }
      } else {
         
         /// division jobs
         if($division_id=="all") {
            foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
               $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                    . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                    . " WHERE a.org_id = '$division_org_id'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
                     $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                     $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
                  }
               }
            }
         } else {
            $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
                 . " FROM ".XOCP_PREFIX."jobs a"
                 . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                 . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                 . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                 . " WHERE a.org_id = '$division_id'";
            $result = $db->query($sql);
            if($db->getRowsNum($result)>0) {
               while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
                  $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                  $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
               }
            }
         }
         
         foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
            foreach($orgs as $org_idx=>$v) {
               $sql = "SELECT a.job_id,a.job_nm,a.job_abbr,a.job_class_id,b.job_class_nm,c.org_nm,d.org_class_nm,c.org_abbr,b.job_class_level,a.summary"
                    . " FROM ".XOCP_PREFIX."jobs a"
                    . " LEFT JOIN ".XOCP_PREFIX."job_class b USING(job_class_id)"
                    . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.org_id"
                    . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
                    . " WHERE a.org_id = '$org_idx'";
               $result = $db->query($sql);
               if($db->getRowsNum($result)>0) {
                  while(list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary)=$db->fetchRow($result)) {
                     $_SESSION["hris_poslevel"][$job_class_id] = array($job_class_id,$job_class_nm,$job_class_level);
                     $_SESSION["hris_jobs"][$job_class_id][$job_id] = array($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$job_class_level,$summary);
                  }
               }
            }
         }
      }
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
      $this->Cell(0,5,"List of IDP :",0,1);
      $x0 = $this->GetX();
      $ch = 4;
      ///////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////
      ///////////////////////////////////////////////////////////////////////////
      
      $d = $_GET["d"]+0;
      $s = $_GET["s"]+0;
      $p = $_GET["p"]+0;
      $_SESSION["hris_posmatrix_division"] = $d;
      $_SESSION["hris_posmatrix_subdivision"] = $s;
      $_SESSION["hris_posmatrix_poslevel"] = $p;
      
      if($d!=$_SESSION["hris_posmatrix_division"]) {
         $_SESSION["hris_posmatrix_subdivision"] = 0;
      } else {
         $_SESSION["hris_posmatrix_subdivision"] = $s;
      }
      
      $_SESSION["hris_subdiv"] = array();
      
      if($_SESSION["hris_posmatrix_division"]=="all") {
         foreach($_SESSION["hris_division_allow"] as $division_org_id=>$a) {
            $this->recurseDivision($division_org_id);
         }
      } else {
         $this->recurseDivision($_SESSION["hris_posmatrix_division"]);
      }
      
      $_SESSION["hris_division_allow"] = array();
      
      list($self_job_id,
           $self_employee_id,
           $self_job_nm,
           $self_nm,
           $self_nip,
           $self_gender,
           $self_jobstart,
           $self_entrance_dttm,
           $self_jobage,
           $self_job_summary,
           $self_person_id,
           $self_user_id,
           $self_first_assessor_job_id,
           $self_next_assessor_job_id)=_hris_getinfobyuserid($user_id);
      
      if($_SESSION["arm_levelmatrix"]==3) {
         $sql = "SELECT a.org_id,b.org_class_id FROM ".XOCP_PREFIX."jobs a LEFT JOIN ".XOCP_PREFIX."orgs b USING(org_id) WHERE a.job_id = '$self_job_id'";
         $result = $db->query($sql);
         list($org_id,$org_class_id)=$db->fetchRow($result);
         if($org_class_id>=3) {
            list($division_org_id,$division_org_nm,$division_org_abbr,$division_org_class_nm,$division_org_class_id,$parent_id) = $this->getDivisionUp($org_id);
            $_SESSION["hris_division_allow"][$division_org_id] = 1;
         } else {
            $this->getDivisionDown($org_id);
         }
         $_SESSION["hris_posmatrix_division"] = $division_org_id;
      } else if($_SESSION["arm_levelmatrix"]==0) {
         $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '3' ORDER BY order_no";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            while(list($division_org_id)=$db->fetchRow($result)) {
               $_SESSION["hris_division_allow"][$division_org_id] = 1;
            }
         }
      }
      
      $cookie_empx = $_COOKIE["empx"]+0;
      $cookie_jobx = $_COOKIE["jobx"]+0;
      
      
      if($_SESSION["arm_levelmatrix"]==0&&!isset($_SESSION["hris_posmatrix_division"])) {
         $_SESSION["hris_posmatrix_division"] = 14;
      }
      
      if(!isset($_SESSION["hris_posmatrix_subdivision"])) {
         $_SESSION["hris_posmatrix_subdivision"] = 0;
      }
      
      /// DIVISION SELECT
      $sql = "SELECT org_id,org_nm,org_abbr FROM ".XOCP_PREFIX."orgs WHERE org_class_id = '3'";
      $result = $db->query($sql);
      $optdiv = "<option value='all'>All</option>";
      if($db->getRowsNum($result)>0) {
         while(list($org_id,$org_nm,$org_abbr)=$db->fetchRow($result)) {
            if($_SESSION["arm_levelmatrix"]==3&&!isset($_SESSION["hris_division_allow"][$org_id])) {
               continue;
            }
            
            if($_SESSION["hris_posmatrix_division"]!="all"&&$_SESSION["hris_posmatrix_division"]==0) {
               $_SESSION["hris_posmatrix_division"] = $org_id;
            }
            
            
            $optdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_division"]?"selected='1'":"").">$org_nm</option>";
         }
      }
      
      $division_id = $_SESSION["hris_posmatrix_division"];
      $subdiv_id = $_SESSION["hris_posmatrix_subdivision"];
      $poslevel_id = $_SESSION["hris_posmatrix_poslevel"];
      
      /// SUBDIVISION SELECT
      
      $optsubdiv = "<option value='0'>All</option>";
      
      $_SESSION["hris_section_allow"] = array();
      
      $_SESSION["hris_subdiv"] = array();
      $this->recurseDivision($division_id);
      ksort($_SESSION["hris_subdiv"]);
      foreach($_SESSION["hris_subdiv"] as $org_class_idx=>$orgs) {
         foreach($orgs as $org_idx=>$v) {
            list($org_id,$org_nm,$org_abbr,$org_class_nm)=$v;
            $optsubdiv .= "<option value='$org_id' ".($org_id==$_SESSION["hris_posmatrix_subdivision"]?"selected='1'":"").">$org_nm $org_class_nm</option>";
            $_SESSION["hris_section_allow"][$org_id] = 1;
         }
      }
      
      /// POSITION SELECT
      $_SESSION["hris_poslevel"] = array();
      $_SESSION["hris_jobs"] = array();
      $this->getAllJobs();
      
      $optlevel = "<option value='0'>All</option>";
      foreach($_SESSION["hris_poslevel"] as $level) {
         list($job_class_id,$job_class_nm)=$level;
         $optlevel .= "<option value='$job_class_id' ".($_SESSION["hris_posmatrix_poslevel"]==$job_class_id?"selected='1'":"").">$job_class_nm</option>";
      }
      
      //// FORM QUERY
      
      //// load matrix
      $jobs = array();
      if(is_array($_SESSION["hris_jobs"])) {
         uksort($_SESSION["hris_jobs"],"sort_job");
         foreach($_SESSION["hris_jobs"] as $job_class_idx=>$v) {
            foreach($v as $job_idx=>$w) {
               list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm)=$w;
               if($poslevel_id==0) {
                  $jobs[$job_id] = $w;
               } else if($poslevel_id==$job_class_id) {
                  $jobs[$job_id] = $w;
               }
            }
         }
      }
      
      $this->SetFont("Arial","",9);
      
      $arrw = array(7,35,15,55,22,22,30);
      $ttlw = 0;
      foreach($arrw as $v) {
         $ttlw += $v;
      }
      
      
      $this->Ln($ch);
      $this->SetFont("Arial","B",9);
      $this->Cell($arrw[0],$ch,"ID",1,0,"C");
      $this->Cell($arrw[1],$ch,"Employee",1,0,"C");
      $this->Cell($arrw[2],$ch,"NIP",1,0,"C");
      $this->Cell($arrw[3],$ch,"Time Frame",1,0,"C");
      $this->Cell($arrw[4],$ch,"Elapsed Time",1,0,"C");
      $this->Cell($arrw[5],$ch,"Progress",1,0,"C");
      $this->Cell($arrw[6],$ch,"Status",1,0,"C");
      $this->Ln();
      
      $ret = "<table class='xxlist' style='width:100%;'>"
           . "<thead>"
           . "<tr>"
           . "<td>ID</td>"
           . "<td>Employee</td>"
           . "<td>NIP</td>"
           . "<td>Time Frame</td>"
           . "<td style='text-align:left;padding-right:5px;'>Elapsed Time</td>"
           . "<td style='text-align:left;'>Progress</td>"
           . "<td>Status</td>"
           . "</tr>"
           . "</thead>"
           . "<tbody>";
      
      foreach($jobs as $job_idx=>$job) {
         list($job_id,$job_nm,$job_abbr,$job_class_id,$job_class_nm,$org_nm,$org_class_nm,$org_abbr,$jlvl,$summary)=$job;
         
         $sql = "SELECT a.employee_id,b.employee_ext_id,c.person_nm"
              . " FROM ".XOCP_PREFIX."employee_job a"
              . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
              . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
              . " WHERE a.job_id = '$job_id'"
              . " ORDER BY c.person_nm";
         $remp = $db->query($sql);
         
         if($db->getRowsNum($remp)>0) {
            while(list($employee_id,$nip,$employee_nm)=$db->fetchRow($remp)) {
               $sql = "SELECT request_id,employee_id,approve_superior_id,approve_superior_dttm,approve_hris_id,approve_hris_dttm,cost_estimate,requested_dttm,status_cd"
                    . " FROM ".XOCP_PREFIX."idp_request"
                    . " WHERE employee_id = '$employee_id'"
                    . " ORDER BY requested_dttm";
               $rreq = $db->query($sql);
               
               if($db->getRowsNum($rreq)>0) {
                  if($old_job_class!=$job_class_id) {
                     $this->Ln($ch);
                     $this->SetFont("Arial","B",9);
                     $this->Cell($ttlw,$ch,$job_class_nm,1,1,"L");
                  }
         
                  $old_job_class = $job_class_id;
                  while(list($request_id,$employee_id,$approve_superior_id,$approve_superior_dttm,$approve_hris_id,$approve_hris_dttm,$cost_estimate,$requested_dttm,$status_cd)=$db->fetchRow($rreq)) {
                     if($status_cd=="rejected") continue;
                     if($status_cd=="nullified") continue;
                     if($status_cd=="completed") continue;
                     
                     $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd NOT IN ('rejected','nullified')";
                     $rc = $db->query($sql);
                     if($db->getRowsNum($rc)==1) {
                        list($cntaap)=$db->fetchRow($rc);
                     } else {
                        $cntaap = 0;
                     }
                     $sql = "SELECT COUNT(*) FROM ".XOCP_PREFIX."idp_request_actionplan WHERE request_id = '$request_id' AND status_cd = 'completed'";
                     $rc = $db->query($sql);
                     if($db->getRowsNum($rc)==1) {
                        list($cntaapc)=$db->fetchRow($rc);
                     } else {
                        $cntaapc = 0;
                     }
                     switch($status_cd) {
                        case "start":
                           $req_status = "New Request";
                           break;
                        case "employee":
                           $req_status = "Employee Request";
                           break;
                        case "approval1":
                           $req_status = "Superior Approval";
                           break;
                        case "approval2":
                           $req_status = "Next Superior Approval";
                           break;
                        case "approval3":
                           $req_status = "HR Approval";
                           break;
                        case "implementation":
                           $req_status = "Implementation";
                           break;
                        case "completed":
                           $req_status = "Completed";
                           break;
                        default:
                           break;
                     }
                     
                     if($cntaap>0) {
                        $progress_qty = ceil(bcmul(100,bcdiv($cntaapc,$cntaap)));
                        if($progress_qty>100) $progress_qty = 100;
                        $progress_qty_txt = toMoneyShort($progress_qty)."%";
                     } else {
                        $progress_qty_txt = "0%";
                        $progress_qty = 0;
                     }
                     
                     $sql = "SELECT b.person_nm,a.employee_ext_id FROM ".XOCP_PREFIX."employee a"
                          . " LEFT JOIN ".XOCP_PREFIX."persons b USING(person_id)"
                          . " WHERE employee_id = '$employee_id'";
                     $rempx = $db->query($sql);
                     if($db->getRowsNum($rempx)>0) {
                        list($employee_nm,$nip)=$db->fetchRow($rempx);
                     }
                     list($timeframe_start,$timeframe_stop)=_idp_get_timeframe($request_id);
                     
                     $sql = "SELECT TO_DAYS(now()),TO_DAYS('$timeframe_start'),TO_DAYS('$timeframe_stop')";
                     $resultx = $db->query($sql);
                     list($now,$start,$stop)=$db->fetchRow($resultx);
                     if($now<=$start) {
                        $progress_time_txt = "0%";
                     } else {
                        $p = $now-$start;
                        $q = $stop-$start;
                        $progress_time = 100*($p/$q);
                        if($progress_time>100) $progress_time = 100;
                        $progress_time_txt = toMoneyShort($progress_time)."%";
                     }
                     
                     $this->SetFont("Arial","",8);
                     $this->Cell($arrw[0],$ch,$request_id,1,0,"C",1);
                     $this->Cell($arrw[1],$ch,$employee_nm,1,0,"L",1);
                     $this->Cell($arrw[2],$ch,$nip,1,0,"L",1);
                     $this->Cell($arrw[3],$ch,sql2ind($timeframe_start,"date")." - ".sql2ind($timeframe_stop,"date"),1,0,"C",1);
                     $this->Cell($arrw[4],$ch,"$progress_time_txt",1,0,"C",1);
                     $this->Cell($arrw[5],$ch,"$progress_qty_txt",1,0,"C",1);
                     $this->Cell($arrw[6],$ch,"$req_status",1,0,"L",1);
                     $this->Ln($ch);
                     
                     $ret .= "<tr>"
                           . "<td>$request_id</td>"
                           . "<td>$employee_nm</td>"
                           . "<td>$nip</td>"
                           . "<td>".sql2ind($timeframe_start,"date")." - ".sql2ind($timeframe_stop,"date")."</td>"
                           . "<td style='text-align:left;padding-right:5px;'>"
                                 . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                 . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_time/2)))."px;'/>"
                                 . "</div>"
                                 . "<div style='float:left;padding-left:3px;'>$progress_time_txt</div>"
                           . "</td>"
                           . "<td style='text-align:left;padding-right:5px;'>"
                                 . "<div style='float:left;overflow:hidden;width:50px !important;border:1px solid #999999;margin:auto;text-align:left;'>"
                                 . "<img src='".XOCP_SERVER_SUBDIR."/modules/hris/images/level_foreground.png' style='width:50px;height:12px;margin-left:".((int)(-50+($progress_qty/2)))."px;'/>"
                                 . "</div>"
                                 . "<div style='float:left;padding-left:3px;'>$progress_qty_txt</div>"
                            . "</td>"
                           . "<td>$link</td>"
                           . "</tr>";
                  }
               }
            }
         }
      } ///for eac
      
      
      
      /////
      
      
      
      $ret .= "</tbody></table>";
      
      
      
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




