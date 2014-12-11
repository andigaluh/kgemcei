<?php

$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db = "dummyhris";

mysql_connect($host,$user,$pass) or die("ERROR:".mysql_error());
mysql_select_db($db) or die("ERROR DB:".mysql_error()); 

$sqlschedule="SELECT DATE(schedule_start) , DATE(schedule_start) - INTERVAL 7 DAY , id_antrain_session FROM antrain_plan_general WHERE status_cancel = 'F' AND is_deleted = 'F'";

/* list($schedule_start)=$db->fetchRow($sqlschedule); */

/* $resultschedule=mysql_query($sqlschedule);
$num=mysql_numrows($resultschedule); */

/* $sqlpropose = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_proposed WHERE pk.psid = $psid";  
$resultpropose = $db->query($sqlpropose);
list($user_id_proposed,$emailpropose,$namapropose)=$db->fetchRow($resultpropose); */
/* 	
$sqlapprove = "SELECT p.user_id,ps.email,ps.person_nm FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id LEFT JOIN antrain_session pk ON p.user_id = pk.id_approved WHERE pk.psid = $psid";  
$resultapprove = $db->query($sqlapprove);
list($user_id_approved,$emailapprove,$namaapprove)=$db->fetchRow($resultapprove);
 */

/* mysql_close();
$i=0; */


while (list($schedule_start,$schedule_send_before,$id_antrain_session)=mysql_query($sqlschedule))
 {
	$sqlnow ="SELECT DATE(NOW())";
	$resultnow = mysql_query($sqlnow);
	list($date_now)=mysql_fetch_row($resultnow);
 
	$sqlclerk = "	SELECT p.user_id, ps.email, ps.person_nm, DATE( rk.schedule_start ),DATE(rk.schedule_start) - INTERVAL 7 DAY, rk.id_antrain_session FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id RIGHT JOIN antrain_session pk ON p.user_id = pk.id_created LEFT JOIN antrain_plan_general rk ON rk.id_antrain_session = pk.psid WHERE DATE( NOW( ) ) = DATE( rk.schedule_start ) ";  
	$resultclerk =mysql_query($sqlclerk);
	list($user_id_proposed,$emailclerk,$namaclerk,$date_start_clerk,$date_send_clerk,$id_antrainsession_clerk)=mysql_fetch_row($resultclerk);

	$sqlsm = "SELECT p.user_id, ps.email, ps.person_nm, DATE( rk.schedule_start ),DATE(rk.schedule_start) - INTERVAL 7 DAY, rk.id_antrain_session FROM hris_users p LEFT JOIN hris_persons ps ON p.person_id = ps.person_id RIGHT JOIN antrain_session pk ON p.user_id = pk.id_proposed LEFT JOIN antrain_plan_general rk ON rk.id_antrain_session = pk.psid WHERE DATE( NOW( ) ) = DATE( rk.schedule_start )";  
	$resultsm = $db->query($sqlsm);
	list($user_id_approved,$emailsm,$namasm,$date_start_sm,$date_send_sm,$id_antrainsession_sm)=mysql_fetch_row($resultsm);
 
	if ($date_now == $date_send_clerk)
	{
				$to      = $emailclerk;
				$subject = 'General Training Plan Reminder';
				$message = '
				The General Training will begin in 7 days
				<BR><BR>
				Dont forget to renew!<BR><BR>
				Domain Team
				';
				$headers = 'From: reminder@cc.m-kagaku.co.jp' . "\r\n" .
				'Reply-To: reminder@cc.m-kagaku.co.jp' . "\r\n" .
				'X-Mailer: PHP/' . phpversion();
				mail($to, $subject, $message, $headers);
	echo 'email clerk berhasil terkirim';
	}
	
	
		if ($date_now == $date_send_sm)
	{
				$to      = $emailsm;
				$subject = 'General Training Plan Reminder';
				$message = '
				The General Training will begin in 7 days
				<BR><BR>
				Dont forget to renew!<BR><BR>
				Domain Team
				';
				$headers = 'From: reminder@cc.m-kagaku.co.jp' . "\r\n" .
				'Reply-To: reminder@cc.m-kagaku.co.jp' . "\r\n" .
				'X-Mailer: PHP/' . phpversion();
				mail($to, $subject, $message, $headers);
			echo 'email sm berhasil terkirim';
	}
 }
 
 
 /* 
$id=mysql_result($result,$i,"id");
$domain_name=mysql_result($result,$i,"domain_name");
$company_name=mysql_result($result,$i,"company_name");
$simply_account=mysql_result($result,$i,"simply_account");
$notes=mysql_result($result,$i,"notes");
$schedule_start=mysql_result($result,$i,"schedule_start");
$to      = 'daniel.whiteside@googlemail.com';
$subject = 'Domain renewall reminder';
$message = '
The following domains will expire in 7 days
<BR><BR>
$domain_name - $company_name<BR>
<BR><BR>
Dont forget to renew!<BR><BR>
Domain Team
';
$headers = 'From: oku@sutsurikeru.net' . "\r\n" .
    'Reply-To: oku@sutsurikeru.net' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
mail($to, $subject, $message, $headers);  */
?>