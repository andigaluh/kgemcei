<?php // content="text/plain; charset=utf-8"

require_once ('../../config.php');
define('TTF_DIR',XOCP_DOC_ROOT."/include/fonts/");
require_once ('../../class/jpgraph/jpgraph.php');
require_once ('../../class/jpgraph/jpgraph_radar.php');


$db=&Database::getInstance();

$job_class_id = $_GET["job_class_id"];
$data = array();
$data2 = array();
$titles = array();

$complist[0] = 7; /// INF
$complist[1] = 4; /// RO
$complist[2] = 3; /// SVO

$titles = array("INF","RO","SVO");

if(isset($_SESSION["spider_chart_data"])&&isset($_SESSION["spider_chart_data"][$job_class_id])) {
   $compdiv = $_SESSION["spider_chart_data"];
   if(isset($compdiv[$job_class_id])) {
   
      foreach($complist as $k=>$competency_id) {
         /////////////////////////////
         if(isset($compdiv[$job_class_id][$competency_id])) {
            $v = $compdiv[$job_class_id][$competency_id];
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
   }
} else {
   $data2 = array(0,0,0);
   $data = array(0,0,0);
}

$graph = new RadarGraph (320,300);
$graph->SetFrame(false);

$graph->SetScale('lin',0,4);
$graph->yscale->ticks->Set(1,2);

//$graph->img->SetAntiAliasing();

//$graph->title->Set('Division');
//$graph->title->SetFont(FF_VERDANA,FS_NORMAL,10);

$graph->SetTitles($titles);
$graph->SetCenter(0.5,0.6);
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

$plot->mark->SetType(MARK_IMG_SBALL,'red');
$plot2->mark->SetType(MARK_IMG_MBALL,'bluegreen');

$graph->Add($plot2);
$graph->Add($plot);

$file = XOCP_DOC_ROOT."/tmp/".uniqid("imx").".png";
$_SESSION["tmp_file"][$division_org_id][$job_class_id] = $file;

$graph->Stroke();
$graph->Stroke($file);
