<?php
class FeedController extends Controller {
    private $ticks;

    public function __construct(PDO $db, ConfigModel $config, UserModel $user){
        parent::__construct($db, $config, $user);
        
        $tickModel = new TickModel($db, $config);
        $this->ticks = $tickModel->getPage($config->itemsPerPage);

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
