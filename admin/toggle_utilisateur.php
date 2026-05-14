<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "ADMINISTRATEUR"){
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET["id"] ?? null;
$action = $_GET["action"] ?? null;

if(!$id || !in_array($action, ["activer", "desactiver"])){
    header("Location: liste_utilisateurs.php");
    exit;
}

if((int)$id === (int)$_SESSION["id_utilisateur"]){
    header("Location: liste_utilisateurs.php");
    exit;
}

$nouveauStatut = ($action === "activer") ? 1 : 0;

$stmt = $pdo->prepare("UPDATE utilisateur SET actif = :actif WHERE id_utilisateur = :id");
$stmt->execute([
    "actif" => $nouveauStatut,
    "id" => $id
]);

header("Location: liste_utilisateurs.php");
exit;