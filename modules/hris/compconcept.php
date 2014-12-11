<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/compconcept.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_COMPCONCEPT_DEFINED') ) {
   define('HRIS_COMPCONCEPT_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_CompetencyConcept extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_COMPCONCEPT_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_COMPCONCEPT_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_CompetencyConcept($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function compconcept() {
      switch($_SESSION["lang"]) {
         case "ID":
      $ret = "<style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>
<p align=\"justify\">
Konsep kompetensi pertama kali diperkenalkan oleh David McClelland. Dari studinya, ia menyimpulkan bahwa tes pengetahuan dan akademis, seperti halnya tingkatan sekolah, tidak bisa meramalkan kesuksesan seseorang di pekerjaan atau kehidupannya.
</p>
<p align=\"justify\">
Jika bukan intelegensi, lalu apa yang bisa meramalkan kesuksesan? Melalui penelitiannya, McClelland membandingkan orang-orang yang sukses dengan yang kurang sukses untuk mengidentifikasi karakteristik kesuksesan. Karakteristik inilah yang disebut kompetensi.
</p>
<p align=\"justify\">
<span class=\"style1\">Kompetensi</span> didefinisikan sebagai pengetahuan, ketrampilan, ciri kepribadian, konsep diri, nilai-nilai dan motif yang diperlukan seseorang untuk melakukan pekerjaannya dengan sukses. 
</p>
<p align=\"justify\">
Konsep kompetensi ini dapat digambarkan sebagai fenomena gunung es dimana aspek yang banyak membedakan kesuksesan seseorang, seperti motif, nilai-nilai, konsep diri, umumnya merupakan aspek yang tersembunyi, sulit dinilai dan dikembangkan. Sebaliknya pengetahuan dan ketrampilan merupakanaspek yang terlihat sehingga lebih mudah untuk dinilai dan dikembangkan. Dalam prakteknya, aspek yang terlihat sering disebut sebagai <span class=\"style1\">kompetensi teknikal Hard</span>, sedangkan aspek yang tersembunyi disebut <span class=\"style1\">kompetensi Soft</span>
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/gunung-es.jpg\">
</div>
<p align=\"justify\"> Hubungan kompetensi dan unjuk kerja</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/kompetensi-graph.jpg\">
</div>

<p align=\"justify\">Tipe kompetensi yang diterapkan di MCCI adalah sebagai  berikut :</p>
<ul>
  <li>Kompetensi general </li>
</ul>
<ul>
  <ul>
  <li>Berhubungan dengan organisasi (visi,  misi, nilai-nilai and strategi organisasi). </li>
  <li>Diterapkan pada semua posisi karenanya  akan menggambarkan kultur perusahaan</li>
  <li>Terdiri dari kompetensi soft dan  teknikal</li>
  </ul>
</ul>
<ul>
  <li>Kompetensi managerial </li>
</ul>
<ul><ul>
  <li>Berhubungan dengan posisi dan peran yang  harus dijalankan fungsi Managerial </li>
  <li>Terdiri dari kompetensi soft dan  teknikal</li>
</ul></ul>
<ul>
  <li>Kompetensi spesifik</li>
</ul>
<ul><ul>
  <li>Berhubungan dengan posisi dan menggambarkan  tugas dan output pekerjaan </li>
  <li>Terdiri  dari kompetensi teknikal</li>
</ul></ul>
<p>&nbsp;</p>";
            break;
         case "EN":
      $ret = "<style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>
<p align=\"justify\">
The concept of competency was first introduced by David McClelland. From his study, he concluded that knowledge and academic tests, like at the school level, can not predict the success of someone in his or her job or life.
</p>
<p align=\"justify\">
If it is not intelligence, what can predict a success? Through his research, McClelland compared successful people with those who are less successful to identify the characteristic of success. This characteristic is called competency.
</p>
<p align=\"justify\">
<span class='style1'>Competency</span> is defined as knowledge, skill, personal character, self-concept, values and motives needed by someone to do his or her job successfully.
</p>
<p align=\"justify\">
The concept of competency can be described as an iceberg phenomenon, in which the aspects that mostly distinguish the success of someone, such as motive, values, self-concept, are generally aspects that are hidden, difficult to be evaluated and to be improved. On the contrary, knowledge and skill are aspects that are seen, so it is easier to evaluate and improve them. In practice, the seen aspects are frequently called <span class='style1'>Technical/Hard competency</span>, while the hidden aspects are called <span class='style1'>Soft competency</span>.
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/gunung-es_en.png\" style='width:500px;'>
</div>
<p align=\"justify\">The relationship between competency and achievement</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/kompetensi-graph_en.png\" style='width:500px;'>
</div>

<p align=\"justify\">The types of competency applied at MCCI are as follows:</p>
<ul>
  <li class='style1'>General Competency</li>
  <ul>
  <li>Related to the organization (visions, missions, values and strategies of the organization).</li>
  <li>Applied on all positions, so it will describe the company’s culture</li>
  <li>Consists of soft and technical competency</li>
  </ul>
  <br/>
  <li class='style1'>Managerial Competency</li>
  <ul>
  <li>Related to the position and role that must be operated by the managerial functions</li>
  <li>Consists of soft and technical competency</li>
  </ul>
  <br/>
  <li class='style1'>Specific Competency</li>
  <ul>
  <li>Related to the position and describes the assignment and the working output</li>
  <li>Consists of technical competency</li>
</ul>
<p>&nbsp;</p>";
            break;
      }
      return $ret;
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->compconcept();
            break;
         default:
            $ret = $this->compconcept();
            break;
      }
      return $ret;
   }
}

} // HRIS_COMPCONCEPT_DEFINED
?>