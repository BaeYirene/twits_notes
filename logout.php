<?php
session_start();
session_destroy();

// Hapus cookies
setcookie("user_id", "", time() - 3600, "/");

header("Location: login.php");
exit();
?>
