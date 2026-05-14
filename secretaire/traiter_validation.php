<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$idActivite = $_GET["id"] ?? null;
$decision = $_GET["decision"] ?? null;

if(!$idActivite || !in_array($decision, ["VALIDEE", "REJETEE"])){
    header("Location: validations.php");
    exit;
}

$verif = $pdo->prepare("
    SELECT id_activite, statut_validation
    FROM activite_pedagogique
    WHERE id_activite = ?
    LIMIT 1
");

$verif->execute([$idActivite]);
$activite = $verif->fetch(PDO::FETCH_ASSOC);

if(!$activite || $activite["statut_validation"] !== "EN_ATTENTE"){
    header("Location: validations.php");
    exit;
}

try{

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO validation_activite(
            decision,
            commentaire,
            id_activite,
            id_validateur
        )
        VALUES(
            :decision,
            :commentaire,
            :id_activite,
            :id_validateur
        )
    ");

    $stmt->execute([
        "decision" => $decision,
        "commentaire" => $decision === "VALIDEE" ? "Activité validée." : "Activité rejetée.",
        "id_activite" => $idActivite,
        "id_validateur" => $_SESSION["id_utilisateur"]
    ]);

    $pdo->commit();

}catch(Exception $e){

    $pdo->rollBack();
}

header("Location: validations.php");
exit;