<?php
#require_once __DIR__ . '/../bootstrap.php';

#confirm_setup();

#require_once CLASSES_DIR . '/Config.php';
#require LIB_DIR . '/session.php';

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
?>

<!DOCTYPE html>
<html>
    <head>
        <title><?= $config->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= htmlspecialchars($config->basePath) ?>css/tkr.css?v=<?= time() ?>">
    </head>
    <body>
    <h2>Login</h2>
<?php if ($error): ?>
    <p style="color:red"><?=  htmlspecialchars($error) ?></p>
<?php endif; ?>
    <form method="post" action="<?= $config->basePath ?>login.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Login</button>
    </form>
    </body>
</html>
