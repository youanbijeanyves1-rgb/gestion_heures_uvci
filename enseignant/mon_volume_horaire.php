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
        COUNT(*) AS total_activites_validees,
        COALESCE(SUM(nombre_heures), 0) AS total_heures_saisies,
        COALESCE(SUM(nb_sequences), 0) AS total_sequences,
        COALESCE(SUM(volume_horaire_calcule), 0) AS volume_horaire_total
    FROM activite_pedagogique
    WHERE id_enseignant = :id_enseignant
      AND statut_validation = 'VALIDEE'
";

$paramsVolume = [
    "id_enseignant" => $enseignant["id_enseignant"]
];

if($dateDebut !== "" && $dateFin !== ""){
    $sqlVolume .= "
        AND DATE(date_saisie) BETWEEN :date_debut AND :date_fin
    ";

    $paramsVolume["date_debut"] = $dateDebut;
    $paramsVolume["date_fin"] = $dateFin;
}

$stmtVolume = $pdo->prepare($sqlVolume);
$stmtVolume->execute($paramsVolume);
$volume = $stmtVolume->fetch(PDO::FETCH_ASSOC);

$sqlDetails = "
    SELECT
        ap.date_saisie,
        c.intitule_cours,
        r.titre_ressource,
        ap.type_activite,
        ap.niveau_complexite,
        ap.nombre_heures,
        ap.nb_sequences,
        ap.volume_horaire_calcule
    FROM activite_pedagogique ap
    JOIN cours c ON c.id_cours = ap.id_cours
    LEFT JOIN ressource_pedagogique r ON r.id_ressource = ap.id_ressource
    WHERE ap.id_enseignant = :id_enseignant
      AND ap.statut_validation = 'VALIDEE'
";

$paramsDetails = [
    "id_enseignant" => $enseignant["id_enseignant"]
];

if($dateDebut !== "" && $dateFin !== ""){
    $sqlDetails .= "
        AND DATE(ap.date_saisie) BETWEEN :date_debut AND :date_fin
    ";

    $paramsDetails["date_debut"] = $dateDebut;
    $paramsDetails["date_fin"] = $dateFin;
}

$sqlDetails .= "
    ORDER BY ap.date_saisie DESC
";

$stmtDetails = $pdo->prepare($sqlDetails);
$stmtDetails->execute($paramsDetails);
$details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_enseignant.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Mon volume horaire</h1>
                <p>Vérification du volume horaire validé par période.</p>
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

                    <a href="mon_volume_horaire.php" class="btn-secondary">
                        Réinitialiser
                    </a>

                </form>

            </div>

            <div class="cards">

                <div class="card">
                    <h3>Activités validées</h3>
                    <p><?= (int)$volume["total_activites_validees"] ?></p>
                </div>

                <div class="card">
                    <h3>Heures saisies</h3>
                    <p><?= number_format($volume["total_heures_saisies"], 2, ',', ' ') ?></p>
                    <small>heures</small>
                </div>

                <div class="card">
                    <h3>Séquences</h3>
                    <p><?= number_format($volume["total_sequences"], 0, ',', ' ') ?></p>
                    <small>séquences</small>
                </div>

                <div class="card">
                    <h3>Volume horaire validé</h3>
                    <p><?= number_format($volume["volume_horaire_total"], 2, ',', ' ') ?></p>
                    <small>heures</small>
                </div>

            </div>

            <br>

            <div class="table-card">

                <div class="table-header">
                    <h2>Détail du volume horaire validé</h2>

                    <a href="dashboard.php" class="btn-secondary">
                        Retour
                    </a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Cours</th>
                            <th>Ressource</th>
                            <th>Type</th>
                            <th>Niveau</th>
                            <th>Heures</th>
                            <th>Séquences</th>
                            <th>Volume calculé</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if(count($details) > 0): ?>

                            <?php foreach($details as $d): ?>
                                <tr>
                                    <td>
                                        <?= date("d/m/Y", strtotime($d["date_saisie"])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($d["intitule_cours"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($d["titre_ressource"] ?? "Non précisée") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($d["type_activite"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($d["niveau_complexite"]) ?>
                                    </td>

                                    <td>
                                        <?= number_format($d["nombre_heures"], 2, ',', ' ') ?> h
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($d["nb_sequences"]) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format($d["volume_horaire_calcule"], 2, ',', ' ') ?> h
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="8" class="empty">
                                    Aucune activité validée pour cette période.
                                </td>
                            </tr>

                        <?php endif; ?>
                    </tbody>
                </table>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>
