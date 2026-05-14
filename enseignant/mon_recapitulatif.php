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
        e.email,
        e.telephone,
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

$sqlStats = "
    SELECT 
        COUNT(*) AS total_activites,
        COALESCE(SUM(nombre_heures), 0) AS total_heures,
        COALESCE(SUM(nb_sequences), 0) AS total_sequences,
        COALESCE(SUM(volume_horaire_calcule), 0) AS volume_total
    FROM activite_pedagogique
    WHERE id_enseignant = :id_enseignant
      AND statut_validation = 'VALIDEE'
";

$params = [
    "id_enseignant" => $enseignant["id_enseignant"]
];

if($dateDebut !== "" && $dateFin !== ""){
    $sqlStats .= "
        AND DATE(date_saisie) BETWEEN :date_debut AND :date_fin
    ";

    $params["date_debut"] = $dateDebut;
    $params["date_fin"] = $dateFin;
}

$stmtStats = $pdo->prepare($sqlStats);
$stmtStats->execute($params);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

$volumeTotal = (float)$stats["volume_total"];
$chargeStatutaire = (float)($enseignant["charge_statutaire"] ?? 0);

if($enseignant["statut"] === "PERMANENT"){
    $heuresComplementaires = max(0, $volumeTotal - $chargeStatutaire);
    $texteHC = number_format($heuresComplementaires, 2, ',', ' ') . " h";
}else{
    $texteHC = "Non concerné";
}

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

if($dateDebut !== "" && $dateFin !== ""){
    $sqlDetails .= "
        AND DATE(ap.date_saisie) BETWEEN :date_debut AND :date_fin
    ";
}

$sqlDetails .= "
    ORDER BY ap.date_saisie DESC
";

$stmtDetails = $pdo->prepare($sqlDetails);
$stmtDetails->execute($params);
$details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_enseignant.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Mon récapitulatif</h1>
                <p>Récapitulatif individuel des activités pédagogiques validées.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>ENSEIGNANT</small>
            </div>
        </header>

        <section class="content">

            <div class="filter-card no-print">
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

                    <a href="mon_recapitulatif.php" class="btn-secondary">
                        Réinitialiser
                    </a>

                    <button type="button" onclick="window.print()" class="btn-primary">
                        Imprimer / PDF
                    </button>

                </form>
            </div>

            <div class="table-card">

                <h2>Fiche récapitulative individuelle</h2>

                <p>
                    <strong>Enseignant :</strong>
                    <?= htmlspecialchars($enseignant["nom"] . " " . $enseignant["prenoms"]) ?>
                </p>

                <p>
                    <strong>Grade :</strong>
                    <?= htmlspecialchars($enseignant["libelle_grade"] ?? "Non défini") ?>
                </p>

                <p>
                    <strong>Statut :</strong>
                    <?= htmlspecialchars($enseignant["statut"]) ?>
                </p>

                <p>
                    <strong>Email :</strong>
                    <?= htmlspecialchars($enseignant["email"]) ?>
                </p>

                <p>
                    <strong>Téléphone :</strong>
                    <?= htmlspecialchars($enseignant["telephone"]) ?>
                </p>

                <p>
                    <strong>Période :</strong>
                    <?php if($dateDebut !== "" && $dateFin !== ""): ?>
                        du <?= date("d/m/Y", strtotime($dateDebut)) ?>
                        au <?= date("d/m/Y", strtotime($dateFin)) ?>
                    <?php else: ?>
                        Toutes les périodes
                    <?php endif; ?>
                </p>

            </div>

            <br>

            <div class="cards">

                <div class="card">
                    <h3>Activités validées</h3>
                    <p><?= (int)$stats["total_activites"] ?></p>
                </div>

                <div class="card">
                    <h3>Heures saisies</h3>
                    <p><?= number_format($stats["total_heures"], 2, ',', ' ') ?></p>
                    <small>heures</small>
                </div>

                <div class="card">
                    <h3>Séquences</h3>
                    <p><?= number_format($stats["total_sequences"], 0, ',', ' ') ?></p>
                    <small>séquences</small>
                </div>

                <div class="card">
                    <h3>Volume horaire validé</h3>
                    <p><?= number_format($volumeTotal, 2, ',', ' ') ?></p>
                    <small>heures</small>
                </div>

                <div class="card">
                    <h3>Heures complémentaires</h3>
                    <p><?= htmlspecialchars($texteHC) ?></p>
                </div>

            </div>

            <br>

            <div class="table-card">

                <div class="table-header">
                    <h2>Détail des activités validées</h2>

                    <a href="dashboard.php" class="btn-secondary no-print">
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
                                    <td><?= date("d/m/Y", strtotime($d["date_saisie"])) ?></td>
                                    <td><?= htmlspecialchars($d["intitule_cours"]) ?></td>
                                    <td><?= htmlspecialchars($d["titre_ressource"] ?? "Non précisée") ?></td>
                                    <td><?= htmlspecialchars($d["type_activite"]) ?></td>
                                    <td><?= htmlspecialchars($d["niveau_complexite"]) ?></td>
                                    <td><?= number_format($d["nombre_heures"], 2, ',', ' ') ?> h</td>
                                    <td><?= htmlspecialchars($d["nb_sequences"]) ?></td>
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