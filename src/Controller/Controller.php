<?php
class Controller {
    // Renders the requested template inside templates/main/php
    protected function render(string $childTemplateFile, array $vars = []) {
        $templatePath = TEMPLATES_DIR . "/main.php";
        $childTemplatePath = TEMPLATES_DIR . "/partials/" . $childTemplateFile;

        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template not found: $templatePath");
        }

        if (!file_exists($childTemplatePath)) {
            throw new RuntimeException("Template not found: $childTemplatePath");
        }

        // always check for flash messages and add them if they exist
        if (Session::hasFlashMessages()){
            $flashMessages = Session::getFlashMessages();
            $flashView = new FlashView();
            $flashSection = $flashView->renderFlashSection($flashMessages);

            $vars['flashSection'] = $flashSection;
        }

        extract($vars, EXTR_SKIP);
        include $templatePath;
    }  
}