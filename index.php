<?php /**
*Servidor Desarollo con SVN 8512
*
*/
if(!defined('sugarEntry'))define('sugarEntry', true);

die("Prueba 1 GIT HUB");

/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/

$startTime = microtime();

require_once('include/entryPoint.php');
/***
* @deprecated En proximas versiones se desactiva el offline client
* @author Jesus Andrade
* @date 20081218
*/
/*
// OFFLINE CLIENT
if(isset($sugar_config['disc_client']) && $sugar_config['disc_client']) 
{
	require_once ('modules/Sync/SyncController.php');
	$current_user->is_admin = '0'; //No admins for disc client
}
*/
global $currentModule;
global $moduleList;
global $system_config;
$menu = array();
///////////////////////////////////////////////////////////////////////////////
////	REDIRECTION VARS
if(!empty($_REQUEST['cancel_redirect'])) 
{
	if(!empty($_REQUEST['return_action'])) 
	{
		$_REQUEST['action'] = $_REQUEST['return_action'];
		$_POST['action'] = $_REQUEST['return_action'];
		$_GET['action'] = $_REQUEST['return_action'];
	}
	if(!empty($_REQUEST['return_module'])) 
	{
		$_REQUEST['module'] = $_REQUEST['return_module'];
		$_POST['module'] = $_REQUEST['return_module'];
		$_GET['module'] = $_REQUEST['return_module'];
	}
	if(!empty($_REQUEST['return_id'])) 
	{
		$_REQUEST['id'] = $_REQUEST['return_id'];
		$_POST['id'] = $_REQUEST['return_id'];
		$_GET['id'] = $_REQUEST['return_id'];
	}
}


if(isset($_REQUEST['action']))
{
	$action = $_REQUEST['action'];
} 
else 
{
	$action = "";
}
if(isset($_REQUEST['module'])) 
{
	$module = $_REQUEST['module'];
} 
else 
{
	$module = "";
}
if(isset($_REQUEST['view'])) 
{
    $view = $_REQUEST['view'];
} 
else 
{
    $view = "";
}

if(isset($_REQUEST['record'])) 
{
	$record = $_REQUEST['record'];
} 
else 
{
	$record = "";
}
////	REDIRECTION VARS
///////////////////////////////////////////////////////////////////////////////
$system_config = new Administration();
$system_config->retrieveSettings('system');

$authController = new AuthenticationController((!empty($sugar_config['authenticationClass'])? $sugar_config['authenticationClass'] : 'SugarAuthenticate'));

///////////////////////////////////////////////////////////////////////////////
////	USER LOGIN AUTHENTICATION
//FIRST PLACE YOU CAN INSTANTIATE A SUGARBEAN;
// for Disconnected Client
if(isset($_REQUEST['MSID'])) 
{
	session_id($_REQUEST['MSID']);
	session_start();
	if(isset($_SESSION['user_id']) && isset($_SESSION['seamless_login'])) 
	{
		unset ($_SESSION['seamless_login']);
		global $current_user;
		$authController->sessionAuthenticate($_SESSION['user_id']);
		$current_user->authenticated = true;
		$use_current_user_login = true;
	}
	else
	{

	    if(isset($_COOKIE['PHPSESSID'])) 
	    {
	       setcookie('PHPSESSID', '', time()-42000, '/');
        }
	    sugar_cleanup(false);
	    session_destroy();
	    exit('Not a valid entry method');
	}
} 
else 
{
	session_start();
}


// If recording is available, call the recorder to record this round trip.
if(is_file("recorder.php"))
{
    include("recorder.php");
}
// Double check the server's unique key is in the session.  Make sure this is not an attempt to hijack a session
$user_unique_key = (isset($_SESSION['unique_key'])) ? $_SESSION['unique_key'] : '';
$server_unique_key = (isset($sugar_config['unique_key'])) ? $sugar_config['unique_key'] : '';
$allowed_actions = array('Authenticate', 'Login'); // these are actions where the user/server keys aren't compared


/***
* @deprecated En proximas versiones se desactiva el offline client
* @author Jesus Andrade
* @date 20081218
*/
/*
//OFFLINE CLIENT CHECK
if(isset($sugar_config['disc_client']) && $sugar_config['disc_client'] == true && 
   isset($sugar_config['oc_converted']) && $sugar_config['oc_converted'] == false)
{
    header('Location: oc_convert.php?first_time=true');
    exit ();
}
*/
// to preserve a timed-out user's click choice
if(($user_unique_key != $server_unique_key) && (!in_array($action, $allowed_actions)) && 
   (!isset($_SESSION['login_error']))) 
   {
	session_destroy();
	$post_login_nav = '';

	if(!empty($record) && !empty($action) && !empty($module)) 
	{
		if(in_array(strtolower($action), array('save', 'delete')) || isset($_REQUEST['massupdate'])
			|| isset($_GET['massupdate']) || isset($_POST['massupdate']))
			$post_login_nav = '';
		else
			$post_login_nav = '&login_module='.$module.'&login_action='.$action.'&login_record='.$record;
	}

	header('Location: index.php?action=Login&module=Users'.$post_login_nav);
	exit ();
}

if(isset($_REQUEST['PHPSESSID']))
	$GLOBALS['log']->debug("****Starting Application for  session ".$_REQUEST['PHPSESSID']);
else
	$GLOBALS['log']->debug("****Starting Application for new session");

// We use the REQUEST_URI later to construct dynamic URLs.  IIS does not pass this field
// to prevent an error, if it is not set, we will assign it to ''
if(!isset($_SERVER['REQUEST_URI'])) 
{
	$_SERVER['REQUEST_URI'] = '';
}
// Check to see ifthere is an authenticated user in the session.
if(isset($_SESSION['authenticated_user_id'])) 
{

	$GLOBALS['log']->debug('We have an authenticated user id: '.$_SESSION['authenticated_user_id']);
	/**
	 * CN: Bug 4128: some users are getting redirected to
	 * action=Login&module=Users, even after they have been auth'd
	 * Setting it manually here
	 */
	if(isset($_REQUEST['action']) && isset($_REQUEST['module'])) 
	{
		if($_REQUEST['action'] == 'Login' && $_REQUEST['module'] == 'Users') 
		{
			$_REQUEST['action'] = 'index';
			$_REQUEST['module'] = 'Home';
			$action = 'index';
			$module = 'Home';
			header('Location: index.php?action=index&module=Home');
			die();
		}
	}
} 
elseif(isset($action) && isset($module) && ($action == 'Authenticate') && $module == 'Users') 
{
	$GLOBALS['log']->debug('We are authenticating user now');
} 
else 
{
	$GLOBALS['log']->debug('The current user does not have a session.  Going to the login page');
	$action = 'Login';
	$module = 'Users';
	$_REQUEST['action'] = $action;
	$_REQUEST['module'] = $module;
}

if(!$use_current_user_login) 
{  // disconnected client's flag
	$current_user = new User();
	if(isset($_SESSION['authenticated_user_id'])) 
	{ // set in modules/Users/Authenticate.php
		if(!$authController->sessionAuthenticate()) 
		{ // if the object we get back is null for some reason, this will break - like user prefs are corrupted
			$GLOBALS['log']->fatal('User retrieval for ID: ('.$_SESSION['authenticated_user_id'].') does not exist in database or retrieval failed catastrophically.  Calling session_destroy() and sending user to Login page.');
			session_destroy();
			header('Location: index.php?action=Login&module=Users');
			die();
		}
		$GLOBALS['log']->debug('Current user is: '.$current_user->user_name);
	}
}

// cn: bug 12274 - prepare secret guid for asynchronous calls
if(!isset($_SESSION['asynchronous_key']) || empty($_SESSION['asynchronous_key'])) {
	$_SESSION['asynchronous_key'] = create_guid();
}
////	END USER LOGIN AUTHENTICATION
///////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////
////	USER TIMEZONE SETTING
// ut=0 => upgrade script set users's timezone
if(isset($_SESSION['authenticated_user_id']) && !empty($_SESSION['authenticated_user_id'])) 
{
	$ut = $current_user->getPreference('ut');
	if(empty($ut) && $_REQUEST['action'] != 'SaveTimezone') {
		$module = 'Users';
		$action = 'SetTimezone';
		$record = $current_user->id;
	}

}
////	END USER TIMEZONE SETTING
///////////////////////////////////////////////////////////////////////////////


$skipHeaders = false;
$skipFooters = false;

// Set the current module to be the module that was passed in
if(!empty($module)) {
	$currentModule = $module;
}

///////////////////////////////////////////////////////////////////////////////
////	RENDER PAGE REQUEST BASED ON $module - $action - (and/or) $record

// if we have an action and a module, set that action as the current.
if(!empty($action) && !empty($module)) {
	$GLOBALS['log']->info('In module: '.$module.' -- About to take action '.$action);
	$GLOBALS['log']->debug('in module '.$module.' -- in '.$action);
	$GLOBALS['log']->debug('----------------------------------------------------------------------------------------------------------------------------------------------');

    //_ppd(ereg('^SupportPortal', $action) );
	if(ereg('^Save', $action) || ereg('^Delete', $action) || ereg('^Popup', $action) ||
        ereg('^ChangePassword', $action) || ereg('^Authenticate', $action) || ereg('^Logout', $action) ||
        ereg('^Export', $action) || (ereg('^SupportPortal', $action) && ereg('^documentation', $view)) || ereg('^PrintPDF', $action)) {
    		$skipHeaders = true;
    		if(ereg('^Popup', $action) || ereg('^ChangePassword', $action) || ereg('^Export', $action) || ereg('^SupportPortal', $action) || ereg('^PrintPDF', $action))
    			$skipFooters = true;
	}
	if((isset($_REQUEST['sugar_body_only']) && $_REQUEST['sugar_body_only'])) 
	{
		$skipHeaders = true;
		$skipFooters = true;
	}
	if((isset($_REQUEST['from']) && $_REQUEST['from'] == 'ImportVCard') || 
	          !empty($_REQUEST['to_pdf']) || !empty($_REQUEST['to_csv'])) 
	{
		$skipHeaders = true;
		$skipFooters = true;
	}
	if($action == 'BusinessCard' || $action == 'ConvertLead' || $action == 'Save') 
	{
		header('Expires: Mon, 20 Dec 1998 01:00:00 GMT');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
	}
	if($action == 'Import' && isset($_REQUEST['step']) && $_REQUEST['step'] == '4') 
	{
		$skipHeaders = true;
		$skipFooters = true;
	}
	
	if($action == 'Save2') 
	{
		$currentModuleFile = 'include/generic/Save2.php';
	} elseif($action == 'SubPanelViewer') {
		$currentModuleFile = 'include/SubPanel/SubPanelViewer.php';
	} elseif($action == 'DeleteRelationship') {
		$currentModuleFile = 'include/generic/DeleteRelationship.php';
	} elseif($action == 'Login' && isset($_SESSION['authenticated_user_id'])) 
	{
		header('Location: index.php?action=Logout&module=Users');
		die();
	} 
	else 
	{
		$currentModuleFile = 'modules/'.$module.'/'.$action.'.php';
	}
} 
elseif(!empty($module)) 
{ // ifwe do not have an action, but we have a module, make the index.php file the action
	$currentModuleFile = 'modules/'.$currentModule.'/index.php';
} 
else 
{ // Use the system default action and module
    // use $sugar_config['default_module'] and $sugar_config['default_action'] as set in config.php
	// Redirect to the correct module with the correct action.  We need the URI to include these fields.
	header('Location: index.php?action='.$sugar_config['default_action'].'&module='.$sugar_config['default_module']);
	die();
}
////	END RENDER PAGE REQUEST BASED ON $module - $action - (and/or) $record
///////////////////////////////////////////////////////////////////////////////
$export_module = $currentModule;

if (!empty($currentModuleFile)) 
{
	$GLOBALS['log']->info('current page is '.$currentModuleFile);
}

$GLOBALS['log']->info('current module is '.$currentModule);
$GLOBALS['request_string'] = ''; // for printing

//some postings use "GET" and others use "POST", so let's merge the two  
//and use it assign it back to get string for creation of print url
$tmp_post_get = array_merge($_GET,$_POST);
$_GET  = $tmp_post_get; 
$_POST = $tmp_post_get;


if($_POST['excluir_print'] != 1){
		foreach ($_GET as $key => $val){
			if(is_array($val)) 
			{
				foreach ($val as $k => $v) 
				{
					$GLOBALS['request_string'] .= $val[$k].'='.urlencode($v).'&';
				}
			} 
			else{
				$GLOBALS['request_string'] .= $key.'='.urlencode($val).'&';
			}
		}
}


$GLOBALS['request_string'] .= 'print=true';
// end printing

$version_query = 'SELECT count(*) as the_count FROM config WHERE category=\'info\' AND name=\'sugar_version\'';
if($current_user->db->dbType == 'oci8'){



}
else if ($current_user->db->dbType == 'mssql')
{
	$version_query .= " AND CAST(value AS varchar(8000)) = '$sugar_db_version'";
}
 else {
	$version_query .= " AND value = '$sugar_db_version'";
}
$result = $current_user->db->query($version_query);
$row = $current_user->db->fetchByAssoc($result, -1, true);
$row_count = $row['the_count'];


if($row_count == 0 && empty($sugar_config['disc_client']))
{
    if ($sugar_version == '4.5.0f') {
        $update_query = 'UPDATE config SET value=\'4.5.0\' WHERE category=\'info\' AND name=\'sugar_version\'';
        $current_user->db->query($update_query);
    }
    else {
        sugar_die("Sugar CRM $sugar_version Files May Only Be Used With A Sugar CRM $sugar_db_version Database.");
    }
}

//Used for current record focus
$focus = null;



///////////////////////////////////////////////////////////////////////////////
////	LANGUAGE PACK STRING EXTRACTION
// ifthe language is not set yet, then set it to the default language.
if(isset($_SESSION['authenticated_user_language']) && $_SESSION['authenticated_user_language'] != '') 
{
	$current_language = $_SESSION['authenticated_user_language'];
} 
else 
{
	$current_language = $sugar_config['default_language'];
}
$GLOBALS['log']->debug('current_language is: '.$current_language);
//set module and application string arrays based upon selected language
$app_strings = return_application_language($current_language);
if(empty($current_user->id))
{
    $app_strings['NTC_WELCOME'] = '';
}
if(!empty($system_config->settings['system_name'])){
	
	$app_strings['LBL_BROWSER_TITLE'] = $system_config->settings['system_name'];
}
$app_list_strings = return_app_list_strings_language($current_language);
$mod_strings = return_module_language($current_language, $currentModule);
insert_charset_header();

//TODO: Clint - this key map needs to be moved out of $app_list_strings since it never gets translated.
//best to just have an upgrade script that changes the parent_type column from Account to Accounts, etc.
$app_list_strings['record_type_module'] = array(
	'Contact' => 'Contacts',
	'Account' => 'Accounts',
	'Opportunity' => 'Opportunities',
	'Case' => 'Cases',
	'Note' => 'Notes',
	'Call' => 'Calls',
	'Email' => 'Emails',
	'Meeting' => 'Meetings',
	'Task' => 'Tasks',
	'Lead' => 'Leads',
	'Bug' => 'Bugs',
	'Project' => 'Project', // cn: Bug 4638 - missing and broke notifications link
	'ProjectTask' => 'ProjectTask', // cn: missing and broke notifications link
);
////	END LANGUAGE PACK STRING EXTRACTION
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
////	ADMIN ONLY VIEWS SECURITY
if(!isset($_POST['Modulo']) ){
	if(!is_admin($current_user) && !empty($adminOnlyList[$module])
	&& !empty($adminOnlyList[$module]['all'])
	&& (empty($adminOnlyList[$module][$action]) || $adminOnlyList[$module][$action] != 'allow')) 
			{
				sugar_die("Unauthorized access to $module:$action.");
			}
	}


////	ADMIN ONLY VIEWS SECURITY
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
////	DETAIL VIEW-SPECIFIC RENDER CODE
//ifDetailView, set focus to record passed in
if($action == "DetailView"){
	if(!isset($_REQUEST['record']))
	die("A record number must be specified to view details.");

	$GLOBALS['log']->debug('----> BEGIN DETAILVIEW TRACKER <----');
	// if we are going to a detail form, load up the record now.
	// Use the record to track the viewing.
	// todo - Have a record of modules and thier primary object names.
	
	$entity = $beanList[$currentModule];
	require_once ($beanFiles[$entity]);	
	$focus = new $entity ();
	$result = $focus->retrieve($_REQUEST['record']);
	if($result) 
	{
		// Only track a viewing ifthe record was retrieved.
		$focus->track_view($current_user->id, $currentModule);
	}
	$GLOBALS['log']->debug('----> END DETAILVIEW TRACKER <----');
}
////	END DETAIL-VIEW SPECIFIC RENDER CODE
///////////////////////////////////////////////////////////////////////////////


// set user, theme and language cookies so that login screen defaults to last values
if(isset($_SESSION['authenticated_user_id'])){
	$GLOBALS['log']->debug("setting cookie ck_login_id_20 to ".$_SESSION['authenticated_user_id']);
	setcookie('ck_login_id_20', $_SESSION['authenticated_user_id'], time() + 86400 * 90);
}
if(isset($_SESSION['authenticated_user_theme'])){
	$GLOBALS['log']->debug("setting cookie ck_login_theme_20 to ".$_SESSION['authenticated_user_theme']);
	setcookie('ck_login_theme_20', $_SESSION['authenticated_user_theme'], time() + 86400 * 90);
}
if(isset($_SESSION['authenticated_user_language'])){
	$GLOBALS['log']->debug("setting cookie ck_login_language_20 to ".$_SESSION['authenticated_user_language']);
	setcookie('ck_login_language_20', $_SESSION['authenticated_user_language'], time() + 86400 * 90);
}

///////////////////////////////////////////////////////////////////////////////
////	START OUTPUT BUFFERING STUFF
ob_start();
////	END DETAIL-VIEW SPECIFIC RENDER CODE
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
////	THEME PATH SETUP AND THEME CHANGES
if(isset($_SESSION['authenticated_user_theme']) && $_SESSION['authenticated_user_theme'] != '') {
	$theme = $_SESSION['authenticated_user_theme'];
} else {
	$theme = $sugar_config['default_theme'];
}
$image_path = 'themes/'.$theme.'/images/';

//if the theme is changed
$_SESSION['theme_changed'] = false;
if(isset($_REQUEST['usertheme'])) 
{
	$_SESSION['theme_changed'] = true;
	$_SESSION['authenticated_user_theme'] = clean_string($_REQUEST['usertheme']);
	$theme = clean_string($_REQUEST['usertheme']);
}

//if the language is changed
if(isset($_REQUEST['userlanguage'])) 
{
	$_SESSION['theme_changed'] = true;
	$_SESSION['authenticated_user_language'] = clean_string($_REQUEST['userlanguage']);
	$current_language = clean_string($_REQUEST['userlanguage']);
}

$GLOBALS['log']->debug('Current theme is: '.$theme);
ACLController :: filterModuleList($moduleList);
ACLController :: filterModuleList($modInvisListActivities);
//TODO move this code into $theme/header.php so that we can be within the <DOCTYPE xxx> and <HTML> tags.

// /include/javascript/sugar_3.js
//

$prefijo_minif = "/min/?f=";
$prefijo_minig = "/min/?g=";
//echo "<!DOCTYPE html>";

//$_REQUEST['to_pdf'] = true;

if(empty($_REQUEST['to_pdf']) && empty($_REQUEST['to_csv'])) 
{
	

	/*
	echo '<script type="text/javascript" src="include/javascript/cookie.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<link rel="stylesheet" type="text/css" media="all" href="themes/'.$theme.'/calendar-win2k-cold-1.css?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '">';
	echo '<script>jscal_today = ' . (1000 * strtotime($timedate->handle_offset(gmdate('Y-m-d H:i:s'), 'Y-m-d H:i:s'))) . '; if(typeof app_strings == "undefined") app_strings = new Array();</script>';
	echo '<script type="text/javascript" src="jscalendar/calendar.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<script type="text/javascript" src="jscalendar/lang/calendar-es.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<script type="text/javascript" src="jscalendar/calendar-setup_3.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<script src="include/javascript/yui/YAHOO.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
    echo '<script src="include/javascript/yui/log.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<script src="include/javascript/yui/dom.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
    echo '<script src="'.$prefijo_minif.'include/javascript/yui/event.js"></script>';
	echo '<script src="include/javascript/yui/animation.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<script src="include/javascript/yui/connection.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<script src="include/javascript/yui/dragdrop.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
    echo '<script src="'.$prefijo_minif.'include/javascript/yui/container.js"></script>';
	echo '<script src="include/javascript/yui/ygDDList.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	echo '<script type="text/javascript" src="include/javascript/sugar_3.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
	//Euc path Se agrega el javascript personalizado propio de Mr Chispa 10/03/2008
	echo '<script type="text/javascript" src="include/javascript/mrchispa.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '"></script>';
    */

  
	//echo '<script src="'.$prefijo_minig.'js_index_1"></script>';
	if($_POST['action'] != 'Popup'){ 
		include ('themes/'.$theme.'/header.php');
}
	
	

	echo '<link rel="stylesheet" type="text/css" media="all" href="themes/'.$theme.'/calendar-win2k-cold-1.css?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '">';
	echo '<script>jscal_today = ' . (1000 * strtotime($timedate->handle_offset(gmdate('Y-m-d H:i:s'), 'Y-m-d H:i:s'))) . '; if(typeof app_strings == "undefined") app_strings = new Array();</script>';
	
	


	$linksL = array();
	

	echo '<script type="text/javascript">var asynchronous_key = "'.$_SESSION['asynchronous_key'].'";</script>'; // cn: bug 12274 - create session-stored key to defend against CSRF
	echo $timedate->get_javascript_validation();
	$jsalerts = new jsAlerts();
    if(!is_file($sugar_config['cache_dir'] . 'jsLanguage/' . $current_language . '.js')) {
        require_once('include/language/jsLanguage.php');
        jsLanguage::createAppStringsCache($current_language);
    }
    //echo '<script type="text/javascript" src="' . $sugar_config['cache_dir'] . 'jsLanguage/' . $current_language . '.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '&j=' . $sugar_config['js_lang_version'] . '"></script>';


    $linksL[] = $sugar_config['cache_dir'] . 'jsLanguage/' . $current_language . '.js';


    if(!is_file($sugar_config['cache_dir'] . 'jsLanguage/' . $currentModule . '/' . $current_language . '.js')) {
        require_once('include/language/jsLanguage.php');
        jsLanguage::createModuleStringsCache($currentModule, $current_language);
    }
    //echo '<script type="text/javascript" src="' . $sugar_config['cache_dir'] . 'jsLanguage/' . $currentModule . '/' . $current_language . '.js?s=' . $sugar_version . '&c=' . $sugar_config['js_custom_version'] . '&j=' . $sugar_config['js_lang_version'] . '"></script>';


    $linksL[] = $sugar_config['cache_dir'] . 'jsLanguage/' . $currentModule . '/' . $current_language . '.js';
    
    echo '<script src="'.$prefijo_minif.implode(',',$linksL).'"></script>';




} elseif(isset($_REQUEST['popup']) && !empty($_REQUEST['popup'])) {
	// cn: bug 12274 - add security metadata envelope for async calls in popups
	echo '<script type="text/javascript">var asynchronous_key = "'.$_SESSION['asynchronous_key'].'";</script>'; // cn: bug 12274 - create session-stored key to defend against CSRF
}




// Set module shortcuts menu to an empty array, in case
// we don't have access to the module
if(!isset($module_menu)) $module_menu = array();

//skip headers for popups, deleting, saving, importing and other actions
if(!$skipHeaders) 
{
	$GLOBALS['log']->debug("including headers");
	if(!is_file('themes/'.$theme.'/header.php')) 
	{
        if(is_file('themes/'. $sugar_config['default_theme']. '/header.php')) 
        {
            $theme = $sugar_config['default_theme'];
        } 
        else 
        {
            sugar_die("Invalid theme specified");
        }
	}
	// Only print the errors for admin users.
	if(!empty($_SESSION['HomeOnly'])) 
	{
		$moduleList = array ('Home');
	}

	//include ('themes/'.$theme.'/header.php');

	if(is_admin($current_user)) 
	{
		if(isset($_REQUEST['show_deleted'])) 
		{
			if($_REQUEST['show_deleted']) 
			{
				$_SESSION['show_deleted'] = true;
			} 
			else 
			{
				unset ($_SESSION['show_deleted']);
			}

		}
	}

	include_once ('modules/Administration/DisplayWarnings.php');

	// cn: displays an email count in Welcome bar if preference set
	if(!empty($current_user->id) && $current_user->getPreference('email_show_counts') == 1) $current_user->displayEmailCounts();
	echo "<!-- crmprint -->";
} 
else 
{
	$GLOBALS['log']->debug("skipping headers");
}
////	END THEME PATH SETUP AND THEME CHANGES
///////////////////////////////////////////////////////////////////////////////


loadLicense();


// added a check for security of tabs to see if an user has access to them
// this prevents passing an "unseen" tab to the query string and pulling up its contents
if(!isset($modListHeader)) 
{
	if(isset($current_user)) 
	{

		$modListHeader = query_module_access_list($current_user);
	}
}



if(	array_key_exists($_REQUEST['module'], $modListHeader)
	|| in_array($_REQUEST['module'], $modInvisList)
	|| ((array_key_exists("Activities", $modListHeader)
	|| array_key_exists("Calendar", $modListHeader))
	&& in_array($_REQUEST['module'], $modInvisListActivities) )
	|| ($_REQUEST['module'] == "iFrames"
	&& isset($_REQUEST['record']))) 
{
	// Only include the file if there is a file.  User login does not have a filename but does have a module.
	if(!empty($currentModuleFile)) 
	{
		///////////////////////////////////////////////////////////////////////
		////	DISPLAY REQUESTED PAGE
		$GLOBALS['log']->debug('--------->  BEGING INCLUDING REQUESTED PAGE: ['.$currentModuleFile.']  <------------');
		include($currentModuleFile);
		$GLOBALS['log']->debug('--------->  END INCLUDING REQUESTED PAGE: ['.$currentModuleFile.']  <------------');
		////	END DISPLAY REQUESTED PAGE
		///////////////////////////////////////////////////////////////////////
	}
	if(isset($focus) && is_subclass_of($focus, 'SugarBean') && $focus->bean_implements('ACL')) 
	{
		ACLController :: addJavascript($focus->module_dir, '', $focus->isOwner($current_user->id));
	}
} 
else { 
	// avoid js error when set_focus is not defined
	echo '<script>function set_focus(){return;}</script><p class="error">Advertencia: Usted no tiene permiso para acceder a este m&oacute;dulo.</p>';
}

if(!$skipFooters) 
{
	echo "<!-- crmprint -->";
	echo $jsalerts->getScript();

	include ('themes/'.$theme.'/footer.php');

	if(!isset($_SESSION['avail_themes']))
		$_SESSION['avail_themes'] = serialize(get_themes());
	if(!isset($_SESSION['avail_languages']))
		$_SESSION['avail_languages'] = serialize(get_languages());
	$user_mod_strings = return_module_language($current_language, 'Users');
	echo "<table cellpadding='0' cellspacing='0' width='100%' border='0' class=''>";

	if($_REQUEST['action'] != 'Login') 
	{
	    //set theme
		echo "<tr><td align='center'><table border='0'><tr><td width='40%' align='right'>{$user_mod_strings['LBL_THEME']}&nbsp;</td>";
		echo "<td width='60%' align='left'><select OnChange='location.href=\"index.php?" . str_replace("&print=true", "", $GLOBALS['request_string']) . "&usertheme=\"+this.value' style='width: 120px; font-size: 10px' name='usertheme'>";
		if(isset($_SESSION['authenticated_user_theme']) && !empty($_SESSION['authenticated_user_theme'])) 
		{
			$authenticated_user_theme = $_SESSION['authenticated_user_theme'];
		} 
		else 
		{
			$authenticated_user_theme = $sugar_config['default_theme'];
		}

		echo get_select_options_with_id(unserialize($_SESSION['avail_themes']), $authenticated_user_theme);
		echo '</select></td></tr>';

		echo '</td></tr></table>';
	}
	// Under the Sugar Public License referenced above, you are required to leave in all copyright statements in both
	// the code and end-user application.
	echo "<tr><td align='center' class='copyRight'>";
	if($sugar_config['calculate_response_time']) 
	{
		$endTime = microtime();
		$deltaTime = microtime_diff($startTime, $endTime);
		$response_time_string = $app_strings['LBL_SERVER_RESPONSE_TIME']." ".number_format(round($deltaTime,2),2)." ".$app_strings['LBL_SERVER_RESPONSE_TIME_SECONDS'].'<br />';
		$string="";
		$string.= ($response_time_string);
		$string.="<br>";
		
	}
		
	if($sugar_config['show_page_resources']) {
		// Print out the resources used in constructing the page.
		$included_files = get_included_files();


		// take all of the included files and make a list that does not allow for duplicates based on case
		// I believe the full get_include_files result set appears to have one entry for each file in real
		// case, and one entry in all lower case.
		$list_of_files_case_insensitive = array();

		foreach($included_files as $key=>$name)	{
			// preserve the first capitalization encountered.
			$list_of_files_case_insensitive[strtolower($name)] = $name;
		}
		//_pp($list_of_files_case_insensitive);
		$string.= ($app_strings['LBL_SERVER_RESPONSE_RESOURCES'].'('.$sql_queries.','.sizeof($list_of_files_case_insensitive).')<br>');
		$string.="<br>";			
		// Display performance of the internal and external caches....
		$string.= "External cache (hits/total=ratio) local ({$GLOBALS['external_cache_request_local_hits']}/{$GLOBALS['external_cache_request_local_total']}=".round($GLOBALS['external_cache_request_local_hits']*100/$GLOBALS['external_cache_request_local_total'],0)."%)";
		$string.="<br>";
		if($GLOBALS['external_cache_request_external_total']){
			// Only display out of process cache results if there was at least one attempt to retrieve from the out of process cache (this signifies that it was enabled).
			$string.= " external ({$GLOBALS['external_cache_request_external_hits']}/{$GLOBALS['external_cache_request_external_total']}=".round($GLOBALS['external_cache_request_external_hits']*100/$GLOBALS['external_cache_request_external_total'],0)."%)<br>";
		$string.="<br>";				
		}
		
		// Patch Victor hugo espinosa zarate 27/07/2010 -- Se adiciona condicion - Solamente opcion para sitio local 
		// Se comentarea 15/01/2013		 
			// if(is_admin($current_user)){ die("...enter to validation...");
				//echo "<a href='javascript:ver_debug(2)'>Ver Consultas</a></div><br>";
				//echo "<a href='javascript:ver_debug(1)'>Ver Archivos</a></div><br>";
			 //}
			 
		// Fin patch
	}

?>
<script>



/**
* Funcion Que abre una ventana para ver el debug de mrchispa
* @author Jesus Andrade
* @date 20081222
*/
function ver_debug(tipo){
	
	myWindow=window.open('','Debug','widht=480,height=640,scrollbars=1');
	myWindow.document.write('<?php echo $string;?>');
	myWindow.document.write('<hr>');
	
	if(tipo==1){
		<?php
		$includeFiles=get_included_files();
		for($i=0;$i<count($includeFiles);$i++ ){
			?>
			myWindow.document.write('<?php echo str_replace("\\" ,'&frasl;' ,$includeFiles[$i]);?>');
			myWindow.document.write('<br>');		
			<?php
		}
		?>
	}
	if(tipo==2){
		<?php
		for($i=0;$i<count($sql_debugger_brm);$i++ ){
			//cambiamos comilla doble por simple
			$sql_debugger_brm[$i]=str_replace("\"" ,"'" ,$sql_debugger_brm[$i]);
			//cambiamos saltos de linea por caracter de espacio
			$sql_debugger_brm[$i]=eregi_replace("[\n|\r|\n\r]{1,}", " ",$sql_debugger_brm[$i]);
			//eliminamos caracteres de espacios repetidos
			$sql_debugger_brm[$i]=eregi_replace("[[:space:]]{1,}"," ",$sql_debugger_brm[$i]);
			?>
			myWindow.document.write("<?php echo $sql_debugger_brm[$i];?>");		
			myWindow.document.write('<br>');				
			<?php
		}
		?>
	}
	myWindow.focus();
}
</script>

<script>

  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-42017413-1', 'brm.com.co');
  ga('send', 'pageview');

</script>

<?php

if(!$_SESSION['authenticated_user_id'])
	   echo '<table align="center" class="_tabla" width="80%">
              <tr>
	              <td class="contenidoTabla fondo_1" width="50%">
	              				<h1 class="tituloContenido">Politica de Calidad</h1>
				 				
					</td>
					<td class="contenidoTabla fondo_1" width="50%">
	              				<h1 class="tituloContenido">Objetivos de Calidad</h1>
				 				
					</td>					
                </tr>

                              <tr>
	              <td class="contenidoTabla fondo_1" width="50%">
	              				
				 				<p class="contenido_1">BRM S.A. busca la satisfacción de los requerimientos de sus clientes por medio de la prestación de servicios de telemercadeo y publicidad en medios impresos y/o digitales.

<br><br>Realizamos campañas de mercadeo relacional mediante estrategias de telemercadeo y/o publicidad en medios impresos o digitales con un servicio oportuno, personal, competente y confiable en el manejo de la información.

<br><br>Buscamos el mejoramiento continuo de nuestros procesos por medio del cumplimiento del sistema de gestión de calidad.</p>
					</td>
<td class="contenidoTabla fondo_1" width="50%">
	              				<p class=""><ul class="contenido_1 sss">
									<li>Alcanzar la satisfacción  de nuestros clientes a un nivel del 75%.</li>
									<li>Lograr a través  del desarrollo de las personas mantener y entrenar al mejor talento.</li>
									<li>Obtener el mayor nivel de eficiencia y productividad incrementando la rentabilidad.</li>
									<li>Cumplir los tiempos de entrega pactados con nuestros clientes.</li>
									<li>Disminuir el producto no conforme.</li>
									<li>Disminuir quejas y reclamos de nuestros clientes.</li>
					</td>              					
                </tr>
			</table>'; 


	
echo ('&copy; 2004-2007 <a href="http://www.sugarcrm.com" target="_blank" class="copyRightLink">SugarCRM Inc.</a> All Rights Reserved.<br />');

	// Under the Sugar Public License referenced above, you are required to leave in all copyright statements in both
	// the code and end-user application as well as the the powered by image. You can not change the url or the image below  .




	echo "<A href='http://www.sugarforge.org' target='_blank'><img style='margin-top: 2px' border='0' width='106' height='23' src='include/images/poweredby_sugarcrm.png' alt='Powered By SugarCRM'></a>\n";


   






	// End Required Image
	echo "</td></tr></table>\n";

	echo "</body></html>";
	
	


}
if(!function_exists("ob_get_clean"))
 {
	function ob_get_clean() 
	{
		$ob_contents = ob_get_contents();
		ob_end_clean();
		return $ob_contents;
	}
}

if(isset($_GET['print'])) 
{
	$page_str = ob_get_clean();
	$page_arr = explode("<!-- crmprint -->", $page_str);
	include ("phprint.php");
}

if(isset($sugar_config['log_memory_usage']) && $sugar_config['log_memory_usage'] && function_exists('memory_get_usage')) 
{
	$fp = @ fopen("memory_usage.log", "ab");
	@ fwrite($fp, "Usage: ".memory_get_usage()." - module: ". (isset($module) ? $module : "<none>")." - action: ". (isset($action) ? $action : "<none>")."\n");
	@ fclose($fp);
}

////////////////////
//put online user, by jesus
// Modificado por victor hugo espinosa zarate 13/10/2011
require_once('include/database/PearDatabase.php');
$db = PearDatabase::getInstance();
$query = "UPDATE users set online=1, last_login_date='".gmdate("Y-m-d H:i:s")."' , login='".gmdate("Y-m-d H:i:s")."', ip ='".query_client_ip()."'   where id='".$_SESSION['authenticated_user_id']."';"; 
$db->query($query);

//Logout expired users
/*v&m*/
/*echo 'Linea 896 en index.php';
__P($sugar_config['logout_expired_users']);*/
/*v&m*/


if($sugar_config['logout_expired_users']['enabled']){
	$query = "select value from config where category='update_expired_users' and name='last_respawn_date' ";
	$result=$db->query($query);
	$row = $db->fetchByAssoc($result);
	$tiempo=split(" ",$row['value']);
	
	/*v&m*/
/*	echo $query;
	__P($tiempo);*/
	/*v&m*/
	
	$hora=split(":",$tiempo[1]);	
	$fecha=split("-",$tiempo[0]);
	//update expired users process
	if(mktime(gmdate("H"),gmdate("i"),gmdate("s"),gmdate("m"),gmdate("d"),gmdate("Y"))-mktime($hora[0],$hora[1],$hora[2],$fecha[1],$fecha[2],$fecha[0])>=$sugar_config['logout_expired_users']['respawn_time']){
		//traer los usuario logueados y mirar si el tiempo expiro
		$query="select id,last_login_date from users where online=1 and deleted=0";
		$result=$db->query($query);	
		$expired_users=0;
		while($row = $db->fetchByAssoc($result) ){
			$tiempo=split(" ",$row['last_login_date']);
			$hora=split(":",$tiempo[1]);	
			$fecha=split("-",$tiempo[0]);
			if(mktime(gmdate("H"),gmdate("i"),gmdate("s"),gmdate("m"),gmdate("d"),gmdate("Y"))-mktime($hora[0],$hora[1],$hora[2],$fecha[1],$fecha[2],$fecha[0])>=$sugar_config['session_timeout']){	
				$query = "UPDATE users set online=0 where id='".$row['id']."';";
				$db->query($query);
				$expired_users++;
			}
		}
		$query = "UPDATE config set value='".gmdate("Y-m-d H:i:s")."' where category='update_expired_users' and name='last_respawn_date' ";
		$db->query($query);
		echo "<hr>Expired Users: ".$expired_users;
	}
}

//put online user
////////////////////


@session_write_close(); // submitted by Tim Scott in SugarCRM forums
sugar_cleanup();
?>