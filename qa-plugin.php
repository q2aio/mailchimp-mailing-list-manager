<?php

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../../');
		exit;
	}

	define('MA_MLM_BASE_PATH', dirname(__FILE__));

	//register the event module that deletes the cache files if a change happens.
	//qa_register_plugin_module('event', 'qa-ma-mailing-list-manager-event.php', 'qa_ma_mlm_event', 'Mailing List Manager Event Handler');

	//admin page
	qa_register_plugin_module('module', 'qa-ma-mailing-list-manager-admin.php', 'qa_ma_mlm_admin', 'Mailing List Manager Admin');

	//event module that catches new/confirmed member events.
	qa_register_plugin_module('event', 'qa-ma-mailing-list-manager-event.php', 'qa_ma_mlm_event', 'Mailing List Manager Event Handler');

	//Layer to add checkboxes to registration form
	qa_register_plugin_layer('qa-ma-mailing-list-manager-layer.php', 'Mailing List Manager Layer');
	
	//Layer to add checkboxes to registration form
	qa_register_plugin_module('page', 'qa-ma-mailing-list-manager-page.php', 'qa_ma_mlm_page', 'Page to export user email for import into 3rd party mailing list program.');
	
	//Layer to add checkboxes to registration form
	qa_register_plugin_module('page', 'qa-ma-mailing-list-manager-sync.php', 'qa_ma_mlm_sync', 'Page to sync users with MailChimp.');

/*
	Omit PHP closing tag to help avoid accidental output
*/