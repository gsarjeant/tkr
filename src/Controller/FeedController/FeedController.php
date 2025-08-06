<?php
declare(strict_types=1);

class FeedController extends Controller {
    private $ticks;

    public function __construct() {
        global $app;
        
        try {
            $tickModel = new TickModel($app['db'], $app['config']);
            $this->ticks = $tickModel->getPage($app['config']->itemsPerPage);
            Log::debug("Loaded " . count($this->ticks) . " ticks for feeds");
        } catch (Exception $e) {
            Log::error("Failed to load ticks for feed: " . $e->getMessage());
            // Provide empty feed rather than crashing - RSS readers can handle this
            $this->ticks = [];
        }
    }

    public function rss(){
        global $app;
        
        $generator = new RssGenerator($app['config'], $this->ticks);
        Log::debug("Generating RSS feed with " . count($this->ticks) . " ticks");

        header('Content-Type: ' . $generator->getContentType());
        echo $generator->generate();
    }

    public function atom(){
        global $app;
        
        $generator = new AtomGenerator($app['config'], $this->ticks);
        Log::debug("Generating Atom feed with " . count($this->ticks) . " ticks");

        header('Content-Type: ' . $generator->getContentType());
        echo $generator->generate();
    }
}
