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
      $this->Cell( 0, 12, "Individual Development Program",0,1,"R" );
      $this->Line($x0,20+$y0,$this->w-15,20+$y0);
      $this->Ln(7);
      
   }
   
   function incumbent($request_id) {
      global $picy,$datay;
      global $employee_id,$job_id;
      global $employee_nm;
      global $nip;
      $db=&Database::getInstance();
      $sql = "SELECT employee_id,current_job_id FROM ".XOCP_PREFIX."idp_request WHERE request_id = '$request_id'";
      $result = $db->query($sql);
      list($employee_id,$job_id)=$db->fetchRow($result);
      //$this->Ln(4);
      //$this->Ln(4);
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
      
      $ry = 22;
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
   
   function idp_request($request_id) {
      global $picy,$datay;
      global $employee_id,$job_id,$employee_nm;
      $db=&Database::getInstance();
      
      /// competency fit
      $cf_compgroup = array();
      $cf_pass = array();
      
      $asid = $_GET["asid"];
      
      $x0 = $this->GetX();
      $y0 = max($picy,$datay);
      $bottom_y = 185;
      $this->SetXY($x0,$y0);
      $this->Ln(4);
      $this->SetFont('Arial','B',10);
      $this->Cell(0,5,"General Information :",0,1);
      $x0 = $this->GetX();
      
      $ttl_cost = _idp_calc_cost_estimate($request_id);
      $cost_txt = toMoney($ttl_cost);
      $return_note_txt = "";
      
      $sql = "SELECT a.requested_dttm,a.timeframe_start,a.timeframe_stop,a.requester_id,c.person_nm,a.employee_id,a.status_cd,d.job_nm,d.job_abbr,"
           . "a.approve_superior_id,a.approve_superior_dttm,a.approve_higher_superior_id,a.approve_higher_superior_dttm"
           . " FROM ".XOCP_PREFIX."idp_request a"
           . " LEFT JOIN ".XOCP_PREFIX."employee b ON b.employee_id = a.requester_id"
           . " LEFT JOIN ".XOCP_PREFIX."persons c ON c.person_id = b.person_id"
           . " LEFT JOIN ".XOCP_PREFIX."jobs d ON d.job_id = a.current_job_id"
           . " WHERE a.request_id = '$request_id'";
      $result = $db->query($sql);
      $btn_delete = "";
      if($db->getRowsNum($result)==1) {
         list($requested_dttm,$timeframe_start,$timeframe_stop,$requester_id,$requester_nm,$employee_id,$status_cd,$job_nm,$job_abbr,
              $approve_superior_id,$approve_superior_dttm,$approve_higher_superior_id,$approve_higher_superior_dttm)=$db->fetchRow($result);
         switch($status_cd) {
            case "employee":
               $status_txt = "Request Completion by Employee";
               break;
            case "approval1":
               $status_txt = "Waiting Superior Approval";
               break;
            case "approval2":
               $status_txt = "Waiting Next Superior Approval";
               break;
            case "approval3":
               $status_txt = "Waiting HR Confirmation";
               break;
            case "implementation":
               $status_txt = "Implementation";
               break;
            case "completed":
               $status_txt = "Completed";
               break;
            case "start":
            default:
               $status_txt = "New Request";
               break;
         }
         
         $sql = "SELECT return_note FROM ".XOCP_PREFIX."idp_request_return_note WHERE request_id = '$request_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($return_note)=$db->fetchRow($result);
            $return_note_txt = $return_note;
         }
      }
      
      $ch = 4;
      $this->SetLineWidth(0.1);
      $this->SetFont('Arial','B',9);
      $myw = $this->w-30;
      $cw = floor($myw/4);
      $myw = 4*$cw;
      $arrw = array($cw+10,$cw-25,$cw+25,$cw-10);
      $xx = $this->GetX();
      $yy = $this->GetY();
      $this->Cell($cw,$ch,"Status","BR",0,"C");
      $this->Cell($cw,$ch,"Start Date","BR",0,"C");
      $this->Cell($cw,$ch,"Stop Date $cw","BR",0,"C");
      $this->Cell($cw,$ch,"Cost Estimate IDR","B",0,"C");
      $this->Ln();
      $this->SetFont('Arial','',9);
      $this->Cell($cw,$ch,$status_txt,"R",0,"C");
      $this->Cell($cw,$ch,sql2ind($timeframe_start,"date"),"R",0,"C");
      $this->Cell($cw,$ch,sql2ind($timeframe_stop,"date"),"R",0,"C");
      $this->Cell($cw,$ch,$cost_txt,"",0,"C");
      $x1 = $this->GetX();
      $this->Ln();
      $h = $this->GetY()-$yy;
      $this->SetLineWidth(0.4);
      $this->mRoundedRect->RoundedRect($xx, $yy, $x1-$xx, $h, 1.5, 'S');
      
      if($return_note_txt!="") {
         $this->SetTextColor(0,0,255);
         $this->Ln(4);
         $this->Cell(49,$ch,"IDP Request returned with notes: ",0,"L");
         $this->MultiCell(0,$ch,$return_note_txt,0,"L");
         $this->SetTextColor(0,0,0);
      }
      
      
      $margin_left = $this->GetX();
      $this->Ln(4);
      $this->SetFont('Arial','B',10);
      $this->Cell(0,5,"Competency To Be Developed :",0,1);
      $sql = "SELECT a.competency_id,b.competency_nm,b.competency_abbr,a.focus_dev"
           . " FROM ".XOCP_PREFIX."idp_request_competency a"
           . " LEFT JOIN ".XOCP_PREFIX."competency b USING(competency_id)"
           . " WHERE a.request_id = '$request_id'"
           . " ORDER BY a.priority_no";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($competency_id,$competency_nm,$competency_abbr,$focus_dev)=$db->fetchRow($result)) {
            
            if(($this->GetY()+5)>$bottom_y) {
               $this->AddPage();
               $ap_y0 = $ap_y1 = $max_y = $yy = $this->GetY();
            }
            
            
            //// competency title & focus of development //////////////////////////
            $yy = $this->GetY();
            $max_y = 0;
            $this->SetLineWidth(0.4);
            $this->SetFont('Arial','B',9);
            $this->Cell($myw,$ch+2," $competency_abbr - $competency_nm","B",0,"L");
            $x1 = $this->GetX();
            $this->Ln();
            
            $this->SetLineWidth(0.1);
            $this->SetFont('Arial','',9);
            $this->SetFillColor(245);
            $this->Cell($arrw[0],$ch,"Focus of Development","B",0,"C",TRUE);
            $this->Cell($arrw[1],$ch,"Development Method","B",0,"C",TRUE);
            $this->Cell($arrw[2],$ch,"Action Plan","B",0,"C",TRUE);
            $this->Cell($arrw[3],$ch,"Time Frame","B",0,"C",TRUE);
            $this->SetFillColor(255);
            $this->SetLineWidth(0.1);
            $this->Ln();
            
            $nly = $this->GetY()+($this->NbLines($arrw[0],$focus_dev)*$ch);
            
            if($nly>$bottom_y) {
               $max_y = $this->GetY();
               $this->SetLineWidth(0.4);
               $this->mRoundedRect->RoundedRect($xx, $yy, $x1-$xx, $max_y-$yy, 0.75, 'S');
               $this->SetLineWidth(0.1);
               $this->Line($xx+$arrw[0],$yy,$xx+$arrw[0],$max_y);
               $this->Line($xx+$arrw[0]+$arrw[1],$yy,$xx+$arrw[0]+$arrw[1],$max_y);
               $this->Line($xx+$arrw[0]+$arrw[1]+$arrw[2],$yy,$xx+$arrw[0]+$arrw[1]+$arrw[2],$max_y);
               $this->AddPage();
               $ap_y0 = $ap_y1 = $max_y = $yy = $this->GetY();
               $this->SetFont('Arial','',9);
               $this->SetFillColor(245);
               $this->Cell($arrw[0],$ch,"Focus of Development","B",0,"C",TRUE);
               $this->Cell($arrw[1],$ch,"Development Method","B",0,"C",TRUE);
               $this->Cell($arrw[2],$ch,"Action Plan","B",0,"C",TRUE);
               $this->Cell($arrw[3],$ch,"Time Frame","B",0,"C",TRUE);
               $this->SetFillColor(255);
               $this->SetLineWidth(0.1);
               $this->Ln();
            }
            
            $ap_y0 = $this->GetY();
            $this->SetFont('Arial','',9);
            $this->MultiCell($arrw[0],$ch,"$focus_dev",0,"L");
            $max_y = max($this->GetY(),$max_y);
            
            //// action plan //////////////////////////////////////////////////////
            
            $this->SetY($ap_y0);
            
            $sql = "SELECT a.actionplan_id,a.event_id FROM ".XOCP_PREFIX."idp_request_actionplan a"
                 . " WHERE a.request_id = '$request_id' AND competency_id = '$competency_id'"
                 . " ORDER BY a.actionplan_id";
            $resulta = $db->query($sql);
            if($db->getRowsNum($resulta)>0) {
               $no = 0;
               while(list($actionplan_id,$event_id)=$db->fetchRow($resulta)) {
                  $sql = "SELECT a.status_cd,a.method_t,b.method_type,a.method_id,c.method_nm,a.plan_start_dttm,a.plan_stop_dttm FROM ".XOCP_PREFIX."idp_request_actionplan a"
                       . " LEFT JOIN ".XOCP_PREFIX."idp_development_method_type b USING(method_t)"
                       . " LEFT JOIN ".XOCP_PREFIX."idp_development_method c ON c.method_id = a.method_id"
                       . " WHERE a.request_id = '$request_id' AND a.actionplan_id = '$actionplan_id'";
                  $rap = $db->query($sql);
                  if($db->getRowsNum($rap)==1) {
                     list($aap_status_cd,$method_t,$method_type,$method_id,$method_nm,$plan_start_dttm,$plan_stop_dttm)=$db->fetchRow($rap);
                     
                     $this->SetFont('Arial','',9);
                     
                     $ap_y1 = $this->GetY();
                     
                     if($this->GetY()>$bottom_y) {
                        $this->SetY($max_y);
                        $this->SetLineWidth(0.4);
                        $this->mRoundedRect->RoundedRect($xx, $yy, $x1-$xx, $max_y-$yy, 0.75, 'S');
                        $this->SetLineWidth(0.1);
                        $this->Line($xx+$arrw[0],$ap_y0-$ch,$xx+$arrw[0],$max_y);
                        $this->Line($xx+$arrw[0]+$arrw[1],$ap_y0-$ch,$xx+$arrw[0]+$arrw[1],$max_y);
                        $this->Line($xx+$arrw[0]+$arrw[1]+$arrw[2],$ap_y0-$ch,$xx+$arrw[0]+$arrw[1]+$arrw[2],$max_y);
                        $this->AddPage();
                        $ap_y0 = $ap_y1 = $max_y = $yy = $this->GetY();
                     }
                     
                     if($no>0) {
                        $this->SetLineWidth(0.1);
                        $this->Line($margin_left+$arrw[0],$ap_y1,$x1,$ap_y1);
                     }
                     $no++;
                     
                     $this->SetX($arrw[0]+$margin_left);
                     $this->MultiCell($arrw[1],$ch,$method_type,"","L");
                     $max_y = max($this->GetY(),$max_y);
                     
                     $actionplan_remark = "";
                     $actionplan_start = "0000-00-00 00:00:00";
                     $actionplan_stop = "0000-00-00 00:00:00";
                     $report_status = "";
                     $cost_estimate = 0;
                     if($method_t!="") {
                        $editor_file = XOCP_DOC_ROOT."/modules/hris/include/idp/method_${method_t}.php";
                        if(file_exists($editor_file)) {
                           require_once($editor_file);
                           $fremark = "${method_t}_idp_m_getRemark";
                           list($actionplan_remark,$actionplan_start,$actionplan_stop,$report_status,$cost_estimate,$fr1,$fr2) = $fremark($request_id,$actionplan_id);
                        }
                     }
                     
                     if(trim($fr1)=="") {
                        $fr1 = "?";
                     }
                     
                     $this->SetY($ap_y1);
                     $this->SetX($margin_left+$arrw[0]+$arrw[1]);
                     $this->MultiCell($arrw[2],$ch,$fr1,"0","L");
                     $max_y = max($this->GetY(),$max_y);
                     
                     if(trim($fr2)!="") {
                        $this->SetTextColor(90,90,90);
                        $this->SetFont('Arial','',8);
                        $this->SetX($margin_left+$arrw[0]+$arrw[1]);
                        $this->MultiCell($arrw[2],$ch,$fr2,"0","L");
                        $max_y = max($this->GetY(),$max_y);
                        $this->SetFont('Arial','',9);
                        $this->SetTextColor(0,0,0);
                     }
                     
                     switch($method_t) {
                        case "TRN_EX":
                        case "TRN_IN":
                           $this->SetTextColor(90,90,190);
                           $this->SetFont('Arial','','8');
                           $this->SetX($margin_left+$arrw[0]+$arrw[1]);
                           $this->MultiCell($arrw[2],$ch,"IDR : ".toMoneyShort($cost_estimate),"0","L");
                           $max_y = max($this->GetY(),$max_y);
                           $this->SetFont('Arial','','9');
                           $this->SetTextColor(0,0,0);
                           break;
                        default:
                           break;
                     }
                     
                     $this->Ln(1);
                     
                     $max_y0 = $this->GetY();
                     
                     $this->SetY($ap_y1);
                     $this->SetX($margin_left+$arrw[0]+$arrw[1]+$arrw[2]);
                     $this->MultiCell($arrw[3],$ch,sql2ind($actionplan_start,"date")." - ".sql2ind($actionplan_stop,"date"),"0","C");
                     if($aap_status_cd=="completed") {
                        $this->SetX($margin_left+$arrw[0]+$arrw[1]+$arrw[2]);
                        $this->MultiCell($arrw[3],$ch,"Status: completed","0","C");
                     }
                     $this->Ln(1);
                     $max_y = max($this->GetY(),$max_y);
                     $this->SetY(max($max_y0,$this->GetY()));
                     
                     ///".sql2ind($actionplan_start,"date")." - ".sql2ind($actionplan_stop,"date")."
                     
                     $action_txt .= "<div style='padding:0px;".($aap_status_cd=="completed"?"background-color:#dff;":"")."'>"
                                  . "<table class='tblidpcom' style='border-spacing:0px;'><colgroup>"
                                  . "<col width='198'/>"
                                  . "<col/>"
                                  . "<col width='260'/>"
                                  . "</colgroup>"
                                  . "<tbody>"
                                  . "<tr>"
                                  . "<td style='width:198px;'>$method_type"
                                  . ($method_t!="PROJECT"?" [<span class='ylnk' onclick='edit_action_plan(\"$request_id\",\"$actionplan_id\",this,event);'>edit</span>]":"")
                                  . "</td>"
                                  . "<td>$actionplan_remark"
                                  . ($method_t=="PROJECT"||$method_t=="SELF"||$method_t=="COACH"||$method_t=="COUNSL"?"":"<div style='font-size:0.9em;color:#888;'>IDR ".toMoneyShort($cost_estimate)."</div>")
                                  . "</td>"
                                  . "<td style='width:260px;text-align:center;border-right:0px;'><div style='width:260px;'>".sql2ind($actionplan_start,"date")." - ".sql2ind($actionplan_stop,"date")."</div>"
                                  . ($aap_status_cd=="completed"?"<div style='color:green;width:260px;'>Completed</div>":$event_status_txt)
                                  ."</td>"
                                  . "</tr>"
                                  . "</tbody>"
                                  . "</table>"
                                  . "</div>";
                  }
               
               }
            }
            
            $this->SetY($max_y);
                  
            ///////////////////////////////////////////////////////////////////////
            $this->SetLineWidth(0.4);
            $this->mRoundedRect->RoundedRect($xx, $yy, $x1-$xx, $max_y-$yy, 0.75, 'S');
            
            $this->SetLineWidth(0.1);
            $this->Line($xx+$arrw[0],$ap_y0-$ch,$xx+$arrw[0],$max_y);
            $this->Line($xx+$arrw[0]+$arrw[1],$ap_y0-$ch,$xx+$arrw[0]+$arrw[1],$max_y);
            $this->Line($xx+$arrw[0]+$arrw[1]+$arrw[2],$ap_y0-$ch,$xx+$arrw[0]+$arrw[1]+$arrw[2],$max_y);
            $this->Ln($ch);
         }
      }
      
      if($this->GetY()>$bottom_y) {
         $this->AddPage();
         $ap_y0 = $ap_y1 = $max_y = $yy = $this->GetY();
      }
      $this->SetFont('Arial','B',10);
      $this->Cell(0,5,"Assignment Action Plan :",0,1);
      /////////////////////////////////////////////
      $myw = $this->w-30;
      $cw = floor($myw/5);
      $myw = 5*$cw;
      $arrw = array($cw-10,$cw+20,$cw-20,$cw,$cw+10);
      $xx = $this->GetX();
      $yy = $this->GetY();
      
      $max_y = $yy;
      $sql = "SELECT kpo,project_nm,project_id,start_dttm,due_dttm,priority_no,cost_estimate,report_status_cd"
           . " FROM ".XOCP_PREFIX."idp_project"
           . " WHERE request_id = '$request_id'"
           . " ORDER BY priority_no";
      $result = $db->query($sql);
      $project_no = 0;
      if($db->getRowsNum($result)>0) {
         while(list($kpo,$project_nm,$project_id,$start_dttm,$due_dttm,$priority_no,$cost_estimate,$report_status_cd)=$db->fetchRow($result)) {
            $nl_project_nm = $this->NbLines($arrw[0],$project_nm);
            $nl_kpo = $this->NbLines($arrw[4],$kpo);
            $ny_project_nm = $this->GetY()+($nl_project_nm*$ch);
            $ny_kpo = $this->GetY()+($nl_kpo*$ch);
            $ny = max($ny_kpo,$ny_project_nm);
            if($ny>$bottom_y) {
               $this->AddPage();
               $ap_y0 = $ap_y1 = $max_y = $yy = $this->GetY();
            }
            $this->SetLineWidth(0.1);
            $yy = $this->GetY();
            $this->SetFont("Arial","B","9");
            $project_no++;
            $this->Cell($arrw[0],$ch+1,"Project Assignment $project_no","B",0,"C");
            $this->Cell($arrw[1],$ch+1,"Activities","B",0,"C");
            $this->Cell($arrw[2],$ch+1,"Time Frame","B",0,"C");
            $this->Cell($arrw[3],$ch+1,"KPD","B",0,"C");
            $this->Cell($arrw[4],$ch+1,"KPO","B",0,"C");
            $this->SetFont("Arial","","9");
            $x1 = $this->GetX();
            $this->Ln();
            $max_y = max($this->GetY(),$max_y);
            
            $ap_y0 = $this->GetY();
            
            $this->SetFont('Arial','',9);
            $this->MultiCell($arrw[0],$ch,$project_nm,0,"L");
            $max_y = max($this->GetY(),$max_y);
            $this->SetY($ap_y0);
            $this->SetX($margin_left+$arrw[0]+$arrw[1]+$arrw[2]+$arrw[3]);
            $this->MultiCell($arrw[4],$ch,$kpo,0,"L");
            $max_y = max($this->GetY(),$max_y);
            
            
            $this->SetY($ap_y0);
            ///////////////////////////////////////////////////////////////////////
            /// Render Activities
            $sql = "SELECT activity_id,activity_nm,kpd,activity_start_dttm,activity_stop_dttm,status_cd FROM ".XOCP_PREFIX."idp_project_activities"
                 . " WHERE request_id = '$request_id' AND project_id = '$project_id' AND status_cd IN ('normal','finish')"
                 . " ORDER BY activity_start_dttm,activity_stop_dttm";
            $resultact = $db->query($sql);
            $activity_txt = "";
            if($db->getRowsNum($resultact)>0) {
               $no = 0;
               $max_y1 = 0;
               while(list($activity_id,$activity_nm,$kpd,$activity_start_dttm,$activity_stop_dttm,$status_cd)=$db->fetchRow($resultact)) {
                  if(trim($activity_nm)=="") {
                     $activity_nm = _EMPTY;
                  }
                  if(trim($kpd)=="") {
                     $kpd = _EMPTY;
                  }
                  
                  
                  
                  $y_nl0 = ($this->NbLines($arrw[1],$activity_nm)*$ch)+$max_y;
                  $y_nl1 = ($this->NbLines($arrw[3],$kpd)*$ch)+$max_y;
                  $y_nl2 = (2*$ch)+$max_y; /// time frame
                  
                  if(max($y_nl0,$y_nl1,$y_nl2)>$bottom_y) {
                     $this->SetY($max_y);
                     $this->SetLineWidth(0.4);
                     $this->mRoundedRect->RoundedRect($xx, $yy, $x1-$xx, $max_y-$yy, 0.75, 'S');
                     $this->SetLineWidth(0.1);
                     $this->Line($xx+$arrw[0],$yy,$xx+$arrw[0],$max_y);
                     $this->Line($xx+$arrw[0]+$arrw[1],$yy,$xx+$arrw[0]+$arrw[1],$max_y);
                     $this->Line($xx+$arrw[0]+$arrw[1]+$arrw[2],$yy,$xx+$arrw[0]+$arrw[1]+$arrw[2],$max_y);
                     $this->Line($xx+$arrw[0]+$arrw[1]+$arrw[2]+$arrw[3],$yy,$xx+$arrw[0]+$arrw[1]+$arrw[2]+$arrw[3],$max_y);
                     
                     $this->AddPage();
                     $ap_y0 = $ap_y1 = $max_y = $yy = $max_y1 = $this->GetY();
                     $no = 0;
                  }
                  
                  if($no>0) {
                     $this->Line($margin_left+$arrw[0],$this->GetY(),$margin_left+$arrw[0]+$arrw[1]+$arrw[2]+$arrw[3],$this->GetY());
                  }
                  
                  $no++;
                  
                  $ap_y1 = $this->GetY();
                  $this->SetX($margin_left+$arrw[0]);
                  $this->MultiCell($arrw[1],$ch,$activity_nm,0,"L");
                  $max_y = max($this->GetY(),$max_y);
                  $max_y1 = max($this->GetY(),$max_y1);
                  
                  $this->SetY($ap_y1);
                  $this->SetX($margin_left+$arrw[0]+$arrw[1]);
                  $this->MultiCell($arrw[2],$ch,sql2ind($activity_start_dttm,"date")." -\n".sql2ind($activity_stop_dttm,"date"),0,"C");
                  $max_y = max($this->GetY(),$max_y);
                  $max_y1 = max($this->GetY(),$max_y1);
                  
                  $this->SetY($ap_y1);
                  $this->SetX($margin_left+$arrw[0]+$arrw[1]+$arrw[2]);
                  $this->MultiCell($arrw[3],$ch,$kpd,0,"L");
                  $max_y = max($this->GetY(),$max_y);
                  $max_y1 = max($this->GetY(),$max_y1);
                  
                  $this->SetY($max_y1);
               }
            }
            
            ///////////////////////////////////////////////////////////////////////
            $this->SetY($max_y);
                  
            $this->SetLineWidth(0.4);
            $this->mRoundedRect->RoundedRect($xx, $yy, $x1-$xx, $max_y-$yy, 0.75, 'S');
            $this->SetLineWidth(0.1);
            $this->Line($xx+$arrw[0],$yy,$xx+$arrw[0],$max_y);
            $this->Line($xx+$arrw[0]+$arrw[1],$yy,$xx+$arrw[0]+$arrw[1],$max_y);
            $this->Line($xx+$arrw[0]+$arrw[1]+$arrw[2],$yy,$xx+$arrw[0]+$arrw[1]+$arrw[2],$max_y);
            $this->Line($xx+$arrw[0]+$arrw[1]+$arrw[2]+$arrw[3],$yy,$xx+$arrw[0]+$arrw[1]+$arrw[2]+$arrw[3],$max_y);
            $this->Ln();
         }
      }
      
      list($employee_job_id,
           $employee_employee_idx,
           $employee_job_nm,
           $employee_nm,
           $employee_nip,
           $employee_gender,
           $employee_jobstart,
           $employee_entrance_dttm,
           $employee_jobage,
           $employee_job_summary,
           $employee_person_id,
           $employee_user_idx,
           $employee_first_assessor_job_id,
           $employee_next_assessor_job_id)=_hris_getinfobyemployeeid($employee_id);
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$employee_first_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
	  _dumpvar($sql);
      if($db->getRowsNum($result)==1) {
         list($first_assessor_job,$first_assessor_nip,$first_assessor_nm,$first_assessor_employee_id)=$db->fetchRow($result);
      }
      
      $sql = "SELECT a.job_nm,b.employee_ext_id,c.person_nm,b.employee_id"
           . " FROM ".XOCP_PREFIX."jobs a"
           . " LEFT JOIN ".XOCP_PREFIX."employee_job e USING(job_id)"
           . " LEFT JOIN ".XOCP_PREFIX."employee b USING(employee_id)"
           . " LEFT JOIN ".XOCP_PREFIX."persons c USING(person_id)"
           . " WHERE a.job_id = '$employee_next_assessor_job_id'"
           . " ORDER BY e.start_dttm DESC LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($next_assessor_job,$next_assessor_nip,$next_assessor_nm,$next_assessor_employee_id)=$db->fetchRow($result);
      }
      
      if($this->GetY()+(6*$ch)>$bottom_y) {
         $this->AddPage();
      }
      
      $arrw = array(80,80,80);
      $this->Ln($ch);
      $margin_left = $this->GetX()+(floor($myw-240)/1);
      $yy = $this->GetY();
      $this->SetX($margin_left);
      $this->Cell($arrw[0],$ch,"Requested By,","B",0,"C");
      $this->Cell($arrw[1],$ch,"Approved By Superior,","B",0,"C");
      $this->Cell($arrw[2],$ch,"Approved By Next Superior,","B",0,"C");
      $this->Ln();
      $this->SetX($margin_left);
      $this->SetFont('Arial','B',9);
      $this->Cell($arrw[0],$ch,$employee_nm,"B",0,"C");
      $this->Cell($arrw[1],$ch,$first_assessor_nm,"B",0,"C");
      $this->Cell($arrw[2],$ch,$next_assessor_nm,"B",0,"C");
      $this->Ln();
      $this->SetFont('Arial','',9);
      $this->SetX($margin_left);
      $this->Cell($arrw[0],$ch,"Employee","B",0,"C");
      $this->Cell($arrw[1],$ch,$first_assessor_job,"B",0,"C");
      $this->Cell($arrw[2],$ch,$next_assessor_job,"B",0,"C");
      $this->Ln();
      $this->SetX($margin_left);
      $this->Cell($arrw[0],$ch,"Requested At,","",0,"C");
      $this->Cell($arrw[1],$ch,"Approved At,","",0,"C");
      $this->Cell($arrw[2],$ch,"Approved At,","",0,"C");
      $this->Ln();
      $this->SetX($margin_left);
      $this->Cell($arrw[0],$ch,sql2ind($requested_dttm),"",0,"C");
      $this->Cell($arrw[1],$ch,sql2ind($approve_superior_dttm),"",0,"C");
      $this->Cell($arrw[2],$ch,sql2ind($approve_higher_superior_dttm),"",0,"C");
      $this->Ln($ch);
      $h = $this->GetY()-$yy;
      $this->SetLineWidth(0.4);
      $this->mRoundedRect->RoundedRect($margin_left, $yy, 240, $h, 0, 'S');
      $this->Line($margin_left+$arrw[0],$yy,$margin_left+$arrw[0],$yy+$h);
      $this->Line($margin_left+$arrw[0]+$arrw[1],$yy,$margin_left+$arrw[0]+$arrw[1],$yy+$h);
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
      $this->Cell(100,10,"Generated: HRIS ".sql2ind(getSQLDate())." by $user_nm ($user_nip) / IDP : $employee_nm - $nip / $xrequest_id",0,0,'L');
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

if(isset($_GET["r"])&&$_GET["r"]>0) {
   $arr_print[] = $_GET["r"]+0;
}

$pdf = new _fpdf_IDPRequest( 'L', 'mm', 'A4' );
$pdf->Open();
$pdf->formInit();

$pdf->SetMargins(15,15,15);
global $page_count;
$page_count = 0;
$pdf->SetDrawColor(0,0,0);
$pdf->SetTextColor(0,0,0);

global $xrequest_id;
if(count($arr_print)>0) {
   foreach($arr_print as $request_id) {
      $xrequest_id = $request_id;
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(FALSE);
      $pdf->AliasNbPages();
      $pdf->incumbent($request_id);
      $pdf->idp_request($request_id);
   }
} else {
   $this->AddPage();
}

$pdf->Output();




