<?php
class FeedController extends Controller {
    private Config $config;
    private array $ticks;
    private array $vars;

    public function __construct(){
        $this->config = Config::load();
        $this->ticks = iterator_to_array(Tick::streamTicks($this->config->itemsPerPage));
        $this->vars = [
            'config' => $this->config,
            'ticks' => $this->ticks,
        ];
    }

    public function rss(){
        $this->render("feed/rss.php", $this->vars);
    }

    public function atom(){
        $this->render("feed/atom.php", $this->vars);
    }
}
