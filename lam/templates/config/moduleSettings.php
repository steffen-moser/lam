<?php
/*
$Id$

  This code is part of LDAP Account Manager (http://www.ldap-account-manager.org/)
  Copyright (C) 2009 - 2013  Roland Gruber

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/


/**
* Here the user can edit the module settings.
*
* @package configuration
* @author Roland Gruber
*/


/** Access to config functions */
include_once('../../lib/config.inc');
/** Access to account types */
include_once('../../lib/types.inc');

// start session
if (strtolower(session_module_name()) == 'files') {
	session_save_path("../../sess");
}
@session_start();

setlanguage();

// check if config is set
// if not: load login page
if (!isset($_SESSION['conf_config'])) {
	/** go back to login if password is invalid */
	require('conflogin.php');
	exit;
}

// check if user canceled editing
if (isset($_POST['cancelSettings'])) {
	metaRefresh("../login.php");
	exit;
}

$conf = &$_SESSION['conf_config'];

$errorsToDisplay = checkInput();

// check if button was pressed and if we have to save the settings or go to another tab
if (isset($_POST['saveSettings']) || isset($_POST['editmodules'])
	|| isset($_POST['edittypes']) || isset($_POST['generalSettingsButton'])
	|| isset($_POST['moduleSettings'])) {
	if (sizeof($errorsToDisplay) == 0) {
		// go to final page
		if (isset($_POST['saveSettings'])) {
			metaRefresh("confsave.php");
			exit;
		}
		// go to types page
		elseif (isset($_POST['edittypes'])) {
			metaRefresh("conftypes.php");
			exit;
		}
		// go to modules page
		elseif (isset($_POST['editmodules'])) {
			metaRefresh("confmodules.php");
			exit;
		}
		// go to types page
		elseif (isset($_POST['generalSettingsButton'])) {
			metaRefresh("confmain.php");
			exit;
		}
	}
}

$allTypes = getTypes();

echo $_SESSION['header'];

echo "<title>" . _("LDAP Account Manager Configuration") . "</title>\n";

// include all CSS files
$cssDirName = dirname(__FILE__) . '/../../style';
$cssDir = dir($cssDirName);
$cssFiles = array();
$cssEntry = $cssDir->read();
while ($cssEntry !== false) {
	if (substr($cssEntry, strlen($cssEntry) - 4, 4) == '.css') {
		$cssFiles[] = $cssEntry;
	}
	$cssEntry = $cssDir->read();
}
sort($cssFiles);
foreach ($cssFiles as $cssEntry) {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../../style/" . $cssEntry . "\">\n";
}

echo "<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"../../graphics/favicon.ico\">\n";
echo "</head><body>\n";
// include all JavaScript files
$jsDirName = dirname(__FILE__) . '/../lib';
$jsDir = dir($jsDirName);
$jsFiles = array();
while ($jsEntry = $jsDir->read()) {
	if (substr($jsEntry, strlen($jsEntry) - 3, 3) != '.js') continue;
	$jsFiles[] = $jsEntry;
}
sort($jsFiles);
foreach ($jsFiles as $jsEntry) {
	echo "<script type=\"text/javascript\" src=\"../lib/" . $jsEntry . "\"></script>\n";
}

?>
		<table border=0 width="100%" class="lamHeader ui-corner-all">
			<tr>
				<td align="left" height="30">
					<a class="lamHeader" href="http://www.ldap-account-manager.org/" target="new_window">&nbsp;<img src="../../graphics/logo32.png" width=24 height=24 class="align-middle" alt="LDAP Account Manager">&nbsp;&nbsp;LDAP Account Manager</a>
				</td>
				<td align="right">
					<?php echo _('Server profile') . ': ' . $conf->getName(); ?>
					&nbsp;&nbsp;
				</td>
			</tr>
		</table>
		<br>
<?php

// print error messages
for ($i = 0; $i < sizeof($errorsToDisplay); $i++) call_user_func_array('StatusMessage', $errorsToDisplay[$i]);

echo ("<form id=\"inputForm\" action=\"moduleSettings.php\" method=\"post\" autocomplete=\"off\" onSubmit=\"saveScrollPosition('inputForm')\">\n");

// hidden submit buttons which are clicked by tabs
echo "<div style=\"display: none;\">\n";
	echo "<input name=\"generalSettingsButton\" type=\"submit\" value=\" \">";
	echo "<input name=\"edittypes\" type=\"submit\" value=\" \">";
	echo "<input name=\"editmodules\" type=\"submit\" value=\" \">";
	echo "<input name=\"moduleSettings\" type=\"submit\" value=\" \">";
echo "</div>\n";
	
// tabs
echo '<div class="ui-tabs ui-widget ui-widget-content ui-corner-all">';

echo '<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">';
echo '<li id="generalSettingsButton" class="ui-state-default ui-corner-top" onmouseover="jQuery(this).addClass(\'tabs-hover\');" onmouseout="jQuery(this).removeClass(\'tabs-hover\');">';
	echo '<a href="#" onclick="document.getElementsByName(\'generalSettingsButton\')[0].click();"><img src="../../graphics/tools.png" alt=""> ';
	echo _('General settings') . '</a>';
echo '</li>';
echo '<li id="edittypes" class="ui-state-default ui-corner-top" onmouseover="jQuery(this).addClass(\'tabs-hover\');" onmouseout="jQuery(this).removeClass(\'tabs-hover\');">';
	echo '<a href="#" onclick="document.getElementsByName(\'edittypes\')[0].click();"><img src="../../graphics/gear.png" alt=""> ';
	echo _('Account types') . '</a>';
echo '</li>';
echo '<li id="editmodules" class="ui-state-default ui-corner-top" onmouseover="jQuery(this).addClass(\'tabs-hover\');" onmouseout="jQuery(this).removeClass(\'tabs-hover\');">';
	echo '<a href="#" onclick="document.getElementsByName(\'editmodules\')[0].click();"><img src="../../graphics/modules.png" alt=""> ';
	echo _('Modules') . '</a>';
echo '</li>';
echo '<li id="moduleSettings" class="ui-state-default ui-corner-top">';
	echo '<a href="#" onclick="document.getElementsByName(\'moduleSettings\')[0].click();"><img src="../../graphics/modules.png" alt=""> ';
	echo _('Module settings') . '</a>';
echo '</li>';
echo '</ul>';

?>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('#moduleSettings').addClass('ui-tabs-active');
	jQuery('#moduleSettings').addClass('ui-state-active');
	jQuery('#moduleSettings').addClass('user-bright');
});
</script>

<div class="ui-tabs-panel ui-widget-content ui-corner-bottom user-bright">
<?php


// module settings
$types = $conf->get_ActiveTypes();

// get list of scopes of modules
$scopes = array();
for ($m = 0; $m < sizeof($types); $m++) {
	$mods = $conf->get_AccountModules($types[$m]);
	for ($i = 0; $i < sizeof($mods); $i++) $scopes[$mods[$i]][] = $types[$m];
}

// get module options
$options = getConfigOptions($scopes);
// get current setting
$old_options = $conf->get_moduleSettings();


// display module boxes
$tabindex = 1;
$modules = array_keys($options);
$_SESSION['conf_types'] = array();
for ($i = 0; $i < sizeof($modules); $i++) {
	if (sizeof($options[$modules[$i]]) < 1) continue;
	echo "<fieldset class=\"ui-corner-all user-border user-bright\">\n";
	$icon = '';
	$module = moduleCache::getModule($modules[$i], 'none');
	$iconImage = $module->getIcon();
	if ($iconImage != null) {
		$icon = '<img align="middle" src="../../graphics/' . $iconImage . '" alt="' . $iconImage . '"> ';
	}
	echo "<legend>$icon" . getModuleAlias($modules[$i], "none") . "</legend>\n";
	$configTypes = parseHtml($modules[$i], $options[$modules[$i]], $old_options, false, $tabindex, 'user');
	$_SESSION['conf_types'] = array_merge($configTypes, $_SESSION['conf_types']);
	echo "</fieldset>\n";
	echo "<br>";
}

echo "<input type=\"hidden\" name=\"postAvailable\" value=\"yes\">\n";

echo "</div></div>";

$buttonContainer = new htmlTable();
$buttonContainer->addElement(new htmlSpacer(null, '10px'), true);
$saveButton = new htmlButton('saveSettings', _('Save'));
$saveButton->setIconClass('saveButton');
$buttonContainer->addElement($saveButton);
$cancelButton = new htmlButton('cancelSettings', _('Cancel'));
$cancelButton->setIconClass('cancelButton');
$buttonContainer->addElement($cancelButton, true);
$buttonContainer->addElement(new htmlSpacer(null, '10px'), true);
parseHtml(null, $buttonContainer, array(), false, $tabindex, 'user');

if ((sizeof($errorsToDisplay) == 0) && isset($_POST['scrollPositionTop']) && isset($_POST['scrollPositionLeft'])) {
	// scroll to last position
	echo '<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery(window).scrollTop(' . $_POST['scrollPositionTop'] . ');
			jQuery(window).scrollLeft('. $_POST['scrollPositionLeft'] . ');
	});
	</script>';
}

echo "</form>\n";
echo "</body>\n";
echo "</html>\n";


/**
 * Checks user input and saves the entered settings.
 *
 * @return array list of errors
 */
function checkInput() {
	if (!isset($_POST['postAvailable'])) {
		return array();
	}
	$conf = &$_SESSION['conf_config'];
	$types = $conf->get_ActiveTypes();
	
	// check module options
	// create option array to check and save
	$options = array();
	$opt_keys = array_keys($_SESSION['conf_types']);
	for ($i = 0; $i < sizeof($opt_keys); $i++) {
		$element = $opt_keys[$i];
		// text fields
		if ($_SESSION['conf_types'][$element] == "text") {
			$options[$element] = array($_POST[$element]);
		}
		// text fields
		elseif ($_SESSION['conf_types'][$element] == "text_obfuscated") {
			$options[$element] = array(obfuscateText($_POST[$element]));
		}
		// hidden fields
		elseif ($_SESSION['conf_types'][$element] == "hidden") {
			$options[$element] = array($_POST[$element]);
		}
		// checkboxes
		elseif ($_SESSION['conf_types'][$element] == "checkbox") {
			if (isset($_POST[$element]) && ($_POST[$element] == "on")) $options[$element] = array('true');
			else $options[$element] = array('false');
		}
		// dropdownbox
		elseif ($_SESSION['conf_types'][$element] == "select") {
			$options[$element] = array($_POST[$element]);
		}
		// multiselect
		elseif ($_SESSION['conf_types'][$element] == "multiselect") {
			$options[$element] = $_POST[$element];  // value is already an array
		}
		// textarea
		elseif ($_SESSION['conf_types'][$element] == "textarea") {
			$options[$element] = explode("\r\n", $_POST[$element]);
		}
	}

	// get list of scopes of modules
	$scopes = array();
	for ($m = 0; $m < sizeof($types); $m++) {
		$mods = $conf->get_AccountModules($types[$m]);
		for ($i = 0; $i < sizeof($mods); $i++) $scopes[$mods[$i]][] = $types[$m];
	}
	// check options
	$errors = checkConfigOptions($scopes, $options);
	$conf->set_moduleSettings($options);
	return $errors;
}

?>




