<?php
class AuthController extends Controller {
    function showLogin(?string $error = null){
        global $app;
        
        $csrf_token = Session::getCsrfToken();

        $vars = [
            'config' => $app['config'],
            'csrf_token' => $csrf_token,
            'error' => $error,
        ];

        $this->render('login.php', $vars);
    }

    function handleLogin(){
        global $app;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            Log::debug("Login attempt for user {$username}");

            $userModel = new UserModel($app['db']);
            $user = $userModel->getByUsername($username);

            //if ($user && password_verify($password, $user['password_hash'])) {
            if ($user && password_verify($password, $user['password_hash'])) {
                Log::info("Successful login for {$username}");

                Session::newLoginSession($user);
                header('Location: ' . Util::buildRelativeUrl($app['config']->basePath));
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
        global $app;
        
        Log::info("Logout from user " . $_SESSION['username']);
        Session::end();

        header('Location: ' . Util::buildRelativeUrl($app['config']->basePath));
        exit;
    }
}