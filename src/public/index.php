<?php
session_start();

// load configuration variables
require_once('../config.php');

// autoload classes
require_once('../vendor/autoload.php');
spl_autoload_register(function($classname) {
    require_once("../classes/" . $classname . ".php");
});

$config['displayErrorDetails'] = false;

$app = new \Slim\App(["settings" => $config]);

// Get container
$container = $app->getContainer();


// Register view component on container
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

// Register db component on container
$container['db'] = function ($container) {
    $db = new MySQLDatabase(DB_HOST, DB_NAME, DB_PORT, DB_USER, DB_PASS);

    return $db;
};

// Register session component on container
$container['session'] = function ($container) {
    $session = new Session(BASE_URL);

    return $session;
};


// Register provider
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

// Override the default Not Found Handler
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['view']->render($response, '404.twig', ['noMenu'=>true]); 
    };
};


// Add middleware
$app->add(function ($request, $response, $next) {
    $this->view->offsetSet('flash', $this->flash);
    return $next($request, $response);
});


// Homepage
$app->get('/', function($request , $response, $args) {
    $latestResults = Website::getLatest($this->db);
    $topResults = Website::getTopHits($this->db);
    $baseURL = "http://" . $_SERVER["HTTP_HOST"] . BASE_URL; 
    $isAdmin = $this->session->isLoggedIn() ? true : false;
    $page = $this->router->pathFor('home');

    if(isset($_SESSION["postData"])) {
        $postData = $_SESSION["postData"];
        $_SESSION["postData"] = null;

        $urlError = Website::isValidURL($postData["url"]) ? false : true; 
        $nameError = Website::isValidName($postData["shortName"]) ?  false : true;
    }
    else {
        $postData = null;
        $urlError = false;
        $nameError = false;
    }

    return $this->view->render($response, 'index.twig', compact("latestResults",
                                                                "topResults",
                                                                "baseURL",
                                                                "postData",
                                                                "isAdmin",
                                                                "urlError",
                                                                "nameError",
                                                                "page"));
})->setName('home');


// Admin login route and redirect
$app->get('/admin', function($request, $response, $args) {
    if($this->session->isLoggedIn()) {
        // redirect to previous page if already logged in
        $this->flash->addMessage("dismissableFail", "You are already logged in");
        return $response->withRedirect($this->session->getPrevPage());
    }
    else {
        // render login page
        return $this->view->render($response, 'admin.twig');
    }
})->setName('admin');


// Process admin login form data
$app->post('/admin', function($request, $response, $args) {
    $username = trim($request->getParam("username"));
    $password = trim($request->getParam("password"));

    $admin = Admin::authenticate($this->db, $username, $password);
    $router = $this->router;

    if($admin) {
        // login user and redirect to previous page
        $this->session->login($admin);
        $this->flash->addMessage("dismissableSuccess", "You have successfully logged in");
        return $response->withRedirect($this->session->getPrevPage());
    }
    else {
        // authentication failed
        $this->flash->addMessage("dismissableFail", "Username/password is incorrect");
        return $response->withRedirect($router->pathFor('admin'));
    }
})->setName("adminLogin");


// Process logging out admin
$app->get('/logout', function($request, $response, $args) {
    $router = $this->router;

    if($this->session->isLoggedIn()) {
        $this->session->logout();

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
$app->get('/delete/{id}', function ($request, $response, $args) {
    $id = (int)$args["id"];

    if($this->session->isLoggedIn()) {
        $website = Website::findById($this->db, $id);
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


// Delete entry link
$app->post('/delete/{id}', function ($request, $response, $args) {
    $id = (int)$args["id"];
    $page = $request->getParam("page");
    $this->session->updatePage($page);

    $router = $this->router;
    return $response->withRedirect($router->pathFor('deleteEntry', ["id"=>$id]));
})->setName('deleteEntryLink');


// Process delete entry form
$app->post('/processDelete', function ($request, $response, $args) {
    if($this->session->isLoggedIn()) {
        $id = (int)$this->request->getParam("id");
        $website = Website::findById($this->db, $id);

        // redirect back to previous page if cancel button is clicked
        if($this->request->getParam("cancelButton")) {
            return $response->withRedirect($this->session->getPrevPage());
        }

        // delete entry if delete button is clicked
        if($this->request->getParam("deleteButton")) {
            if($website) {
                $website->delete();
                $this->flash->addMessage("dismissableSuccess", "Website entry has been deleted");
                return $response->withRedirect($this->session->getPrevPage());
            }
            else {
                $this->flash->addMessage("dismissableFail", "Delete website entry failed");
                return $response->withRedirect($this->session->getPrevPage());
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
$app->get('/all', function($request, $response, $args) {
    $isAdmin = $this->session->isLoggedIn() ? true : false;

    $baseURL = "http://" . $_SERVER["HTTP_HOST"] . BASE_URL;

    $search = trim($request->getParam("search"));
    $sort = trim($request->getParam("sort"));
    $sortOrder = trim($request->getParam("sortOrder"));
    $page = (int)trim($request->getParam("page"));
    $displayItems = trim($request->getParam("displayItems"));
    $validDisplayValues = array(5,10,20,50,100,"all");

    if($search || $sort || $sortOrder) {
        $totalItems = Website::getNumEntriesFromFilter($this->db, $search, $sort, $sortOrder);
    }
    else {
        $totalItems = Website::getTotalEntries($this->db);
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
        $allResults = Website::getByFilter($this->db, $limit, $offset,$search, $sort, $sortOrder);
    }
    else {
        $allResults = Website::getAllSorted($this->db, $limit, $offset);
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
    $page = $sessionPage;

    return $this->view->render($response, 'all.twig', compact("allResults",
                                                              "baseURL",
                                                              "search",
                                                              "sort",
                                                              "sortOrder",
                                                              "pages",
                                                              "displayItems",
                                                              "isAdmin",
                                                              "page"));
})->setName('all');


// Redirect to the specified url
$app->get('/{name}', function($request, $response, $args) {
    $name = $args["name"];

    if($website = Website::getWebsiteByName($this->db, $name)) {
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
$app->post('/', function($request, $response, $args) {
    $url = Website::addScheme(trim($request->getParam('url')));
    $shortName = trim(strtolower($request->getParam('shortName')));
    $formError = false;

    // Make sure user entered URL is a valid format
    if(!Website::isValidURL($url)) {
        $this->flash->addMessage('fail', 'Error: Invalid URL input');
        $formError = true;
    }

    if(!Website::isValidFormattedName($shortName)) {
        // Make sure user entered a custom name in a valid format
        $this->flash->addMessage('fail', 'Error: Invalid formatted custom name');
        $formError = true;
    }   
    else if(Website::isReservedName($shortName)) {
        // Make sure that custom name is not a reserved keyword
        $this->flash->addMessage('fail', "Error: {$shortName} is a reserved keyword");
        $formError = true;
    }
    else if(Website::getWebsiteByName($this->db, $shortName)) {
        // Make sure that custom name is not already used
        $this->flash->addMessage('fail', 'Error: Custom name already exists');
        $formError = true;
    }

    // Create new database entry for the website if no errors with input data
    if(!$formError) {
        $website = new Website($this->db);
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

