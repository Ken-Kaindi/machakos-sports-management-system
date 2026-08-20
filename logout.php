<?php
require_once 'includes/auth.php';
logout();
header("Location: login.php?logout=success");
exit();
?>