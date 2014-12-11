<?php
//--------------------------------------------------------------------//
// Filename : class/xocpobject.php                                    //
// Software : XOCP - X Open Community Portal                          //
// Version  : 0.1                                                     //
// Date     : 2002-11-13                                              //
// Author   : adiet                                                   //
// License  : GPL                                                     //
//--------------------------------------------------------------------//

if ( !defined('HRIS_LANGUAGE_DEFINED') ) {
   define('HRIS_LANGUAGE_DEFINED', TRUE);

define("_HRIS_COMPILEDREPORT_BLOCK_TITLE","Summary CA & PMS");
define("_HRIS_EDITPEER_BLOCK_TITLE","Peer Relation");
define("_HRIS_EDITCUSTOMER_BLOCK_TITLE","Customer Relation");
define("_HRIS_PEBEKAFEE_BLOCK_TITLE","PEBEKA Fee Data Upload");
define("_HRIS_EDITPAYROLLPARAMETER_BLOCK_TITLE","Payroll Parameter Definition");
define("_HRIS_EDITPAYROLLOBJECTCLASS_BLOCK_TITLE","Payroll Component Setup");
define("_HRIS_EDITPAYROLLOBJECTCLASSINCOME_BLOCK_TITLE","Payroll Component Setup - Income");
define("_HRIS_EDITPAYROLLOBJECTCLASSDEDUCTION_BLOCK_TITLE","Payroll Component Setup - Deduction");
define("_HRIS_EDITPAYROLLOBJECTCLASSTAX_BLOCK_TITLE","Payroll Component Setup - Tax");
define("_HRIS_EMPLOYEEPAYROLLSETUP_BLOCK_TITLE","Employee Payroll Setup");
define("_HRIS_EDITABSENTCLASS_BLOCK_TITLE","Absent Classification");
define("_HRIS_EDITLEAVECLASS_BLOCK_TITLE","Leave Classification");
define("_HRIS_TSPRINT_BLOCK_TITLE","Yearly Work Schedule");
define("_HRIS_EDITCALENDAREVENT_BLOCK_TITLE","Calendar Event");
define("_HRIS_EDITCALENDARFLAGTYPE_BLOCK_TITLE","Flag Type Definition");
define("_HRIS_MONITORASSESSMENT_BLOCK_TITLE","Monitor Assessment");
define("_HRIS_RECALCASSESSMENT_BLOCK_TITLE","Recalculate Assessment Result");
define("_HRIS_TSGROUPSCHEDULE_BLOCK_TITLE","Work Group Schedule");
define("_HRIS_TSWORKGROUP_BLOCK_TITLE","Work Group Definition");
define("_HRIS_TSWORKSCHEDULE_BLOCK_TITLE","Work Schedule Management");
define("_HRIS_EDITROLE_BLOCK_TITLE","Role Registration");
define("_HRIS_ASSESSMENTSCHEDULE_BLOCK_TITLE","Assessment Schedule");
define("_HRIS_ASSESSMENTRESULTMATRIX_BLOCK_TITLE","Assessment Result Matrix");
define("_HRIS_IDPEVENTCONFIRMATION_BLOCK_TITLE","IDP - Event Participation Confirmation");
define("_IDP_EVENTYOUHAVEREGISTRATIONCONFIRMATION","You have event confirmation:");
define("_IDP_YOURIDPREQUESTCOMPLETE","Your IDP request confirmed by HR.");
define("_IDP_YOURIDPREQUESTRETURNED2","Your IDP request returned by your next superior.");
define("_IDP_YOURIDPREQUESTAPPROVED2","Your IDP request has been approved by your next superior.");
define("_IDP_YOURIDPREQUESTAPPROVED1","Your IDP request has been approved by your superior.");
define("_IDP_YOURREPORTRETURNEDBYDM","Division Manager did not approve your report.");
define("_IDP_YOURAPPROVAL1REPORTRETURNED","Division Manager did not approve report : %s.");
define("_IDP_YOURREPORTHASBEENCOMPLETED","Your report has been approved completely.");
define("_IDP_YOUHAVEDMREPORTNOTIFICATION","Section Manager approve report by %s.");
define("_IDP_YOUHAVEAPPROVAL2REPORT","Report from %s need your approval.");
define("_IDP_YOURREPORTRETURNED","Your superior did not approve your report.");
define("_IDP_YOUHAVEAPPROVAL1REPORT","Report from %s need your approval.");
define("_IDP_YOUHAVEHRAPPROVAL","[HR] IDP request confirmation for: %s.");
define("_IDP_YOURAPPROVAL1RETURNED","IDP approval returned by next superior: %s.");
define("_IDP_YOUHAVEAPPROVAL2","IDP request need approval: %s.");
define("_IDP_YOURIDPREQUESTRETURNED","Your IDP request was not approved.");
define("_IDP_YOURSUBORDINATEIDPREQUESTAPPROVED2","IDP request from %s has been approved by your superior.");
define("_IDP_YOUHAVEAPPROVAL1","IDP request from %s need approval.");
define("_IDP_IDPCREATEDNOTSTARTED","You created new request for: %s.");
define("_IDP_YOURIDPREQUESTSTARTED","Your IDP request has been initiated.");
define("_HRIS_HOMEUSER_BLOCK_TITLE","Home");
define("_HRIS_IDPREPORTDIVISIONAPPROVAL_BLOCK_TITLE","IDP - Report Approval (Next Superior)");
define("_HRIS_IDPREPORTSECTIONAPPROVAL_BLOCK_TITLE","IDP - Report Approval (Superior)");
define("_HRIS_IDPMYREPORT_BLOCK_TITLE","IDP - My Report");
define("_HRIS_IDPHRAPPROVAL_BLOCK_TITLE","IDP - HR Monitor");
define("_HRIS_IDPEVENTEMPLOYEEREGISTRATION_BLOCK_TITLE","IDP - Event Invitation");
define("_HRIS_IDPEVENTMANAGEMENT_BLOCK_TITLE","IDP - Event Management");
define("_HRIS_IDPINSTITUTES_BLOCK_TITLE","Institutes");
define("_HRIS_IDPNEXTSUPERIORAPPROVAL_BLOCK_TITLE","IDP - Request Approval (Next Superior)");
define("_HRIS_IDPSUPERIORAPPROVAL_BLOCK_TITLE","IDP - Request Approval (Superior)");
define("_HRIS_IDPMYREQUEST_BLOCK_TITLE","IDP - My Request");
define("_HRIS_IDPREQUEST_BLOCK_TITLE","IDP - Start Request");
define("_HRIS_IDPREVIEWTOOL_BLOCK_TITLE","IDP - Subordinate Request");
define("_HRIS_IDPDEVLIB_BLOCK_TITLE","Subject Definition");
define("_HRIS_IDPMETHODCLASS_BLOCK_TITLE","Method Type");
define("_HRIS_PAGESEDITOR_BLOCK_TITLE","Pages Editor");
define("_HRIS_ASSESSMENTSTART_BLOCK_TITLE","Start Assessment");
define("_HRIS_ASSESSMENTSESSIONCHECK_BLOCK_TITLE","Session Check");
define("_HRIS_ASSESSORCHECK_BLOCK_TITLE","Assessor Check");
define("_HRIS_EDITPEERGROUP_BLOCK_TITLE","Peer Group");
define("_HRIS_ASSESSMENTSESSION_BLOCK_TITLE","Assessment Session Management");
define("_HRIS_HRVISION_BLOCK_TITLE","Vision, Mission And Objective");
define("_HRIS_USER_BLOCK_TITLE","User Management");
define("_HRIS_ASSESSMENT_BLOCK_TITLE","Competency Assessment");
define("_HRIS_ASSESSMENTRESULT_BLOCK_TITLE","Individual Competency Assessment Result");
define("_HRIS_EMPLOYEE_BLOCK_TITLE","Personnel Administration");
define("_HRIS_EDITCOMPETENCYGROUP_BLOCK_TITLE","Competency Group Definition");
define("_HRIS_EDITCOMPETENCY_BLOCK_TITLE","Competency Definition");
define("_HRIS_PGROUP2ORG_BLOCK_TITLE","Group Access");
define("_HRIS_EDITJOBTITLES_BLOCK_TITLE","Job Titles");
define("_HRIS_EDITLOCATION_BLOCK_TITLE","Location");
define("_HRIS_EDITWORKAREA_BLOCK_TITLE","Work Area");
define("_HRIS_EDITJOBCLASS_BLOCK_TITLE","Position Level");
define("_HRIS_DEVELOPMENTPLAN_BLOCK_TITLE","Development Plan");
define("_HRIS_ASSESSMENTPROC_BLOCK_TITLE","Assessment Procedure");
define("_HRIS_POSMATRIX_BLOCK_TITLE","Competency Matrix");
define("_HRIS_COMPDICTIONARY_BLOCK_TITLE","Competency Dictionary");
define("_HRIS_COMPMODEL_BLOCK_TITLE","Competency Model");
define("_HRIS_JOBTITLES_BLOCK_TITLE","Job Description");
define("_HRIS_HRPROCEDURE_BLOCK_TITLE","HR Procedure");
define("_HRIS_HRBUSINESSPROCESS_BLOCK_TITLE","HR Business Process");
define("_HRIS_COMPCONCEPT_BLOCK_TITLE","Concept");
define("_HRIS_ORGCHART_BLOCK_TITLE","Organization Structure");
define("_HRIS_HRPOLICY_BLOCK_TITLE","HR Policy");
define("_HRIS_ABOUTHR_BLOCK_TITLE","About HR");
define("_HRIS_HOMEGUEST_BLOCK_TITLE","Home");
define("_HRIS_ORGCLASS_BLOCK_TITLE","Organization Levels");
define("_HRIS_ORGS_BLOCK_TITLE","Organization Names");
define("_HRIS_ORGREL_BLOCK_TITLE","Organization Relation");

define("_HRIS_PGROUP_LIST","Groups");
define("_HRIS_GROUP_EDITACCESS","Edit Access");
define("_HRIS_PGROUP_NAME","Group Name");
define("_HRIS_PGROUP_ASSIGNEDPORGLIST","Allowed Organization");
define("_HRIS_PGROUP_NOORGASSIGNED","-");
define("_HRIS_PGROUP_ADDORG","Add Organization");
define("_HRIS_SELECTORG","Select Organization");

define("_HRIS_EMPLOYEE_SEARCHMESSAGE","-");
define("_HRIS_EMPLOYEE_DUMMY_ID","baru");
define("_HRIS_EMPLOYEESELECT","Select Personnel");

} // HRIS_LANGUAGE_DEFINED
?>