<!DOCTYPE html>
<html>
<head>
	<title><?= $page->title() ?></title>
</head>
<body style="max-width: 600px;margin:0 auto;font-family:'Helvetica Neue', Helvetica,Arial, sans-serif;">
	<center>
		<h1 style="margin-top:40px;"><?= $page->title() ?></h1>
		<div><a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" href="<?= $site->url() ?>/panel/pages/<?= $page->diruri() ?>/edit">Edit in Panel</a> <a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" href="https://github.com/bnomei/kirby-opener">Github Docs</a><br><br></div>
	</center>

	<h2 style="color:#666;margin-top:40px;">JSON</h2>
	<pre style="background-color:#eee;padding:20px;"><?php a::show($json) ?></pre>

	<?php $oe = kirby()->site()->pages()->filterBy('template', 'openerexample')->first(); 
	var_dump($oe);
	?>
</body>
</html>