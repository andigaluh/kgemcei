<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_orgs.php                        //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ORGSAJAX_DEFINED') ) {
   define('HRIS_ORGSAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_OrgsAjax extends AjaxListener {
   
   function _hris_class_OrgsAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_orgs.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editOrgs","app_saveOrgs",
                            "app_getOptionParent","app_getChildren","app_editOrg2",
                            "app_saveOrg2","app_newChild","app_expandAll","app_Inactive",
                            "app_getChanges","app_addHistory");
   }
   
   function app_addHistory($args) {
      _dumpvar($args);
      $db=&Database::getInstance();
      $org_id = $args[0];
      $sql = "INSERT INTO ".XOCP_PREFIX."orgs_history SELECT NULL,".XOCP_PREFIX."orgs.* FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $db->query($sql);
      _debuglog($sql);
      return $this->list_history($org_id);
   }
   
   function list_history($org_id) {
      $db=&Database::getInstance();
      $sql = "SELECT history_id,org_nm,org_abbr,start_dttm,stop_dttm"
           . " FROM ".XOCP_PREFIX."orgs_history"
           . " WHERE org_id = '$org_id'"
           . " ORDER BY start_dttm DESC";
      $result = $db->query($sql);
      $ret = "";
      if($db->getRowsNum($result)>0) {
         while(list($history_id,$org_nm,$org_abbr,$start_dttm,$stop_dttm)=$db->fetchRow($result)) {
            $ret .= "<div style='border-bottom:1px solid #bbb;'>"
                  . "<table style='width:100%;'><colgroup><col width='30'/><col/><col width='150'/><col width='150'/></colgroup>"
                  . "<tr><td>$org_abbr</td><td><span class='xlnk' onclick='view_changes_detail(\"$history_id\",\"$org_id\",this,event);'>".htmlentities($org_nm)."</span></td><td>".sql2ind($start_dttm,"date")."</td><td>".sql2ind($stop_dttm,"date")."</td></tr></table>"
                  . "</div>";
         }
         $ret .= "<div id='dvhistory_empty' style='display:none;text-align:center;padding:5px;font-style:italic;'>"._EMPTY."</div>";
      } else {
         $ret .= "<div id='dvhistory_empty' style='text-align:center;padding:5px;font-style:italic;'>"._EMPTY."</div>";
      }
      
      return $ret;
   }
   
   function app_getChanges($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      
      $ret = "<div style='border-bottom:1px solid #bbb;padding:3px;text-align:right;'><input type='button' value='Add History' onclick='add_history(\"$org_id\",this,event);'/></div>";
      $ret .= "<div id='history_list'>".$this->list_history($org_id)."</div>";
      
      return $ret;
      
   }
   
   function app_expandAll($args) {
      $expst = $args[0];
      if($expst==1) {
         list($p,$h,$c) = $this->app_getChildren(array(0));
         return $h;
      } else {
         list($p,$h,$c) = $this->app_getChildren(array(0,1));
         return $h;
      }
   }
   
   function app_newChild($args) {
      $db=&Database::getInstance();
      $parent_id = $args[0];
      $user_id = getUserID();
      $sql = "SELECT a.org_class_id,b.order_no"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$parent_id'";
      $result = $db->query($sql);
      list($org_class_idx,$order_nox)=$db->fetchRow($result);
      $sql = "SELECT org_class_id,order_no,org_class_nm FROM ".XOCP_PREFIX."org_class"
           . " WHERE order_no > '$order_nox'"
           . " LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($org_class_id,$order_no,$org_class_nm)=$db->fetchRow($result);
      } else {
         return "UNKNOWNLEVEL";
      }
      
      $sql = "SELECT MAX(org_id) FROM ".XOCP_PREFIX."orgs";
      $result = $db->query($sql);
      list($org_idx)=$db->fetchRow($result);
      $org_id = $org_idx+1;
      
      $sql = "SELECT MAX(order_no) FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$parent_id'";
      $result = $db->query($sql);
      list($order_nox)=$db->fetchRow($result);
      $order_no = $order_nox+10;
      
      $user_id = getUserID();
      $org_cd = $org_nm = $org_abbr = "";
      $sql = "INSERT INTO ".XOCP_PREFIX."orgs (org_id,org_nm,org_cd,org_class_id,created_user_id,order_no,parent_id,org_abbr)"
           . " VALUES('$org_id','$org_nm','$org_cd','$org_class_id','$user_id','$order_no','$parent_id','$org_abbr')";
      $db->query($sql);
      $ret = "";
      $haschild = 1;
      $org_nm = "<span style='font-style:italic;'>New $org_class_nm</span>";
      $ret .= "<table style='border:none;width:100%;margin:0px;'>"
            . "<colgroup><col width='10'/><col/><col width='50'/></colgroup><tbody>"
            . "<tr>"
                . "<td><img id='expst_${org_id}' style='cursor:pointer;' onclick='toggle_children(\"$org_id\",this,event);' src='".XOCP_SERVER_SUBDIR."/images/".($haschild==1?"org_collapse.png":"org_nochild.png")."'/></td>"
                . "<td><span id='sp_${org_id}' class='xlnk' onclick='edit_org(\"$org_id\",this,event);'>$org_nm</span> [ $org_class_nm ] ~ <span id='spabbr_${org_id}'>$org_abbr</span></td>"
                . "<td id='tdabbr_${org_id}' style='text-align:left;'>&nbsp;</td>"
            . "</tr></tbody></table>";
      
      return array($org_id,$ret);
      
   }
   
   function app_getChildren($args) {
      $db=&Database::getInstance();
      $parent_id = $args[0];
      $recursion = $args[1];
      if($parent_id==0) {
         $marginleft = 0;
         $sboborder = "border-top:0px;";
      } else {
         $marginleft = 20;
         $sboborder = "";
      }
      
      $ret = "";
      $sql = "SELECT a.org_id,a.org_nm,a.description,b.org_class_nm,a.org_cd,c.org_cd,c.org_nm,d.org_class_nm,"
           . "a.org_abbr,a.order_no as org_order,b.order_no as class_order"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " LEFT JOIN ".XOCP_PREFIX."orgs c ON c.org_id = a.parent_id"
           . " LEFT JOIN ".XOCP_PREFIX."org_class d ON d.org_class_id = c.org_class_id"
           . " WHERE a.status_cd = 'normal'"
           . " AND a.parent_id = '$parent_id'"
           . " ORDER BY a.org_class_id,a.order_no";
      $result = $db->query($sql);
      $childcount = $db->getRowsNum($result);
      $no = 0;
      if($childcount>0) {
         while(list($org_id,$org_nm,$description,$org_class_nm,$org_cd,$parent_cd,$parent_nm,$parent_class,
                    $org_abbr,$order_no,$class_orderx)=$db->fetchRow($result)) {
            $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$org_id'";
            $resch = $db->query($sql);
            $haschild = 1;
            if($db->getRowsNum($resch)>0) {
               $haschild = 1;
            }
            if(trim($org_nm)=="") {
               $org_nm = "<span style='font-style:italic;'>New $org_class_nm</span>";
            } else {
               $org_nm = htmlentities(stripslashes($org_nm));
            }
            
            if($no>0) $sboborder = "";

            $ret .= "<div id='dvorg_${org_id}' class='sbo' style='${sboborder}margin-left:${marginleft}px;'><table style='border:none;width:100%;margin:0px;'>"
                  . "<colgroup><col width='10'/><col/><col width='50'/></colgroup><tbody>"
                  . "<tr>"
                      . "<td><img id='expst_${org_id}' style='cursor:pointer;' onclick='toggle_children(\"$org_id\",this,event);' src='".XOCP_SERVER_SUBDIR."/images/".($recursion==1?"org_expand.png":"org_collapse.png")."'/></td>"
                      . "<td><span id='sp_${org_id}' class='xlnk' onclick='edit_org(\"$org_id\",this,event);'>$org_nm</span> [ $org_class_nm ] ~ <span id='spabbr_${org_id}'>$org_abbr</span></td>"
                      . "<td id='tdabbr_${org_id}' style='text-align:left;'>&nbsp;</td>"
                  . "</tr></tbody></table>";
            if($recursion==1) {
               list($xxp,$xxh,$xxc)=$this->app_getChildren(array($org_id,1));
               $ret .= "<div id='dvc_${org_id}' style='padding:2px;padding-right:0px;'>$xxh</div>";
            }
            $ret .= "</div>";
            $no++;
         }
      }
      
      $sql = "SELECT b.order_no FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$parent_id'";
      $result = $db->query($sql);
      list($class_order)=$db->fetchRow($result);
      $sql = "SELECT org_class_id,order_no,org_class_nm FROM ".XOCP_PREFIX."org_class"
           . " WHERE order_no > '$class_order'"
           . " LIMIT 1";
      $result = $db->query($sql);
      if($db->getRowsNum($result)==1) {
         list($org_class_id,$order_no,$corg_class)=$db->fetchRow($result);
         $ret .= "<div id='dvadd_${parent_id}' class='sbo' style='margin-left:${marginleft}px;'><table style='border:0px;width:100%;'>"
              . "<tbody>"
              . "<tr>"  
                 . "<td style='color:#999999;padding-left:4px;'>[<span onclick='addchild(\"$parent_id\",this,event);' class='ylnk'>add $corg_class</span>]</td>"
              . "</tr></tbody></table></div>";
      } else {
         $ret .= "<div id='dvadd_0' class='sbo' style='margin-left:${marginleft}px;'><table style='border:0px;width:100%;'>"
              . "<tbody>"
              . "<tr>"  
                 . "<td style='font-style:italic;color:#999999;padding-left:4px;'>Lowest organization level. Please define new lower level to add.</td>"
              . "</tr></tbody></table></div>";
      }
      


      return array($parent_id,$ret,$childcount);
      
   }
   
   function app_getOptionParent($args) {
      $db=&Database::getInstance();
      $class = $args[0];
      $org_id = $args[1];
      $sql = "SELECT parent_id FROM ".XOCP_PREFIX."orgs WHERE org_id = '$org_id'";
      $result = $db->query($sql);
      list($parent_id)=$db->fetchRow($result);
      $sql = "SELECT order_no FROM ".XOCP_PREFIX."org_class WHERE org_class_id = '$class'";
      $result = $db->query($sql);
      list($order_no)=$db->fetchRow($result);
      $sql = "SELECT a.org_id,a.org_nm,a.org_cd,b.org_class_nm"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id != '$org_id'"
           . " AND b.order_no < '$order_no'"
           . " ORDER BY b.order_no,a.org_nm";
      $result = $db->query($sql);
      $optparent = "<option value='0'>-</option>";
      if($db->getRowsNum($result)>0) {
         while(list($org_idx,$org_nmx,$org_cdx,$org_class_nmx)=$db->fetchRow($result)) {
            if($parent_id==$org_idx) {
               $sel = "selected='1'";
            } else {
               $sel = "";
            }
            $optparent .= "<option value='$org_idx'${sel}>$org_cdx $org_nmx [$org_class_nmx]</option>";
         }
      }
      return $optparent;
   }
   
   function app_saveOrgs($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $org_nm = addslashes(trim(urldecode($args[1])));
      $description = addslashes(trim(urldecode($args[2])));
      $org_class_id = $args[3];
      $parent_id = $args[4];
      $order_no = _bctrim(bcadd($args[6],0));
      $org_cd = addslashes(trim(urldecode($args[5])));
      $org_abbr = addslashes(trim(urldecode($args[7])));
      $_SESSION["hris_org_class_id"] = $org_class_id;
      if($org_nm=="") {
         $org_nm = "noname";
      }
      if($org_id=="new") {
         $sql = "SELECT MAX(org_id) FROM ".XOCP_PREFIX."orgs";
         $result = $db->query($sql);
         list($org_idx)=$db->fetchRow($result);
         $org_id = $org_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."orgs (org_id,org_nm,org_cd,description,org_class_id,created_user_id,order_no,parent_id,org_abbr)"
              . " VALUES('$org_id','$org_nm','$org_cd','$description','$org_class_id','$user_id','$order_no','$parent_id','$org_abbr')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."orgs SET org_nm = '$org_nm',"
              . "org_cd = '$org_cd',"
              . "description = '$description',"
              . "org_class_id = '$org_class_id',"
              . "order_no = '$order_no',"
              . "parent_id = '$parent_id',"
              . "org_abbr = '$org_abbr'"
              . " WHERE org_id = '$org_id'";
         $db->query($sql);
      }
      
      $sql = "SELECT org_class_nm FROM ".XOCP_PREFIX."org_class WHERE org_class_id = '$org_class_id'";
      $result = $db->query($sql);
      list($org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT a.org_cd,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$parent_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_cd,$parent_nm,$parent_class)=$db->fetchRow($result);
      }
      
      $ret = "<table style='border:0px;width:100%;'>"
           . "<colgroup><col width='60'/><col width='50'/><col/><col width='200'/></colgroup><tbody>"
           . "<tr>"
               . "<td>$org_abbr</td>"
               . "<td style='text-align:center;'>$order_no</td>"
               . "<td><div style='overflow:hidden;width:240px;'><div style='width:900px;'><span id='sp_${org_id}' class='xlnk' onclick='edit_org(\"$org_id\",this,event);'>".htmlentities(stripslashes($org_nm))." [$org_class_nm]</span></div></div></td>"
               . "<td><div style='overflow:hidden;width:190px;'><div style='width:900px;'>$parent_cd $parent_nm [$parent_class]</div></div></td>"
           . "</tr></tbody></table>";

      return array("dvorg_${org_id}",$ret,"$parent_cd $parent_nm [$parent_class]");
   }
   
   function app_saveOrg2($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $org_nm = addslashes(trim(urldecode($args[1])));
      $description = addslashes(trim(urldecode($args[2])));
      $org_cd = addslashes(trim(urldecode($args[3])));
      $order_no = _bctrim(bcadd($args[4],0));
      $org_abbr = addslashes(trim(urldecode($args[5])));
      $_SESSION["hris_org_class_id"] = $org_class_id;
      if($org_nm=="") {
         $org_nm = "noname";
      }
      if($org_id=="new") {
         $sql = "SELECT MAX(org_id) FROM ".XOCP_PREFIX."orgs";
         $result = $db->query($sql);
         list($org_idx)=$db->fetchRow($result);
         $org_id = $org_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."orgs (org_id,org_nm,org_cd,org_class_id,created_user_id,order_no,parent_id,org_abbr)"
              . " VALUES('$org_id','$org_nm','$org_cd','$org_class_id','$user_id','$order_no','$parent_id','$org_abbr')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."orgs SET org_nm = '$org_nm',"
              . "org_cd = '$org_cd',"
              //. "description = '$description',"
              . "order_no = '$order_no',"
              . "org_abbr = '$org_abbr'"
              . " WHERE org_id = '$org_id'";
         $db->query($sql);
      }
      
      $sql = "SELECT org_class_nm FROM ".XOCP_PREFIX."org_class WHERE org_class_id = '$org_class_id'";
      $result = $db->query($sql);
      list($org_class_nm)=$db->fetchRow($result);
      
      $sql = "SELECT a.org_cd,a.org_nm,b.org_class_nm FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id = '$parent_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($parent_cd,$parent_nm,$parent_class)=$db->fetchRow($result);
      }
      
      $ret = "<table style='border:0px;width:100%;'>"
           . "<colgroup><col width='60'/><col width='50'/><col/><col width='200'/></colgroup><tbody>"
           . "<tr>"
               . "<td>$org_abbr</td>"
               . "<td style='text-align:center;'>$order_no</td>"
               . "<td><div style='overflow:hidden;width:240px;'><div style='width:900px;'><span id='sp_${org_id}' class='xlnk' onclick='edit_org(\"$org_id\",this,event);'>".htmlentities(stripslashes($org_nm))." [$org_class_nm]</span></div></div></td>"
               . "<td><div style='overflow:hidden;width:190px;'><div style='width:900px;'>$parent_cd $parent_nm [$parent_class]</div></div></td>"
           . "</tr></tbody></table>";

      return array("dvorg_${org_id}",$org_id,htmlentities(stripslashes($org_nm)),htmlentities(stripslashes($org_abbr)));
   }
   
   function app_editOrgs($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $sql = "SELECT org_class_id,org_class_nm"
           . " FROM ".XOCP_PREFIX."org_class"
           . " ORDER BY org_class_id";
      $result = $db->query($sql);
      $arr_class = array();
      if($db->getRowsNum($result)>0) {
         while(list($org_class_idx,$org_class_nmx)=$db->fetchRow($result)) {
            $arr_class[$org_class_idx] = $org_class_nmx;
         }
      }
      
      if($org_id=="new") {
         if(isset($_SESSION["hris_org_class_id"])) {
            $org_class_id = $_SESSION["hris_org_class_id"];
         } else {
            $org_class_id=0;
         }
         $qparent = "";
      } else {
         $sql = "SELECT description,org_cd,org_nm,addr_txt,zip_cd,telecom,org_class_id,"
              . "order_no,parent_id,org_abbr"
              . " FROM ".XOCP_PREFIX."orgs"
              . " WHERE org_id = '$org_id'";
         $result = $db->query($sql);
         list($desc,$org_cd,$org_nm,$addr_txt,$zip,$telecom,$org_class_id,
              $order_no,$parent_id,$org_abbr)=$db->fetchRow($result);
         $org_nm = htmlentities($org_nm,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
         $qparent = " AND a.org_class_id < '$org_class_id'";
      }
      
      $sql = "SELECT a.org_id,a.org_nm,a.org_cd,b.org_class_nm,a.org_abbr"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id != '$org_id'"
           . $qparent
           . " ORDER BY a.org_class_id,a.org_nm";
      $result = $db->query($sql);
      $optparent = "<option value='0'>-</option>";
      if($db->getRowsNum($result)>0) {
         while(list($org_idx,$org_nmx,$org_cdx,$org_class_nmx,$org_abbrx)=$db->fetchRow($result)) {
            if($parent_id==$org_idx) {
               $sel = "selected='1'";
            } else {
               $sel = "";
            }
            $optparent .= "<option value='$org_idx'${sel}>$org_abbrx $org_nmx [$org_class_nmx]</option>";
         }
      }
      
      $opt = "";
      foreach($arr_class as $k=>$v) {
         $opt .= "<option value='$k'".($k==$org_class_id?" selected='1'":"").">$v</option>";
      }
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='150'/><col></colgroup>"
           . "<tbody>"
           . "<tr><td>Organization Name</td><td><input type='text' value=\"$org_nm\" id='inp_org_nm' name='org_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Code</td><td><input type='text' value=\"$org_cd\" id='inp_org_cd' name='org_cd' style='width:100px;'/></td></tr>"
           . "<tr><td>Abbreviation</td><td><input type='text' value=\"$org_abbr\" id='inp_org_abbr' name='org_abbr' style='width:100px;'/></td></tr>"
           . "<tr><td>Description</td><td><textarea id='description' style='width:90%;height:80px;'>$desc</textarea></td></tr>"
           . "<tr><td>Level</td><td><select onchange='getoptparent(\"$org_id\",this,event);' id='selclass'>$opt</select></td></tr>"
           . "<tr><td>Parent</td><td><select id='selparent'>$optparent</select></td></tr>"
           . "<tr><td>Order</td><td><input type='text' value=\"$order_no\" id='inp_order_no' name='order_no' style='width:50px;'/></td></tr>"
           . "<tr><td colspan='2'><input onclick='save_org();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($org_id!="new"?"<input onclick='inactive_org();' type='button' value='Set Inactive'/>&nbsp;<input onclick='delete_org();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      return $ret;
   }
   
   function app_editOrg2($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $sql = "SELECT org_class_id,org_class_nm"
           . " FROM ".XOCP_PREFIX."org_class"
           . " ORDER BY org_class_id";
      $result = $db->query($sql);
      $arr_class = array();
      if($db->getRowsNum($result)>0) {
         while(list($org_class_idx,$org_class_nmx)=$db->fetchRow($result)) {
            $arr_class[$org_class_idx] = $org_class_nmx;
         }
      }
      
      if($org_id=="new") {
         if(isset($_SESSION["hris_org_class_id"])) {
            $org_class_id = $_SESSION["hris_org_class_id"];
         } else {
            $org_class_id=0;
         }
         $qparent = "";
      } else {
         $sql = "SELECT description,org_cd,org_nm,addr_txt,zip_cd,telecom,org_class_id,"
              . "order_no,parent_id,org_abbr"
              . " FROM ".XOCP_PREFIX."orgs"
              . " WHERE org_id = '$org_id'";
         $result = $db->query($sql);
         list($desc,$org_cd,$org_nm,$addr_txt,$zip,$telecom,$org_class_id,
              $order_no,$parent_id,$org_abbr)=$db->fetchRow($result);
         $org_nm = htmlentities($org_nm,ENT_QUOTES);
         $desc = htmlentities($desc,ENT_QUOTES);
         $qparent = " AND a.org_class_id < '$org_class_id'";
      }
      
      $sql = "SELECT a.org_id,a.org_nm,a.org_cd,b.org_class_nm,a.org_abbr"
           . " FROM ".XOCP_PREFIX."orgs a"
           . " LEFT JOIN ".XOCP_PREFIX."org_class b USING(org_class_id)"
           . " WHERE a.org_id != '$org_id'"
           . $qparent
           . " ORDER BY a.org_class_id,a.org_nm";
      $result = $db->query($sql);
      $optparent = "<option value='0'>-</option>";
      if($db->getRowsNum($result)>0) {
         while(list($org_idx,$org_nmx,$org_cdx,$org_class_nmx,$org_abbrx)=$db->fetchRow($result)) {
            if($parent_id==$org_idx) {
               $sel = "selected='1'";
            } else {
               $sel = "";
            }
            $optparent .= "<option value='$org_idx'${sel}>$org_abbrx $org_nmx [$org_class_nmx]</option>";
         }
      }
      
      $opt = "";
      foreach($arr_class as $k=>$v) {
         $opt .= "<option value='$k'".($k==$org_class_id?" selected='1'":"").">$v</option>";
      }
      
      $ret = "<div id='frm'><table class='xxfrm' style='width:100%;'>"
           . "<colgroup><col width='150'/><col></colgroup>"
           . "<tbody>"
           . "<tr><td>Organization Name</td><td><input type='text' value=\"$org_nm\" id='inp_org_nm' name='org_nm' style='width:90%;'/></td></tr>"
           . "<tr><td>Code</td><td><input type='text' value=\"$org_cd\" id='inp_org_cd' name='org_cd' style='width:100px;'/></td></tr>"
           . "<tr><td>Abbreviation</td><td><input type='text' value=\"$org_abbr\" id='inp_org_abbr' name='org_abbr' style='width:100px;'/></td></tr>"
           //. "<tr><td>Description</td><td><textarea id='description' style='width:90%;height:80px;'>$desc</textarea></td></tr>"
           . "<tr><td>Level</td><td>".$arr_class[$org_class_id]."</td></tr>"
           . "<tr><td>Order</td><td><input type='text' value=\"$order_no\" id='inp_order_no' name='order_no' style='width:50px;'/></td></tr>"
           . "<tr><td colspan='2'>"
           
           . "<table style='width:100%;'><tbody><tr><td style='text-align:left;'>"
           
           . "<input type='button' value='Changes' onclick='view_changes(\"$org_id\",this,event);'/>"
           
           . "</td><td style='text-align:right;'>"
           . "<input onclick='save_org();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($org_id!="new"?"<input onclick='inactive_org();' type='button' value='Set Inactive'/>&nbsp;<input onclick='delete_org();' type='button' value='"._DELETE."'/>":"")
           
           . "</td></tr></tbody></table>"
           
           . "</td></tr>"
           . "</tbody></table><div id='changes' style='display:none;padding:10px;'></div></div>";
      return $ret;
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $sql = "UPDATE ".XOCP_PREFIX."orgs SET status_cd = 'nullified' WHERE org_id = '$org_id'";
      $db->query($sql);
      $sql = "UPDATE ".XOCP_PREFIX."orgs SET parent_id = '0' WHERE parent_id = '$org_id'";
      $db->query($sql);
   }
   
   function app_Inactive($args) {
      $db=&Database::getInstance();
      $org_id = $args[0];
      $sql = "UPDATE ".XOCP_PREFIX."orgs SET status_cd = 'inactive' WHERE org_id = '$org_id'";
      $db->query($sql);
      $this->recurseInactive($org_id);
   }
   
   function recurseInactive($parent_id) {
      $db=&Database::getInstance();
      $sql = "SELECT org_id FROM ".XOCP_PREFIX."orgs WHERE parent_id = '$parent_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         while(list($org_id)=$db->fetchRow($result)) {
            $sql = "UPDATE ".XOCP_PREFIX."orgs SET status_cd = 'inactive' WHERE org_id = '$org_id'";
            $db->query($sql);
            $this->recurseInactive($org_id);
         }
      }
   }
   
}

} /// HRIS_ORGSAJAX_DEFINED
?>