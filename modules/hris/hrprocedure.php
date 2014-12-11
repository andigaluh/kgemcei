<?php
//--------------------------------------------------------------------//
// Filename : modules/hris/hrprocedure.php                            //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2008-07-18                                              //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_HRPROCEDURE_DEFINED') ) {
   define('HRIS_HRPROCEDURE_DEFINED', TRUE);

include_once(XOCP_DOC_ROOT."/modules/hris/modconsts.php");
include_once(XOCP_DOC_ROOT."/modules/hris/include/vocab.php");

class _hris_HRProcedure extends XocpBlock {
   var $catchvar = _HRIS_CATCH_VAR;
   var $blockID = _HRIS_HRPROCEDURE_BLOCK;
   var $width = "100%";
   var $language;
   var $display_title = TRUE;
   var $title = _HRIS_HRPROCEDURE_BLOCK_TITLE;
   var $display_comment = TRUE;
   var $data;
   
   function _hris_HRProcedure($catch=NULL) { /* fungsi konstruktor wajib punya parameter $catch
                                                yang diteruskan ke konstruktor parent class */
      $this->XocpBlock($catch);              /* ini meneruskan $catch ke parent constructor */
   }
   
   
   function hrprocedure() {
      switch($_SESSION["lang"]) {
         case "ID":
            $ret = "
	  <style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>
<p align=\"justify\">Sebagai  bagian dari prosedur QSHE, maka prosedur pengelolaan SDM dapat dilihat secara  rinci di dokumen QSHE </p>
<p align=\"justify\">Namun secara garis besar, konsep CBHRM di MCCI dapat  digambarkan sebagai berikut :</p>
 <li>Recruitment</li>
<p align=\"justify\">Fokus  proses rekruitmen dan seleksi adalah mendapatkan calon karyawan yang tidak  hanya memiliki kemampuan, pengalaman dan motivasi untuk berprestasi tetapi juga  yang memiliki visi, misi, nilai-nilai dan tujuan hidup yang cocok dengan  organisasi MCCI.</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/kompetensi_general.jpg\" width=\"441\" height=\"134\">
</div>

<p align=\"justify\" >Proses seleksi akan dilakukan dengan berbagai metode  mulai dari interviu, tes psikologis + penilaian kompetensi hingga tes  kesehatan. Sebelum diangkat sebagai karyawan tetap, calon karyawan akan  menjalani masa trainee dan/atau masa percobaan untuk memastikan kecocokan  dengan kondisi/kebutuhan perusahaan.</p>
  <li>Penilaian prestasi kerja dan penilaian  kompetensi</li>
<p align=\"justify\">Meskipun  dilakukan dalam periode yang sama, penilaian prestasi kerja dan penilaian  kompetensi akan dilakukan dengan menggunakan metode yang berbeda. Penilaian  kerja dilakukan lewat mekanisme <span class=\"style1\">Performance Review  (PR)</span>, sedangkan penilaian kompetensi dilakukan lewat <span class=\"style1\">Competency Assessment (CA)</span>.</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/penilaian.jpg\">
</div>
<p align=\"justify\">
Fokus PR adalah pada penilaian atas hasil (output) unjuk kerja karyawan pada periode tertentu, sedangkan fokus CA adalah pada penilaian kompetensi karyawan yang memungkinnya menghasilkan unjuk kerja tertentu. 
</p>
<p align=\"justify\">
Dari perbandingan antara kompetensi karyawan dengan kompetensi yang dipersyaratkan pekerjaan/posisi maka dapat diketahui seberapa jauh kecocokan kompetensi karyawan dengan pekerjaannya (Job/Position match).
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/job_match.jpg\">
</div>
<p align=\"justify\">
Selanjutnya, atas dasar hasil penilaian prestasi dan kompetensi karyawan tersebut, maka akan didapatkan pengelompokkan karyawan sebagai berikut 
</p>
<div align=\"center\">
  <p><img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/matrix_performanace.jpg\"></p>
</div>
 <li>Pelatihan dan pengembangan</li>

<p align=\"justify\">Untuk dapat mendorong karyawan menjadi karyawan STAR maka  perlu disusun program pelatihan dan pengembangan yang mengacu pada kebutuhan  individual setiap karyawan. Kebutuhan tersebut muncul dari analisa kesenjangan  (Gap analysis) antara kompetensi posisi dengan kompetensi yang dimiliki  karyawan. </p>
<p align=\"justify\">Program pelatihan dan pengembangan yang dimaksud tidak  hanya berupa pelatihan formal saja tetapi juga mencakup penugasan, on the job  training, coaching dan counseling dari atasan. Selanjutnya, program juga harus  dievaluasi untuk melihat efektifitasnya dalam meningkatkan kompetensi karyawan.  Perlu diingat, selain perusahaan, karyawanpun harus terlibat aktif dalam proses  pengembangan ini dalam bentuk pembelajaran sendiri (self study).</p>
<p align=\"justify\">
Proses pelatihan dan pengembangan merupakan suatu siklus yang dapat  digambarkan sebagai berikut :
</p>
<div align=\"center\"> 
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/piramida.jpg\">
</div>
<li>Pengembangan karir</li>
<p align=\"justify\">Pengembangan  karir harus dipahami tidak hanya berupa <span class=\"style1\">promosi posisi</span>  tetapi juga berupa <span class=\"style1\">pengembangan pribadi (<em>personal growth</em>)</span>, seperti  penambahan kualitas dan kemampuan individual. Pemahaman ini perlu dimiliki  mengingat karakter struktur organisasi bisnis manufakturing yang cenderung  statis sehingga tidak bisa mengakomodasi promosi posisi bagi semua karyawan.</p>
<p align=\"justify\">Pengembangan karir bukan hanya  menjadi tanggung jawab Atasan saja tetapi juga karyawan yang bersangkutan.  Secara proaktif karyawan dapat melakukan proses pembelajaran untuk pengembangan  kompetensi dan percepatan karirnya sendiri. Hal ini dimungkinkan karena  informasi mengenai persyaratan kompetensi di posisi yang lebih tinggi dapat  diketahui secara transparan.
<p align=\"justify\">
Dalam <span class=\"style1\">promosi  posisi</span>, acuan yang dipakai adalah ada tidaknya posisi yang kosong di  organisasi, catatan prestasi kerja di posisi sebelumnya serta kompetensi dan  potensi karyawan di posisi yang baru. Untuk mengetahui kompetensi dan potensi  karyawan di posisi baru, maka sebelum dipromosi, akan dilakukan penilaian  kompetensi dan potensi secara sistematis hingga mendapat persetujuan dari  komite promosi. Program pengembangan kompetensi akan dilakukan selama masa  percobaan jika ditemukan masih adanya kesenjangan antara kompetensi karyawan  saat ini dengan kompetensi yang dipersyaratkan di posisi baru. Akhirnya, promosi  akan dilakukan jika seluruh penilaian di akhir masa percobaan menunjukkan  kesesuaian antara kompetensi karyawan dengan posisi barunya.</p>
<p align=\"justify\">Perbedaan mekanisme promosi posisi  pada sistem HR yang lama dan CBHRM dapat digambarkan sebagai berikut :</p>
<div align=\"center\">
<p><img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/table.jpg\"></p>
</div>
<p align=\"justify\">
Terkait dengan promosi posisi adalah promosi karena kenaikan level kompetensi yang dimiliki karyawan (sekarang dikenal sebagai promosi sub-grade). Perubahan yang sisgnifikan dari sistem sekarang adalah promosi tidak lagi didasarkan pada <span class=\"style1\"><em>likely years</em></span> tetapi berdasarkan pencapaian level kompetensi <span class=\"style1\"><em>(Job/position match)</em></span>.
</p>
<p align=\"justify\">
Promosi juga diartikan sebagai <span class=\"style1\"> <em>pengembangan pribadi (personal growth)</em></span> dimana kualitas individual (potensi dan prestasi kerja) karyawan dikembangkan ke arah yang lebih baik (karyawan STAR). Pengembangan dapat dilakukan dengan berbagai cara seperti penugasan, rotasi, coaching dan konseling.
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/matrix-2.jpg\">
</div>
<p align=\"justify\">
Pengembangan lebih lanjut bagi karyawan level Supervisor ke atas di kelompok STAR adalah program <span class=\"style1\">Talent pool</span>. Talent pool terdiri dari karyawan level Supervisor ke atas yang terseleksi untuk dipersiapkan sebagai pemimpin masa depan, tidak hanya di MCCI tetapi juga di grup perusahaan MCC TPA. Setelah diseleksi jajaran Direksi, pengembangan mereka akan dimonitor dan dievaluasi langsung oleh President Director (CEO).
</p>";
            break;
         case "EN":
            $ret = "
	  <style type=\"text/css\">
<!--
.style1 {color: #0099FF}
-->
</style>
<p align=\"justify\">
As a part of the QSHE procedures, the HR management procedure can be seen in details in the QSHE document.
</p>
<p align=\"justify\">
In broad outline, however, the concept of CBHRM at MCCI can be described as follows:
</p>
<ul>
 <li>Recruitment</li>
<p align=\"justify\">
The process of recruitment and selection focuses on getting candidates of employees who do not only have capabilities, experiences and motivation to reach achievement but also who have visions, missions, values and objectives of life, which are in line with the organization of MCCI.
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/general_competency.png\" style='width:500px;'>
</div>

<p align=\"justify\" >
The process of selection will be done with various methods, starting from interview, psychological test + evaluation on competency to health test. Before being admitted as a permanent employee, a candidate of employee shall pass through the period of trainee and/or probation period to ensure a harmony with the company's condition/necessity.
</p>
  <li>
  Evaluation on working achievement and competency
  </li>
<p align=\"justify\">
Although being done during the same period, the evaluation on working achievement and competency will be conducted by using different methods. Working evaluation will be done through the mechanism of <span class='style1'>Performance Review (PR)</span>, while competency evaluation through <span class='style1'>Competency Assessment (CA)</span>.
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/penilaian_en.png\">
</div>
<p align=\"justify\">
PR focuses on the evaluation on results (output) of the company's achievement during a certain period, while CA focuses on the evaluation on the employee's competency that will enable them to produce certain working achievement.
</p>
<p align=\"justify\">
By comparing between the employee's competency and that required by job/position, it can be known how far the competency between the employees and their jobs are matched (Job/Position match).
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/job_match_en.png\" style='width:500px;'>
</div>

<p align=\"justify\">
Later, based on the results of evaluation on achievement and competency of the employees, they are categorized as follows:
</p>

<div align=\"center\">
  <p><img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/matrix_performance_en.png\" style='width:550px;'></p>
</div>
 <li>Training and development</li>

<p align=\"justify\">
To motivate employees to be STAR employees, a training and development training that refers to the individual necessities of each employee needs to be arranged. The necessities emerge from the gap analysis between the competency of position and that owned by employees.
</p>

<p align=\"justify\">
The training and development program is not only in the form of formal training, but also covers assignment, job training, coaching and counseling from the superior. Later, the must also be evaluated to see its effectiveness in improving the employee's competency. It is necessary to remember that besides the company, employees must also actively involve in this development process in the form of self-study.
</p>

<p align=\"justify\">
The training and development process is a cycle that can be described as follows:
</p>

<div align=\"center\"> 
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/piramida_en.png\" style='width:280px;'>
</div>
<li>Career development</li>
<p align=\"justify\">
Career development must be understood not only as a <span class='style1'>promotion of position</span> but also in the form of <span class='style1'>personal growth</span>, such as improving an individual's quality and ability. It is necessary to have this understanding as the manufacturing business has an organizational structure character that tends to be static so it can not accommodate promotion of position for all employees.
</p>

<p align=\"justify\">
Career development is not only under the responsibilities of the superior but also the employees in question. An employee can proactively conduct a learning process to develop his or her competency and career speed-up. It is possible because information on the requirement for a higher position can be known transparently.
</p>

<p align=\"justify\">
In the <span class='style1'>promotion of position</span>, the reference used is whether there is a vacant position in the organization, the record of working achievement in the earlier position and the competency and potency of the employee for the new position. To know the competency and potency of the employee in his or her new position, before being promoted, evaluations on the competency and potency will be conducted systematically until getting approval from the committee of promotion. The competency development program will be done during the probation period if a gap between the current competency of the employee and the competency required for the new position is still found. Finally, the promotion will be made if the whole evaluation at the end of the probation period shows that the competency of the employee is matched to his or her new position.
</p>

<p align=\"justify\">
The differences in the mechanisms of position promotion in the old HR system and CBHRM can be described as follows:
</p>
<div align=\"center\">
<p>
<!-- 

Silakan copy file gambar versi inggris ke server dengan nama table_en.jpg
kemudian ganti nama file table.jpg dibawah ini sesuai dengan nama file versi inggrisnya.

-->
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/annex.png\" style='width:580px;'>
</p>
</div>
<p align=\"justify\">
Promotion of position is a promotion resulted from the increasing level of competency of an employee (it is now known as sub-grade promotion). The significant change from the current system is that the promotion is no longer based on <span class='style1'>likely years</span> but based on the level of competency he or she achieves <span class='style1'>(job/position match)</span>.
</p>
<p align=\"justify\">
The promotion can also be seen as a <span class='style1'>personal growth</span>, in which the individual quality of (potency and working achievement) of an employee is developed to a better direction (STAR employee). The development can be conducted in various ways, such as assignment, rotation, coaching and counseling.
</p>
<div align=\"center\">
<img src=\"".XOCP_SERVER_SUBDIR."/modules/hris/images/matrix-2_en.png\">
</div>
<p align=\"justify\">
Further development for employees who have positions from the supervisor level and above in the STAR group is called <span class='style1'>Talent pool</span> program. The talent pool consists of employees from the supervisor level and above who are selected to be prepared as future leaders, not only at MCCI, but also at the MCC TPA company group. After being selected by the Board of Directors, their development will be directly monitored and evaluated by the President Director (CEO).
</p>
</ul>
";
            break;
      }
   
      return $ret;
   
   }
   
   
   function main() {
      $db = &Database::getInstance();

      switch ($this->catch) {
         case $this->blockID:
            $this->hrprocedure();
            break;
         default:
            $ret = $this->hrprocedure();
            break;
      }
      return $ret;
   }
}

} // HRIS_HRPROCEDURE_DEFINED
?>