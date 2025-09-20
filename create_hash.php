<?php
// Set the password you want to use for your admin account here
$my_password = 'admin123';

// Generate the secure hash
$hashed_password = password_hash($my_password, PASSWORD_DEFAULT);

// Display the hash
echo "Your new password is: <strong>" . htmlspecialchars($my_password) . "</strong><br><br>";
echo "Copy this entire line into your SQL query:<br>";
echo "<textarea rows='3' style='width: 100%;'>" . htmlspecialchars($hashed_password) . "</textarea>";
?>