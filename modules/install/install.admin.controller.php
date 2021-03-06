<?php

/**
 * @class   installAdminController
 * @author  NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 * @brief   admin controller class of the install module
 **/
class installAdminController extends install {


	/**
	 * @brief Initialization
	 **/
	function init() {
	}

	/**
	 * @brief Install the module
	 **/
	function procInstallAdminInstall() {
		$module_name = Context::get('module_name');
		if(!$module_name) return new object(-1, 'invalid_request');

		$oInstallController = &getController('install');
		$oInstallController->installModule($module_name, './modules/' . $module_name);

		$this->setMessage('success_installed');
	}

	/**
	 * @brief Upate the module
	 **/
	function procInstallAdminUpdate() {
		set_time_limit(0);
		$module_name = Context::get('module_name');
		if(!$module_name) return new object(-1, 'invalid_request');

		$oModule = &getModule($module_name, 'class');
		if($oModule) $output = $oModule->moduleUpdate();
		else $output = new BaseObject(-1, 'invalid_request');

		return $output;
	}

	/**
	 * @brief Change settings
	 **/
	function procInstallAdminSaveTimeZone() {
		$db_info = Context::getDBInfo();

		$admin_ip_list = Context::get('admin_ip_list');

		if($admin_ip_list) {
			$admin_ip_list = preg_replace("/[\r|\n|\r\n]+/", ",", $admin_ip_list);
			$admin_ip_list = preg_replace("/\s+/", "", $admin_ip_list);
			if(preg_match('/(<\?|<\?php|\?>)/xsm', $admin_ip_list)) {
				$admin_ip_list = '';
			}
			$admin_ip_list .= ',127.0.0.1,' . $_SERVER['REMOTE_ADDR'];
			$admin_ip_list = explode(',', trim($admin_ip_list, ','));
			$admin_ip_list = array_unique($admin_ip_list);
			if(!IpFilter::validate($admin_ip_list)) {
				return new BaseObject(-1, 'msg_invalid_ip');
			}
		}

		$default_url = Context::get('default_url');
		if($default_url && strncasecmp('http://', $default_url, 7) !== 0 && strncasecmp('https://', $default_url, 8) !== 0) $default_url = 'http://' . $default_url;
		if($default_url && substr($default_url, -1) !== '/') $default_url = $default_url . '/';

		/* convert NON Alphabet URL to punycode URL - Alphabet URL will not be changed */
		require_once(_DAOL_PATH_ . 'libs/idna_convert/idna_convert.class.php');
		$IDN = new idna_convert(array('idn_version' => 2008));
		$default_url = $IDN->encode($default_url);

		$time_zone = Context::get('time_zone');
		$db_info->time_zone = $time_zone;

		$use_mobile_view = Context::get('use_mobile_view');
		if($use_mobile_view != 'Y') $use_mobile_view = 'N';

		$use_ssl = Context::get('use_ssl');
		if(!$use_ssl) $use_ssl = 'none';

		$use_nofollow = Context::get('use_nofollow');
		if($use_nofollow != 'Y') $use_nofollow = 'N';

		$http_port = Context::get('http_port');
		$https_port = Context::get('https_port');

		$use_rewrite = Context::get('use_rewrite');
		if($use_rewrite != 'Y') $use_rewrite = 'N';

		$use_sso = Context::get('use_sso');
		if($use_sso != 'Y') $use_sso = 'N';

		$use_db_session = Context::get('use_db_session');
		if($use_db_session != 'Y') $use_db_session = 'N';

		$qmail_compatibility = Context::get('qmail_compatibility');
		if($qmail_compatibility != 'Y') $qmail_compatibility = 'N';

		$use_html5 = Context::get('use_html5');
		if(!$use_html5) $use_html5 = 'N';

		$db_info->default_url = $default_url;
		$db_info->qmail_compatibility = $qmail_compatibility;
		$db_info->use_db_session = $use_db_session;
		$db_info->use_rewrite = $use_rewrite;
		$db_info->use_sso = $use_sso;
		$db_info->use_ssl = $use_ssl;
		$db_info->use_nofollow = $use_nofollow;
		$db_info->use_html5 = $use_html5;
		$db_info->use_mobile_view = $use_mobile_view;
		$db_info->admin_ip_list = $admin_ip_list;

		if($http_port) $db_info->http_port = (int)$http_port;
		else if($db_info->http_port) unset($db_info->http_port);

		if($https_port) $db_info->https_port = (int)$https_port;
		else if($db_info->https_port) unset($db_info->https_port);

		unset($db_info->lang_type);
		$site_args = new stdClass();
		$site_args->site_srl = 0;
		$site_args->index_module_srl = Context::get('index_module_srl');
		$site_args->default_language = Context::get('change_lang_type');
		$site_args->domain = $db_info->default_url;
		$oModuleController = getController('module');
		$oModuleController->updateSite($site_args);

		$oInstallController = getController('install');
		if(!$oInstallController->makeConfigFile()) {
			return new BaseObject(-1, 'msg_invalid_request');
		} else {
			Context::setDBInfo($db_info);
			if($default_url) {
				$site_args = new stdClass;
				$site_args->site_srl = 0;
				$site_args->domain = $default_url;
				$oModuleController = getController('module');
				$oModuleController->updateSite($site_args);
			}
			$this->setRedirectUrl(Context::get('error_return_url'));
		}
	}

	function procInstallAdminRemoveFTPInfo() {
		$ftp_config_file = Context::getFTPConfigFile();
		if(file_exists($ftp_config_file)) unlink($ftp_config_file);
		if($_SESSION['ftp_password']) unset($_SESSION['ftp_password']);
		$this->setMessage('success_deleted');
	}

	function procInstallAdminSaveCDNInfo() {
		$cdn_info = Context::getCDNInfo();
		$cdn_info->cdn_use = Context::get('cdn_use');
		$cdn_info->cdn_type = Context::get('cdn_type');

		$buff = '<?php if(!defined("__XE__")) exit();' . "\n";
		foreach($cdn_info as $key => $val) {
			if(!$val) continue;
			if(preg_match('/(<\?|<\?php|\?>|fputs|fopen|fwrite|fgets|fread|\/\*|\*\/|chr\()/xsm', preg_replace('/\s/', '', $val))) {
				continue;
			}
			$buff .= sprintf("\$cdn_info->%s = '%s';\n", $key, str_replace("'", "\\'", $val));
		}
		$buff .= "?>";
		$config_file = Context::getcdnConfigFile();
		FileHandler::WriteFile($config_file, $buff);
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	function procInstallAdminSaveFTPInfo() {
		$ftp_info = Context::getFTPInfo();
		$ftp_info->ftp_user = Context::get('ftp_user');
		$ftp_info->ftp_port = Context::get('ftp_port');
		$ftp_info->ftp_host = Context::get('ftp_host');
		$ftp_info->ftp_pasv = Context::get('ftp_pasv');
		if(!$ftp_info->ftp_pasv) $ftp_info->ftp_pasv = "N";
		$ftp_info->sftp = Context::get('sftp');

		$ftp_root_path = Context::get('ftp_root_path');
		if(substr($ftp_root_path, strlen($ftp_root_path) - 1) == "/") {
			$ftp_info->ftp_root_path = $ftp_root_path;
		} else {
			$ftp_info->ftp_root_path = $ftp_root_path . '/';
		}

		if(ini_get('safe_mode')) {
			$ftp_info->ftp_password = Context::get('ftp_password');
		}

		$buff = '<?php if(!defined("__XE__")) exit();' . "\n";
		foreach($ftp_info as $key => $val) {
			if(!$val) continue;
			if(preg_match('/(<\?|<\?php|\?>|fputs|fopen|fwrite|fgets|fread|file_get_contents|file_put_contents|exec|proc_open|popen|passthru|show_source|phpinfo|system|\/\*|\*\/|chr\()/xsm', preg_replace('/\s/', '', $val))){
				continue;
			}
			$buff .= sprintf("\$ftp_info->%s = '%s';\n", $key, str_replace("'", "\\'", $val));
		}
		$buff .= "?>";
		$config_file = Context::getFTPConfigFile();
		FileHandler::WriteFile($config_file, $buff);
		if($_SESSION['ftp_password']) unset($_SESSION['ftp_password']);
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	function procInstallAdminSaveSMTPInfo() {
		$smtp_info = Context::getSMTPInfo();
		$smtp_info->smtp_use = Context::get('smtp_use');
		$smtp_info->smtp_host = Context::get('smtp_host');
		$smtp_info->smtp_port = Context::get('smtp_port');
		$smtp_info->smtp_type = Context::get('smtp_type');
		$smtp_info->smtp_user = Context::get('smtp_user');
		$smtp_info->smtp_password = Context::get('smtp_password');

		$buff = '<?php if(!defined("__XE__")) exit();' . "\n";
		foreach($smtp_info as $key => $val) {
			if(!$val) continue;
			if(preg_match('/(<\?|<\?php|\?>|fputs|fopen|fwrite|fgets|fread|\/\*|\*\/|chr\()/xsm', preg_replace('/\s/', '', $val))) {
				continue;
			}
			$buff .= sprintf("\$smtp_info->%s = '%s';\n", $key, str_replace("'", "\\'", $val));
		}
		$buff .= "?>";
		$config_file = Context::getSMTPConfigFile();
		FileHandler::WriteFile($config_file, $buff);
		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	function procInstallAdminConfig() {
		$this->procInstallAdminSaveTimeZone();

		//언어 선택
		$selected_lang = Context::get('selected_lang');
		$this->saveLangSelected($selected_lang);

		//모듈 설정 저장(썸네일, 풋터스크립트)
		$config = new stdClass();
		$config->thumbnail_type = Context::get('thumbnail_type');
		$config->htmlFooter = Context::get('htmlFooter');
		$config->siteTitle = Context::get('site_title');
		$this->setModulesConfig($config);

		//파비콘
		$favicon = Context::get('favicon');
		$this->saveIcon($favicon, 'favicon.ico');

		//모바일아이콘
		$mobicon = Context::get('mobicon');
		$this->saveIcon($mobicon, 'mobicon.png');

		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	//from procInstallAdminSaveTimeZone
	/**
	 * @brief Supported languages (was procInstallAdminSaveLangSelected)
	 **/
	function saveLangSelected($selected_lang) {
		$langs = $selected_lang;

		$lang_supported = Context::loadLangSupported();
		$buff = null;
		for($i = 0; $i < count($langs); $i++) {
			$buff .= sprintf("%s,%s\n", $langs[$i], $lang_supported[$langs[$i]]);

		}
		FileHandler::writeFile(_DAOL_PATH_ . 'files/config/lang_selected.info', trim($buff));
		//$this->setMessage('success_updated');
	}

	/**
	 * Change the way the thumbnails are show.
	 * @param $config
	 * @return void
	 */
	function setModulesConfig($config){
		$documentConfig = getModel('document')->getDocumentConfig();

		if(!$config->thumbnail_type || $config->thumbnail_type != 'ratio'){
			$documentConfig->thumbnail_type = 'crop';
		}
		else{
			$documentConfig->thumbnail_type = 'ratio';
		}

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('document',$documentConfig);

		$args = new stdClass;
		$args->htmlFooter = $config->htmlFooter;
		$args->siteTitle = $config->siteTitle;
		$oModuleController->updateModuleConfig('module',$args);
	}

	function saveIcon($icon, $iconname) {
		$mobicon_size = array('57', '114');
		$target_file = $icon['tmp_name'];
		$type = $icon['type'];
		$target_filename = _DAOL_PATH_ . 'files/attach/xeicon/' . $iconname;

		list($width, $height, $type_no, $attrs) = @getimagesize($target_file);
		if($iconname == 'favicon.ico' && preg_match('/^.*(x-icon|\.icon)$/i', $type)) {
			$fitHeight = $fitWidth = '16';
		} else if($iconname == 'mobicon.png' && preg_match('/^.*(png).*$/', $type) && in_array($height, $mobicon_size) && in_array($width, $mobicon_size)) {
			$fitHeight = $fitWidth = $height;
		} else {
			return false;
		}
		//FileHandler::createImageFile($target_file, $target_filename, $fitHeight, $fitWidth, $ext);
		FileHandler::copyFile($target_file, $target_filename);
	}
}
