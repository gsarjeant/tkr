<?php
declare(strict_types=1);

class TickController extends Controller{
    public function index(int $id){
        global $app;
        
        Log::debug("Fetching tick with ID: {$id}");
        
        try {
            $tickModel = new TickModel($app['db'], $app['config']);
            $vars = $tickModel->get($id);
            
            if (empty($vars) || !isset($vars['tick'])) {
                Log::warning("Tick not found for ID: {$id}");
                http_response_code(404);
                echo '<h1>404 - Tick Not Found</h1>';
                return;
            }
            
            Log::info("Successfully loaded tick {$id}: " . substr($vars['tick'], 0, 50) . (strlen($vars['tick']) > 50 ? '...' : ''));
            $this->render('tick.php', $vars);
            
        } catch (Exception $e) {
            Log::error("Failed to load tick {$id}: " . $e->getMessage());
            http_response_code(500);
            echo '<h1>500 - Internal Server Error</h1>';
        }
    }
}