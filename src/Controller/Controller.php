<?php
class Controller {
    protected function render(string $templateFile, array $vars = []) {
        $templatePath = TEMPLATES_DIR . "/" . $templateFile;

        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template not found: $templatePath");
        }

        // PHP scoping
        // extract the variables from $vars into the local scope.
        extract($vars, EXTR_SKIP);
        include $templatePath;
    }  
}