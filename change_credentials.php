<?php
require_once __DIR__ . '/../config.php';

// Ensure admin is logged in using the project session keys
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$adminId = $_SESSION['admin_user_id'] ?? null;
if (!$adminId) {
    header('Location: login.php');
    exit;
}

$pdo = getPDO();
$errors = [];
$message = '';
$field_errors = []; // For field-specific errors

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Invalid request token.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_username = trim($_POST['new_username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Fetch current admin row
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        if (!$admin) {
            $errors[] = 'Admin account not found.';
        } else {
            // Verify current password with support for legacy plaintext storage
            $storedHash = $admin['password_hash'] ?? null;
            $legacyPlain = $admin['password'] ?? null;
            $verified = false;
            if ($storedHash && password_verify($current_password, $storedHash)) {
                $verified = true;
            } elseif ($legacyPlain !== null && hash_equals($legacyPlain, $current_password)) {
                $verified = true;
                // migrate to password_hash column
                try {
                    $hasCol = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'password_hash'")->fetch();
                    if (!$hasCol) {
                        $pdo->exec("ALTER TABLE admin_users ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL");
                    }
                    $rehash = password_hash($current_password, PASSWORD_DEFAULT);
                    $u = $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
                    $u->execute([$rehash, $adminId]);
                } catch (Exception $e) {
                    error_log('Password migration failed: ' . $e->getMessage());
                }
            } else {
                $field_errors['current_password'] = 'Current password is incorrect.';
            }

            // Only proceed with other validations if current password is correct
            if (empty($field_errors['current_password'])) {
                // Username uniqueness check
                if ($new_username !== '' && $new_username !== $admin['username']) {
                    $u = $pdo->prepare('SELECT id FROM admin_users WHERE username = ? AND id != ? LIMIT 1');
                    $u->execute([$new_username, $adminId]);
                    if ($u->fetch()) {
                        $field_errors['new_username'] = 'Username already taken.';
                    }
                }

                // Password validation
                if ($new_password !== '') {
                    if ($new_password !== $confirm_password) {
                        $field_errors['confirm_password'] = 'New passwords do not match.';
                    }
                    if (strlen($new_password) < 8) {
                        $field_errors['new_password'] = 'New password must be at least 8 characters.';
                    }
                }

                // If no errors, perform updates
                if (empty($errors) && empty($field_errors)) {
                    if ($new_username !== '' && $new_username !== $admin['username']) {
                        $stmt = $pdo->prepare('UPDATE admin_users SET username = ? WHERE id = ?');
                        $stmt->execute([$new_username, $adminId]);
                        // Update session if you store username in session elsewhere
                        $_SESSION['admin_username'] = $new_username;
                        if (function_exists('admin_log')) admin_log($adminId, 'profile_update', json_encode(['username'=>$new_username]));
                    }

                    if ($new_password !== '') {
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
                        $stmt->execute([$hash, $adminId]);
                        if (function_exists('admin_log')) admin_log($adminId, 'password_change', null);
                        // Force re-login after password change
                        session_unset();
                        session_destroy();
                        // Start new session to show message after redirect
                        session_start();
                        $_SESSION['password_changed'] = true;
                        header('Location: login.php');
                        exit;
                    }

                    $message = 'Credentials updated successfully.';
                    
                    // Regenerate CSRF token after successful update
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
                }
            }
        }
    }
}

// Fetch current username for display
$stmt = $pdo->prepare('SELECT username FROM admin_users WHERE id = ? LIMIT 1');
$stmt->execute([$adminId]);
$current = $stmt->fetch();
$current_username = $current['username'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Change Credentials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .is-invalid { border-color: #dc3545; }
        .invalid-feedback { display: block; }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Change Credentials</h4>
                    <p class="text-muted">Current username: <strong><?php echo htmlspecialchars($current_username); ?></strong></p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password *</label>
                            <input name="current_password" type="password" class="form-control <?php echo isset($field_errors['current_password']) ? 'is-invalid' : ''; ?>" required>
                            <?php if (isset($field_errors['current_password'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['current_password']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Username (optional)</label>
                            <input name="new_username" type="text" class="form-control <?php echo isset($field_errors['new_username']) ? 'is-invalid' : ''; ?>" placeholder="Leave blank to keep current" value="<?php echo htmlspecialchars($_POST['new_username'] ?? ''); ?>">
                            <?php if (isset($field_errors['new_username'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['new_username']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password (optional)</label>
                            <input name="new_password" type="password" class="form-control <?php echo isset($field_errors['new_password']) ? 'is-invalid' : ''; ?>" placeholder="Leave blank to keep current">
                            <?php if (isset($field_errors['new_password'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['new_password']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input name="confirm_password" type="password" class="form-control <?php echo isset($field_errors['confirm_password']) ? 'is-invalid' : ''; ?>">
                            <?php if (isset($field_errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($field_errors['confirm_password']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Credentials</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>