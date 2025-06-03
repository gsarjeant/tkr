<?php
class LoginController {
    function index(?string $error = null){
        $config = Config::load();
        $csrf_token = $_SESSION['csrf_token'];

        $vars = [
            'config' => $config,
            'csrf_token' => $csrf_token,
            'error' => $error,
        ];

        echo render_template(TEMPLATES_DIR . '/login.php', $vars);
    }

    function login(){
        $config = Config::load();

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validateCsrfToken($_POST['csrf_token'])) {
                die('Invalid CSRF token');
            }
        
            // TODO: move into session.php
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
        
            $db = get_db();
            $stmt = $db->prepare("SELECT id, username, password_hash FROM user WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: ' . $config->basePath);
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        }

        $csrf_token = generateCsrfToken();
    }
}