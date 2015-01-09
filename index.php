<?php
/********************************************************************************
*  Copyright notice
*
*  (c) 2014 Christoph Taubmann (info@cms-kit.org)
*  All rights reserved
*
*  This script is part of cms-kit Framework. 
*  This is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License Version 3 as published by
*  the Free Software Foundation, or (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/licenses/gpl.html

*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*********************************************************************************/

/**
* Setup a new project
* used to create the first project as well
* This wizard is accessible without being logged in (but you must enter the super password)
*/
$backend = '../../';
require $backend.'inc/php/session.php';
require $backend.'inc/php/functions.php';
require $backend.'inc/global_configuration.php';

/**
 * looking for language files in login
 */
$lang = browserLang( glob('locales/*.php') );

$LL = array();
@include 'locales/'.$lang.'.php';


/**
 * create the project-directory if it does not exists
 */
if (!file_exists($backend.'../projects'))
{
	if(!is_writable($backend.'../')) exit('could not create projects because main-directory is not writable');
	mkdir($backend.'../projects');
	chmod($backend.'../projects', 0777);
	file_put_contents($backend.'../projects/index.html', '');
	chmod($backend.'../projects/index.html', 0777);
}

/**
 * Language translator (needed here, because we don't include inc/php/header.php)
 * @param $str
 * @return mixed
 */
function L($str)
{

	global $LL;
	if(isset($LL[$str])) {
		return $LL[$str];
	} else {
		return str_replace('_', ' ', $str);
	}
}

/**
 * Tooltip-Labels used in Step 3 / 4 below
 * @param $what
 * @param bool $float
 * @return string
 */
function hlp($what, $float=true)
{
	global $LL;
	return (isset($LL['project_setup_help_'.$what]) ? 
			'<a class="tt'.($float?' fr':'').'" href="#">?<span>'.L('project_setup_help_'.$what).'</span></a>' : 
			'');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Create a new project</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1" />
<script type="text/javascript" src="../../../vendor/cmskit/jquery-ui/jquery.min.js"></script>
<link rel="stylesheet" type="text/css" href="inc/css/styles.css" />
<script type="text/javascript" src="inc/js/functions.js"></script>

</head>
<body>
<div id="wrapper">
<?php

// #### 1 #### no (correct) super-password/captcha => draw login-form (again)
if (!isset($_POST['pass']) || crpt($_POST['pass'], $super[0]) !== $super[0].':'.$super[1])
{
	//echo '<h3>'.L('incorrect_password').'</h3>';
	
	require 'inc/step1.php';
}
if (isset($_POST['pass']) && (!isset($_POST['captcha_answer']) || $_POST['captcha_answer'] != $_SESSION['captcha_answer']) )
{
	echo '<h3>'.L('incorrect_captcha').'</h3>';
	require 'inc/step1.php';
}


// #### 2 #### no (wished) Project-Name is given => draw Input to enter Project-Name
if (!isset($_POST['wished_name']))
{
	require 'inc/step2.php';
}

$_POST['wished_name'] = @preg_replace('/[^a-z0-9_]/si', '', $_POST['wished_name']);
$projectPath = $backend.'../projects/'.$_POST['wished_name'];

// Project still exists => show Error & draw Input to enter Project-Name
if (file_exists($projectPath.'/objects/__configuration.php'))
{
	echo '<h3>'.L('Project_Name').' "'.$_POST['wished_name'].'" '.L('already_in_use').'</h3>';
	require 'inc/step2.php';
}

// #### 3 #### draw input for Database-/Folder-Credentials
if (!isset($_POST['generate_project']))
{
	require 'inc/step3.php';
}

// #### 4 #### show the Success-Form
if (isset($_POST['generate_project']))
{
	require 'inc/step4.php';
}

// ... this shouldn't happen
echo '<h3>'.L('nothing_to_do').'</h3>';
?>

</body>
</html>
