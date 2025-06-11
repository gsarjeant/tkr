<?php
class FeedController extends Controller {
    private ConfigModel $config;
    private array $ticks;
    private array $vars;

    protected function render(string $templateFile, array $vars = []) {
        $templatePath = TEMPLATES_DIR . "/" . $templateFile;

        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template not found: $templatePath");
        }

        extract($vars, EXTR_SKIP);
        include $templatePath;
    }

    public function __construct(){
        $this->config = ConfigModel::load();
        $this->ticks = iterator_to_array(TickModel::streamTicks($this->config->itemsPerPage));
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
