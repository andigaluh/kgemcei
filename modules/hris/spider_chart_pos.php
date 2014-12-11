<?php // content="text/plain; charset=utf-8"

require_once ('../../config.php');
define('TTF_DIR',XOCP_DOC_ROOT."/include/fonts/");
require_once ('../../class/jpgraph/jpgraph.php');
require_once ('../../class/jpgraph/jpgraph_radar.php');


$db=&Database::getInstance();

$division_org_id = $_GET["division_org_id"];
$job_class_id = $_GET["job_class_id"];
$data = array();
$data2 = array();
$titles = array();

if(isset($_SESSION["spider_chart_data"])&&isset($_SESSION["spider_chart_data"][$division_org_id][$job_class_id])) {
   $compdiv = $_SESSION["spider_chart_data"];
   if(isset($compdiv[$division_org_id][$job_class_id])) {
      $sql = "SELECT competency_id FROM ".XOCP_PREFIX."competency WHERE compgroup_id IN ('1','2') ORDER BY xurut01 DESC,competency_id";
      $result = $db->query($sql);
      
      if($db->getRowsNum($result)>0) {
         while(list($competency_id)=$db->fetchRow($result)) {
            /////////////////////////////
            if(isset($compdiv[$division_org_id][$job_class_id][$competency_id])) {
               $v = $compdiv[$division_org_id][$job_class_id][$competency_id];
               $ttlccl = $v["ccl"];
               $ttlccl_count = $v["ccl_count"];
               $ccl = $ttlccl/$ttlccl_count;
               $ttlrcl = $v["rcl"];
               $ttlrcl_count = $v["rcl_count"];
               $rcl = $ttlrcl/$ttlrcl_count;
               $data[] = $ccl;
               $data2[] = $rcl;
               list($compgroup_id,$competency_cd,$competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id) = $_SESSION["spider_chart_competency"][$competency_id];
               $titles[] = $competency_abbr;
            } else {
               $data[] = 0;
               $data2[] = 0;
               list($compgroup_id,$competency_cd,$competency_abbr,$competency_nm,$competency_class,$desc_en,$desc_id) = $_SESSION["spider_chart_competency"][$competency_id];
               $titles[] = $competency_abbr;
            }
            /////////////////////////////
         }
         //foreach($compdiv[$division_org_id][$job_class_id] as $competency_id=>$v) {
      }
   }
} else {
   $titles=array('Data','Not','Found','Data','Not','Found');
   $data=array(3, 3, 3, 3, 3, 3);
   $data2=array(4, 4, 4 ,4 ,4, 4);
}


$graph = new RadarGraph (320,300);
$graph->SetFrame(false);

$graph->SetScale('lin',0,4);
$graph->yscale->ticks->Set(1,2);

//$graph->img->SetAntiAliasing();

//$graph->title->Set('Division');
//$graph->title->SetFont(FF_VERDANA,FS_NORMAL,10);

$graph->SetTitles($titles);
$graph->SetCenter(0.5,0.51);
$graph->HideTickMarks();
//$graph->SetColor('lightgreen@0.7');
$graph->axis->SetColor('lightgray');
$graph->grid->SetColor('lightgray');
$graph->grid->Show();

$graph->axis->title->SetFont(FF_ARIAL,FS_NORMAL,8);
$graph->axis->title->SetMargin(8);
$graph->SetGridDepth(DEPTH_BACK);
$graph->SetSize(0.8);

$plot2 = new RadarPlot($data2);
$plot2->SetColor('dodgerblue3');
$plot2->SetLineWeight(6);
//$plot2->SetFillColor('blue@0.7');

$plot = new RadarPlot($data);
$plot->SetColor('red');
$plot->SetLineWeight(3);
$plot->SetFillColor('red@0.7');

$data3=array(4,4,4,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000);
$plot3 = new RadarPlot($data3);
$plot3->SetLineWeight(0);

$data4=array(1000,1000,1000,1000,1000,1000,1000,1000,1000,4,4,4,4,4,4,4,4,4,4,4);
$plot4 = new RadarPlot($data4);
$plot4->SetLineWeight(0);

$data5=array(1000,1000,1000,4,4,4,4,4,4,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000,1000);
$plot5 = new RadarPlot($data5);
$plot5->SetLineWeight(0);

$plot->mark->SetType(MARK_IMG_SBALL,'red');
$plot2->mark->SetType(MARK_IMG_MBALL,'bluegreen');

$plot4->mark->SetType(MARK_FILLEDCIRCLE,'red');
$plot4->mark->SetFillColor('red');
$plot4->mark->SetColor('red');
$plot4->mark->SetWidth(3);

$plot3->mark->SetType(MARK_FILLEDCIRCLE,'yellow');
$plot3->mark->SetFillColor('yellow');
$plot3->mark->SetColor('yellow');
$plot3->mark->SetWidth(3);

$plot5->mark->SetType(MARK_FILLEDCIRCLE,'green');
$plot5->mark->SetFillColor('green');
$plot5->mark->SetColor('green');
$plot5->mark->SetWidth(3);

$graph->Add($plot2);
$graph->Add($plot);

$graph->Add($plot5);
$graph->Add($plot4);
$graph->Add($plot3);

$file = XOCP_DOC_ROOT."/tmp/".uniqid("imx").".png";
$_SESSION["tmp_file"][$division_org_id][$job_class_id] = $file;

$graph->Stroke();
$graph->Stroke($file);

