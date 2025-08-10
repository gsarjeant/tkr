<?php
declare(strict_types=1);

class CssController extends Controller {
    public function index() {
        global $app;
        $cssModel = new CssModel($app['db']);
        $customCss = $cssModel->getAll();

        $vars = [
            'user' => $app['user'],
            'settings' => $app['settings'],
            'customCss' => $customCss,
        ];

        $this->render("css.php", $vars);
    }

    public function serveCustomCss(string $baseFilename){
        global $app;
        $cssModel = new CssModel($app['db']);
        $filename = "$baseFilename.css";
        Log::debug("Attempting to serve custom css: {$filename}");

        // Make sure the file exists in the database
        $cssRow = $cssModel->getByFilename($filename);
        if (!$cssRow){
            http_response_code(404);
            Log::error("Custom css file not in database: {$filename}");
            exit;
        }

        // Make sure the file exists on the filesystem and is readable
        $filePath = CSS_UPLOAD_DIR . "/$filename";
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            Log::error("Custom css file not found or not readable: $filePath");
            exit;
        }

        // Make sure the file has a .css extension
        $ext = strToLower(pathinfo($filename, PATHINFO_EXTENSION));
        if($ext != 'css'){
            http_response_code(400);
            Log::error("Invalid file type requested: $ext");
            exit;
        }

        // If we get here, serve the file
        Log::debug("Serving custom css: {$filename}");
        header('Content-type: text/css');
        header('Cache-control: public, max-age=3600');
        readfile($filePath);
        exit;
    }

    public function serveDefaultCss(){
        $filePath = PUBLIC_DIR . '/css/default.css';
        Log::debug("Serving default css: {$filePath}");

        // Make sure the default CSS file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            Log::error("Default CSS file not found");
            exit;
        }

        //  Serve the file
        header('Content-type: text/css');
        header('Cache-control: public, max-age=3600');
        readfile($filePath);
        exit;
    }

    public function handlePost() {
        switch ($_POST['action']) {
        case 'upload':
            $this->handleUpload();
            break;
        case 'set_theme':
            $this->handleSetTheme();
            break;
        case 'delete':
            $this->handleDelete();
            break;
        }

        // redirect after handling to avoid resubmitting form
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    public function handleDelete(): void{
        global $app;

        // Don't try to delete the default theme.
        if (!$_POST['selectCssFile']){
            http_response_code(400);
            exit("Cannot delete default theme");
        }

        // Get the data for the selected CSS file
        $cssId = $_POST['selectCssFile'];
        $cssModel = new CssModel($app['db']);
        $cssRow = $cssModel->getById($cssId);

        // exit if the requested file isn't in the database
        if (!$cssRow){
            http_response_code(400);
            exit("No entry found for css id $cssId");
        }

        // get the filename
        $cssFilename = $cssRow["filename"];

        // delete the file from the database
        if (!$cssModel->delete($cssId)){
            http_response_code(400);
            exit("Error deleting theme");
        }

        // Build the full path to the file
        $filePath = CSS_UPLOAD_DIR . "/$cssFilename";

        // Exit if the file doesn't exist or isn't readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            exit("CSS file not found: $filePath");
        }

        // Delete the file
        if (!unlink($filePath)){
            http_response_code(400);
            exit("Error deleting file: $filePath");
        }

        // Set the theme back to default
        try {
            $app['settings']->cssId = null;
            $app['settings'] = $app['settings']->save();
            Session::setFlashMessage('success', 'Theme ' . $cssFilename . ' deleted.');
        } catch (Exception $e) {
            Log::error("Failed to update config after deleting theme: " . $e->getMessage());
            Session::setFlashMessage('error', 'Theme deleted but failed to update settings');
        }
    }

    private function handleSetTheme() {
        global $app;

        try {
            if ($_POST['selectCssFile']){
                // Set custom theme
                $app['settings']->cssId = (int)($_POST['selectCssFile']);
            } else {
                // Set default theme
                $app['settings']->cssId = null;
            }

            // Update the site theme
            $app['settings'] = $app['settings']->save();
            Session::setFlashMessage('success', 'Theme applied.');
        } catch (Exception $e) {
            Log::error("Failed to save theme setting: " . $e->getMessage());
            Session::setFlashMessage('error', 'Failed to apply theme');
        }
    }

    private function handleUpload() {
        try {
            // Check if file was uploaded
            if (!isset($_FILES['uploadCssFile']) || $_FILES['uploadCssFile']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error occurred');
            }

            $file = $_FILES['uploadCssFile'];
            $description = $_POST['description'] ?? '';

            // Validate file extension
            $filename = $file['name'];
            $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if ($fileExtension !== 'css') {
                throw new Exception('File must have a .css extension');
            }

            // Validate file size (1MB = 1048576 bytes)
            $maxSize = 1048576; // 1MB
            if ($file['size'] > $maxSize) {
                throw new Exception('File size must not exceed 1MB');
            }

            // Read and validate CSS content
            $fileContent = file_get_contents($file['tmp_name']);
            if ($fileContent === false) {
                throw new Exception('Unable to read uploaded file');
            }

            // Validate CSS content
            $this->validateCssContent($fileContent);

            // Scan for malicious content
            $this->scanForMaliciousContent($fileContent, $filename);

            // Generate safe filename
            $safeFilename = $this->generateSafeFileName($filename);
            $uploadPath = CSS_UPLOAD_DIR . '/' . $safeFilename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to save uploaded file');
            }

            // Add upload to database
            global $app;
            $cssModel = new CssModel($app['db']);
            $cssModel->save($safeFilename, $description);

            // Set success flash message
            Session::setFlashMessage('success', 'Theme uploaded as ' . $safeFilename);

        } catch (Exception $e) {
            // Set error flash message
            // Todo - don't do a global catch like this. Subclass Exception.
            Session::setFlashMessage('error', 'Upload exception: ' . $e->getMessage());
        }
    }

    private function validateCssContent($content) {
        // Remove comments
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);

        // Basic CSS validation - check for balanced braces
        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');

        if ($openBraces !== $closeBraces) {
            throw new Exception('Invalid CSS: Unbalanced braces detected');
        }

        // Check for basic CSS structure (selector { property: value; })
        if (!preg_match('/[^{}]+\{[^{}]*\}/', $content) && !empty(trim($content))) {
            // Allow empty files or files with only @charset, @import, etc.
            if (!preg_match('/^\s*(@charset|@import|@media|:root)/i', trim($content))) {
                throw new Exception('Invalid CSS: No valid CSS rules found');
            }
        }
    }

    private function scanForMaliciousContent($content, $fileName) {
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/javascript:/i',
            '/vbscript:/i',
            '/data:.*base64/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
            '/behavior\s*:/i',
            '/-moz-binding/i',
            '/\\\00/i', // null bytes
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw new Exception('Malicious content detected in CSS file');
            }
        }

        // Check filename for suspicious characters
        if (preg_match('/[<>:"\/\\|?*\x00-\x1f]/', $fileName)) {
            throw new Exception('Filename contains invalid characters');
        }

        // Check for excessively long lines (potential DoS)
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (strlen($line) > 10000) {  // 10KB per line limit
                throw new Exception('CSS file contains excessively long lines');
            }
        }

        // Check for excessive nesting or selectors (potential DoS)
        if (substr_count($content, '{') > 1000) {
            throw new Exception('CSS file contains too many rules');
        }
    }

    private function generateSafeFileName($originalName) {
        // Remove path information and dangerous characters
        $fileName = basename($originalName);
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        return $fileName;
    }

}
