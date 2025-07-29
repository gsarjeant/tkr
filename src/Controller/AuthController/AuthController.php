<?php
class AuthController extends Controller {
    function showLogin(?string $error = null){
        global $config;
        $csrf_token = Session::getCsrfToken();

        $vars = [
            'config' => $config,
            'csrf_token' => $csrf_token,
            'error' => $error,
        ];

        $this->render('login.php', $vars);
    }

    function handleLogin(){
        global $config;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            Log::debug("Login attempt for user {$username}");

            $userModel = new UserModel();
            $user = $userModel->getByUsername($username);

            //if ($user && password_verify($password, $user['password_hash'])) {
            if ($user && password_verify($password, $user['password_hash'])) {
                Log::info("Successful login for {$username}");

                Session::newLoginSession($user);
                header('Location: ' . $config->basePath);
                exit;
            } else {
                Log::warning("Failed login for {$username}");

                // Set a flash message and reload the login page
                Session::setFlashMessage('error', 'Invalid username or password');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }

    function handleLogout(){
        Log::info("Logout from user " . $_SESSION['username']);
        Session::end();

        global $config;
        header('Location: ' . $config->basePath);
        exit;
    }
}