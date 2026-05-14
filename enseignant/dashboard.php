<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "ENSEIGNANT"){
    header("Location: ../auth/login.php");
    exit;
}

$idUtilisateur = $_SESSION["id_utilisateur"];

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

$idEnseignant = $enseignant["id_enseignant"];

$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_activites,
        COALESCE(SUM(volume_horaire_calcule), 0) AS volume_total
    FROM activite_pedagogique
    WHERE id_enseignant = ?
      AND statut_validation = 'VALIDEE'
");
$stmtStats->execute([$idEnseignant]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

$volumeTotal = (float)$stats["volume_total"];
$chargeStatutaire = (float)($enseignant["charge_statutaire"] ?? 0);

if($enseignant["statut"] === "PERMANENT"){
    $heuresComplementaires = max(0, $volumeTotal - $chargeStatutaire);
    $texteHC = number_format($heuresComplementaires, 2, ',', ' ') . " h";
}else{
    $texteHC = "Non concerné";
}

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_enseignant.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Tableau de bord Enseignant</h1>
                <p>Espace personnel de suivi pédagogique.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>ENSEIGNANT</small>
                <a href="../auth/logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </header>

        <section class="content">

            <div class="welcome-card">
                <div>
                    <h2>
                        Bienvenue, <?= htmlspecialchars($enseignant["nom"] . " " . $enseignant["prenoms"]) ?>.
                    </h2>

                    <p>
                        Grade :
                        <strong><?= htmlspecialchars($enseignant["libelle_grade"] ?? "Non défini") ?></strong>
                        —
                        Statut :
                        <strong><?= htmlspecialchars($enseignant["statut"]) ?></strong>
                    </p>
                </div>
            </div>

            <div class="cards">

                <a href="mes_activites.php" class="card-link">
                    <div class="card">
                        <span class="card-icon blue">📋</span>
                        <h3>Mes activités pédagogiques</h3>
                        <p><?= (int)$stats["total_activites"] ?></p>
                        <small>Consulter les activités qui me sont attribuées</small>
                    </div>
                </a>

                <a href="mon_volume_horaire.php" class="card-link">
                    <div class="card">
                        <span class="card-icon purple">⏱️</span>
                        <h3>Mon volume horaire</h3>
                        <p><?= number_format($volumeTotal, 2, ',', ' ') ?> h</p>
                        <small>Vérifier le volume horaire validé</small>
                    </div>
                </a>

                <a href="mes_heures_complementaires.php" class="card-link">
                    <div class="card">
                        <span class="card-icon green">➕</span>
                        <h3>Mes heures complémentaires</h3>
                        <p><?= htmlspecialchars($texteHC) ?></p>
                        <small>Les vacataires ne sont pas concernés</small>
                    </div>
                </a>

                <a href="mon_recapitulatif.php" class="card-link">
                    <div class="card">
                        <span class="card-icon orange">📄</span>
                        <h3>Mon récapitulatif</h3>
                        <small>Télécharger ou consulter mon récapitulatif pédagogique</small>
                    </div>
                </a>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>