<?php
/*********************************************************************************
 *  ZiPatcher is a module loader patch creation tool for the SugarCRM application
 *  developed by SugarCRM, Inc. Copyright (C) 2004-2010 SugarCRM Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 ********************************************************************************/
chdir('..');
require_once('ZiPatcher/ZiPatcher.php');
$z = new ZiPatcher();
$do = (!empty($_REQUEST['do']))?$_REQUEST['do']:'scan';
switch($do){
	case 'build':
		$z->buildPackage();
		break;
	default:
		$z->scanForCustomizations();
		echo "<form method='POST'>";
		echo "<input type='hidden' name='do' value='build'>";
		echo "<h4>Name</h4><input name='name' size='60'>";
		echo "<h4>Author</h4><input name='author' size='60'>";
		echo "<h4>Description</h4><textarea rows=5 style='width:100%' name='description'></textarea>";
		echo "<h4>Uninstallable: <input type='checkbox' name='uninstall' value='1'></h4>";
		echo "<h4>Please select any customizations you wish to include</h4>";
		echo "<SELECT name='customfiles[]' MULTIPLE SIZE=30 style='width:100%'>";
		foreach($z->custom as $path=>$file){
			echo "<OPTION VALUE='$path'>$path";
		}
		echo "</SELECT>";
		echo <<<EOQ
		<h4>You may also manually provide the name of files to be included in the package, include full path below</h4>
		<textarea rows=10 style='width:100%' name='manualfiles'></textarea><br>
		<input type='submit' value='Create Package'>
		</form>
		<img src='include/images/poweredby_sugarcrm.png'/>
EOQ;
}


