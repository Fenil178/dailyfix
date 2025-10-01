<?php
// Clear the user ID cookie
setcookie("encrypted_user_id", "", time() - 3600, "/");

// Clear the user role cookie
setcookie("encrypted_user_role", "", time() - 3600, "/");

// Clear the user name cookie
setcookie("encrypted_user_name", "", time() - 3600, "/");

// Clear the profile image cookie
setcookie("encrypted_profile_image", "", time() - 3600, "/");

// Redirect the user to the login page after clearing the cookies.
header("Location: login.php");

// It's a best practice to call exit() after a header redirect to ensure no further code is executed.
exit();
?>