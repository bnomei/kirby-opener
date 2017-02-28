<?php
return function($site, $pages, $page) {

	// prepare json response
	$json = ['code' => 400, 'message' => '', 'fileurl' => ''];

	/////////////////////////////////////
	/////////////////////////////////////
	// Example 5
	/////////////////////////////////////
	/////////////////////////////////////
	if(param('fileparam') == 'fileXY') {
		$code = f::exists(kirby()->roots()->index().DS.'license.md') ? 200 : 400;
		$json = [
	        'code' => $code, 
	        'fileurl' => kirby()->urls()->index().'/license.md',
	      ];
	    sleep(5); // wait a bit for example purposes
	}

	

	/////////////////////////////////////
	/////////////////////////////////////
	// Example 8
	/////////////////////////////////////
	/////////////////////////////////////

	// #1: check if work need to be done at all
	if(param('mycmd') == 'dowork') {
		
		// #2: require a user to be logged in and request to come from opener plugin ajax call
		if( !$site->user() || 
			!boolval(param('panel')) || // added by plugin automatically
			!r::ajax()
		) {
			die(response::json($json, 400));
		}

		// #3: get page to work at
		$pageToWork = null;

		// #3.1: try autoid
		if($autoid = param('autoid')) {

			// left for you to implement
			$pageToWork = myGetPageByAutoIdFunction($autoid);
		} 
		// #3.2: try diruri
		else if($diruri = param('diruri')) {
			// pages method from plugin
			$pageToWork  = $pages->openerDiruriEncodedGetPage($diruri);
		}

		// #4: validate with secret and start working
		if($pageToWork && $pageToWork->openerSecret() == param('secret')) {
			// do work
			sleep(5);
			// then respond...
			$json['code'] = 200;
			$json['message'] = 'Lunchtime!';
		}
	}

	if(r::ajax()) {
		die(response::json($json, intval($json['code'])));
	} else {
		return compact('json');
	}
};