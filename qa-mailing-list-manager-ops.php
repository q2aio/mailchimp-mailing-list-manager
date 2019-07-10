<?php

function mc_add_email($userid, $handle, $confsend = false){
    
    $merge_vars = array('OPTIN_IP' => $_SERVER['REMOTE_ADDR']);
	$merge_fields = array('FNAME' => $handle);
	$merge_fields['LNAME'] = $handle;
    
    //grab the serialized settings from db and unserialize them (if they exist)
	$saveSettingsSerialized = qa_opt('mc_mlm_settings');

	if ($saveSettingsSerialized == ''){
		$saveSettings = array();
	}else{
		$saveSettings = unserialize($saveSettingsSerialized);
	}
	
	if (isset($api) == false){
		//Add the Mail Chimp API lib
		require_once("vendor/autoload.php");
		
		//Create the object with the stored API key
		$api = new \DrewM\MailChimp\MailChimp($saveSettings['mc_api_key']);

	}
	
	//pull the members email address only once in the loop.
	if (isset($regEmail) == false){
		require_once QA_INCLUDE_DIR . 'qa-db-users.php';
		require_once QA_INCLUDE_DIR . 'qa-db-selects.php';

		$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($userid, true));
		$regEmail = $userinfo['email'];
	}
	
	foreach($saveSettings['list'] as $listId => $listSettings) {
	   $params = array();
    	$params["id"] = $listId;
        $params["email_address"] = $regEmail;
        $params["merge_vars"] = $merge_vars;
        $params["merge_fields"] = $merge_fields;
        $params["email_type"] = 'html';
        $params["double_optin"] = $confsend;
        $params["update_existing"] = false;
        $params["replace_interests"] = false;
        $params["send_welcome"] = false;
        $params["status"] = "subscribed";
        
        $retval = $api->post('lists/' . $listId. '/members', $params);
        
        if ($retval['status'] != "subscribed") {
    		error_log("Unable to load listSubscribe()!");
    		error_log("Code=".$retval['status']);
    		error_log("Msg=".$retval['title']);
    		error_log("Detail=".$retval['detail']);
    		error_log("Type=".$retval['type']);
    		if($retval['status']!= 400)
    	    	die();
    	} else {
    		//Success
    		//echo "Subscribed - look for the confirmation email!\n";
    	} 
    	
    	$segmentsListArr = json_decode($listSettings['tags_list']);
    	
    	// adding a member to the tag
		foreach($segmentsListArr as $segmentID => $segmentName){
			$retval = $api->post('lists/' . $listId. '/segments/' . $segmentID . '/members', $params);
		}
	}
	
	return;
    
}