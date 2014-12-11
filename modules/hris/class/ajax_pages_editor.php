<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/class/ajax_pages_editor.php                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2006-07-21                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_PAGESEDITORAJAX_DEFINED') ) {
   define('HRIS_PAGESEDITORAJAX_DEFINED', TRUE);

require_once(XOCP_DOC_ROOT."/class/xocpajaxlistener.php");

class _hris_class_PagesEditorAjax extends AjaxListener {
   
   function _hris_class_PagesEditorAjax($act_name) {
      $this->_act_name = $act_name;
      $this->_include_file = XOCP_DOC_ROOT."/modules/hris/class/ajax_pages_editor.php";
      $this->init();
      parent::init();
   }
   
   function init() {
      $this->registerAction($this->_act_name,"app_Delete","app_editPage","app_savePage",
                                             "app_getImgPath","app_getNextImage","app_deleteImage");
   }
   
   function app_deleteImage($args) {
      $db=&Database::getInstance();
      $page_id = $args[0];
      $image_id = $args[1];
      $sql = "DELETE FROM ".XOCP_PREFIX."page_images"
           . " WHERE page_id = '$page_id'"
           . " AND image_id = '$image_id'";
      $result = $db->query($sql);
      
      $sql = "SELECT image_id,image_nm,fpath,mime_type,upload_dttm,default_image FROM ".XOCP_PREFIX."page_images"
           . " WHERE page_id = '$page_id'"
           . " ORDER BY image_id $order_t LIMIT 1";
      $result = $db->query($sql);
      $imgs = "";
      $image_nm = "";
      if($db->getRowsNum($result)>0) {
         list($image_id,$image_nm,$fpath,$mime_type,$upload_dttm,$default)=$db->fetchRow($result);
         return array($image_id,uniqid("f"),$image_nm);
      } else {
         return "EMPTY";
      }
      
      
   }

   function app_getImgPath($args) {
      $db=&Database::getInstance();
      $page_id = $args[0];
      $image_id = $args[1];
      $sql = "SELECT image_id,image_nm,fpath,mime_type,upload_dttm,default_image FROM ".XOCP_PREFIX."page_images"
           . " WHERE page_id = '$page_id'"
           . " AND image_id = '$image_id'";
      $result = $db->query($sql);
      if($db->getRowsNum($result)>0) {
         list($image_id,$image_nm,$fpath,$mime_type,$upload_dttm,$default)=$db->fetchRow($result);
         $path = "$fpath/$image_nm";
         return array($fpath,$image_nm);
      }
   }
   
   function app_getNextImage($args) {
      $db=&Database::getInstance();
      $page_id = $args[0];
      $image_id = substr($args[1],5);
      $type = $args[2];
      if($type>0) {
         $op = ">";
         $order_t = "ASC";
      } else {
         $op = "<";
         $order_t = "DESC";
      }
      $sql = "SELECT image_id,image_nm,fpath,mime_type,upload_dttm,default_image FROM ".XOCP_PREFIX."page_images"
           . " WHERE page_id = '$page_id'"
           . " AND image_id $op '$image_id'"
           . " ORDER BY image_id $order_t LIMIT 1";
      $result = $db->query($sql);
      $imgs = "";
      $image_nm = "";
      if($db->getRowsNum($result)>0) {
         list($image_id,$image_nm,$fpath,$mime_type,$upload_dttm,$default)=$db->fetchRow($result);
         return array($image_id,uniqid("f"),$image_nm);
      } else {
         return "EMPTY";
      }
   }
   
   function app_savePage($args) {
      $db=&Database::getInstance();
      $page_id = $args[0];
      $page_title = addslashes(trim(urldecode($args[1])));
      $page_content = addslashes(trim(urldecode($args[2])));
      if($page_title=="") {
         $page_title = "noname";
      }
      if($page_id=="new") {
         $sql = "SELECT MAX(page_id) FROM ".XOCP_PREFIX."static_pages";
         $result = $db->query($sql);
         list($page_idx)=$db->fetchRow($result);
         $page_id = $page_idx+1;
         $user_id = getUserID();
         $sql = "INSERT INTO ".XOCP_PREFIX."static_pages (page_id,page_title,page_content,created_user_id)"
              . " VALUES('$page_id','$page_title','$page_content','$user_id')";
         $db->query($sql);
      } else {
         $sql = "UPDATE ".XOCP_PREFIX."static_pages SET page_title = '$page_title', page_content = '$page_content',"
              . "updated_user_id = '$user_id', updated_dttm = now()"
              . " WHERE page_id = '$page_id'";
         $db->query($sql);
      }
      $ret = "<span id='sp_${page_id}' class='xlnk' onclick='edit_page(\"$page_id\",this,event);'>".htmlentities(stripslashes($page_title))."</span>";

      return array("tdpage_${page_id}",$ret);
   }
   
   function app_editPage($args) {
      $db=&Database::getInstance();
      $page_id = $args[0];
      if($page_id=="new") {
         $sql = "SELECT MAX(page_id) FROM ".XOCP_PREFIX."static_pages";
         $result = $db->query($sql);
         list($page_idx)=$db->fetchRow($result);
         $page_id = $page_idx+1;
         $user_id = getUserID();
         $page_title = "Untitled";
         $page_content = "";
         $sql = "INSERT INTO ".XOCP_PREFIX."static_pages (page_id,page_title,created_user_id)"
              . " VALUES('$page_id','$page_title','$user_id')";
         $db->query($sql);
      } else {
         $sql = "SELECT page_content,page_title FROM ".XOCP_PREFIX."static_pages"
              . " WHERE page_id = '$page_id'";
         $result = $db->query($sql);
         list($page_content,$page_title)=$db->fetchRow($result);
         $page_title = htmlentities($page_title,ENT_QUOTES);
         //$page_content = htmlentities($page_content,ENT_QUOTES);
      }
      
      $_SESSION["editor_page_id"] = $page_id;
      
      $ret = "<form id='frm'><table class='xxfrm' style='width:100%;'><tbody>"
           . "<tr><td>Page ID</td><td>$page_id</td></tr>"
           . "<tr><td>Page Name</td><td><input type='text' value=\"$page_title\" id='inp_page_title' name='page_title' style='width:90%;'/></td></tr>"
           . "<tr><td>Description</td><td><div id='page_content' style='height:500px;'>$page_content</div></td></tr>"
           . "<tr><td>Link URL</td><td>".XOCP_SERVER.XOCP_SERVER_SUBDIR."/index.php?XP_sp_menu=0&page_id=${page_id}</td></tr>"
           . "<tr><td colspan='2'><input onclick='save_page();' type='button' value='"._SAVE."'/>&nbsp;"
           . "<input onclick='cancel_edit();' type='button' value='"._CANCEL."'/>&nbsp;&nbsp;"
           . ($page_id!="new"?"<input onclick='delete_page();' type='button' value='"._DELETE."'/>":"")
           . "</td></tr>"
           . "</tbody></table></form>";
      
      $sql = "SELECT image_id,image_nm,fpath,mime_type,upload_dttm,default_image FROM ".XOCP_PREFIX."page_images"
           . " WHERE page_id = '$page_id'"
           . " ORDER BY image_id LIMIT 1";
      $result = $db->query($sql);
      $imgs = "";
      $image_nm = "NO IMAGE";
      if($db->getRowsNum($result)>0) {
         list($image_id,$image_nm,$fpath,$mime_type,$upload_dttm,$default)=$db->fetchRow($result);
         $imgs .= "<img id='impr_${image_id}' src='".XOCP_SERVER_SUBDIR."/modules/hris/page_img_thumb.php?page_id=${page_id}&image_id=${image_id}&rand=".uniqid("asdf")."' style='border:0px;'/>";
      }
      
      $ret .= "<br/><table class='xxfrm' style='width:100%;' id='editorimgs'>"
           . "<colgroup><col width='150'/><col/></colgroup>"
           //. "<thead><tr><td colspan='3'>Images</td></tr></thead>"
           . "<tbody>"
           . "<tr><td>Upload New Image</td><td><iframe src='".XOCP_SERVER_SUBDIR."/modules/hris/upload_image.php' style='border:none;height:40px;overflow:hidden;'></iframe></td></tr>"
           . "<tr><td>Images</td><td>"
           . "<div id='img_name' style='padding:4px;margin:4px;border:1px solid #888888;text-align:center;'>$image_nm</div>"
           . "<div id='img_thumb_div' style='overflow:auto;height:124px;vertical-align:bottom;text-align:center;'>$imgs</div>"
           . "<div style='text-align:left;'>"
           
           . "<img style='height:15px;cursor:pointer;' src='".XOCP_SERVER_SUBDIR."/images/prev.gif' onclick='next_image(-1,this,event);'/>&nbsp;"
           . "<img style='height:15px;cursor:pointer;' src='".XOCP_SERVER_SUBDIR."/images/next.gif' onclick='next_image(1,this,event);'/>"
           . "</div>"
           . "</td></tr>"
           . "<tr><td colspan='2'>"
           . "<table style='width:100%;'><tbody><tr>"
           . "<td style='text-align:left;'>"
           . "<input type='button' value='Delete' onclick='delete_img();'/>"
           . "</td><td>"
           . "<input type='button' value='Send to editor' onclick='send_full();'/>&nbsp;"
           . "<!-- input type='checkbox' id='ck_imglink' value='imglink'/><label for='ck_imglink'> Link to original</label -->"
           . "</td></tr></tbody></table>"
           . "</td></tr>"
           . "</tbody></table>";
      
      
      
      
      $title = "<span id='sp_${page_id}' class='xlnk' onclick='edit_page(\"$page_id\",this,event);'>".htmlentities(stripslashes($page_title))."</span>";
      return array($page_id,$title,$ret);
   }
   
   function app_Delete($args) {
      $db=&Database::getInstance();
      $page_id = $args[0];
      $sql = "DELETE FROM ".XOCP_PREFIX."static_pages WHERE page_id = '$page_id'";
      $db->query($sql);
   }
   
}

} /// HRIS_PAGESEDITORAJAX_DEFINED
?>