<?php

class qa_ma_mlm_sync
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
		if ($request=='admin/ma-mlm-sync')
			return true;

		return false;
	}

	function process_request( $request )
	{
		$level = qa_get_logged_in_level();

		if($level == null || $level < QA_USER_LEVEL_ADMIN)
			qa_fatal_error('Only admins can access this page');

		
		if (isset($_GET['confirmed'])){
			$users = qa_db_query_sub('SELECT email FROM ^users WHERE flags&#', QA_USER_FLAGS_EMAIL_CONFIRMED);
			$filename = 'email-export-confirmed.txt';
		}else{
			$users = qa_db_query_sub('SELECT email FROM ^users');
			$filename = 'email-export.txt';
		}
		
		header("Content-Disposition: attachment; filename=$filename");
		while ( ($email=qa_db_read_one_value($users,true)) !== null ) {
			echo "$email\n";
		}
		die();
		
	}



}


