<?php
declare(strict_types=1);

class HomeController extends Controller {
    // GET handler
    // renders the homepage view.
    public function index(){
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $data = $this->getHomeData($page);
        $this->render("home.php", $data);
    }

    public function getHomeData(int $page): array {
        global $app;

        Log::debug("Loading home page $page");

        $tickModel = new TickModel($app['db'], $app['settings']);
        $limit = $app['settings']->itemsPerPage;
        $offset = ($page - 1) * $limit;
        $ticks = $tickModel->getPage($limit, $offset);

        $view = new TicksView($app['settings'], $ticks, $page);
        $tickList = $view->getHtml();

        Log::info("Home page loaded with " . count($ticks) . " ticks");

        return [
            'settings'     => $app['settings'],
            'user'       => $app['user'],
            'tickList'   => $tickList,
        ];
    }

    // POST handler
    // Saves the tick and reloads the homepage
    public function handleTick(){
        global $app;

        $result = $this->processTick($_POST);

        // redirect to the index (will show the latest tick if one was sent)
        header('Location: ' . Util::buildRelativeUrl($app['settings']->basePath));
        exit;
    }

    public function processTick(array $postData): array {
        global $app;

        $result = ['success' => false, 'message' => ''];

        if (!isset($postData['new_tick'])) {
            Log::warning("Tick submission without new_tick field");
            $result['message'] = 'No tick content provided';
            return $result;
        }

        $tickContent = trim($postData['new_tick']);
        if (empty($tickContent)) {
            Log::debug("Empty tick submission ignored");
            $result['message'] = 'Empty tick ignored';
            return $result;
        }

        try {
            $tickModel = new TickModel($app['db'], $app['settings']);
            $tickModel->insert($tickContent);
            Log::info("New tick created: " . substr($tickContent, 0, 50) . (strlen($tickContent) > 50 ? '...' : ''));
            $result['success'] = true;
            $result['message'] = 'Tick saved successfully';
        } catch (Exception $e) {
            Log::error("Failed to save tick: " . $e->getMessage());
            $result['message'] = 'Failed to save tick';
        }

        return $result;
    }

}