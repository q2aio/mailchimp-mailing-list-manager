<?php

	class qa_mc_mlm_event {
		
		var $directory;
        var $urltoroot;
		
		//this runs before the module is used.
        function load_module($directory, $urltoroot)
        {
			//file system path to the plugin directory
            $this->directory=$directory;
                        
			//url path to the plugin relative to the current page request.
            $this->urltoroot=$urltoroot;
			
        }

		function process_event($event, $userid, $handle, $cookieid, $params)
		{
			//Did the user just confirm themselves and are we adding them to the mailing list when they do?
			//|| Did the user just register themselves and are we adding them to the mailing list when they do?

			//create the merge_vars for the subscriber ip.
			$merge_vars = array('OPTIN_IP' => $_SERVER['REMOTE_ADDR']);
			$merge_fields = array('FNAME' => $userid);
			$merge_fields['LNAME'] = "";
			
			//Is this a registration event?
			
			if ($event == 'u_register') {
				//Congratulations, we've got a new member.
				
				//grab the serialized settings from db and unserialize them (if they exist)
				$saveSettingsSerialized = qa_opt('mc_mlm_settings');
				
				if ($saveSettingsSerialized == ''){
					$saveSettings = array();
				}else{
					$saveSettings = unserialize($saveSettingsSerialized);
				}

				
				//Is the Mail Chimp api key set along with settings for a list?
				if (isset($saveSettings['mc_api_key']) && 
					isset($saveSettings['list'])) {
						
					//loop through and look for an enabled list that also wants to display a checkbox on the registration page
					foreach($saveSettings['list'] as $listId => $listSettings) {
						
						//Is this list enabled AND ( 
						//	(asking for a checkbox on the registration page AND it's been checked?)
						//  OR is a checkbox not needed and this is an automatic subscription)
						if ($listSettings['enabled'] && 
								( ($listSettings['regcheckbox'] && (int)qa_post_text('mailchimp_list_regsubscribe_' . $listId))
								|| $listSettings['regcheckbox'] == false) ) {
							
							//We have a subscriber. Do we need to wait for them to confirm or can we subscribe them right now?
							if ($listSettings['afterconf']) {
								//Add record to the confirm table to be subscribed after the user confirms.
								//While it's possible to not have to add the subscription with regcheckbox == false and confirmation (because we can check for this later,
								//It's easier to follow the logic if we handle all cases similarly here.
								
								//serialize the current settings to store in the db. This keeps the terms of the membership the same throughout the registration process.
								$listSettingsSerialized = serialize($listSettings);

								//the listProvider is 'mc' for MailChimp. This allows the plug-in to be expanded to handle other providers without altering the table in the future.
								qa_db_query_sub(
									'INSERT INTO ^mamlmConfirm (userId, created, listProvider, listId, listSettings) 
											VALUES (#, NOW(), \'mc\', #, #)',
										$userid, $listId, $listSettingsSerialized
									);
									
							}else{
								//No need to wait, subscribe them now.
								
								//Add the Mail Chimp API lib and create the api object only once in the loop.
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
								
								$params = array();
				        		$params["id"] = $listId;
						        $params["email_address"] = $regEmail;
						        $params["merge_vars"] = $merge_vars;
						        $params["merge_fields"] = $merge_fields;
						        $params["email_type"] = 'html';
						        $params["double_optin"] = $listSettings['confsend'];
						        $params["update_existing"] = true;
						        $params["replace_interests"] = false;
						        $params["send_welcome"] = false;
						        $params["status"] = "subscribed";
								//return $this->callServer("listSubscribe", $params
								
								$segmentsListArr = json_decode($listSettings['tags_list']);
								
								//call the MC api and request the subscribe.
								//listSubscribe($id, $email_address, $merge_vars=NULL, $email_type='html', $double_optin=true, $update_existing=false, $replace_interests=true, $send_welcome=false)
								$retval = $api->post('lists/' . $listId. '/members', $params);
								
								if ($retval['status'] != "subscribed") {
									error_log("Unable to load listSubscribe()!");
									error_log("Code=".$retval['status']);
									error_log("Msg=".$retval['title']);
									error_log("Detail=".$retval['detail']);
									error_log("Type=".$retval['type']);
									die();
								} else {
									//Success
									//echo "Subscribed - look for the confirmation email!\n";
								}
								
								// adding a member to the tag
								foreach($segmentsListArr as $segmentID => $segmentName){
									$retval = $api->post('lists/' . $listId. '/segments/' . $segmentID . '/members', $params);
								}

							}
							
							
						}
						
					}	
					
					
					
				}

				
			}
			
			//if ($event == 'tag_favorite'){
				// trigger when the user mark a tag as favourite
			//}
			
			if ($event == 'u_confirmed') {
				//The new member confirmed.
				
				//grab the serialized settings from db and unserialize them (if they exist)
				$saveSettingsSerialized = qa_opt('mc_mlm_settings');

				if ($saveSettingsSerialized == ''){
					$saveSettings = array();
				}else{
					$saveSettings = unserialize($saveSettingsSerialized);
				}

				
				//Is the Mail Chimp api key set along with settings for a list?
				if (isset($saveSettings['mc_api_key']) && 
					isset($saveSettings['list'])) {

					//look for any records requiring subscription.
					$sqlQuery = 'Select userId, created, ListProvider, listId, listSettings
						FROM ^mamlmConfirm
						WHERE userId = #';

					//execute the SQL
					$result = qa_db_query_sub( $sqlQuery, $userid );

					//retrieve the data
					$confirmRecords = qa_db_read_all_assoc($result);

					//loop through the records and subscribe the member to the list.
					foreach ($confirmRecords as $confirmRecord) {
						//Add the Mail Chimp API lib and create the api object only once in the loop.
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

						//grab the specific settings for this request.
						$listSettingsSerialized = $confirmRecord['listSettings'];
						$listSettings = unserialize($listSettingsSerialized);

						$params = array();
		        		$params["id"] = $confirmRecord['listId'];
				        $params["email_address"] = $regEmail;
				        $params["merge_vars"] = $merge_vars;
				        $params["merge_fields"] = $merge_fields;
				        $params["email_type"] = 'html';
				        $params["double_optin"] = $listSettings['confsend'];
				        $params["update_existing"] = true;
				        $params["replace_interests"] = false;
				        $params["send_welcome"] = false;
				        
				        //$retval = $api->listSubscribe( $confirmRecord['listId'] , $regEmail, $merge_vars, 'html', $listSettings['confsend'], true, false, false);
						
						$retval = $api->post('lists/' . $listId. '/members', $params);
						
						if ($retval['status'] != "subscribed") {
							error_log("Unable to load listSubscribe()!");
							error_log("Code=".$retval['status']);
							error_log("Msg=".$retval['title']);
							error_log("Detail=".$retval['detail']);
							error_log("Type=".$retval['detail']);
							die();
						} else {
							//Success
							//echo "Subscribed - look for the confirmation email!\n";
						}			
						
						//remove the record from the database.
						$sqlQuery = 'Delete FROM ^mamlmConfirm
							WHERE userId = #
								AND listId = #';

						//execute the SQL
						$result = qa_db_query_sub( $sqlQuery, $userid, $confirmRecord['listId']);

					
					}

				}
				
			}
			
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/