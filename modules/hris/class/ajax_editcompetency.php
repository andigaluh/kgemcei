<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_editcompetency.php              //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_COMPETENCYAJAX_DEFINED') ) {
   define('HRIS_COMPETENCYAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_EditCompetencyAjax extends AjaxListener {
   
   function _hris_class_EditCompetencyAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_editcompetency.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editCompetency","app_saveCompetency",
                            "app_vCompetency","app_editBehaviour","app_vBehaviour","app_saveBehaviour",
                            "app_deleteBehaviour","app_addBehaviour","app_editQA","app_vQA",
                            "app_saveQA","app_deleteQA","app_editLevelTitle","app_saveLevelTitle",
                            "app_searchCompetency");
   }
   
   function app_searchCompetency($args) {
      $db=&Database::getInstance();
      $q = $args[0];
      $compgroup_id = $args[1];
      $qstr = formatQueryString($q);
      $ret = array();
      
      if($compgroup_id>0) {
         $qgroup = " AND a.compgroup_id = '$compgroup_id'";
      } else {
         $qgroup = "";
      }
      
      $sql = "SELECT a.competency_id, a.competency_nm, a.competency_cd, "
           . "MATCH(a.competency_abbr) AGAINST('$qstr' IN BOOLEAN MODE) as score0, "
           . "MATCH (a.competency_nm) AGAINST ('$qstr' IN BOOLEAN MODE) as score"
           . " FROM ".XOCP_PREFIX."competency a"
           . " WHERE (MATCH (a.competency_nm) AGAINST ('$qstr' IN BOOLEAN MODE)"
//           . " OR MATCH (a.competency_cd) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . " OR MATCH (a.competency_abbr) AGAINST ('$qstr' IN BOOLEAN MODE)"
           . ")"
           . $qgroup
           . " ORDER BY score DESC, score0 DESC";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         $no = 0;
         while(list($competency_id,$competency_nm,$competency_cd)=$db->fetchRow($result)) {
            if($no >= 1000) break;
            $ret[] = array("$competency_nm [$competency_cd]",$competency_id);
            $no++;
         }
      }
      
      if(count($ret)>0) {
         return $ret;
      } else {
         return "EMPTY";
      }
      
   }
   
   function app_saveLevelTitle($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $lvl = $args[1];
      $level_en = addslashes(trim(urldecode($args[2])));
      $level_id = addslashes(trim(urldecode($args[3])));
      $sql = "UPDATE ".XOCP_PREFIX."competency SET desc_en_level_${lvl} = '$level_en',"
           . "desc_id_level_${lvl} = '$level_id'"
           . " WHERE competency_id = '$competency_id'";
      $db->query($sql);
      return $this->vLevelTitle($competency_id,$lvl);
   }
   
   function vLevelTitle($competency_id,$lvl) {
      $db=&Database::getInstance();
      $sql = "SELECT desc_en_level_${lvl},desc_id_level_${lvl}"
           . " FROM ".XOCP_PREFIX."competency"
           . " WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($en,$id)=$db->fetchRow($result);
         if(trim($en)=="") {
            $en = _EMPTY;
         }
         if(trim($id)=="") {
            $id = _EMPTY;
         }
         //// level description
         return "";
         $ret = "<div><span style='font-style:italic;'>$en</span><br/>"
              . "<span style='font-style:italic;'>$id</span></div>"
              . "[<span class='xlnk' onclick='editleveltitle(\"$competency_id\",\"$lvl\",this,event);'>edit</span>]";
         return $ret;
      }
   }
   
   function app_editLevelTitle($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $lvl = $args[1];
      $sql = "SELECT desc_en_level_${lvl},desc_id_level_${lvl} FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      list($desc_en_txt,$desc_id_txt) = $db->fetchRow($result);
      $txt_en = htmlentities($desc_en_txt,ENT_QUOTES);
      $txt_id = htmlentities($desc_id_txt,ENT_QUOTES);
      $ret = "EN:<br/><textarea id='level_en' style='width:90%;'>$txt_en</textarea>"
           . "<br/>ID:<br/><textarea id='level_id' style='width:90%;'>$txt_id</textarea>"
           . "<div style='text-align:left;padding:1px;'>"
               . "[<span class='xlnk' onclick='save_leveltitle();'>save</span>]"
               . "&nbsp;[<span class='xlnk' onclick='cancel_editleveltitle();'>cancel</span>]"
           . "</div>";
      return array($competency_id,$lvl,$ret);
   }

   function app_deleteQA($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $pl = $args[1];
      $bhid = $args[2];
      $ca_id = $args[3];
      $sql = "DELETE FROM ".XOCP_PREFIX."compbehaviour_qa"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$pl'"
           . " AND behaviour_id = '$bhid'"
           . " AND ca_id = '$ca_id'";
      $db->query($sql);
   }
   
   function app_saveQA($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $pl = $args[1];
      $bhid = $args[2];
      $ca_id = $args[3];
      $arr = parseForm($args[4]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
         // $$k = trim($v);
      }
      $sql = "UPDATE ".XOCP_PREFIX."compbehaviour_qa SET "
           . "q_en_txt = '$q_en_txt',"
           . "q_id_txt = '$q_id_txt',"
           . "a_en_txt = '$a_en_txt',"
           . "a_id_txt = '$a_id_txt',"
           . "qa_method = '$qa_method'"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$pl'"
           . " AND behaviour_id = '$bhid'"
           . " AND ca_id = '$ca_id'";
      $db->query($sql);
      return $this->app_vQA(array($competency_id,$pl,$bhid,$ca_id));
   }
   
   function app_vQA($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $pl = $args[1];
      $bhid = $args[2];
      $ca_id = $args[3];
      $sql = "SELECT q_en_txt,a_en_txt,q_id_txt,a_id_txt,"
           . "qa_method"
           . " FROM ".XOCP_PREFIX."compbehaviour_qa"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$pl'"
           . " AND behaviour_id = '$bhid'"
           . " AND ca_id = '$ca_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($q_en_txt,$a_en_txt,$q_id_txt,$a_id_txt,$qa_method)=$db->fetchRow($result);
      }
      if(trim($q_en_txt)=="") {
         $q_en_txt = _EMPTY;
      }
      if(trim($q_id_txt)=="") {
         $q_id_txt = _EMPTY;
      }
      if(trim($a_en_txt)=="") {
         $a_en_txt = _EMPTY;
      }
      if(trim($a_id_txt)=="") {
         $a_id_txt = _EMPTY;
      }
      $ret = "<table class='qaitem'>"
           . "<colgroup><col width='6%'/><col width='47%'/><col width='47%'/></colgroup>"
           . "<tbody>"
           
           . "<tr><td>&nbsp;</td><td colspan='2' class='method'>&nbsp;Method: $qa_method</td></tr>"
           
           . "<tr>"
           . "<td>$ca_id</td>"
           
           . "<td>"
           . "<span>$q_en_txt</span><br/>"
           . "<span style='font-style:italic;'>$q_id_txt</span>"
           . "</td>"
           . "<td>"
           . "<span>$a_en_txt</span><br/>"
           . "<span style='font-style:italic;'>$a_id_txt</span>"
           . "</td>"
           
           . "</tr><tr><td>&nbsp</td><td colspan='2' style='border:0px;'>"

           . "<div style='text-align:left;padding:1px;'>"
           . "[<span class='xlnk' onclick='edit_qa(\"$pl\",\"$bhid\",\"$ca_id\",this,event);'>edit</span>]"
           . "&nbsp;[<span class='xlnk' onclick='delete_qa(\"$pl\",\"$bhid\",\"$ca_id\",this,event);'>delete</span>]"
           . "</div>"

           . "</td>"
           . "</tr></tbody></table>";
      
      return array($pl,$bhid,$ca_id,$ret);
   }
   
   function app_editQA($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $pl = $args[1];
      $bhid = $args[2];
      $ca_id = $args[3];
      if($ca_id=="new") {
         $sql = "SELECT MAX(ca_id) FROM ".XOCP_PREFIX."compbehaviour_qa"
              . " WHERE competency_id = '$competency_id'"
              . " AND proficiency_lvl = '$pl'"
              . " AND behaviour_id = '$bhid'";
         $result = $db->query($sql);
         list($ca_id)=$db->fetchRow($result);
         $ca_id++;
         $q_en_txt=$a_en_txt=$q_id_txt=$a_id_txt="";
         $qa_method = "interview";
         $sql = "INSERT INTO ".XOCP_PREFIX."compbehaviour_qa (competency_id,proficiency_lvl,behaviour_id,ca_id)"
              . " VALUES ('$competency_id','$pl','$bhid','$ca_id')";
         $db->query($sql);
      } else {
         $sql = "SELECT q_en_txt,a_en_txt,q_id_txt,a_id_txt,"
              . "proficiency_lvl,behaviour_id,qa_method"
              . " FROM ".XOCP_PREFIX."compbehaviour_qa"
              . " WHERE competency_id = '$competency_id'"
              . " AND proficiency_lvl = '$pl'"
              . " AND behaviour_id = '$bhid'"
              . " AND ca_id = '$ca_id'";
         $result = $db->query($sql);
         if($db->getRowsNum($result)>0) {
            list($q_en_txt,$a_en_txt,$q_id_txt,$a_id_txt,$pfl,$bhi,$qa_method)=$db->fetchRow($result);
         }
      }
      $ckmethod[$qa_method] = "checked='1'";
      $ret = "<form id='qaform'><table class='qaitem_edit'>"
           . "<colgroup><col width='6%'/><col width='47%'/><col width='47%'/></colgroup>"
           . "<tbody>"
           
           // method
           . "<tr><td>&nbsp;</td><td colspan='2' class='method'>&nbsp;Method: "
           . "<input type='radio' value='review' name='qa_method' id='qa_method_review' $ckmethod[review]/> <label for='qa_method_review' class='xlnk'>Review</label>&nbsp;&nbsp;"
           . "<input type='radio' value='interview' name='qa_method' id='qa_method_interview' $ckmethod[interview]/> <label for='qa_method_interview' class='xlnk'>Interview</label>&nbsp;&nbsp;"
           . "<input type='radio' value='portfolio' name='qa_method' id='qa_method_portfolio' $ckmethod[portfolio]/> <label for='qa_method_portfolio' class='xlnk'>Portfolio</label>&nbsp;&nbsp;"
           . "<input type='radio' value='test' name='qa_method' id='qa_method_test' $ckmethod[test]/> <label for='qa_method_test' class='xlnk'>Test</label>&nbsp;&nbsp;"
           . "</td></tr>"
           //
           
           . "<tr>"
           . "<td>$ca_id</td>"
           . "<td>"
           . "<div style='text-align:center;'>question</div>"
           . "EN:<br/><textarea name='q_en_txt' id='q_en_txt' style='width:95%;'>$q_en_txt</textarea><br/>"
           . "ID:<br/><textarea name='q_id_txt' id='q_id_txt' style='width:95%;'>$q_id_txt</textarea>"
           . "</td>"
           . "<td>"
           . "<div style='text-align:center;'>evidence guide</div>"
           . "EN:<br/><textarea name='a_en_txt' id='a_en_txt' style='width:95%;'>$a_en_txt</textarea><br/>"
           . "ID:<br/><textarea name='a_id_txt' id='a_id_txt' style='width:95%;'>$a_id_txt</textarea>"
           
           . "</td></tr><tr><td>&nbsp</td><td colspan='2' style='border:0px;'>"
           
           . "<div style='text-align:left;padding:1px;'>"
           . "[<span class='xlnk' onclick='save_qa(\"$pl\",\"$bhid\",\"$ca_id\",this,event);'>save</span>]"
           . "&nbsp;[<span class='xlnk' onclick='cancel_qaedit(\"$pl\",\"$bhid\",\"$ca_id\",this,event);'>cancel</span>]"
           . "&nbsp;[<span class='xlnk' onclick='delete_qa(\"$pl\",\"$bhid\",\"$ca_id\",this,event);'>delete</span>]"
           . "</div>"

           . "</td>"
           . "</tr></tbody></table></form>";
           
      return array($pl,$bhid,$ca_id,$ret);
   }

   function app_addBehaviour($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $proficiency_lvl = $args[1];
      $sql = "SELECT MAX(behaviour_id) FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$proficiency_lvl'";
      $result = $db->query($sql);
      list($new_behaviour_id)=$db->fetchRow($result);
      $new_behaviour_id++;
      $sql = "INSERT INTO ".XOCP_PREFIX."compbehaviour (competency_id,proficiency_lvl,behaviour_id)"
           . " VALUES('$competency_id','$proficiency_lvl','$new_behaviour_id')";
      $db->query($sql);
      $behaviour_id = $new_behaviour_id;
      $ret = "<table class='tblbhv'>"
           . "<colgroup><col width='30'/><col/></colgroup>"
           . "<tbody><tr>"
           . "<td>$behaviour_id</td>"
           . "<td><div id='tdbhtxt${proficiency_lvl}_${behaviour_id}'>"
           . "EN:<br/><textarea id='bhtxt_en' style='width:90%;margin:1px;'></textarea>"
           . "<br/>ID:<br/><textarea id='bhtxt_id' style='width:90%;margin:1px;'></textarea>"
           . "<div style='text-align:left;padding:1px;'>"
               . "[<span class='xlnk' onclick='save_behaviour();'>save</span>]"
               . "&nbsp;[<span class='xlnk' onclick='cancel_editbehaviour();'>cancel</span>]"
               . "&nbsp;[<span class='xlnk' onclick='delete_behaviour(\"$proficiency_lvl\",\"$behaviour_id\",this,event);'>delete</span>]"
           . "</div></div>"
           
           /// questions and answers
           . "<div class='bhq'><div class='bhqheader'"
           . "<div style='float:left;'>Competency Assessment</div>"
           . "<div style='float:right;'>[<span class='xlnk' onclick='add_qa(\"$proficiency_lvl\",\"$behaviour_id\",this,event);'>add</span>]</div>"
           . "<div style='clear:both;'></div>"
           . "</div>"
           
           . "<div id='bhqitem${proficiency_lvl}_${behaviour_id}'>"
           . "<div id='qa${proficiency_lvl}_${behaviour_id}_empty' class='qa_empty'>Empty Competency Assessment</div>"
           . "</div>"
           
           . "</div>"
           . "</td></tr>"

           . "</tbody></table>";
      return array("bh${proficiency_lvl}_${behaviour_id}",$ret,$behaviour_id);
   }
   

   
   function app_deleteBehaviour($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $proficiency_lvl = $args[1];
      $behaviour_id = $args[2];
      $sql = "DELETE FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$proficiency_lvl'"
           . " AND behaviour_id = '$behaviour_id'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."compbehaviour_qa"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$proficiency_lvl'"
           . " AND behaviour_id = '$behaviour_id'";
      $db->query($sql);
   }
   
   function app_saveBehaviour($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $proficiency_lvl = $args[1];
      $behaviour_id = $args[2];
      $bhtxt_en = addslashes(trim(urldecode($args[3])));
      $bhtxt_id = addslashes(trim(urldecode($args[4])));
      $sql = "UPDATE ".XOCP_PREFIX."compbehaviour SET "
           . "behaviour_en_txt = '$bhtxt_en',"
           . "behaviour_id_txt = '$bhtxt_id'"
           . " WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$proficiency_lvl'"
           . " AND behaviour_id = '$behaviour_id'";
      $db->query($sql);
      _debuglog($sql);
      return $this->app_vBehaviour(array($competency_id,$proficiency_lvl,$behaviour_id));
   }
   
   function app_vBehaviour($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $proficiency_lvl = $args[1];
      $behaviour_id = $args[2];
      $sql = "SELECT behaviour_en_txt,behaviour_id_txt FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id' AND proficiency_lvl = '$proficiency_lvl'"
           . " AND behaviour_id = '$behaviour_id'";
      $result = $db->query($sql);
      list($behaviour_en_txt,$behaviour_id_txt)=$db->fetchRow($result);
      if(trim($behaviour_en_txt)=="") {
         $behaviour_en_txt = _EMPTY;
      }
      if(trim($behaviour_id_txt)=="") {
         $behaviour_id_txt = _EMPTY;
      }
      $behaviour_en_txt = htmlentities($behaviour_en_txt);
      $behaviour_id_txt = htmlentities($behaviour_id_txt);
      $ret = "<div style='font-weight:bold;text-align:center;'>Behaviour Description:</div><span>$behaviour_en_txt</span>"
           . "<br/><span style='font-style:italic;'>$behaviour_id_txt</span>"
           . "<br/>[<span class='xlnk' onclick='editbehaviour(\"$proficiency_lvl\",\"$behaviour_id\",this,event);'>edit</span>]"
           . "&nbsp;[<span class='xlnk' onclick='delete_behaviour(\"$proficiency_lvl\",\"$behaviour_id\",this,event);'>delete</span>]";
      return array($proficiency_lvl,$behaviour_id,$ret);
   }
   
   function app_editBehaviour($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $behaviour_id = $args[1];
      $proficiency_lvl = $args[2];
      $sql = "SELECT behaviour_en_txt,behaviour_id_txt FROM ".XOCP_PREFIX."compbehaviour WHERE competency_id = '$competency_id'"
           . " AND proficiency_lvl = '$proficiency_lvl' AND behaviour_id = '$behaviour_id'";
      $result = $db->query($sql);
      list($behaviour_en_txt,$behaviour_id_txt) = $db->fetchRow($result);
      $txt_en = htmlentities($behaviour_en_txt,ENT_QUOTES);
      $txt_id = htmlentities($behaviour_id_txt,ENT_QUOTES);
      $ret = "<div style='font-weight:bold;text-align:center;'>Behaviour Description:</div>EN:<br/><textarea id='bhtxt_en' style='width:90%;'>$txt_en</textarea>"
           . "<br/>ID:<br/><textarea id='bhtxt_id' style='width:90%;'>$txt_id</textarea>"
           . "<div style='text-align:left;padding:1px;'>"
               . "[<span class='xlnk' onclick='save_behaviour();'>save</span>]"
               . "&nbsp;[<span class='xlnk' onclick='cancel_editbehaviour();'>cancel</span>]"
               . "&nbsp;[<span class='xlnk' onclick='delete_behaviour(\"$proficiency_lvl\",\"$behaviour_id\",this,event);'>delete</span>]"
           . "</div>";
      return array("bh${proficiency_lvl}_${behaviour_id}",$ret,$behaviour_id);
   }
   
   function app_saveCompetency($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $arr = parseForm($args[1]);
      foreach($arr as $k=>$v) {
         $$k = addslashes(trim($v));
         //$$k = trim($v);
      }
      if($competency_id=="new") {
         $sql = "SELECT MAX(competency_id) FROM ".XOCP_PREFIX."competency";
         $result = $db->query($sql);
         list($competency_idx)=$db->fetchRow($result);
         $competency_id = $competency_idx+1;
         $user_id = getUserID();
         $_SESSION["new_compclass"] = $competency_class;
         $_SESSION["new_compgroup_id"] = $compgroup_id;
         $sql = "INSERT INTO ".XOCP_PREFIX."competency (competency_id,competency_nm,desc_en,created_user_id,competency_cd,competency_class,job_class_id,compgroup_id,competency_abbr,desc_id)"
              . " VALUES('$competency_id','$competency_nm','$desc_en','$user_id','$competency_cd','$competency_class','$job_class_id','$compgroup_id','$competency_abbr','$desc_id')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."competency SET "
              . "competency_nm = '$competency_nm',"
              . "desc_en = '$desc_en',"
              . "desc_id = '$desc_id',"
              . "competency_abbr = '$competency_abbr',"
              . "competency_cd = '$competency_cd',"
              . "competency_class = '$competency_class',"
              . "job_class_id = '$job_class_id',"
              . "compgroup_id = '$compgroup_id'"
              . " WHERE competency_id = '$competency_id'";
         $db->query($sql);
      }

      $sql = "SELECT a.competency_id,a.competency_cd,a.competency_nm,a.desc_en,"
           . "b.compgroup_nm,a.competency_class,a.job_class_id,a.competency_abbr"
           . " FROM ".XOCP_PREFIX."competency a"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
           . " WHERE a.competency_id = '$competency_id'";
      
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($competency_id,$competency_cd,$competency_nm,$desc_en,$compgroup_nm,$competency_class,$job_class_id,$competency_abbr)=$db->fetchRow($result);
      } else {
         return "EMPTY";
      }
      if(trim($competency_nm)=="") {
         $competency_nm = "[noname]";
      }
      $ret = "<table style='border:0px;width:100%;'>"
                  . "<colgroup><col width='50'/><col/><col width='75'/><col width='75'/></colgroup><tbody>"
                  . "<tr><td>$competency_abbr</td>"
                      . "<td><div style='overflow:hidden;width:340px;'><div style='width:900px;'><span id='sp_${competency_id}' class='xlnk' onclick='edit_competency(\"$competency_id\",this,event);'>".htmlentities($competency_nm)."</span></div></div></td>"
                      . "<td><div style='overflow:hidden;width:75px;'><div style='width:900px;'>$compgroup_nm</div></div></td>"
                      . "<td><div style='overflow:hidden;width:75px;'><div style='width:900px;'>$competency_class</div></div></td>"
                  . "</tr></tbody></table>";
      
      return array("dvcompetency_${competency_id}",$ret);
   }
   
   function app_editCompetency($args) {
      require_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");
      global $proficiency_level_name;
      $db=&Database::getInstance();
      $competency_id = $args[0];
      if($competency_id=="new") {
         $compgroup_id = $_SESSION["new_compgroup_id"];
         $competency_class = $_SESSION["new_compclass"];

         $sql = "SELECT MAX(competency_id) FROM ".XOCP_PREFIX."competency";
         $result = $db->query($sql);
         list($competency_idx)=$db->fetchRow($result);
         $competency_id = $competency_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."competency (competency_id,created_user_id)"
              . " VALUES('$competency_id','$user_id')";
         $db->query($sql);
         $compgroup_id = "";
         list($c,$wdv_dv)=$this->app_vCompetency(array($competency_id));
         $desc_en_level[1] = $desc_en_level[2] = $desc_en_level[3] = $desc_en_level[4] = "";
         $desc_id_level[1] = $desc_id_level[2] = $desc_id_level[3] = $desc_id_level[4] = "";
      } else {
         $wdv_dv = "EMPTY";
         $sql = "SELECT desc_en,desc_id,competency_nm,competency_cd,compgroup_id,competency_class,job_class_id,competency_abbr,"
              . "desc_en_level_1,desc_en_level_2,desc_en_level_3,desc_en_level_4"
              . " FROM ".XOCP_PREFIX."competency"
              . " WHERE competency_id = '$competency_id'";
         $result = $db->query($sql);
         list($desc_en,$desc_id,$competency_nm,$competency_cd,$compgroup_id,
              $competency_class,$job_class_id,$competency_abbr,
              $desc_en_level[1],$desc_en_level[2],$desc_en_level[3],$desc_en_level[4],
              $desc_id_level[1],$desc_id_level[2],$desc_id_level[3],$desc_id_level[4])=$db->fetchRow($result);
         $competency_nm = htmlentities($competency_nm,ENT_QUOTES);
         $competency_abbr = htmlentities($competency_abbr,ENT_QUOTES);
         $competency_cd = htmlentities($competency_cd,ENT_QUOTES);
         $desc_en = htmlentities($desc_en,ENT_QUOTES);
      }
      
      $sql = "SELECT compgroup_id,compgroup_nm"
           . " FROM ".XOCP_PREFIX."compgroup"
           . " ORDER BY compgroup_id";
      $result = $db->query($sql);
      $opt_compgroup = "";
      $radio_compgroup = "";
      if($db->getRowsNum($result)>0) {
         while(list($compgroup_idx,$compgroup_nmx)=$db->fetchRow($result)) {
            if($compgroup_id==$compgroup_idx) {
               $ckcg = "checked='1'";
            } else {
               $ckcg = "";
            }
            $radio_compgroup .= "<input type='radio' name='compgroup_id' id='cg_${compgroup_idx}' value='$compgroup_idx' $ckcg/> <label for='cg_${compgroup_idx}'>$compgroup_nmx</label>&nbsp;&nbsp;";
         }
      }
      $ckccl[$competency_class] = "checked='1'";

      $job_class_opt = "<select name='job_class_id' id='inp_job_class_id'>";
      $sqljbclass = "SELECT DISTINCT(job_class_id) FROM hris_mdp_employes WHERE job_class_id < 7 ORDER BY  job_class_id ASC ";
      $resultjbclass = $db->query($sqljbclass);     
      while(list($jobcl)=$db->fetchRow($resultjbclass)){
          $sql = "SELECT job_class_cd, job_class_nm,job_class_id FROM hris_job_class WHERE job_class_id = '$jobcl'";
          $result = $db->query($sql);
          list($job_cd,$job_class,$job_classid)=$db->fetchRow($result);   
          if($job_class_id == $job_classid){$selected = 'selected';}else{$selected='';}

          $job_class_opt .= "<option name='job_class_id' value=\"$jobcl\" $selected>$job_class</option>";
      }
      $job_class_opt .= "</select>";

      $radio_compclass = "<input type='radio' name='competency_class' id='ccl_soft' value='soft' $ckccl[soft]/> <label for='ccl_soft'>Soft</label>&nbsp;&nbsp;"
                       . "<input type='radio' name='competency_class' id='ccl_technical' value='technical' $ckccl[technical]/> <label for='ccl_technical'>Technical</label>&nbsp;&nbsp;";
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='120'/><col/></colgroup>"
           . "<tbody>"
           . "<tr><td>Competency Title</td><td><input type='text' value=\"$competency_nm\" id='inp_competency_nm' name='competency_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Abbreviation</td><td><input type='text' value=\"$competency_abbr\" id='inp_competency_abbr' name='competency_abbr' style='width:50px;'/></td></tr>"
           . "<tr><td>Code</td><td><input type='text' value=\"$competency_cd\" id='inp_competency_cd' name='competency_cd' style='width:50px;'/></td></tr>"
           . "<tr><td>Job Class</td><td>"
           . $job_class_opt
           . "</td></tr>"
           . "<tr><td>Group</td><td>"
           . $radio_compgroup
           . "</td></tr>"
           . "<tr><td>Classification</td><td>"
           . $radio_compclass
           . "</td></tr>"
           . "<tr><td>Description</td><td>"
           . "EN:<br/><textarea name='desc_en' id='desc_en' style='width:90%;'>$desc_en</textarea><br/>"
           . "ID:<br/><textarea name='desc_id' id='desc_id' style='width:90%;'>$desc_id</textarea>"
           . "</td></tr>"

           . "<tr><td colspan='2' style='background-color:#ddd;'>"
           
           . "<table style='width:100%;border-spacing:0px;'><tbody><tr><td style='text-align:left;'>"
           
           . "<input onclick='print_competency();' type='button' value='"._PRINT."'/>&nbsp;"
           
           . "</td><td style='text-align:right;'>"
           
           . "<input onclick='save_competency();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($competency_id!="new"?"<input onclick='delete_competency();' type='button' value='"._DELETE."'/>":"")
           
           . "</td></tr></tbody></table>"
           
           . "</td></tr>"

           . "<tr></tbody></table></form>"
           
           . "<table width='100%'><tbody><tr><td style='background-color:#ffffff;text-align:left;padding:10px;'>";
           
      ///// behaviour
      $sql = "SELECT behaviour_id,behaviour_en_txt,behaviour_id_txt,proficiency_lvl FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id'";
      $result = $db->query($sql);
      $bhitem = array();
      if($db->getRowsNum($result)>0) {
         while(list($behaviour_id,$behaviour_en_txt,$behaviour_id_txt,$proficiency_lvl)=$db->fetchRow($result)) {
            $behaviour_en_txt = htmlentities($behaviour_en_txt,ENT_QUOTES);
            $behaviour_id_txt = htmlentities($behaviour_id_txt,ENT_QUOTES);
            $bhitem[$proficiency_lvl][$behaviour_id] = array($behaviour_en_txt,$behaviour_id_txt);
         }
      }
      
      ///// questions and answers
      $sql = "SELECT ca_id,q_en_txt,a_en_txt,q_id_txt,a_id_txt,"
           . "proficiency_lvl,behaviour_id,qa_method"
           . " FROM ".XOCP_PREFIX."compbehaviour_qa"
           . " WHERE competency_id = '$competency_id'"
           . " ORDER BY proficiency_lvl,behaviour_id,ca_id";
      $result = $db->query($sql);
      $bhqitem = array();
      if($db->getRowsNum($result)>0) {
         while(list($ca_id,$q_en_txt,$a_en_txt,$q_id_txt,$a_id_txt,
                    $proficiency_lvl,$behaviour_id,$qa_method)=$db->fetchRow($result)) {
            $bhqitem[$proficiency_lvl][$behaviour_id][$ca_id] = array($q_en_txt,$a_en_txt,$q_id_txt,$a_id_txt,$qa_method);
         }
      }
      
      for($i=4;$i>0;$i--) {
         $pl = $i;
         $ret .= "<div id='pl${pl}'>"
              . "<table class='plheader'><tbody><tr><td>Proficiency Level ${pl} - ".$proficiency_level_name[$pl]."</td>"
              . "<td>"
              . "<input type='button' onclick='addbehaviour(\"${competency_id}\",${pl},this,event);' value='Add Behaviour'/>"
//              . "[<span class='xlnk2' onclick='addbehaviour(\"${competency_id}\",${pl},this,event);'>add behaviour</span>]"
              . "</td></tr>"
              . "<tr><td colspan='2' style='background-color:#ddddee;color:#333377;font-weight:normal;' id='tdlttl${pl}'>"
              . $this->vLevelTitle($competency_id,$pl)
              . "</td></tr>"
              . "</tbody></table>"
              . "<div id='bh${pl}' style='margin-bottom:3px;'>";
         if(is_array($bhitem[$pl])) {
            ksort($bhitem[$pl]);
            foreach($bhitem[$pl] as $behaviour_id=>$v) {
               list($behaviour_en_txt,$behaviour_id_txt)=$v;
               if(trim($behaviour_en_txt)=="") {
                  $behaviour_en_txt = _EMPTY;
               }
               if(trim($behaviour_id_txt)=="") {
                  $behaviour_id_txt = _EMPTY;
               }
               $txt = "<div style='font-weight:bold;text-align:center;'>Behaviour Description:</div><span>$behaviour_en_txt</span>"
                    . "<br/><span style='font-style:italic;'>$behaviour_id_txt</span>"
                    . "<br/>[<span class='xlnk' onclick='editbehaviour(\"$pl\",\"$behaviour_id\",this,event);'>edit</span>]"
                    . "&nbsp;[<span class='xlnk' onclick='delete_behaviour(\"$pl\",\"$behaviour_id\",this,event);'>delete</span>]";
               $ret .= "<div class='behaviouritem' id='bh${pl}_${behaviour_id}'>"
                     . "<table class='tblbhv'><colgroup><col width='30'/><col/></colgroup><tbody>"
                     ."<tr><td>$behaviour_id</td>"
                     . "<td><div class='bhtxt' id='tdbhtxt${pl}_{$behaviour_id}'>$txt</div>"
                     . "<div class='bhq'><div class='bhqheader'"
                     . "<div style='float:left;'>Competency Assessment</div>"
                     . "<div style='float:right;'>[<span class='xlnk' onclick='add_qa(\"$pl\",\"$behaviour_id\",this,event);'>add</span>]</div>"
                     . "<div style='clear:both;'></div>"
                     . "</div>"
                     
                     ///// questions and answers
                     . "<div id='bhqitem${pl}_${behaviour_id}'>";
               if(is_array($bhqitem[$pl][$behaviour_id])) {
                  ksort($bhqitem[$pl][$behaviour_id]);
                  foreach($bhqitem[$pl][$behaviour_id] as $ca_id=>$w) {
                     list($q_en_txt,$a_en_txt,$q_id_txt,$a_id_txt,$qa_method)=$w;
                     if(trim($q_en_txt)=="") {
                        $q_en_txt = _EMPTY;
                     }
                     if(trim($q_id_txt)=="") {
                        $q_id_txt = _EMPTY;
                     }
                     if(trim($a_en_txt)=="") {
                        $a_en_txt = _EMPTY;
                     }
                     if(trim($a_id_txt)=="") {
                        $a_id_txt = _EMPTY;
                     }
                     $ret .= "<div class='qa' id='qa${pl}_${behaviour_id}_${ca_id}'>"
                           
                           . "<table class='qaitem'>"
                           . "<colgroup><col width='6%'/><col width='47%'/><col width='47%'/></colgroup>"
                           . "<tbody>"
                           
                           . "<tr><td>&nbsp;</td><td colspan='2' class='method'>&nbsp;Method: $qa_method</td></tr>"

                           . "<tr>"
                           . "<td>$ca_id</td>"
                           
                           . "<td>"
                           . "<span>$q_en_txt</span><br/>"
                           . "<span style='font-style:italic;'>$q_id_txt</span>"
                           . "</td>"
                           . "<td>"
                           . "<span>$a_en_txt</span><br/>"
                           . "<span style='font-style:italic;'>$a_id_txt</span>"
                           . "</td>"
           
                           . "</tr><tr><td>&nbsp</td><td colspan='2' style='border:0px;'>"

                           . "<div style='text-align:left;padding:1px;'>"
                           . "[<span class='xlnk' onclick='edit_qa(\"$pl\",\"$behaviour_id\",\"$ca_id\",this,event);'>edit</span>]"
                           . "&nbsp;[<span class='xlnk' onclick='delete_qa(\"$pl\",\"$behaviour_id\",\"$ca_id\",this,event);'>delete</span>]"
                           . "</div>"

                           . "</tr></tbody></table>"
                           
                           . "</div>";
                  }
                  $ret .= "<div id='qa${pl}_${behaviour_id}_empty' class='qa_empty' style='display:none;'>Empty Competency Assessment</div>";
               } else {
                  $ret .= "<div id='qa${pl}_${behaviour_id}_empty' class='qa_empty'>Empty Competency Assessment</div>";
               }
               $ret .= "</div>"
                     
                     . "</div>"
                     . "</td></tr>"
                     . "</tbody></table>"
                     . "</div>";
            }
            $ret .= "<div class='behaviouritem_empty' style='display:none;' id='bh${pl}_empty'>Empty behaviour</div>";
         } else {
            $ret .= "<div class='behaviouritem_empty' id='bh${pl}_empty'>Empty behaviour</div>";
         }
         $ret .= "</div>"
              . "</div>";
      }
      
      $ret .= "</td></tr>"
           . "</tbody></table>";
      return array($competency_id,$ret,$wdv_dv);
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."competency WHERE competency_id = '$competency_id'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."compbehaviour"
           . " WHERE competency_id = '$competency_id'";
      $db->query($sql);
      $sql = "DELETE FROM ".XOCP_PREFIX."compbehaviour_qa"
           . " WHERE competency_id = '$competency_id'";
      $db->query($sql);
   }
   
   function app_vCompetency($args) {
      $db=&Database::getInstance();
      $competency_id = $args[0];
      $sql = "SELECT a.competency_id,a.competency_cd,a.competency_nm,a.desc_en,"
           . "b.compgroup_nm,a.competency_class,a.competency_abbr"
           . " FROM ".XOCP_PREFIX."competency a"
           . " LEFT JOIN ".XOCP_PREFIX."compgroup b USING(compgroup_id)"
           . " WHERE a.competency_id = '$competency_id'";
      
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($competency_id,$competency_cd,$competency_nm,$desc_en,$compgroup_nm,$competency_class,$competency_abbr)=$db->fetchRow($result);
         if(trim($competency_nm)=="") {
            $competency_nm = "[noname]";
         }
         $ret = "<table style='border:0px;width:100%;'>"
              . "<colgroup><col width='50'/><col/><col width='75'/><col width='75'/></colgroup><tbody>"
              . "<tr><td>$competency_abbr</td>"
                  . "<td><div style='overflow:hidden;width:340px;'><div style='width:900px;'><span id='sp_${competency_id}' class='xlnk' onclick='edit_competency(\"$competency_id\",this,event);'>".htmlentities(stripslashes($competency_nm))."</span></div></div></td>"
                  . "<td><div style='overflow:hidden;width:75px;'><div style='width:900px;'>$compgroup_nm</div></div></td>"
                  . "<td><div style='overflow:hidden;width:75px;'><div style='width:900px;'>$competency_class</div></div></td>"
              . "</tr></tbody></table>";
      }
      return array($competency_id,$ret);
   }
   
}

} /// HRIS_COMPETENCYAJAX_DEFINED
?>
