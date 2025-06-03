<?php
class FeedController {
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
        echo render_template(TEMPLATES_DIR . "/feed/rss.php", $this->vars);
    }

    public function atom(){
        echo render_template(TEMPLATES_DIR . "/feed/atom.php", $this->vars);
    }
}
