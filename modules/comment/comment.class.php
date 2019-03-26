<?php
require_once(_DAOL_PATH_ . 'modules/comment/comment.item.php');

/**
 * comment
 * comment module's high class
 *
 * @author  NAVER (developers@xpressengine.com)
 * @package /modules/comment
 * @version 0.1
 */
class comment extends ModuleObject {
	/**
	 * Implemented if additional tasks are required when installing
	 * @return BaseObject
	 */
	function moduleInstall() {
		$oDB = &DB::getInstance();

		// register the action forward (for using on the admin mode)
		$oModuleController = &getController('module');

		$oDB->addIndex
		(
			"comments",
			"idx_module_list_order",
			array("module_srl", "list_order"),
			true
		);

		// 2007. 10. 17 add a trigger to delete comments together with posting deleted
		$oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');
		// 2007. 10. 17 add a trigger to delete all of comments together with module deleted
		$oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');
		// 2008. 02. 22 add comment setting when a new module added
		$oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');

		if(!is_dir('./files/cache/tmp')) {
			FileHandler::makeDir('./files/cache/tmp');
		}

		return new BaseObject();
	}

	/**
	 * Method to check if installation is succeeded
	 * @return bool
	 */
	function checkUpdate() {
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		// 2007. 10. 17 add a trigger to delete comments together with posting deleted
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after')) return true;
		// 2007. 10. 17 add a trigger to delete all of comments together with module deleted
		if(!$oModuleModel->getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after')) return true;
		// 2007. 10. 23 add a column for recommendation votes or notification of the comments
		if(!$oDB->isColumnExists("comments", "voted_count")) return true;
		if(!$oDB->isColumnExists("comments", "notify_message")) return true;
		// 2008. 02. 22 add comment setting when a new module added
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before')) return true;
		// 2008. 05. 14 add a column for blamed count
		if(!$oDB->isColumnExists("comments", "blamed_count")) return true;
		if(!$oDB->isColumnExists("comment_voted_log", "point")) return true;

		if(!$oDB->isIndexExists("comments", "idx_module_list_order"))
			return true;
		//2012. 02. 24 add comment published status column and index
		if(!$oDB->isColumnExists("comments", "status")) {
			return true;
		}
		if(!$oDB->isIndexExists("comments", "idx_status")) {
			return true;
		}

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'comment', 'controller', 'triggerCopyModule', 'after')) return true;

		if(!$oDB->isIndexExists("comments", "idx_parent_srl")){
			return TRUE;
		}

		return false;
	}

	/**
	 * Execute update
	 * @return BaseObject
	 */
	function moduleUpdate() {
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
		// 2007. 10. 17 add a trigger to delete comments together with posting deleted
		if(!$oModuleModel->getTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after'))
			$oModuleController->insertTrigger('document.deleteDocument', 'comment', 'controller', 'triggerDeleteDocumentComments', 'after');
		// 2007. 10. 17 add a trigger to delete all of comments together with module deleted
		if(!$oModuleModel->getTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after'))
			$oModuleController->insertTrigger('module.deleteModule', 'comment', 'controller', 'triggerDeleteModuleComments', 'after');
		// 2007. 10. 23 add a column for recommendation votes or notification of the comments
		if(!$oDB->isColumnExists("comments", "voted_count")) {
			$oDB->addColumn("comments", "voted_count", "number", "11");
			$oDB->addIndex("comments", "idx_voted_count", array("voted_count"));
		}

		if(!$oDB->isColumnExists("comments", "notify_message")) {
			$oDB->addColumn("comments", "notify_message", "char", "1");
		}
		// 2008. 02. 22 add comment setting when a new module added
		if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before'))
			$oModuleController->insertTrigger('module.dispAdditionSetup', 'comment', 'view', 'triggerDispCommentAdditionSetup', 'before');
		// 2008. 05. 14 add a column for blamed count
		if(!$oDB->isColumnExists("comments", "blamed_count")) {
			$oDB->addColumn('comments', 'blamed_count', 'number', 11, 0, true);
			$oDB->addIndex('comments', 'idx_blamed_count', array('blamed_count'));
		}
		if(!$oDB->isColumnExists("comment_voted_log", "point"))
			$oDB->addColumn('comment_voted_log', 'point', 'number', 11, 0, true);

		if(!$oDB->isIndexExists("comments", "idx_module_list_order"))
			$oDB->addIndex
			(
				"comments",
				"idx_module_list_order",
				array("module_srl", "list_order"),
				true
			);

		//2012. 02. 24 add comment published status column and index
		if(!$oDB->isColumnExists("comments", "status")) {
			$oDB->addColumn("comments", "status", "number", 1, 1, true);
		}
		if(!$oDB->isIndexExists("comments", "idx_status"))
			$oDB->addIndex
			(
				"comments",
				"idx_status",
				array("status", "comment_srl", "module_srl", "document_srl"),
				true
			);

		// 2012. 08. 29 Add a trigger to copy additional setting when the module is copied 
		if(!$oModuleModel->getTrigger('module.procModuleAdminCopyModule', 'comment', 'controller', 'triggerCopyModule', 'after')) {
			$oModuleController->insertTrigger('module.procModuleAdminCopyModule', 'comment', 'controller', 'triggerCopyModule', 'after');
		}

		if(!$oDB->isIndexExists("comments", "idx_parent_srl")){
			$oDB->addIndex('comments', 'idx_parent_srl', array('parent_srl'));
		}

		return new BaseObject(0, 'success_updated');
	}

	/**
	 * Regenerate cache file
	 * @return void
	 */
	function recompileCache() {
		if(!is_dir('./files/cache/tmp')) {
			FileHandler::makeDir('./files/cache/tmp');
		}
	}
}
