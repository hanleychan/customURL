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

    return $this->view->render($response, 'index.twig', compact("latestResults", "topResults", "baseURL"));
})->setName('home');

// Show all entries page
$app->get('/all', function($request, $response, $args) use ($db) {
    $baseURL = "http://" . $_SERVER["HTTP_HOST"] . BASE_URL;

    $search = trim($request->getParam("search"));
    $sort = trim($request->getParam("sort"));
    $sortOrder = trim($request->getParam("sortOrder"));

    if($search || $sort || $sortOrder) {
        $allResults = Website::getBySearch($db, $search, $sort, $sortOrder);
    }
    else {
        $allResults = Website::getAllSorted($db);
    }

    return $this->view->render($response, 'all.twig', compact("allResults", "baseURL", "search", "sort", "sortOrder"));
})->setName('all');

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
$app->post('/addURL', function($request, $response, $args) use ($db) {
    $url = Website::addScheme(trim(strtolower($request->getParam('url'))));
    $shortName = trim(strtolower($request->getParam('shortName')));

    // Make sure user entered URL is a valid format
    if(!Website::isValidURL($url)) {
        $this->flash->addMessage('fail', 'Error: Invalid URL input');
        header("location: " . BASE_URL);
        exit;
    }

    // Make sure user entered shortened name is a valid format
    if(Website::isValidName($shortName)) {
        // Make sure shortened name isn't already used
        if(!Website::getWebsiteByName($db, $shortName)) {
            // Create new database entry for the website
            $website = new Website($db);
            $website->url = $url;
            $website->shortname = $shortName;
            $website->hits = 0;
            $website->added = date("Y-m-d H:i:s");
            $website->save();
    
            $this->flash->addMessage('success', 'Website has been successfully added');
        }
        else {
            $this->flash->addMessage('fail', 'Error: Custom name already exists');
        }
    }
    else {
        $this->flash->addMessage('fail', 'Error: Invalid custom name');
    }

    header("Location: " . BASE_URL);
    exit;
})->setName('addURL');

$app->run();

?>

