<?php

/**
 * menuMobile class
 * mobile class of the menu module
 *
 * @author  NAVER (developers@xpressengine.com)
 * @Adaptor DAOL Project (developer@daolcms.org)
 * @package /modules/menu
 * @version 0.1
 */
class menuMobile extends moduleObject {
	/**
	 * Result data list
	 * @var array
	 */
	var $result = array();

	/**
	 * Menu depth arrange
	 * @return void
	 */
	function straightenMenu($menu_item, $depth){
		if(!$menu_item['link']) return;
		$obj = new stdClass();
		$obj->href = $menu_item['href'];
		$obj->depth = $depth;
		$obj->text = $menu_item['text'];
		$obj->open_window = $menu_item['open_window'];
		$this->result[] = $obj;
		if(!$menu_item['list']) return;
		foreach($menu_item['list'] as $item){
			$this->straightenMenu($item, $depth + 1);
		}
	}

	/**
	 * Display menu
	 * @return void
	 */
	function dispMenuMenu(){
		$menu_srl = Context::get('menu_srl');
		$oAdminModel = getAdminModel('menu');
		$menu_info = $oAdminModel->getMenu($menu_srl);

		if(file_exists($menu_info->php_file)) include($menu_info->php_file);
		foreach($menu->list as $menu_item){
			$this->straightenMenu($menu_item, 0);
		}

		Context::set('menu', $this->result);

		$this->setTemplatePath(sprintf("%stpl/", $this->module_path));
		$this->setTemplateFile('menu.html');

	}
}
