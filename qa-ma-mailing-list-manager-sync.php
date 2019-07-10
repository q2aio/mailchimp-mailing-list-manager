<?php

class qa_mc_mlm_sync
{
	private $directory;
	private $urltoroot;

	function load_module( $directory, $urltoroot )
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}


	function match_request( $request )
	{
		if ($request=='admin/mailchimp-sync')
			return true;

		return false;
	}

	function process_request( $request )
	{
		$level = qa_get_logged_in_level();

		if($level == null || $level < QA_USER_LEVEL_ADMIN)
			qa_fatal_error('Only admins can access this page');
			
		$qa_content = qa_content_prepare();

		
		if (isset($_GET['confirmed'])){
			$users = qa_db_query_sub('SELECT userid,email,handle FROM ^users WHERE flags&#', QA_USER_FLAGS_EMAIL_CONFIRMED);
		}else{
			$users = qa_db_query_sub('SELECT userid,email,handle FROM ^users');
		}
		
		// iterating through all values and adding it to mailchimp list.
		while ( ($values=qa_db_read_one_assoc($users,true)) !== null ) {
			require_once $this->directory . '/qa-mailing-list-manager-ops.php';
			mc_add_email($values['userid'], $values['handle']);
		}
		return $qa_content;
		
	}



}


