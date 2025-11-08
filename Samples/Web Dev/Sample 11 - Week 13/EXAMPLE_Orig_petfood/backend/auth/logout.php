<?php
session_start();
session_unset();
session_destroy();
header('Location: /EXAMPLE_Orig_petfood/backend/auth/login.php');
exit;
?>
