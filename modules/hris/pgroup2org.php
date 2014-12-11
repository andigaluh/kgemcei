<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/pgroup2org.php                              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2002-03-24                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_PGROUP2ORG_DEFINED') ) {
   define('HRIS_PGROUP2ORG_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/class/ajax_pgroup2org.php");

class _hris_Pgroup2Org extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_PGROUP2ORG_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_PGROUP2ORG_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $org_id;
   var $obj;
   
   function _hris_Pgroup2Org($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                            yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);          /* ini meneruskan $catch ke parent constructor */
      
      parent::init($catch);
      
      $this->org_id = $_SESSION["hris_org_id"];
      
   }
   
   
   function browse($f = NULL) {
      if($f == NULL) {
         $db = Database::getInstance();
         $sql = "SELECT a.pgroup_cd,a.pgroup_id,b.org_id,c.org_nm"
              . " FROM ".XOCP_PREFIX."pgroups a"
              . " LEFT JOIN ".XOCP_PREFIX."pgroup2org b USING (pgroup_id)"
              . " LEFT JOIN ".XOCP_PREFIX."orgs c USING (org_id)"
              . " ORDER BY a.pgroup_cd,c.org_nm";
         $result = $db->query($sql);
         $c = $db->getRowsNum($result);
         if($c > 0) {
            $dp = new XocpDataPage();
            $dp->setPageSize(20);
            unset($old_pgroup_id);
            unset($old_pgroup_cd);
            while(list($pgroup_cd,$pgroup_id,$org_id,$org_nm)=$db->fetchRow($result)) {
               if($pgroup_id != $old_pgroup_id) {
                  if(isset($old_pgroup_cd)) {
                     $dp->addData(array($old_pgroup_id,$old_pgroup_cd,implode("<br/>",$orglist)));
                  }
                  unset($orglist);
                  $orglist = array();
                  if ($org_nm != "") {
                  	$orglist[] = $org_nm;
                  }
                  $old_pgroup_cd = $pgroup_cd;
                  $old_pgroup_id = $pgroup_id;
               } else {
                  if ($org_nm != "") {
                  	$orglist[] = $org_nm;
                  }
               }
            }
            $dp->addData(array($old_pgroup_id,$old_pgroup_cd,implode(", ",$orglist)));
            $dp->serialize();
            $found = $dp->getCount();
            $dpfile = $dp->getFile();
            $dp->reset();
         } else {
            return $sql . _HRIS_PGROUP_NOTFOUND;
         }
      } else {
         $dp = XocpDataPage::unserialize($f);
         $dp->setPage($_GET["p"]);
         $found = $dp->getCount();
      }

      $xurl = XOCP_SERVER_SUBDIR."/index.php?".$this->getURLParam();
      $prevnext = $dp->getPageLinks($xurl);

      $htable = new XocpSimpleTable();
      $sno = $htable->addRow(_HRIS_PGROUP_LIST." : $found "._FOUND,$prevnext);
      $htable->setCellAlign($sno,array("","right"));
      $htable->setWidth("100%");

      $ftable = new XocpSimpleTable();
      $sno = $ftable->addRow($prevnext);
      $ftable->setCellAlign($sno,array("right"));
      $ftable->setWidth("100%");

      $table = new XocpTable(0);
      $table->setWidth("100%");
      $hno = $table->addHeader($htable->render());
      $table->setColSpan($hno,2);
      $fno = $table->addFooter($ftable->render());
      $table->setColSpan($fno,2);
      
      $no = $dp->getOffset() + 1;
      $data = $dp->retrieve();
      foreach($data as $x) {
         list($pgroup_id,$pgroup_cd,$orglist) = $x;
         $rno = $table->addRow($no,"<a href='$xurl&amp;edit=y&amp;x=$pgroup_id'>$pgroup_cd</a><br/>$orglist");
         $table->setCellAlign($rno,"center");
         $no++;
      }

      return $table->render();

   }
   



   function editPGroup($pgroup_id) {
      $db =& Database::getInstance();
      $sql = "SELECT a.pgroup_cd,c.org_id,c.org_nm,c.org_cd,d.org_class_nm"
           . " FROM ".XOCP_PREFIX."pgroups a"
           . " LEFT JOIN ".XOCP_PREFIX."pgroup2org b USING (pgroup_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c USING (org_id)"
           . " LEFT JOIN ".XOCP_PREFIX."org_class d USING(org_class_id)"
           . " WHERE a.pgroup_id = '$pgroup_id'"
           . " ORDER BY c.org_nm";
      $result = $db->query($sql);
      $c = $db->getRowsNum($result);
      $pgroup_cd = "";
      if($c > 0) {
         while(list($pgroup_cdx,$org_id,$org_nm,$org_cd,$org_class_nm) = $db->fetchRow($result)) {
            $orgarray[$org_id] = "$org_cd $org_nm [$org_class_nm]";
            $pgroup_cd = $pgroup_cdx;
         }
      }
      
      $pgroupname = new XocpFormLabel(_HRIS_PGROUP_NAME, "<span style='font-weight:bold;'>$pgroup_cd</span>");
      $elementtray_orgs = new XocpFormElementTray(_HRIS_PGROUP_ASSIGNEDPORGLIST,"<br/>");
      if(is_array($orgarray)) {
         reset($orgarray);
         $n = 0;
         foreach($orgarray as $org_id=>$org_nm) {
            if(empty($org_nm)) continue;
            $ckbname = "org$n";
            $xckb = new XocpFormCheckBox("",$ckbname);
            $xckb->addOption($org_id,$org_nm);
            $elementtray_orgs->addElement($xckb);
            $n++;
         }
         if($n>0) {
            $delete_org = new XocpFormButton("", "deleteorg", _DELETE, "submit");
            $elementtray_orgs->addElement($delete_org);
         } else {
            $noorg = new XocpFormLabel("",_HRIS_PGROUP_NOORGASSIGNED);
            $elementtray_orgs->addElement($noorg);
         }
      }
   
   
      $hidden_n = new XocpFormHidden("n",$n);
      $hpgroup_id = new XocpFormHidden("upgroup","$pgroup_id");
      
      $add_button = new XocpFormButton("", "addorg", _ADD, "submit");
      $selectorg = new XocpFormSelect("","addorg_id");
      $sql = "SELECT a.org_cd,a.org_id,a.org_nm,a.description,b.org_class_nm" 
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.status_cd = 'normal'"
           . " ORDER BY a.org_cd";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_cd,$org_id,$org_nm,$description,$org_class_nm)=$db->fetchRow($result)) {
            $selectorg->addOption($org_id,"$org_cd $org_nm [$org_class_nm]");
         }
      }
      
      $elementtray_buttons = new XocpFormElementTray(_HRIS_PGROUP_ADDORG,"<br/>");
      $elementtray_buttons->addElement($selectorg);
      $elementtray_buttons->addElement($add_button);
   
      $cancel_button = new XocpFormButton("", "cancel", _CANCEL, "submit");
      
      $form = new XocpThemeForm(_HRIS_GROUP_EDITACCESS, "updatepgroup2orgform", "index.php","post");
      $form->setWidth("100%");
               
      $form->addElement($pgroupname);
      $form->addElement($elementtray_orgs);
      $form->addElement($this->varForm());
      $form->addElement($hidden_n);
      $form->addElement($hpgroup_id);
      $form->addElement($elementtray_buttons);
      $form->addElement($cancel_button);
   
      return $form;
   }

   function setMgtAccess() {
      $ret = "";
      $ret .= "<div>TEST TEST</div>";

      return $ret;

   }



   function main() {
      switch($this->catch) {
         case _HRIS_PGROUP2ORG_BLOCK :
            _dumpVar($_GET);
            if($_GET["f"] != "") {
               $ret = $this->browse($_GET["f"]);
               break;
            } else if($_GET["edit"] == "y") {
               $form = $this->editPGroup($_GET["x"]);
               $ret .= $form->render();
               break;
            } else if(!empty($_POST["addorg"])) {
               $db =& Database::getInstance();
               $sql = "INSERT INTO ".XOCP_PREFIX."pgroup2org (pgroup_id,org_id)"
                    . " VALUES ('".$_POST["upgroup"]."','".$_POST["addorg_id"]."')";
               $db->query($sql);
               $form = $this->editPGroup($_POST["upgroup"]);
               $form->setComment($db->error());
               $ret .= $form->render();
               break;
            } else if(!empty($_POST["deleteorg"])) {
               $pgroup_id = $_POST["upgroup"];
               $n = $_POST["n"];
               $db =& Database::getInstance();
               if($n>0) {
                  for($i=0;$i<$n;$i++) {
                     $org_id = $_POST["org$i"];
                     if($org_id != '') {
                        $sql = "DELETE FROM ".XOCP_PREFIX."pgroup2org"
                             . " WHERE pgroup_id = '$pgroup_id' AND org_id = '$org_id'";
                        $db->query($sql);
                     }
                  }
               }
               $form = $this->editPGroup($pgroup_id);
               $ret .= $form->render();
               break;
            }

         default :
   
            $ret .= $this->browse();

      }

      return $ret;
      
   }
   
}

} // HRIS_PGROUP2ORG_DEFINED
?>