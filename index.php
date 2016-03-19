<?php
session_start();

require_once('includes/initialize.php');
require_once('vendor/autoload.php');

$db = new MySQLDatabase();
$app = new \Slim\App();

// Get container
$container = $app->getContainer();


// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('templates', [
        'cache' => false 
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    return $view;
};


// Register provider
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};


// Add middleware
$app->add(function ($request, $response, $next) {
    $this->view->offsetSet('flash', $this->flash);
    return $next($request, $response);
});


// Homepage
$app->get('/', function($request , $response, $args) use ($db) {
    $latestResults = Website::getLatest($db);
    $topResults = Website::getTopHits($db);
    $baseURL = "http://" . $_SERVER["HTTP_HOST"] . BASE_URL; 

    if(isset($_SESSION["postData"])) {
        $postData = $_SESSION["postData"];
        $_SESSION["postData"] = null;
    }
    else {
        $postData = null;
    }

    return $this->view->render($response, 'index.twig', compact("latestResults", "topResults", "baseURL", "postData"));
})->setName('home');


// Show all entries page
$app->get('/all', function($request, $response, $args) use ($db) {
    $baseURL = "http://" . $_SERVER["HTTP_HOST"] . BASE_URL;

    $search = trim($request->getParam("search"));
    $sort = trim($request->getParam("sort"));
    $sortOrder = trim($request->getParam("sortOrder"));
    $page = (int)trim($request->getParam("page"));
    $displayItems = trim($request->getParam("displayItems"));
    $validDisplayValues = array(5,10,20,50,100,"all");

    if($search || $sort || $sortOrder) {
        $totalItems = Website::getNumEntriesFromFilter($db, $search, $sort, $sortOrder);
    }
    else {
        $totalItems = Website::getTotalEntries($db);
    }

    if($displayItems) {
        if($displayItems === "all") {
            $numItemsPerPage = $totalItems;
        }
        else if (in_array($displayItems, $validDisplayValues)) {
            $numItemsPerPage = (int)$displayItems;
        }
        else {
            $numItemsPerPage = 10;
            $displayItems = 10;
        }
    }
    else {
        $numItemsPerPage = 10;
    }

    $pages = new Pagination($totalItems, $numItemsPerPage, $page);
    $limit = $pages->numItemsPerPage;
    $offset = $pages->calculateOffset();

    if($search || $sort || $sortOrder) {
        $allResults = Website::getByFilter($db, $limit, $offset,$search, $sort, $sortOrder);
    }
    else {
        $allResults = Website::getAllSorted($db, $limit, $offset);
    }

    return $this->view->render($response, 'all.twig', compact("allResults", "baseURL", "search", "sort", "sortOrder", "pages", "displayItems"));
})->setName('all');

$app->get('/admin', function($request, $response, $args) use ($db) {
    return 'admin';
})->setName('admin');


// Redirect to the specified url
$app->get('/{name}', function($request, $response, $args) use ($db) {
    $name = $args["name"];

    if($website = Website::getWebsiteByName($db, $name)) {
        // update number of times redirection link has been used
        $website->hits += 1;
        $website->save();

        header("Location: " . $website->url);
        exit;
    }
    else {
        return $this->view->render($response, "invalid.twig", ["name"=>$name]);
    }
});


// Process adding a new website
$app->post('/', function($request, $response, $args) use ($db) {
    $url = Website::addScheme(trim($request->getParam('url')));
    $shortName = trim(strtolower($request->getParam('shortName')));
    $formError = false;

    // Make sure user entered URL is a valid format
    if(!Website::isValidURL($url)) {
        $this->flash->addMessage('fail', 'Error: Invalid URL input');
        $formError = true;
    }

    // Make sure user entered a custom name in a valid format
    if(!Website::isValidName($shortName)) {
        $this->flash->addMessage('fail', 'Error: Invalid custom name');
        $formError = true;
    }
    else {
        // Make sure custom name isn't already used
        if(Website::getWebsiteByName($db, $shortName)) {
            $this->flash->addMessage('fail', 'Error: Custom name already exists');
            $formError = true;
        }
    }

    // Create new database entry for the website if no errors with input data
    if(!$formError) {
        $website = new Website($db);
        $website->url = $url;
        $website->shortname = $shortName;
        $website->hits = 0;
        $website->added = date("Y-m-d H:i:s");
        $website->save();

        $this->flash->addMessage('success', 'Website has been successfully added');
    }
    else {
        $_SESSION["postData"] = $_POST;
    }

    $router = $this->router;
    return $response->withRedirect($router->pathFor('home'));
})->setName('addURL');

$app->run();

?>

