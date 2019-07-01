<?php

class qa_html_theme_layer extends qa_html_theme_base
	{
		
		function html() {
			
			//remove the textbox asking for content on the edit question/topic page.
			if ($this->template == 'register'){
				
				//grab the serialized settings from db and unserialize them (if they exist)
				$saveSettingsSerialized = qa_opt('mc_mlm_settings');
				
				if ($saveSettingsSerialized == ''){
					$saveSettings = array();
				}else{
					$saveSettings = unserialize($saveSettingsSerialized);
				}


				if (isset($saveSettings['mc_api_key']) && 
						isset($saveSettings['list'])) {
					//loop through and look for an enabled list that also wants to display a checkbox on the registration page
					foreach($saveSettings['list'] as $listId => $listSettings) {
						
						if ($listSettings['enabled'] && 
								$listSettings['regcheckbox']) {
							
							//try to preserve the submited value, if the form was submitted.
							if (count($_POST)) {
								$checked = qa_post_text('mailchimp_list_regsubscribe_' . $listId);
							}else{
								$checked = isset($listSettings['regprecheck']) ? $listSettings['regprecheck'] : 0;
							}
							
							$this->content['form']['fields']['mailchimp_list_regsubscribe_' . $listId] = array(
								'label' => isset($listSettings['regtext']) ? $listSettings['regtext'] : 'Subscribe to the mailing list.',
								'type' => 'checkbox',
								'value' => $checked,
								'error' => '',
								'tags' => 'NAME="mailchimp_list_regsubscribe_' . $listId . '"',
							);

							
						}
						
					}
				}
				
				
			}
			qa_html_theme_base::html(); // call back through to the default function
			
		}
		

	}
	
