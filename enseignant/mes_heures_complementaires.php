<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "ENSEIGNANT"){
    header("Location: ../auth/login.php");
    exit;
}

$idUtilisateur = $_SESSION["id_utilisateur"];

$dateDebut = $_GET["date_debut"] ?? "";
$dateFin   = $_GET["date_fin"] ?? "";

$stmtEns = $pdo->prepare("
    SELECT 
        e.id_enseignant,
        e.nom,
        e.prenoms,
        e.statut,
        g.libelle_grade,
        g.charge_statutaire
    FROM enseignant e
    LEFT JOIN grade g ON g.id_grade = e.id_grade
    WHERE e.id_utilisateur = ?
    LIMIT 1
");
$stmtEns->execute([$idUtilisateur]);
$enseignant = $stmtEns->fetch(PDO::FETCH_ASSOC);

if(!$enseignant){
    die("Aucun profil enseignant n’est lié à ce compte utilisateur.");
}

$sqlVolume = "
    SELECT 
        COALESCE(SUM(volume_horaire_calcule), 0) AS volume_total
    FROM activite_pedagogique
    WHERE id_enseignant = :id_enseignant
      AND statut_validation = 'VALIDEE'
";

$params = [
    "id_enseignant" => $enseignant["id_enseignant"]
];

if($dateDebut !== "" && $dateFin !== ""){
    $sqlVolume .= "
        AND DATE(date_saisie) BETWEEN :date_debut AND :date_fin
    ";

    $params["date_debut"] = $dateDebut;
    $params["date_fin"] = $dateFin;
}

$stmtVolume = $pdo->prepare($sqlVolume);
$stmtVolume->execute($params);
$resultat = $stmtVolume->fetch(PDO::FETCH_ASSOC);

$volumeTotal = (float)$resultat["volume_total"];
$chargeStatutaire = (float)($enseignant["charge_statutaire"] ?? 0);

if($enseignant["statut"] === "PERMANENT"){
    $heuresComplementaires = max(0, $volumeTotal - $chargeStatutaire);
    $messageHC = number_format($heuresComplementaires, 2, ',', ' ') . " h";
}else{
    $heuresComplementaires = null;
    $messageHC = "Non concerné";
}

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_enseignant.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Mes heures complémentaires</h1>
                <p>Suivi des heures complémentaires par période.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>ENSEIGNANT</small>
            </div>
        </header>

        <section class="content">

            <div class="welcome-card">
                <h2>
                    <?= htmlspecialchars($enseignant["nom"] . " " . $enseignant["prenoms"]) ?>
                </h2>

                <p>
                    Grade :
                    <strong><?= htmlspecialchars($enseignant["libelle_grade"] ?? "Non défini") ?></strong>
                    —
                    Statut :
                    <strong><?= htmlspecialchars($enseignant["statut"]) ?></strong>
                </p>
            </div>

            <div class="filter-card">
                <form method="GET" class="filter-form">

                    <div class="form-group">
                        <label>Date début</label>
                        <input 
                            type="date" 
                            name="date_debut"
                            value="<?= htmlspecialchars($dateDebut) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Date fin</label>
                        <input 
                            type="date" 
                            name="date_fin"
                            value="<?= htmlspecialchars($dateFin) ?>"
                        >
                    </div>

                    <button type="submit" class="btn-primary">
                        Filtrer
                    </button>

                    <a href="mes_heures_complementaires.php" class="btn-secondary">
                        Réinitialiser
                    </a>

                </form>
            </div>

            <div class="cards">

                <div class="card">
                    <h3>Volume horaire validé</h3>
                    <p><?= number_format($volumeTotal, 2, ',', ' ') ?></p>
                    <small>heures</small>
                </div>

                <div class="card">
                    <h3>Charge statutaire</h3>
                    <p>
                        <?php if($enseignant["statut"] === "PERMANENT"): ?>
                            <?= number_format($chargeStatutaire, 2, ',', ' ') ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </p>
                    <small>
                        <?= $enseignant["statut"] === "PERMANENT" ? "heures" : "Non applicable" ?>
                    </small>
                </div>

                <div class="card">
                    <h3>Heures complémentaires</h3>
                    <p><?= htmlspecialchars($messageHC) ?></p>
                    <small>
                        <?php if($enseignant["statut"] === "PERMANENT"): ?>
                            Volume validé - charge statutaire
                        <?php else: ?>
                            Les vacataires ne sont pas concernés
                        <?php endif; ?>
                    </small>
                </div>

            </div>

            <br>

            <div class="table-card">
                <h2>Règle de calcul</h2>

                <?php if($enseignant["statut"] === "PERMANENT"): ?>
                    <p>
                        Pour un enseignant permanent, les heures complémentaires sont calculées ainsi :
                    </p>

                    <p>
                        <strong>
                            Heures complémentaires = max(Volume horaire validé - Charge statutaire, 0)
                        </strong>
                    </p>
                <?php else: ?>
                    <p>
                        Vous êtes enregistré comme <strong>vacataire</strong>.
                        Les heures complémentaires ne s’appliquent pas aux vacataires.
                    </p>
                <?php endif; ?>
            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>