<?php
class HomeController extends Controller {
    // GET handler
    // renders the homepage view.
    public function index(){
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $config = ConfigModel::load();
        $user = UserModel::load();

        $limit = $config->itemsPerPage;
        $offset = ($page - 1) * $limit;
        $ticks = iterator_to_array(TickModel::streamTicks($limit, $offset));

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
        if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['tick'])) {
            // ensure that the session is valid before proceeding
            if (!Session::validateCsrfToken($_POST['csrf_token'])) {
                // TODO: maybe redirect to login? Maybe with tick preserved?
                die('Invalid CSRF token');
            }

            // save the tick
            if (trim($_POST['tick'])){
                TickModel::save($_POST['tick']);
            }
        }

        // get the config
        $config = ConfigModel::load();

        // redirect to the index (will show the latest tick if one was sent)
        header('Location: ' . $config->basePath);
        exit;
    }

}