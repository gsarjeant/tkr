<?php
class HomeController extends Controller {
    // GET handler
    // renders the homepage view.
    public function index(){
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        global $config;
        global $user;

        $tickModel = new TickModel();
        $limit = $config->itemsPerPage;
        $offset = ($page - 1) * $limit;
        $ticks = iterator_to_array($tickModel->stream($limit, $offset));

        $view = new HomeView();
        $tickList = $view->renderTicksSection($config->siteDescription, $ticks, $page, $limit);

        $vars = [
            'config'     => $config,
            'user'       => $user,
            'tickList'   => $tickList,
        ];

        $this->render("home.php", $vars);
    }

    // POST handler
    // Saves the tick and reloads the homepage
    public function handleTick(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['new_tick'])) {
            // save the tick
            if (trim($_POST['new_tick'])){
                $tickModel = new TickModel();
                $tickModel->insert($_POST['new_tick']);
            }
        }

        // get the config
        global $config;

        // redirect to the index (will show the latest tick if one was sent)
        header('Location: ' . $config->basePath);
        exit;
    }

}