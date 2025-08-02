<?php
class FeedController extends Controller {
    private $ticks;

    public function __construct() {
        global $app;
        
        $tickModel = new TickModel($app['db'], $app['config']);
        $this->ticks = $tickModel->getPage($app['config']->itemsPerPage);

        Log::debug("Loaded " . count($this->ticks) . " ticks for feeds");
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
