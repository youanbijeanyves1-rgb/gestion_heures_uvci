<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: ../auth/login.php");
    exit;
}