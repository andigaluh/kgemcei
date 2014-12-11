<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/developmentplan.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_DEVELOPMENTPLAN_DEFINED') ) {
   define('HRIS_DEVELOPMENTPLAN_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_DevelopmentPlan extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_DEVELOPMENTPLAN_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_DEVELOPMENTPLAN_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_DevelopmentPlan($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function developmentplan() {
      switch($_SESSION["lang"]) {
         case "ID":
            $ret = "
	  <p align=\"justify\">
	  Rencana pengembangan karyawan didasarkan pada kesenjangan antara kompetensi yang dimiliki karyawan saat ini dengan kompetensi yang dipersyaratkan pekerjaannya. Fokusnya akan bersifat individual sesuai prioritas kebutuhan masing-masing karyawan. Atasan bertanggung jawab untuk membuat rencana tersebut, memonitor pelaksanaannya dan mengevaluasi hasil pengembangannya. Kesemua ini dapat dilakukan secara on-line dalam OCD module.</p>
<p>
Contoh rencana pengembangan dapat dilihat sebagai berikut:
</p>
<div align=\"center\" >
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/report.jpg\">
</div>
<p>
<strong>Keterangan:</strong><br>
RCL = Required Competency Level (Level kompetensi yang dipersyaratkan pekerjaan/posisi)<br>
ITJ = Important to Job (Bobot kepentingan di pekerjaan)<br>
CCL = Current Competency Level (Level kompensi karyawan saat ini)<br>
Gap = Kesenjangan antara kompetensi karyawan dan pekerjaan<br>
DP = Development Priority (Prioritas pengembangan)<br>
<br>

Dari contoh di atas, maka sesuai gap yang ada dan bobot kepentingan kompetensi di pekerjaan maka prioritas pengembangan adalah pada kompetensi Concern for Quality dan Result orientation.
</p>
<p>&nbsp;</p>
";
            break;
         case "EN":
            $ret = "
	  <p align=\"justify\">
	  A plan to develop employees is based on the gap between the competency currently owned by employees and the competency required by their jobs. The focus will be individual in nature in accordance to the necessities of each employee. The superior is responsible to create the plan, monitor its implementation and evaluate its development results. All of them can be done online in the OCD module.
	  </p>
<p align='justify'>
An example of the development plan can be as follows:
</p>
<div align=\"center\" >
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/report.jpg\">
</div>
<p>
<strong>Description:</strong><br/>
RCL = Required Competency Level (Level of competency required by job/position)<br/>
ITJ = Important to Job (Level of importance of job)<br/>
CCL = Current Competency Level (Current level of competency of employee)<br/>
Gap = Gap between the employee's competency and job<br/>
DP = Development Priority<br/>
<br/>
From the above example, in accordance to the existing gap and the level of importance of job, the priority of development is on the competency of concern for quality and result orientation.
</p>
<p>&nbsp;</p>
";
            break;
      }
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->developmentplan();
            break;
         default:
            $ret = $this->developmentplan();
            break;
      }
      return $ret;
   }
}

} // HRIS_DEVELOPMENTPLAN_DEFINED
?>