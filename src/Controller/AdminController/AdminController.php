<?php
class AdminController extends Controller {
    // GET handler
    // render the admin page
    public function index(){
        $data = $this->getAdminData(false);
        $this->render("admin.php", $data);
    }

    public function showSetup(){
        $data = $this->getAdminData(true);
        $this->render("admin.php", $data);
    }
    
    public function getAdminData(bool $isSetup): array {
        Log::debug("Loading admin page" . ($isSetup ? " (setup mode)" : ""));
        
        return [
            'user' => $this->user,
            'config' => $this->config,
            'isSetup' => $isSetup,
        ];
    }

    public function handleSave(){
        if (!Session::isLoggedIn()){
            header('Location: ' . Util::buildRelativeUrl($this->config->basePath, 'login'));
            exit;
        }

        $result = $this->processSettingsSave($_POST, false);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    public function handleSetup(){
        // for setup, we don't care if they're logged in
        // (because they can't be until setup is complete)
        $result = $this->processSettingsSave($_POST, true);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    public function processSettingsSave(array $postData, bool $isSetup): array {
        $result = ['success' => false, 'errors' => []];
        
        Log::debug("Processing settings save" . ($isSetup ? " (setup mode)" : ""));

        // handle form submission
        if (empty($postData)) {
            Log::warning("Settings save called with no POST data");
            $result['errors'][] = 'No data provided';
            return $result;
        }

        $errors = [];

        // User profile
        $username    = trim($postData['username'] ?? '');
        $displayName = trim($postData['display_name'] ?? '');
        $website     = trim($postData['website'] ?? '');

        // Site settings
        $siteTitle           = trim($postData['site_title'] ?? '');
        $siteDescription     = trim($postData['site_description'] ?? '');
        $baseUrl             = trim($postData['base_url'] ?? '');
        $basePath            = trim($postData['base_path'] ?? '/');
        $itemsPerPage        = (int) ($postData['items_per_page'] ?? 25);
        $strictAccessibility = isset($postData['strict_accessibility']);
        $logLevel            = (int) ($postData['log_level'] ?? 0);

        // Password
        $password        = $postData['password'] ?? '';
        $confirmPassword = $postData['confirm_password'] ?? '';
        
        Log::info("Processing settings for user: $username");

        // Validate user profile
        if (!$username) {
            $errors[] = "Username is required.";
        }
        if (!$displayName) {
            $errors[] = "Display name is required.";
        }
        if (!$baseUrl) {
            $errors[] = "Base URL is required.";
        }
        // Make sure the website looks like a URL and starts with a protocol
        if ($website) {
            if (!filter_var($website, FILTER_VALIDATE_URL)) {
                $errors[] = "Please enter a valid URL (including http:// or https://).";
            } elseif (!preg_match('/^https?:\/\//i', $website)) {
                $errors[] = "URL must start with http:// or https://.";
            }
        }

        // Validate site settings
        if (!$siteTitle) {
            $errors[] = "Site title is required.";
        }
        if (!preg_match('#^/[^?<>:"|\\*]*$#', $basePath)) {
            $errors[] = "Base path must look like a valid URL path (e.g. / or /tkr/).";
        }
        if ($itemsPerPage < 1 || $itemsPerPage > 50) {
            $errors[] = "Items per page must be a number between 1 and 50.";
        }

        // If a password was sent, make sure it matches the confirmation
        if ($password && !($password === $confirmPassword)){
            $errors[] = "Passwords do not match";
        }

        // Log validation results
        if (!empty($errors)) {
            Log::warning("Settings validation failed with " . count($errors) . " errors");
            foreach ($errors as $error) {
                Log::debug("Validation error: $error");
            }
        }

        // Validation complete
        if (empty($errors)) {
            try {
                // Update site settings
                $this->config->siteTitle = $siteTitle;
                $this->config->siteDescription = $siteDescription;
                $this->config->baseUrl = $baseUrl;
                $this->config->basePath = $basePath;
                $this->config->itemsPerPage = $itemsPerPage;
                $this->config->strictAccessibility = $strictAccessibility;
                $this->config->logLevel = $logLevel;

                // Save site settings and reload config from database
                $this->config = $this->config->save();
                Log::info("Site settings updated");

                // Update user profile
                $this->user->username = $username;
                $this->user->displayName = $displayName;
                $this->user->website = $website;

                // Save user profile and reload user from database
                $this->user = $this->user->save();
                Log::info("User profile updated");

                // Update the password if one was sent
                if($password){
                    $this->user->setPassword($password);
                    Log::info("User password updated");
                }

                Session::setFlashMessage('success', 'Settings updated');
                $result['success'] = true;
                
            } catch (Exception $e) {
                Log::error("Failed to save settings: " . $e->getMessage());
                Session::setFlashMessage('error', 'Failed to save settings');
                $result['errors'][] = 'Failed to save settings';
            }
        } else {
            foreach($errors as $error){
                Session::setFlashMessage('error', $error);
            }
            $result['errors'] = $errors;
        }
        
        return $result;
    }
}
