<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "ENSEIGNANT"){
    header("Location: ../auth/login.php");
    exit;
}

$idUtilisateur = $_SESSION["id_utilisateur"];

$stmtEns = $pdo->prepare("
    SELECT id_enseignant, nom, prenoms, statut
    FROM enseignant
    WHERE id_utilisateur = ?
    LIMIT 1
");
$stmtEns->execute([$idUtilisateur]);
$enseignant = $stmtEns->fetch(PDO::FETCH_ASSOC);

if(!$enseignant){
    die("Aucun profil enseignant n’est lié à ce compte utilisateur.");
}

$stmtActivites = $pdo->prepare("
    SELECT 
        ap.id_activite,
        ap.type_activite,
        ap.niveau_complexite,
        ap.nombre_heures,
        ap.nb_sequences,
        ap.volume_horaire_calcule,
        ap.statut_validation,
        ap.date_saisie,
        c.intitule_cours,
        r.titre_ressource
    FROM activite_pedagogique ap
    JOIN cours c ON c.id_cours = ap.id_cours
    LEFT JOIN ressource_pedagogique r ON r.id_ressource = ap.id_ressource
    WHERE ap.id_enseignant = ?
    ORDER BY ap.date_saisie DESC
");
$stmtActivites->execute([$enseignant["id_enseignant"]]);
$activites = $stmtActivites->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_enseignant.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Mes activités pédagogiques</h1>
                <p>Consultation des activités pédagogiques qui me sont attribuées.</p>
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
                    Statut :
                    <strong><?= htmlspecialchars($enseignant["statut"]) ?></strong>
                </p>
            </div>

            <div class="table-card">

                <div class="table-header">
                    <h2>Liste de mes activités</h2>

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
                            <th>Statut</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if(count($activites) > 0): ?>

                            <?php foreach($activites as $a): ?>
                                <tr>
                                    <td>
                                        <?= date("d/m/Y", strtotime($a["date_saisie"])) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($a["intitule_cours"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($a["titre_ressource"] ?? "Non précisée") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($a["type_activite"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($a["niveau_complexite"]) ?>
                                    </td>

                                    <td>
                                        <?= number_format($a["nombre_heures"], 2, ',', ' ') ?> h
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($a["nb_sequences"]) ?>
                                    </td>

                                    <td>
                                        <?= number_format($a["volume_horaire_calcule"], 2, ',', ' ') ?> h
                                    </td>

                                    <td>
                                        <?php if($a["statut_validation"] === "VALIDEE"): ?>
                                            <span class="badge success">VALIDÉE</span>
                                        <?php elseif($a["statut_validation"] === "REJETEE"): ?>
                                            <span class="badge danger">REJETÉE</span>
                                        <?php else: ?>
                                            <span class="badge warning">EN ATTENTE</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" class="empty">
                                    Aucune activité pédagogique ne vous est encore attribuée.
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