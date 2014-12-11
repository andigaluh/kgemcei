<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/home_guest.php                             //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HOMEGUEST_DEFINED') ) {
   define('HRIS_HOMEGUEST_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_HomeGuest extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_HOMEGUEST_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_HOMEGUEST_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_HomeGuest($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function home() {
      switch($_SESSION["lang"]) {
      
         case "ID":
            $ret = "<style type=\"text/css\">"
                 . "\n.style1 {color: #0099FF}"
                 . "\n</style>
                  <p align=\"justify\">Selamat datang di modul <span class=\"style1\">OCD (Organization-Competency-Development)</span>, modul Human Resource Information System yang akan membantu Anda memahami dan menjalankan manajemen Sumber Daya Manusia (SDM) di MCCI.</p>
                  <p align=\"justify\">Secara garis besar modul OCD terbagi dalam 2 (dua) menu yakni menu <span class=\"style1\">PUBLIC</span> yang bisa diakses oleh semua karyawan dan menu <span class=\"style1\">LOG IN</span> yang hanya bisa diakses terbatas oleh karyawan tertentu dengan menggunakan password.</p>
                  <p align=\"justify\">Pada menu PUBLIC, Anda dapat mengakses berbagai informasi mengenai organisasi perusahaan berikut kebijakan dan prosedur yang berkaitan dengan manajemen SDM. Sedangkan pada menu LOG IN, Anda dapat melakukan manajemen SDM secara on-line seperti menilai kompetensi karyawan dan menetukan program pengembangan untuk meningkatkan kompetensi karyawan.
                  </p>Salam<br>HR";
            break;
         case "EN":
            $ret = "<style type=\"text/css\">"
                 . "\n.style1 {color: #0099FF}"
                 . "\n</style>
                  <p align=\"justify\">Welcome to the <span class='style1'>OCD (Organization-Competency-Development)</span> module, a Human Resource Information System module that will help you to understand and implement the management of Human Resources (HR) at the MCCI.</p>
                  <p align=\"justify\">In broad outline, the OCD module is divided into 2 (two) menu, namely the <span class='style1'>PUBLIC</span> menu that can be accessed by all employees and the <span class='style1'>LOG IN</span> menu that can only be accessed by limited employees by using password.</p>
                  <p align=\"justify\">In the PUBLIC menu, you can access various information on the company's organization, including its policies and procedures related to the HR management. Meanwhile, in the LOG IN menu, you can do the HR management via online such as judging the employee's competency and determining a development program to improve the employee's competency.
                  </p>Best regards,<br>HR";
            
            break;
      }
   
      return $ret;
   
   
   }
   
   
   function main() {
      $db = &Database::getInstance();
      
      $_SESSION["html"]->redirect(XOCP_SERVER_SUBDIR."/index.php?XP_sp_menu=0&page_id=2&rand=".uniqid());

      switch ($this->catch) {
         case $this->blockID:
            $this->home();
            break;
         default:
            $ret = $this->home();
            break;
      }
      return $ret;
   }
}

} // HRIS_HOMEGUEST_DEFINED
?>