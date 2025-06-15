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

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
 
            // TODO: move into user model
            global $db;
            $stmt = $db->prepare("SELECT id, username, password_hash FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                // TODO: move into session.php
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                Session::generateCsrfToken(true);
                header('Location: ' . $config->basePath);
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        }
    }

    function handleLogout(){
        Session::end();
        global $config;
        header('Location: ' . $config->basePath);
        exit;
    }
}