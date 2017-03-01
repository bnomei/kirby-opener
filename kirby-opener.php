<?php

/****************************************
  CLASSES
 ***************************************/

class KirbyOpener {
  protected $version;

  public static function getSalt() {
    $salt = c::get('plugin.opener.salt', false);
    if(!$salt) {
      $salt = sha1(
        kirby()->site()->url().
        phpversion().
        Kirby::version().
        toolkit::version()
      );
    }
    return $salt;
  }

  public function version() {
    if(is_null($this->version)) {
      $package = json_decode(f::read(dirname(__DIR__) . DS . 'package.json'));
      $this->version = $package->version;
    }

    return $this->version;
  }
}

/****************************************
  METHODS
 ***************************************/

$kirby->set('site::method', 'openerSecret', 
  function($site) {
    return sha1(KirbyOpener::getSalt().date('Ymd'));
});

$kirby->set('pages::method', 'openerSecret', 
  function($pages) {
    return sha1(KirbyOpener::getSalt().date('Ymd'));
});

$kirby->set('pages::method', 'openerDiruriEncodedGetPage', 
  function($pages, $diruriencoded) {
    return page(str_replace('__DS__', DS, urldecode($diruriencoded)));
});

$kirby->set('page::method', 'openerSecret', 
  function($page) {
    return sha1(KirbyOpener::getSalt().$page->url().date('Ymd'));
});

$kirby->set('page::method', 'openerDiruriEncoded', 
  function($page) {
    return urlencode(str_replace(DS, '__DS__', $page->uri()));
});

$kirby->set('page::method', 'openerDiruriEncodedIsEqual', 
  function($page, $diruriencoded) {
    return $page->openerDiruriEncoded() == $diruriencoded;
});

/****************************************
  FIELDS
 ***************************************/

$kirby->set('field', 'opener', __DIR__ . '/fields/opener');


/****************************************
  BLUEPRINTS
 ***************************************/

if(c::get('plugin.opener.examples', true)) {
  $blueprints = new Folder(__DIR__ . '/blueprints/fields');
  foreach ($blueprints->files() as $file) {
    if($file->extension() == 'yml') {
      $kirby->set('blueprint', 'fields/'.$file->name(), $file->root());  
    }
  }

  $kirby->set('blueprint', 'openerexample', __DIR__.'/blueprints/openerexample.yml'); 
}

/****************************************
  TEMPLATES and CONTROLLERS
 ***************************************/
if(c::get('plugin.opener.examples', true)) {
  $kirby->set('template', 'openerexample', __DIR__ . '/templates/openerexample.php');
  $kirby->set('controller', 'openerexample', __DIR__ . '/controllers/openerexample.php');
}

/****************************************
  HOOKS
 ***************************************/
if(c::get('plugin.opener.examples', true)) {
  $kirby->set('hook', 'panel.page.create', function($page) {
    if($page->template() == 'openerexample') {
      try{
        $page->update([
          'title' => 'Opener Plugin Example Page',
          'related' => '/home',
        ]);
      } catch(Exception $ex) {
        // echo $ex->getMessage();
      }
    }
  });
}

/****************************************
  ROUTES
 ***************************************/

if(c::get('plugin.opener.examples', true)) {
  $kirby->set('route', array(
    'pattern' => ['openerexample'],
    'action'  => function() {

      $oe = kirby()->site()->pages()->filterBy('template', 'openerexample')->first();
      if($oe && $oe->slug() != 'openerexample') {
        return page($oe->id());
      }
    }
  ));

  $kirby->set('route', array(
    'pattern' => ['openerexample/(:all)'],
    'action'  => function($all) {

      $oe = kirby()->site()->pages()->filterBy('template', 'openerexample')->first();
      if($oe && $oe->slug() != 'openerexample') {
        return page($oe->id());
      }
    }
  ));

  $kirby->set('route', array(
    'pattern' => ['openerexample-route/(:any)'],
    'action'  => function($any) {

      $json = array();
      $code = c::get('plugin.opener.json.code', 'code');
      $message = c::get('plugin.opener.json.message', 'message');
      $fileurl = c::get('plugin.opener.json.fileurl', 'fileurl');

      if($any == 'success') {
        $json = [$code => 200, $message => 'JSON :)'];
      } else if($any == 'error') {
        $json = [$code => 400, $message => 'JSON :('];
      } else if($any == 'file') {
        $json = [
          $code => 200, 
          $message => 'readme.md',
          $fileurl => kirby()->urls()->index().'/license.md',
        ];
      }

      sleep(5);
      die(response::json($json, 200));
    }
  ));
}

/****************************************
  WIDGET
 ***************************************/
if(str::length(c::get('plugin.opener.license', '')) != 40) {
  // Hi there, play fair and buy a license. Thanks!
  $kirby->set('widget', 'opener', __DIR__ . '/widgets/opener');
}
