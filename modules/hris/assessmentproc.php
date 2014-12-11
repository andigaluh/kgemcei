<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/assessmentproc.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_ASSESSMENTPROC_DEFINED') ) {
   define('HRIS_ASSESSMENTPROC_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_AssessmentProcedure extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_ASSESSMENTPROC_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_ASSESSMENTPROC_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_AssessmentProcedure($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function assessmentproc() {
      switch($_SESSION["lang"]) {
         case "ID":
            $ret = "
	  <style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>
<p align=\"justify\">Seseorang dikatakan memiliki  kompetensi tertentu jika ia dapat <span class=\"style1\">menampilkan seluruh  perilaku</span> sebagaimana diuraikan dalam indikator perilaku kompetensi  (lihat kamus kompetensi). <span class=\"style1\">Jika belum lengkap, maka ia  akan berada pada level kompetensi di bawahnya</span>.</p>
<p>Penilaian kompetensi akan dilakukan  oleh para assessors dengan menggunakan fasilitas OCD module (on line). Mekanismenya  terbagi 2 yakni penilaian kompetensi General dan Managerial Soft dan penilaian  kompetensi teknikal</p>
<ul>
  <li>Penilaian  kompetensi General dan Managerial Soft</li>
</ul>
<p align=\"justify\">Untuk level Assistan Manajer ke atas,  penilaian akan dilakukan dengan menggunakan sistem 360 derajat, dimana Atasan,  Bawahan, Teman sejawat dan Customer (internal) yang akan melakukan penilaian.  Jika jumlah masing kelompok penilai (bawahan,  teman sejawat, customer) lebih dari 1 (satu) maka secara acak sistem akan  memilih penilai. Bagi setiap penilai, akan diberikan password untuk masuk ke  dalam sistem penilaian dimana di dalamnya juga mencantumkan petunjuk penilaian secara  lebih rinci.</p>
<ul>
  <li>Penilaian  kompetensi Spesifik</li>
</ul>
<p align=\"justify\">Sesuai  dengan mekanisme PR, maka penilaian kompetensi spesifik menjadi tanggung jawab  Atasan (minimal Supervisor). Sebagaimana audit ISO, penilaian dilakukan dengan metode interviu, observasi  ataupun pengecekan bukti lapangan (portofolio) oleh team penilai yang dikepalai  oleh Atasan terkait. HR juga akan turut sebagai anggota team penilai.</p>";
            break;
         case "EN":
            $ret = "
	  <style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>
<p align=\"justify\">
Someone is said to have certain competency when he or she <span class='style1'>can perform all behaviors</span> as described in the indicators of behavior (see the competency dictionary). <span class='style1'>If it has been incomplete yet, he or he will stand at the lower level of competency.</span>
</p>
<p align='justify'>
Evaluation on competency will be conducted by assessors by using the OCD module facility (online). The mechanisms are divided into two, namely general and managerial soft competency evaluation and technical competency evaluation.
</p>
<ul>
   <li>General and Managerial Soft Competency Evaluation</li>
   <p align=\"justify\">
   For the level of assistant manager and above, the evaluation will be conducted by using a 360 degree system, in which the superiors, subordinates, colleagues and customers (internal) will do the evaluation. If the number of each evaluating team (subordinates, colleagues and customers) is more than 1 (one), the system will randomly choose the evaluator. Each evaluator will receive a password to enter into the system of evaluation, in which the guideline of evaluation is also mentioned more detailed. 
   </p>
   <li>Specific Competency Evaluation</li>
   <p align=\"justify\">
   In accordance to the PR mechanisms, the specific competency evaluation is under the responsibility of the superior (supervisor at least). According to the ISO audit, the evaluation is conducted by using the method of interview, observation or field checking (portfolio) by the teams of evaluators headed by related superiors. HR will also participate as member of the evaluating team.
   </p>
</ul>";
            break;
      }
      
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->assessmentproc();
            break;
         default:
            $ret = $this->assessmentproc();
            break;
      }
      return $ret;
   }
}

} // HRIS_ASSESSMENTPROC_DEFINED
?>