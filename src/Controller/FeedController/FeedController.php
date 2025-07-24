<?php
class FeedController extends Controller {
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
        $config = ConfigModel::load();
        $tickModel = new TickModel();
        $ticks = iterator_to_array($tickModel->stream($config->itemsPerPage));

        $this->vars = [
            'config' => $config,
            'ticks' => $ticks,
        ];
    }

    public function rss(){
        $this->render("feed/rss.php", $this->vars);
    }

    public function atom(){
        $this->render("feed/atom.php", $this->vars);
    }
}
