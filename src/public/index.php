<?php
session_start();


require_once('../config.php');
require_once('../vendor/autoload.php');

// autoload classes
spl_autoload_register(function($classname) {
    require_once("../classes/" . $classname . ".php");
});

$db = new MySQLDatabase(DB_HOST, DB_NAME, DB_PORT, DB_USER, DB_PASS);
$session = new Session(BASE_URL);
$app = new \Slim\App();

// Get container
$container = $app->getContainer();


// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('../templates', [
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
$app->get('/', function($request , $response, $args) use ($db, $session) {
    $latestResults = Website::getLatest($db);
    $topResults = Website::getTopHits($db);
    $baseURL = "http://" . $_SERVER["HTTP_HOST"] . BASE_URL; 
    $isAdmin = $session->isLoggedIn() ? true : false;
    $session->updatePage($this->router->pathFor('home'));

    if(isset($_SESSION["postData"])) {
        $postData = $_SESSION["postData"];
        $_SESSION["postData"] = null;
    }
    else {
        $postData = null;
    }

    return $this->view->render($response, 'index.twig', compact("latestResults", "topResults", "baseURL", "postData", "isAdmin"));
})->setName('home');


// Admin login route and redirect
$app->get('/admin', function($request, $response, $args) use ($session) {
    if($session->isLoggedIn()) {
        // redirect to previous page if already logged in
        $this->flash->addMessage("dismissableFail", "You are already logged in");
        return $response->withRedirect($session->getPrevPage());
    }
    else {
        // render login page
        return $this->view->render($response, 'admin.twig');
    }
})->setName('admin');


// Process admin login form data
$app->post('/admin', function($request, $response, $args) use ($db, $session) {
    $username = trim($request->getParam("username"));
    $password = trim($request->getParam("password"));

    $admin = Admin::authenticate($db, $username, $password);
    $router = $this->router;

    if($admin) {
        // login user and redirect to previous page
        $session->login($admin);
        $this->flash->addMessage("dismissableSuccess", "You have successfully logged in");
        return $response->withRedirect($session->getPrevPage());
    }
    else {
        // authentication failed
        $this->flash->addMessage("dismissableFail", "Username/password is incorrect");
        return $response->withRedirect($router->pathFor('admin'));
    }
})->setName("adminLogin");


// Process logging out admin
$app->get('/logout', function($request, $response, $args) use ($session) {
    $router = $this->router;

    if($session->isLoggedIn()) {
        $session->logout();

        // redirect to homepage
        $this->flash->addMessage("dismissableSuccess", "You have successfully logged out");
        return $response->withRedirect($router->pathFor('home'));
    }
    else {
        $this->flash->addMessage("fail", "You are not logged in");
        return $response->withRedirect($router->pathFor('error'));
    }
})->setName('logout');


// Delete an entry
$app->get('/delete/{id}', function ($request, $response, $args) use ($db, $session) {
    $id = (int)$args["id"];

    if($session->isLoggedIn()) {
        $website = Website::findById($db, $id);
        if($website) {
            return $this->view->render($response, "delete.twig", compact("website"));
        }
        else {
            $router = $this->router;
            return $response->withRedirect($router->pathFor('error'));
        }
    }
    else {
        $router = $this->router;
        $this->flash->addMessage("fail", "You do not have permission to view this page");
        return $response->withRedirect($router->pathFor('error'));
    }
})->setName('deleteEntry');

// Process delete entry form
$app->post('/processDelete', function ($request, $response, $args) use ($db, $session) {
    if($session->isLoggedIn()) {
        $id = (int)$this->request->getParam("id");
        $website = Website::findById($db, $id);

        // redirect back to previous page if cancel button is clicked
        if($this->request->getParam("cancelButton")) {
            return $response->withRedirect($session->getPrevPage());
        }

        // delete entry if delete button is clicked
        if($this->request->getParam("deleteButton")) {
            if($website) {
                $website->delete();
                $this->flash->addMessage("dismissableSuccess", "Website entry has been deleted");
                return $response->withRedirect($session->getPrevPage());
            }
            else {
                $this->flash->addMessage("dismissableFail", "Delete website entry failed");
                $router = $this->router;
                return $response->withRedirect($router->pathFor('error'));
            }
        }
    }
    else {
        $this->flash->addMessage("fail", "You do not have permission to view this page");
        $router = $this->router;
        return $response->withRedirect($router->pathFor('error'));
    }
})->setName('processDelete');

// Page error route
$app->get('/error', function($request, $response, $args) {
    return $this->view->render($response, 'error.twig');
})->setName('error');

// Show all entries page
$app->get('/all', function($request, $response, $args) use ($db, $session) {
    $isAdmin = $session->isLoggedIn() ? true : false;

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

    $sessionPage = $this->router->pathFor('all');
    // update here
    if($search || $sort || $sortOrder || $page || $displayItems) {
        $sessionPage .= "?";
        $getVariablesJoiner = "";
        if($page) {
            $sessionPage .= "page={$page}";
            $getVariablesJoiner = "&";
        }
        if ($search) {
            $sessionPage .= "{$getVariablesJoiner}search={$search}";
            $getVariablesJoiner = "&";
        }
        if($sort) {
            $sessionPage .= "{$getVariablesJoiner}sort={$sort}";
            $getVariablesJoiner = "&";
        }
        if($sortOrder) {
            $sessionPage .= "{$getVariablesJoiner}sortOrder={$sortOrder}";
            $getVariablesJoiner = "&";
        }
        if($displayItems) {
            $sessionPage .= "{$getVariablesJoiner}displayItems={$displayItems}";
        }
    }
    $session->updatePage($sessionPage);

    return $this->view->render($response, 'all.twig', compact("allResults", "baseURL", "search", "sort", "sortOrder", "pages", "displayItems", "isAdmin"));
})->setName('all');


// Redirect to the specified url
$app->get('/{name}', function($request, $response, $args) use ($db) {
    $name = $args["name"];

    if($website = Website::getWebsiteByName($db, $name)) {
        // update number of times redirection link has been used
        $website->hits += 1;
        $website->save();

        return $response->withRedirect($website->url);
    }
    else {
        $this->flash->addMessage("fail", "Custom name {$name} does not exist");
        $router = $this->router;
        return $response->withRedirect($router->pathFor('error'));
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

