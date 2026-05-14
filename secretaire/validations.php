<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$activites = $pdo->query("
    SELECT 
        ap.id_activite,
        ap.type_activite,
        ap.niveau_complexite,
        ap.nombre_heures,
        ap.nb_sequences,
        ap.volume_horaire_calcule,
        ap.statut_validation,
        e.nom,
        e.prenoms,
        c.intitule_cours,
        r.titre_ressource
    FROM activite_pedagogique ap
    JOIN enseignant e ON e.id_enseignant = ap.id_enseignant
    JOIN cours c ON c.id_cours = ap.id_cours
    LEFT JOIN ressource_pedagogique r ON r.id_ressource = ap.id_ressource
    WHERE ap.statut_validation = 'EN_ATTENTE'
    ORDER BY ap.date_saisie DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_secretaire.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Validation des activités</h1>
                <p>Valider ou rejeter les activités pédagogiques en attente.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>SECRÉTAIRE PRINCIPAL</small>
            </div>
        </header>

        <section class="content">

            <div class="table-card">

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Enseignant</th>
                            <th>Cours</th>
                            <th>Ressource</th>
                            <th>Type</th>
                            <th>Niveau</th>
                            <th>Heures</th>
                            <th>Séquences</th>
                            <th>Volume</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if(count($activites) > 0): ?>
                            <?php foreach($activites as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a["nom"] . " " . $a["prenoms"]) ?></td>
                                    <td><?= htmlspecialchars($a["intitule_cours"]) ?></td>
                                    <td><?= htmlspecialchars($a["titre_ressource"] ?? "Non précisée") ?></td>
                                    <td><?= htmlspecialchars($a["type_activite"]) ?></td>
                                    <td><?= htmlspecialchars($a["niveau_complexite"]) ?></td>
                                    <td><?= htmlspecialchars($a["nombre_heures"]) ?> h</td>
                                    <td><?= htmlspecialchars($a["nb_sequences"]) ?></td>
                                    <td><?= htmlspecialchars($a["volume_horaire_calcule"]) ?></td>
                                    <td class="actions">
                                        <a href="traiter_validation.php?id=<?= $a["id_activite"] ?>&decision=VALIDEE"
                                           class="btn-small success"
                                           onclick="return confirm('Valider cette activité ?');">
                                            Valider
                                        </a>

                                        <a href="traiter_validation.php?id=<?= $a["id_activite"] ?>&decision=REJETEE"
                                           class="btn-small danger"
                                           onclick="return confirm('Rejeter cette activité ?');">
                                            Rejeter
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty">
                                    Aucune activité en attente de validation.
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