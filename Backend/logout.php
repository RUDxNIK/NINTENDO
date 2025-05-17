<?php // N1ntendo
session_start();
session_destroy();
header('Location: index.php');
exit;
?>