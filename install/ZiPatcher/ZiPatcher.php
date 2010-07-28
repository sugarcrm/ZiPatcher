<?php
/*********************************************************************************
 * ZiPatcher is a module loader patch creation tool for the SugarCRM application
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
define('sugarEntry', true);
class ZiPatcher{
	protected $skipScan = array('./cache', './ZiPatcher', './files.md5');
	protected $skipCopy = array('ZiPatcher/packages/', '.DS_Store');
	protected $filesToInstall = array();
	protected $beans = array();
	protected $hooks = array();
	
	/**
	* constructor loads files.md5 for finding customizations and moduleList  
	**/
	function __construct(){
		if(file_exists('files.md5')){
			include('files.md5');
			$this->md5 = $md5_string;
		}
		$this->date =date('Y_m_d_His');
		include('include/modules.php');
		$this->beanFilesR = array_flip($beanFiles);
		$this->beanListR = array_flip($beanList);
		$this->moduleListR = array_flip($moduleList);
	}
	
	/**
	* checks if a file contains a module inside it if 
	* it does it will add it to the installDefs as a module to install
	**/
	function isBeanFile($file){
		if(!empty($this->beanFilesR[$file])){
			$bean = array();
			$bean['path']  = $file;
			$bean['class'] = $this->beanFilesR[$file];
			//if there isn't a class it's not a real module to install
			if(empty($this->beanListR[$bean['class']]))return;
			$bean['module'] = $this->beanListR[$bean['class']];
			if(!empty($this->moduleListR[$bean['module']])){
				$bean['tab'] = true;	
			}
			$this->beans[] = $bean;
				
		}	
	}
	
	/**
	*	checks if a file is a logic hook and if it is it adds it to the 
	*	installdefs
	**/
	function isLogicHook($file){
		$filename = basename($file);
		//APPLICATION LOGIC HOOKS DO NOT HAVE AN UPGRADE SAFE MANNER FOR ADDING THEM SO COPY THE FILE
		if($filename === 'logic_hooks.php' && substr_count($file, '/modules/' ) > 0){
			preg_match("'modules/([^\/]+)/logic_hooks.php'", $file, $matches);
			$module = $matches[1];
			$hook_array = array();
			include($file);
			foreach($hook_array as $action=>$hooks){
				foreach($hooks as $order=>$hook){
						$new_hook = array();
						$new_hook['module'] = $module;
						$new_hook['hook'] = $action;
						$new_hook['order']= $order;
						$new_hook['description'] = !empty($hook[1])?$hook[1]:'';
						$new_hook['file'] = !empty($hook[2])?$hook[2]:'';
						$new_hook['class'] = !empty($hook[3])?$hook[3]:'';
						$new_hook['function'] = !empty($hook[4])?$hook[4]:'';
						$this->hooks[] = $new_hook;
				}
				
			}
			return true;	
		}
		return false;	
	}
	
	/**
	*	compares a file to what is listed in files.md5 if a file is not listed it is assumed to
	*	be custom
	**/
	function isCustom($file){
		if(!empty($this->md5[$file])){
			$contents = file_get_contents($file);
			$md5 =  md5($contents);
			return $md5 != $this->md5[$file];
		}
		return true;
	}
	
	/**
	*	recursively walks down a path checking the files to find customization
	*
	**/
	function scanForCustomizations($path = '.'){
		$d = dir($path);
		while($e = $d->read()){
			if(substr($e, 0, 1) == '.')continue;
			$full = $path . '/'. $e;
			if(in_array($full, $this->skipScan))continue;
			if(is_file($full)){
				if($this->isCustom($full)){
					$this->custom[$full] = $e;	
				}
			}else{
				
				$this->scanForCustomizations($full);	
			}	
			
		}
		
			
	}
	
	/**
	*	creates the directory where the package will be saved to 
	*
	**/
	function createPackageDir(){
		$this->packageDir = 'ZiPatcher/packages/' . $this->props['name'] . $this->date;
		$this->installDir = $this->packageDir .'/install';
		mkdir($this->packageDir  , 0777, true);
		if(file_exists('LICENSE.txt'))copy('LICENSE.txt', $this->packageDir . '/LICENSE.txt');
	}
	
	/**
	*	copies a file from the instance to the install directoy in the package
	*	if the file is a logic hook it does not copy it
	*	if the file is a Bean it adds the installation directives to the installDefs 
	*
	**/
	function copy($path){
		if(is_file($path)){
			$path = str_replace('./', '', $path);
			//skip anything we shouldn't copy
			foreach($this->skipCopy as $substr){
				if(strpos($path, $substr) !== false)return;	
			}
			$full_path = $this->installDir.'/'. dirname($path);
			if(!file_exists($full_path)){
				mkdir($full_path,0777, true);	
			}
			
			
			if(!$this->isLogicHook($path)){
				$this->filesToInstall[] =  $path;
				$this->isBeanFile($path);
				copy($path, $this->installDir.'/' . $path);
			}
		}else{
			$d = dir($path);
			while($e = $d->read()){
				if($e == '.' || $e == '..' || $e =='.svn')continue;
				$this->copy($path .'/'. $e); 	
			}
		}	
	}
	
	/**
	*	allows for users to manually specify files they wish to copy to a package
	*
	**/
	function copyManualFiles(){
		$files = explode("\n",$_POST['manualfiles']);
		foreach($files as $filepath){
			if (strlen(trim($filepath)) > 0) { 
		    	$filepath = trim($filepath);
		    	$filepath  = str_replace('\\', '/', $filepath);
		    	$this->copy($filepath);
	  		}
  		}	
	}
	
	/**
	*	files that the user selected from the custom list that they wish to add to a package
	*
	**/
  	
  	function copyCustomFiles(){
  		if(empty($_POST['customfiles']))return;
		foreach($_POST['customfiles'] as $filepath){
			if (strlen(trim($filepath)) > 0) { 
		    	$filepath = trim($filepath);
		    	$filepath  = str_replace('\\', '/', $filepath);
		    	$this->copy($filepath);
	  		}
  		}	
  	}
  	
  	/**
  	*	initiates the build directives for a package
  	*
  	**/
  	
  	function buildPackage(){
  		$this->props['name'] = (!empty($_REQUEST['name']))?$_REQUEST['name']:'package';
  		$this->props['description'] = (!empty($_REQUEST['description']))?$_REQUEST['description']:'';
  		$this->props['uninstall'] = (!empty($_REQUEST['uninstall']))?'true':'false';
  		$this->props['author'] = (!empty($_REQUEST['author']))?'':'SugarCRM Inc.';
  		$this->createPackageDir();
  		$this->copyManualFiles();
  		$this->copyCustomFiles();	
  		$this->writeDefs();
  		$this->zip();
  		$this->display();
  	}
  	
  	
  	/**
  	*	writes the manifest and install defs for the package
  	*
  	**/
  	function writeDefs(){
  		 $fp = fopen($this->packageDir . '/manifest.php', 'w');
	 	 fwrite($fp, "<?php\n");
	 	 fwrite($fp, $this->createManifest());
	 	 fwrite($fp, "\n");
		 fwrite($fp, $this->createInstallDefs());	 	
	 	 fwrite($fp, "\n");
		 fclose($fp);	
  		
  	}
  	
  	/**
  	*	creates the install defs for a package
  	*	currently it handles 
  	* 		-file copies
  	*		-installing modules
  	*		-installing logic hooks
  	**/
  	function createInstallDefs(){
	
	$installdefs['id'] = 'sugar' . $this->date;
	foreach($this->filesToInstall as $filepath){
		if (strlen(trim($filepath)) > 0) { 
	    	$filepath = trim($filepath);
	    	$filepath  = str_replace('\\', '/', $filepath);
	    	
	    	$installdefs['copy'][] = array('from'=>'<basepath>/install/'. $filepath, 'to'=>$filepath); 
  		}
  	}	
  	$installdefs['beans'] = $this->beans;
  	$installdefs['logic_hooks'] = $this->hooks;
  	
  	return '$installdefs =' . var_export($installdefs,true) .';';
}

/**
*	creates the manifest for a package
*
**/
function createManifest(){
	$date = date('Y-m-d H:i:s');
	$time = time();
	$manifest['acceptable_sugar_versions']=array();
	$manifest['author']=$this->props['author'];
	$manifest['description']=$this->props['description'];
	$manifest['icon']="";
	$manifest['is_uninstallable']=$this->props['uninstall'];
	$manifest['name']=$this->props['name'];
	$manifest['published_date']="$date";
	$manifest['type']="module";
	$manifest['version']="$time";
	return '$manifest =' . var_export($manifest,true) . ';';
}

/**
*	creates the zip of a package
*		-in the future remove zip_utils dependency
*
**/
function zip(){
	require_once('include/utils/zip_utils.php');
	chdir($this->packageDir);
	if(!file_exists('../zips/'))mkdir('../zips/', 0777, true);
  	zip_dir('.', '../zips/package'. $this->date. '.zip');			
}

/**
*	Displays 
*
**/
function display(){
	$hooks = count($this->hooks);
	$beans = count($this->beans);
	$files = count($this->filesToInstall);
	echo <<<EOQ
	<b>Files Added:</b> $files <br>
	<b>Modules Found:</b> $beans <br>
	<b>Logic Hooks Found:</b> $hooks <br>
	<br>
	<a href='packages/zips/package{$this->date}.zip'>Download Now</a>
	
EOQ;
	
}

}

