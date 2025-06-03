<?php
class AdminController extends Controller {
    // GET handler
    // render the admin page
    public function index(){
        $config = Config::load();
        $user = USER::load();

        $vars = [
            'user' => $user,
            'config' => $config,
        ];


        $this->render("admin.php", $vars);
    }

    // POST handler
    // save updated settings
    public function save(){
        $isLoggedIn = isset($_SESSION['user_id']);
        if (!$isLoggedIn){
            header('Location: ' . $config->basePath . 'login.php');
            exit;
        }

        $config = Config::load();
        $user = User::load();

        // handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
        
            // User profile
            $username        = trim($_POST['username'] ?? '');
            $displayName     = trim($_POST['display_name'] ?? '');
            $about           = trim($_POST['about'] ?? '');
            $website         = trim($_POST['website'] ?? '');
        
            // Site settings
            $siteTitle       = trim($_POST['site_title']) ?? '';
            $siteDescription = trim($_POST['site_description']) ?? '';
            $basePath        = trim($_POST['base_path'] ?? '/');
            $itemsPerPage    = (int) ($_POST['items_per_page'] ?? 25);
            // Password
            // TODO - Make sure I really shouldn't trim these
            //        (I'm assuming there may be people who end their password with a space character)
            $password                = $_POST['password'] ?? '';
            $confirmPassword         = $_POST['confirm_password'] ?? '';
        
            // Validate user profile
            if (!$username) {
                $errors[] = "Username is required.";
            }
            if (!$displayName) {
                $errors[] = "Display name is required.";
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
            if ($password && !($password = $confirmPassword)){
                $errors[] = "Passwords do not match";
            }
        
            // TODO: Actually handle errors
            if (empty($errors)) {
                // Update site settings
                $config->siteTitle = $siteTitle;
                $config->siteDescription = $siteDescription;
                $config->basePath = $basePath;
                $config->itemsPerPage = $itemsPerPage;
            
                // Save site settings and reload config from database
                $config = $config->save();
            
                // Update user profile
                $user->username = $username;
                $user->displayName = $displayName;
                $user->about = $about;
                $user->website = $website;
            
                // Save user profile and reload user from database
                $user = $user->save();
            
                // Update the password if one was sent
                if($password){
                    $user->set_password($password);
                }
            }
        }

        header('Location: ' . $config->basePath . '/admin');
        exit;
    }
}