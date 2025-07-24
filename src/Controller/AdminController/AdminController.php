<?php
class AdminController extends Controller {
    // GET handler
    // render the admin page
    public function index(){
        global $config;
        global $user;

        $vars = [
            'user' => $user,
            'config' => $config,
            'isSetup' => false,
        ];

        $this->render("admin.php", $vars);
    }

    public function showSetup(){
        global $config;
        global $user;

        $vars = [
            'user' => $user,
            'config' => $config,
            'isSetup' => true,
        ];

        $this->render("admin.php", $vars);
    }

    public function handleSave(){
        if (!Session::isLoggedIn()){
            header('Location: ' . $config->basePath . '/login');
            exit;
        }

        $this->save();
    }

    public function handleSetup(){
        // for setup, we don't care if they're logged in
        // (because they can't be until setup is complete)
        $this->save();
    }

    // save updated settings
    private function save(){
        global $config;
        global $user;

        // handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];

            // User profile
            $username    = trim($_POST['username'] ?? '');
            $displayName = trim($_POST['display_name'] ?? '');
            $website     = trim($_POST['website'] ?? '');

            // Site settings
            $siteTitle           = trim($_POST['site_title']) ?? '';
            $siteDescription     = trim($_POST['site_description']) ?? '';
            $baseUrl             = trim($_POST['base_url'] ?? '');
            $basePath            = trim($_POST['base_path'] ?? '/');
            $itemsPerPage        = (int) ($_POST['items_per_page'] ?? 25);
            $strictAccessibility = isset($_POST['strict_accessibility']);
            $showTickMood        = isset($_POST['show_tick_mood']);

            // Password
            $password        = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

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

            // Validation complete
            if (empty($errors)) {
                // Update site settings
                $config->siteTitle = $siteTitle;
                $config->siteDescription = $siteDescription;
                $config->baseUrl = $baseUrl;
                $config->basePath = $basePath;
                $config->itemsPerPage = $itemsPerPage;
                $config->strictAccessibility = $strictAccessibility;

                // Save site settings and reload config from database
                // TODO - raise and handle exception on failure
                $config = $config->save();

                // Update user profile
                $user->username = $username;
                $user->displayName = $displayName;
                $user->website = $website;

                // Save user profile and reload user from database
                // TODO - raise and handle exception on failure
                $user = $user->save();

                // Update the password if one was sent
                // TODO - raise and handle exception on failure
                if($password){
                    $user->set_password($password);
                }

                Session::setFlashMessage('success', 'Settings updated');
            } else {
                foreach($errors as $error){
                    Session::setFlashMessage('error', $error);
                }
            }
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
