<?php
declare(strict_types=1);

class AuthController extends Controller {
    function showLogin(?string $error = null){
        global $app;

        $csrf_token = Session::getCsrfToken();

        $vars = [
            'settings' => $app['settings'],
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

            try {
                $userModel = new UserModel($app['db']);
                $user = $userModel->getByUsername($username);

                if ($user && password_verify($password, $user['password_hash'])) {
                    Log::info("Successful login for {$username}");

                    try {
                        Session::newLoginSession($user);
                        header('Location: ' . Util::buildRelativeUrl($app['settings']->basePath));
                        exit;
                    } catch (Exception $e) {
                        Log::error("Failed to create login session for {$username}: " . $e->getMessage());
                        Session::setFlashMessage('error', 'Login failed - session error');
                        header('Location: ' . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                } else {
                    Log::warning("Failed login for {$username}");

                    // Set a flash message and reload the login page
                    Session::setFlashMessage('error', 'Invalid username or password');
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } catch (Exception $e) {
                Log::error("Database error during login for {$username}: " . $e->getMessage());
                Session::setFlashMessage('error', 'Login temporarily unavailable');
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }

    function handleLogout(){
        global $app;

        Log::info("Logout from user " . $_SESSION['username']);
        Session::end();

        header('Location: ' . Util::buildRelativeUrl($app['settings']->basePath));
        exit;
    }
}