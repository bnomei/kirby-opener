<?php // tpl($data) => $field, $fieldname, $page
	
	$code     = c::get('plugin.opener.json.code', 'code');
    $message  = c::get('plugin.opener.json.message', 'message');
    $fileurl  = c::get('plugin.opener.json.fileurl', 'fileurl');
	$delay    = c::get('plugin.opener.reset-delay', 5000); // ms
	$popup    = c::get('plugin.opener.popup-window', false);

	if(strlen(trim($field->value)) > 0): 
		// locked
		//?>
	<input class="input input-is-readonly" type="<?php echo $fieldname ?>" value="<?php echo $field->value() ?>" readonly name="title" id="form-field-<?php echo $fieldname ?>" tabindex="-1"><div class="field-icon"><i class="icon fa fa-lock"></i></div>
<?php
	else: 

	// vars for placeholders
	// NOTE: page($page->id()); would be panel page
	$page = $page; 
	$site = $page->site();
	$pages = $site->pages();

	$settings = c::get('plugin.opener.placeholder', array());
	$defaults = [
		'page.url' => '$page->url()',
		'parent.url' => '$page->parent()->url()',

		'page.secret' => 'secret:$page->openerSecret()',
		'site.secret' => 'secret:$site->openerSecret()',
		'pages.secret' => 'secret:$site->openerSecret()', // equals site.secret
		
		'page.title' => '$page->title()', // aka field.title
		'page.diruri.encoded' => 'diruri:$page->openerDiruriEncoded()',

		// examples
		'page.autoid' => 'autoid:$page->autoid()',
		'panel.user.current' => '$site->user()->username()',
		'homepage.diruri' => '$site->homepage()->diruri()',
		'page.related.diruri' => '$page->related()->toPage()->diruri()',
	];
	
	// eval
	if(c::get('plugins.opener.allow-eval', false)) {
		$eval = [
			// 'contact.url' => 'panel()->page(\'contact\')->url()',
		];
		$defaults = a::merge($defaults, $eval);
	}

	$defaultsAndSettings = a::merge($defaults, $settings);
	$placeholder = array();
	foreach ($defaultsAndSettings as $key => $objAndMethods) {
		$param = explode(':', $objAndMethods);
		$obj = '';
		
		if(c::get('plugins.opener.allow-eval', false)) {
			// eval is dangerous. use with care! 
			// minimal savenet: remove some expressions and limit string length.
			// http://www.php.net/manual/en/function.eval.php
			$str = str_replace([';','<?','?>'], ['','',''], substr($param[count($param)-1], 0, 100));
			eval('$obj = '.$str.';');

		} else {
			$chain = explode('->', $param[count($param)-1]);	
			if(count($chain) > 1) {
				$obj = ${str_replace('$','',$chain[0])};
				for($c = 1; $c < count($chain); $c++) {
					if(isset($obj)) {
						$method = str_replace('()', '', $chain[$c]);
						if(is_callable([$obj, $method])) {
							$obj = $obj->{$method}();
						}
					}
				}
			}
		}

		if($obj) {
			$placeholder[$key] = count($param) > 1 ? $param[0].':'.trim($obj) : trim($obj);	
		}
	}

	// fields (but not structures)
	// TODO: check if this supports multilang installations? $page->content($lang)
	if($content = $page->content()->toArray() ) {
		$fieldValues = array();
		foreach ($content as $key => $value) {
			$yml = Yaml::decode($value);
			$isArray = count($yml) > 0 && gettype($yml[0]) == 'array'; // aka structure
			if(!$isArray && str::length(trim($value)) > 0) {
				$fieldValues['field.'.$key] = $value;
				$fieldValues['field.'.$key.'.encoded'] = $key.':'.urlencode($value);
			}
		}
		$placeholder = a::merge($placeholder, $fieldValues);
	}

	if(c::get('debug', false)) {
		echo '<style>.opener-debug {border:1px solid #ccc;background-color:#ddd;padding:20px;margin-bottom:20px;} .opener-debug pre{font-family: monospace;word-wrap:break-word;}</style><div class="opener-debug">'.a::show($placeholder, false).'</div>';
	}

	$href        = url(str::template($field->command, $placeholder));
	$download    = str::contains($href, c::get('plugin.opener.trigger-download', 'download:yes'));
	$openConfig  = c::get('plugin.opener.trigger-open', 'open:yes');
	$open        = str::contains($href, $openConfig);
	if($open) {
		$href = str_replace('/'.$openConfig, '', $href);
	}
?>

<div class="<?php echo $fieldname ?>-wrapper">
	<div 
		class="<?php echo $fieldname ?>-button <?php ecco($popup, 'popup ', '') ?><?php ecco($download, 'download ', '') ?><?php ecco($open, 'no-ajax ', '') ?>"
		data-delay="<?php echo $delay; ?>"
		data-jsoncode="<?php echo $code; ?>" 
		data-jsonmessage="<?php echo $message; ?>" 
		data-jsonfileurl="<?php echo $fileurl; ?>" 
		>
		<a 
			class="btn btn-rounded <?php echo $field->type(); ?>" 
			id="<?php echo $field->id(); ?>" 
			name="<?php echo $field->name(); ?>" 
			href="<?php echo $href ?>"
			target="_blank"

		><i class="fa fa-spinner fa-spin fa-fw" aria-hidden="true"></i><i class="fa fa-exclamation-circle fa-fw" aria-hidden="true"></i><i class="fa fa-info-circle fa-fw" aria-hidden="true"></i><i class="fa fa-check-circle fa-fw" aria-hidden="true"></i><span 
			name="<?php echo $field->name(); ?>" 
			data-texterror="<?php echo $field->texterror; ?>" 
			data-textsuccess="<?php echo $field->textsuccess; ?>" 
			data-textopening="<?php echo $field->textopening; ?>" 
			data-textprogress="<?php echo $field->textprogress; ?>" 
			data-textdefault="<?php echo $field->text; ?>"><?php echo $field->text; ?></span></a>
		<?php if($download): ?><a id="<?php echo $field->name(); ?>-download" href="" download="" class="<?php echo $fieldname ?>-download" target="_blank">Download</a><?php endif; ?>
	</div>
</div>

<?php endif; ?>
