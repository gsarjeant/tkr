<?php
class FeedController extends Controller {
    private $config;
    private $ticks;

    public function __construct(){
        $this->config = ConfigModel::load();
        $tickModel = new TickModel();
        $this->ticks = $tickModel->getPage($this->config->itemsPerPage);

        Log::debug("Loaded " . count($this->ticks) . " ticks for feeds");
    }

    public function rss(){
        $generator = new RssGenerator($this->config, $this->ticks);
        Log::debug("Generating RSS feed with " . count($this->ticks) . " ticks");

        header('Content-Type: ' . $generator->getContentType());
        echo $generator->generate();
    }

    public function atom(){
        $generator = new AtomGenerator($this->config, $this->ticks);
        Log::debug("Generating Atom feed with " . count($this->ticks) . " ticks");

        header('Content-Type: ' . $generator->getContentType());
        echo $generator->generate();
    }
}
