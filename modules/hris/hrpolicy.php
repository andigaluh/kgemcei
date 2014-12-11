<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/hrpolicy.php                                //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRPOLICY_DEFINED') ) {
   define('HRIS_HRPOLICY_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_HRPolicy extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_HRPOLICY_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_HRPOLICY_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_HRPolicy($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function hrpolicy() {
      switch($_SESSION["lang"]) {
      
         case "ID":
            $ret = "<style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>
<p align=\"justify\">Berbekal pada keinginan untuk memiliki SDM yang berkualitas dalam menghadapi tantangan dunia bisnis yang makin berat, maka pada bulan Mei 2007, jajaran Direksi MCCI telah memutuskan untuk mengubah sistem pengelolaan SDM yang ada menjadi sistem pengelolaan SDM berbasis kompetensi atau dikenal sebagai <span class=\"style1\"><em>Competency Based Human Resource Management (CBHRM)</em></span>.</p>
<p align=\"justify\">CBHRM adalah sistem pengelolaan SDM yang didasarkan pada konsep kompetensi. Sistem ini merupakan transisi dari cara pandang tradisional dalam mengelola SDM yang didasarkan pada apa yang seseorang <span class=\"style1\">miliki</span> (seperti kualifikasi pendidikan formal, masa kerja dsb) ke arah apa yang seseorang <span class=\"style1\">dapat lakukan</span> (kemampuan). </p>
<p align=\"justify\">Dalam CBHRM, kompetisi yang sudah ditetapkan perusahaan akan dijadikan acuan/dasar dalam melakukan semua sistem pengelolaan SDM, mulai dari rekruitmen & seleksi, training & development, penilaian prestasi, pengembangan karir maupun menejemen kompensasi. Untuk bisa berjalan optimal, dalam pelaksanaannya dibutuhkan pula komitmen yang kuat dari manajemen puncak dan para Menejer, serta dukungan perangkat software yang mumpuni.</p>
Skema CBHRM <br>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/hrpolicy.png\" style='width:530px;'>
</div>
<p align=\"justify\">
Fokus pengelolaan SDM berbasis kompetensi adalah <span class=\"style1\">pengembangan</span>. Dalam prakteknya, perusahaan akan menentukan terlebih dahulu kompetensi yang dipersyaratkan untuk setiap posisi. Setelah dilakukan evaluasi terhadap kompetensi karyawan maka proses selanjutnya adalah mengembangkan secara sistematis kompetensi karyawan tersebut ke arah kompetensi yang dipersyaratkan perusahaan. Program pengembangan yang dilakukan tidak hanya terfokus pada training formal saja tetapi juga melalui penugasan, On the Job training, rotasi maupun coaching dan counseling dari atasan. 
</p>
<p align=\"justify\">
Bagi karyawan, dengan adanya kejelasan kompetensi yang harus dicapai, termasuk bagaimana program pengembangannya jika terjadi kesenjangan kompetensi, maka proses pengembangan diri dan karir menjadi lebih jelas dan terarah.	
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/kompetensi.jpg\" width=\"360\" height=\"193\">
</div>
<p>&nbsp;</p>
";
            break;
         case "EN":
            $ret = "<style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>

<p align=\"justify\">Departing from a wish to have a qualified HR in facing challenges in the business world that is getting tougher, in May 2007, the MCCI Board of Directors decided to change the existing system of HR management to be an HR management system, which is based on competency or known as the <span class='style1' style='font-style:italic;'>Competency Based Human Resource Management (CBHRM)</span>.</p>

<p align=\"justify\">
CBHRM is an HR management system, which is based on the concept of competency. The system is a transition from the traditional point of view in managing HR, which is based on what someone <span class='style1'>owns</span> (such as qualifications of formal education, working term, etc.), to what someone <span class='style1'>can do</span> (ability). 
</p>

<p align=\"justify\">
In CBHRM, the competition, which has been determined by the company, will become a reference/basis in carrying out all of the HR management system, starting from the recruitment & selection, training & development, evaluation on achievement, career development or compensation management. To enable it operate optimally, in its implementation, a strong commitment from the top management and the managers, as well as sophisticated software units, are also needed. 
</p>
Scheme of CBHRM <br>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/hrpolicy.png\" style='width:530px;'>
</div>

<p align=\"justify\">
The focus of a competency-based HR management is <span class='style1'>development</span>. In practice, the company will first determine the competency required for every position. After conducting an evaluation on the competency of an employee, the next process is to systematically develop the employee's competency toward a competency required by the company. The development program does not only focus on formal training, but also through assignment, job training, and rotation, as well as coaching and counseling from the employee's superior.
</p>

<p align=\"justify\">
For the employees, by the lucidity of the competency that must be achieved, including how to create its development program if a competency gap happens, so the self-development process and career will be clearer and more guided.
</p>

<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/kompetensi.jpg\" width=\"360\" height=\"193\">
</div>
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
            $this->hrpolicy();
            break;
         default:
            $ret = $this->hrpolicy();
            break;
      }
      return $ret;
   }
}

} // HRIS_HRPOLICY_DEFINED
?>