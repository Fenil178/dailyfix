<?php
/**
 * logout.php
 * Clears user session cookies and redirects to the login page.
 */

// To log the user out, we set the expiration time of each cookie to a time in the past.
// This tells the browser to immediately delete them.
// The path "/" ensures the cookies are cleared for the entire site.

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