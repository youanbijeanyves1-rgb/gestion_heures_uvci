<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if ($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL") {
    header("Location: ../auth/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ETATS RECAPITULATIFS
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    e.nom,
    e.prenoms,
    e.statut,
    g.libelle_grade,
    c.intitule_cours AS cours,
    ap.type_activite,
    ap.nb_sequences,
    ap.volume_horaire_calcule,
    ap.date_saisie
FROM activite_pedagogique ap

INNER JOIN enseignant e
    ON ap.id_enseignant = e.id_enseignant

LEFT JOIN grade g
    ON e.id_grade = g.id_grade

INNER JOIN cours c
    ON ap.id_cours = c.id_cours

WHERE ap.statut_validation = 'VALIDEE'

ORDER BY e.nom ASC
";

$activites = $pdo->query($sql)->fetchAll();

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_secretaire.php"; ?>

    <main class="main">

        <div class="topbar">

            <div>
                <h1>États récapitulatifs</h1>

                <p>
                    Synthèse des activités pédagogiques validées
                </p>
            </div>

            <div class="badge-role">
                SECRÉTAIRE PRINCIPAL
            </div>

        </div>

        <div class="content">

            <div class="card">

                <div class="card-header">

                    <h2>
                        Liste des activités validées
                    </h2>

                </div>

                <div class="table-responsive">

                    <table class="table">

                        <thead>

                            <tr>
                                <th>Enseignant</th>
                                <th>Grade</th>
                                <th>Statut</th>
                                <th>Cours</th>
                                <th>Activité</th>
                                <th>Séquences</th>
                                <th>Volume horaire</th>
                                <th>Date</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if(count($activites) > 0): ?>

                                <?php foreach($activites as $a): ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars($a["nom"] . " " . $a["prenoms"]) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($a["libelle_grade"]) ?>
                                        </td>

                                        <td>
                                            <span class="badge-statut">
                                            <?= htmlspecialchars($a["statut"]) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($a["cours"]) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($a["type_activite"]) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($a["nb_sequences"]) ?>
                                        </td>

                                        <td>
                                            <span class="badge-volume">
                                            <?= htmlspecialchars($a["volume_horaire_calcule"]) ?> h
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($a["date_saisie"]) ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="8" class="text-center">
                                        Aucun état récapitulatif disponible.
                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </main>

</div>

<?php require_once "../includes/footer.php"; ?>