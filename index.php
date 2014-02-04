<?php
/** @var Base $f3 */
$f3 = require('lib/base.php');

ini_set('display_errors', 1);
error_reporting(-1);

//$f3->set('JAR.expire', time()+(60*60*2));

$f3->config('app/config.ini');

## DB Setup
$cfg = Config::instance();
if($cfg->ACTIVE_DB)
    $f3->set('DB', storage::instance()->get($cfg->ACTIVE_DB));
else {
    $f3->error(404,'Sorry, but there is no active DB setup.');
}

$f3->set('FLASH', FlashMessage::instance());

\Template::instance()->extend('image','\ImageViewHelper::render');
\Template::instance()->extend('pagebrowser','\Pagination::renderTag');

## POSTS
// view list
$f3->route(array(
    'GET /',
    'GET /page/@page'
   ),'Controller\Post->getList');
// view single
$f3->route(array(
    'GET /@slug',
    'GET /post/@id'
   ), 'Controller\Post->getSingle');
// post comment
$f3->route('POST /@slug', 'Controller\Post->addComment');

## TAGS
$f3->route(array(
    'GET /tag [ajax]',
    'GET /tag/@slug'
   ),'Controller\Tag->getList');


///////////////
//  backend  //
///////////////

if (\Controller\Backend::isLoggedIn()) {

    # specific routes
    // comments
    $f3->route(array(
        'GET /admin/comment/list/@viewtype',
        'GET /admin/comment/list/@viewtype/@page',
    ), 'Controller\Comment->getList');
    $f3->route('GET /admin/comment/approve/@id', 'Controller\Comment->approve');
    $f3->route('GET /admin/comment/reject/@id', 'Controller\Comment->reject');
    // posts
    $f3->route('GET /admin/post/publish/@id', 'Controller\Post->publish');
    $f3->route('GET /admin/post/hide/@id', 'Controller\Post->hide');

    # general CRUD operations
    // create new
    $f3->route('POST /admin/@module', 'Controller\@module->post');
    // update
    $f3->route('POST /admin/@module/save/@id', 'Controller\@module->post');
    // delete record
    $f3->route('GET /admin/@module/delete/@id', 'Controller\@module->delete');

    # general forms
    // dashboard
    $f3->route('GET /admin', 'Controller\Backend->home');
    // view list
    $f3->route(array(
        'GET /admin/@module',
        'GET /admin/@module/@page')
        , 'Controller\Backend->getList');
    // view create form
    $f3->route('GET /admin/@module/create', 'Controller\Backend->getSingle');
    // view edit form
    $f3->route('GET /admin/@module/edit/@id', 'Controller\Backend->getSingle');

    $f3->route('GET|POST /login', function (Base $f3) {
        $f3->reroute('/admin');
    });


    // setup DB
    $f3->route('GET /install/@type', 'setup->install');
    // uninstall, who would ever need this? :D
    $f3->route('GET /uninstall', 'setup->uninstall');

    // upload file
    $f3->route('POST /admin/file [ajax]', function ($f3) {
        $result = \Web::instance()->receive(function ($file) {
                $allowed_types = array('image/png', 'image/jpeg', 'image/gif', 'image/bmp');
                return in_array($file['type'], $allowed_types);
            },
            true, // overwrite
            true // rename to UTF-8 save filename
        );
        echo json_encode($result);
    });

} else {
    // login
    $f3->route(array('GET|POST /admin/*','GET|POST /admin'),function(Base $f3) {
        $f3->reroute('/login');
    });

    $f3->route('GET|POST /login','Controller\Backend->login');
}

$f3->route('GET /logout', 'Controller\Backend->logout');


// let's cross the finger
$f3->run();
