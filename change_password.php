<?php
require_once __DIR__ . '/config/database.php';

// EDIT THIS LINE TO SET YOUR NEW PASSWORD
$newPassword = 'Rajat@19941996_NEW';

try {
    $pdo = createDatabaseConnection();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE role = 'admin' LIMIT 1");
    $stmt->execute([$hash]);

    if ($stmt->rowCount() > 0) {
        echo "<h1>Password updated successfully!</h1>";
        echo "<p>Your new password is set to: <strong>" . htmlspecialchars($newPassword) . "</strong></p>";
        echo "<br><br><p style='color:red;'><strong>CRITICAL:</strong> Please delete this file (`change_password.php`) from your File Manager immediately for security reasons.</p>";
        echo "<br><a href='/admin/login'>Go to Login Page</a>";
    } else {
        echo "<h1>Failed to update password.</h1><p>Admin user not found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<h1>Database Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
