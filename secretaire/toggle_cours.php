<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;
$action = $_GET["action"] ?? null;

if(!$id || !in_array($action, ["activer", "desactiver"])){
    header("Location: liste_cours.php");
    exit;
}

$nouveauStatut = ($action === "activer") ? 1 : 0;

$stmt = $pdo->prepare("
    UPDATE cours
    SET actif = :actif
    WHERE id_cours = :id
");

$stmt->execute([
    "actif" => $nouveauStatut,
    "id" => $id
]);

header("Location: liste_cours.php");
exit;