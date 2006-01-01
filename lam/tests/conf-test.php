<?php
/*
$Id$

  This code is part of LDAP Account Manager (http://www.sourceforge.net/projects/lam)
  Copyright (C) 2003  Roland Gruber

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
* This test reads all preferences from lam.conf. Then it writes new values and verifies
* if they were written. At last the old values are restored.
*
* @author Roland Gruber
* @package tests
*/

/** access to configuration functions */
include ("../lib/config.inc");

$conf = new Config('test');
echo "<html><head><title></title><link rel=\"stylesheet\" type=\"text/css\" href=\"../style/layout.css\"></head><body>";
echo ("<br><br><big><b> Starting Test...</b></big><br><br>");
// now all preferences are loaded
echo ("Loading preferences...");
$ServerURL = $conf->get_ServerURL();
$cachetimeout = $conf->get_cacheTimeout();
$Passwd = $conf->get_Passwd();
$Adminstring = $conf->get_Adminstring();
$Suff_users = $conf->get_Suffix('user');
$Suff_groups = $conf->get_Suffix('group');
$Suff_hosts = $conf->get_Suffix('host');
$Suff_domains = $conf->get_Suffix('smbDomain');
$userlistAttributes = $conf->get_listAttributes('user');
$grouplistAttributes = $conf->get_listAttributes('group');
$hostlistAttributes = $conf->get_listAttributes('host');
$maxlistentries = $conf->get_maxlistentries();
$defaultlanguage = $conf->get_defaultlanguage();
$scriptpath = $conf->get_scriptPath();
$scriptServer = $conf->get_scriptServer();
$moduleSettings = $conf->get_moduleSettings();
echo ("done<br>");
// next we modify them and save lam.conf
echo ("Changing preferences...");
$conf->set_ServerURL("ldap://123.345.678.123:777");
$conf->set_cacheTimeout("33");
$conf->set_Passwd("123456abcde");
$conf->set_Adminstring("uid=test,o=test,dc=org;uid=root,o=test2,c=de");
$conf->set_Suffix('user', "ou=test,o=test,c=de");
$conf->set_Suffix('group', "ou=testgrp,o=test,c=de");
$conf->set_Suffix('host', "ou=testhst,o=test,c=de");
$conf->set_Suffix('smbDomain', "ou=testdom,o=test,c=de");
$conf->set_listAttributes("#uid;#cn", 'user');
$conf->set_listAttributes("#gidNumber;#cn;#memberUID", 'group');
$conf->set_listAttributes("#cn;#uid;#description", 'host');
$conf->set_maxlistentries("54");
$conf->set_defaultlanguage("de_AT:iso639_de:Deutsch (Oesterreich)");
$conf->set_scriptPath("/var/www/lam/lib/script");
$conf->set_scriptServer("127.0.0.1");
$conf->set_moduleSettings(array("test1" => array(11), "test2" => array("abc"), 'test3' => array(3)));
$conf->save();
echo ("done<br>");
// at last all preferences are read from lam.conf and compared
echo ("Loading and comparing...");
$conf2 = new Config('test');
if ($conf2->get_ServerURL() != "ldap://123.345.678.123:777") echo ("<br><font color=\"#FF0000\">Saving ServerURL failed!</font><br>");
if ($conf2->get_cacheTimeout() != "33") echo ("<br><font color=\"#FF0000\">Saving Cache timeout failed!</font><br>");
if ($conf2->get_Passwd() != "123456abcde") echo ("<br><font color=\"#FF0000\">Saving password failed!</font><br>");
if ($conf2->get_Adminstring() != "uid=test,o=test,dc=org;uid=root,o=test2,c=de") echo ("<br><font color=\"#FF0000\">Saving admin string failed!</font><br>");
if ($conf2->get_Suffix('user') != "ou=test,o=test,c=de") echo ("<br><font color=\"#FF0000\">Saving user suffix failed!</font><br>");
if ($conf2->get_Suffix('group') != "ou=testgrp,o=test,c=de") echo ("<br><font color=\"#FF0000\">Saving group suffix failed!</font><br>");
if ($conf2->get_Suffix('host') != "ou=testhst,o=test,c=de") echo ("<br><font color=\"#FF0000\">Saving host suffix failed!</font><br>");
if ($conf2->get_Suffix('smbDomain') != "ou=testdom,o=test,c=de") echo ("<br><font color=\"#FF0000\">Saving domain suffix failed!</font><br>");
if ($conf2->get_listAttributes('user') != "#uid;#cn") echo ("<br><font color=\"#FF0000\">Saving userlistAttributes failed!</font><br>");
if ($conf2->get_listAttributes('group') != "#gidNumber;#cn;#memberUID") echo ("<br><font color=\"#FF0000\">Saving grouplistAttributes failed!</font><br>");
if ($conf2->get_listAttributes('host') != "#cn;#uid;#description") echo ("<br><font color=\"#FF0000\">Saving hostlistAttributes failed!</font><br>");
if ($conf2->get_maxlistentries() != "54") echo ("<br><font color=\"#FF0000\">Saving maxlistentries failed!</font><br>");
if ($conf2->get_defaultlanguage() != "de_AT:iso639_de:Deutsch (Oesterreich)") echo ("<br><font color=\"#FF0000\">Saving default language failed!</font><br>");
if ($conf2->get_scriptPath() != "/var/www/lam/lib/script") echo ("<br><font color=\"#FF0000\">Saving script path failed!</font><br>");
if ($conf2->get_scriptServer() != "127.0.0.1") echo ("<br><font color=\"#FF0000\">Saving script server failed!</font><br>");
$msettings = $conf2->get_moduleSettings();
if (($msettings['test1'][0] != 11) || ($msettings['test2'][0] != 'abc') || ($msettings['test3'][0] != '3')) echo ("<br><font color=\"#FF0000\">Saving module settings failed!</font><br>");
echo ("done<br>");
// restore old values
echo ("Restoring old preferences...");
$conf2->set_ServerURL($ServerURL);
$conf2->set_cacheTimeout($cachetimeout);
$conf2->set_Passwd($Passwd);
$conf2->set_Adminstring($Adminstring);
$conf2->set_Suffix('user', $Suff_users);
$conf2->set_Suffix('group', $Suff_groups);
$conf2->set_Suffix('host', $Suff_hosts);
$conf2->set_Suffix('smbDomain', $Suff_domains);
$conf2->set_listAttributes($userlistAttributes, 'user');
$conf2->set_listAttributes($grouplistAttributes, 'group');
$conf2->set_listAttributes($hostlistAttributes, 'host');
$conf2->set_maxlistentries($maxlistentries);
$conf2->set_defaultLanguage($defaultlanguage);
$conf2->set_scriptPath($scriptpath);
$conf2->set_scriptServer($scriptServer);
$conf2->set_moduleSettings($moduleSettings);
$conf2->save();
echo ("done<br>");
// finished
echo ("<br><b><font color=\"#00C000\">Test is complete.</font></b>");

?>
