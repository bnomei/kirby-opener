# Kirby Opener (BETA)

![GitHub release](https://img.shields.io/github/release/bnomei/kirby-opener.svg?maxAge=1800) ![License](https://img.shields.io/badge/license-commercial-green.svg) ![Beta](https://img.shields.io/badge/Stage-beta-blue.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-2.3%2B-red.svg)

Kirby Opener is a Kirby CMS Panel Field button that allows you to use placeholders to create dynamic urls which are called with and without ajax response or start downloads.

**NOTE:** This is not a free plugin. In order to use it on a production server, you need to buy a license. For details on Kirby Opener's license model, scroll down to the License section of this document.

## Key Features

- open any URL from within the panel
- add custom data within the URL using *placeholders*
- display custom JSON response status messages at the button label
- trigger downloading of files
- easily extendable *placeholders*
- configural parsing of *json response*

## Requirements

- [**Kirby**](https://getkirby.com/) 2.3+

## Installation

### [Kirby CLI](https://github.com/getkirby/cli)

```
kirby plugin:install bnomei/kirby-opener
```

### Git Submodule

```
$ git submodule add https://github.com/bnomei/kirby-opener.git site/plugins/kirby-opener
```

### Copy and Paste

1. [Download](https://github.com/bnomei/kirby-opener/archive/master.zip) the contents of this repository as ZIP-file.
2. Rename the extracted folder to `kirby-opener` and copy it into the `site/plugins/` directory in your Kirby project.

## Usage

Start the Kirby Panel and create a new page with the template `openerexample` this plugin provides. The plugin also comes with some example fields to get you started. You can find their their [global field definitions](https://getkirby.com/docs/panel/blueprints/global-field-definitions) in the `kirby-opener/blueprints/fields` folder.

To use the plugin to the fullest you will have to define your own urls using *placeholders* and maybe even create the controllers and/or templates to respond with the JSON.

```
example1: openeropenuser
example2: openeropenexternal
example3: openeropenpagefield
example4: openerpopup
example5: openerdownload
example6: openersuccess
example7: openererror
example8: openercontroller
```

![Example](https://github.com/bnomei/kirby-opener/blob/master/example.gif)

### Example 2 explained: Button to open another page

Add this field definition to any blueprint and open the page in the panel.

```yaml
  example2explained:
    type: opener
    command: 'https://www.google.com/?q={field.title}/open:yes'
    text: 'Search for Title in Google'

```

The `{field.title}` is called a *placeholder*. It will be replaced with something context related on the panel page. In this case with the `title` field of the current `$page`-object.

### Example 5 explained: Button to download a file

Add this field definition to a blueprint. It will create a new `opener`-button in the panel with the label `Download fileXY`. While waiting for the response the `...` will be displayed. Once the called page responds with JSON it will be parsed. Unless there is a different `message` in the JSON the `textsuccess` from the blueprint will be displayed.

```yaml
  example5explained:
    type: opener
    command: '/{page.url}/fileparam:fileXY/download:yes'
    text: 'Download a file'
    textprogress: '...'
    textsuccess: 'Download...'

```

The `{page.url}` within the `command` is a *placeholder* and will be replaced by the url of the current page. There are couple of predefined *placeholders* but you probably will want define your own. Which properties of the root JSON object are parsed to determin success, message and url of the file can be configured. These topics will be described later in this readme.

The `download:yes`-parameter can also be configured. It tells the plugins javascript code to download the file and not open it in a popup window (since most browser would block that by default).

For this example let's respond with downloading the kirby license file. In your template code you need to build and return a JSON response.

```php
if(param('fileparam') == 'fileXY') {
	$code = f::exists(kirby()->roots()->index().DS.'license.md') ? 200 : 400;
	$json = [
        'code' => $code, 
        'fileurl' => kirby()->urls()->index().'/license.md',
      ];
    sleep(5); // wait a bit for example purposes
    die(response::json($json, $code));	
}
```

Please note that this is a very basic implementation of returning JSON. The [Kirby Cookbook](https://getkirby.com/docs/cookbook/json) and [Kirby Forum](https://forum.getkirby.com/search?q=json) are a good sources to do better. 

Now open your page in the panel and press the `Download fileXY` button. The download dialog of your browser for the kirby license.md file should appear. unless you removed the license – you little scoundrel.

### Example 8 Explained: Validate User, Panel and Secret

To make sure the command can only be called from within the panel you need to add some sort protection. Let's assume you have an `api` controller (or just a template) prepared. Add the following field definition to any blueprint you want to trigger the api.

```yaml
  exampleController:
    type: opener
    command: '/api/{field.autoid}/{page.diruri.encoded}/{page.secret}/mycmd:dowork'
    text: 'Do Work'
    textprogress: 'working...'
    textsuccess: 'Done.'
    texterror: 'Failed.'
```

So on any page within the panel that has this field you now have a `Do Work`-button. Pressing it will start an ajax request to the `api` page with additional parameters. Since these parameters also contain some *placeholders* as well these will be replaced with context specific values.

Now you need some logic to handle the request. I prefer using a [controller](https://getkirby.com/docs/developer-guide/advanced/controllers) in combination with templates, so paste this to your `api` controller. This plugin comes with and example controller to help you get started. But let's take a look at how the controller works.

```php
<?php
return function($site, $pages, $page) {

	// prepare json response
	$json = ['code' => 400, 'message' => '', 'fileurl' => ''];

	// #1: optional security...
	//     require a user to be logged in and 
	// 	   the request has to come from the panel opener plugin and
	//     it has to be an proper ajax call

	if( !$site->user() || 
		!boolval(param('panel')) || // added by plugin automatically
		!r::ajax()
	) {
		die(response::json($json, 400));
	}

	// #2: now check if work need to be done at all
	if(param('mycmd') == 'dowork') {
		
		// #3: get page to work at
		$pageToWork = null;

		// #3.1: try autoid
		if($autoid = param('autoid')) {

			// left for you to implement
			$pageToWork = myGetPageByAutoIdFunction($autoid);
		} 
		// #3.2: try diruri
		else if($diruri = param('diruri')) {
			// plugin provides a pages method to get page from encoded uri
			// why encode the uri? because it could contain multiple '/' and that would break the parameters.
			$pageToWork  = $pages->openerDiruriEncodedGetPage($diruri);
		}

		// #4: found a page? then validate with secret and start working
		// why a secret? to add an extra layout of security so creating a valid
		// request is something only you can do and nobody from the outside.
		if($pageToWork && $pageToWork->openerSecret() == param('secret')) {
			// do work
			sleep(5);
			// then respond...
			$json['code'] = 200;
			$json['message'] = 'Lunchtime!';
		}
	}

	// for the sake of simplicity just exit now
	die(response::json($json, intval($json['code'])));

	// normaly a controller would return some values to the template
	//return compact('json');
};

```

## Placeholders

The placeholders help you build commands quickly. Why did I implement placeholders instead of parsing the command directly? They help you avoid mistakes in sticking to the DRY-principle.

### field.*
replace the wildcard with any blueprint fieldname to get the value of the field. only numbers and strings are supported.

### field.*.encoded
will get the fields and call `urlencode()` on its value.

### page.url
`$page->url()` in template

### parent.url
`$page->parent()->url()` in template

### page.secret
token you can check in template/controller if request is valid. limited to page.

### site.secret
token you can check in template/controller if request is valid. wildcard version.

### page.diruri.encoded
`urlencoded($page->diruri())` to forward this page to any other. helper functions available – see controller example.

Using the [autoid-plugin](http://github.com/helllicht/kirby-autoid) is a good alternative  to `diruri` if you implement a fast lookup-method maybe with a cache. Since just using `$site->index()` or `$site->search()` might be slow if you have many pages.

### Custom Placeholders
You can also define your own by creating a `site/config/config.php` setting. This plugin grants you access to `$site` and `$page`.

```php
c::set('plugin.opener.placeholder', [
	'myfield' => '$page->myfield()->fieldmethod()',
	'another' => '$page->parent()->diruri()',
	'yetmore' => '$site->otherfield()',
]);
```

## Other Setting

You can set these in your `site/config/config.php`.

### plugin.opener.license
- default: ''
- add your license here and the widget reminding you to buy one will disappear from the Panel.

### plugin.opener.salt
- default: unique SALT for your webserver
- this value is used to create the `secret` and you should set your own value to improve security but it is not required.

### plugin.opener.examples
- default: true
- if disabled the plugin does not install any `blueprints, templates, controllers, hooks and routes` that are used by its examples. use this setting in production enviroment.

### plugin.opener.json.code
- default: `code`
- use this setting to define a json root-object property which will be used to parse the status code.

### plugin.opener.json.message
- default: `message`
- use this setting to define a json root-object property which will be used to parse the response message.

### plugin.opener.json.fileurl
- default: `fileurl`
- use this setting to define a json root-object property which will be used to parse the url of the file to be downloaded.

### plugin.opener.reset-delay
- default: `5000` in ms
- after that delay the button is reset from displaying the message to its initial state.

### plugin.opener.popup-window
- default: false
- downloads are opend via bowser dialog if possible and not as popup windows which most browsers block

### plugin.opener.trigger-download
- default: 'download:yes'
- command part to tell the plugin js script to trigger download for content of JSON response (see `json.fileurl`).

### plugin.opener.trigger-open
- default: 'open:yes'
- command part to tell the plugin js script to trigger a new window/tab with command as url. There will be no ajax call.

### plugins.opener.allow-pagemodels
- default: false
- if enabled you can use `$pageModel` in your placeholders to access functions defined in your [Kirby Page Models](https://getkirby.com/docs/developer-guide/advanced/models).

### plugins.opener.allow-eval
- default: false
- commands only allow you to chain `$page` or `$site` and their methods but without parameters. If you enabled `allow-eval` you can go crazy with your placeholders up to 100 chars and a single statement. But since `eval()` is dangerous this setting is disabled by default. Please be aware of the risks of you enable this setting. 

Placeholders like the following becomes possible if enabled:

```php
c::set('plugin.opener.placeholder', [
	'crazy' => 'panel()->page("some/wicked/uri")->mymethod($page->somefield()->value())', // less than 100 chars
]);
```

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby-opener/issues/new).

## License

Kirby Opener can be evaluated as long as you want on how many private servers you want. To deploy Kirby Opener on any public server, you need to buy a license. You need one unique license per public server (just like Kirby does). See `license.md` for terms and conditions.

[<img src="https://img.shields.io/badge/%E2%80%BA-Buy%20a%20license-green.svg" alt="Buy a license">](https://bnomei.onfastspring.com/kirby-opener)

However, even with a valid license code, it is discouraged to use it in any project, that promotes racism, sexism, homophobia, animal abuse or any other form of hate-speech.

## Technical Support

Technical support is provided on GitHub only. No representations or guarantees are made regarding the response time in which support questions are answered. But you can also join the discussions in the [Kirby Forum](https://forum.getkirby.com/search?q=kirby-opener).

## Credits

Kirby Opener is developed and maintained by Bruno Meilick, a game designer & web developer from Germany.
I want to thank [Fabian Michael](https://github.com/fabianmichael) for inspiring me a great deal and [Julian Kraan](http://juliankraan.com) for telling me about Kirby in the first place.
