<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$idEtat = $_GET["id"] ?? null;

if(!$idEtat){
    header("Location: paiements.php");
    exit;
}

$stmtEtat = $pdo->prepare("
    SELECT 
        ep.*,
        aa.libelle_annee
    FROM etat_paiement ep
    JOIN annee_academique aa ON aa.id_annee = ep.id_annee
    WHERE ep.id_etat = ?
");

$stmtEtat->execute([$idEtat]);
$etat = $stmtEtat->fetch(PDO::FETCH_ASSOC);

if(!$etat){
    header("Location: paiements.php");
    exit;
}

$stmtDetails = $pdo->prepare("
    SELECT
        epd.*,
        e.nom,
        e.prenoms,
        g.libelle_grade
    FROM etat_paiement_detail epd
    JOIN enseignant e ON e.id_enseignant = epd.id_enseignant
    JOIN grade g ON g.id_grade = e.id_grade
    WHERE epd.id_etat = ?
    ORDER BY e.nom, e.prenoms
");

$stmtDetails->execute([$idEtat]);
$details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_secretaire.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>État de paiement</h1>
                <p>Détail global et individuel de la période sélectionnée.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>SECRÉTAIRE PRINCIPAL</small>
            </div>
        </header>

        <section class="content">

            <div class="welcome-card">
                <div>
                    <h2>
                        Période :
                        <?= date("d/m/Y", strtotime($etat["date_debut_periode"])) ?>
                        au
                        <?= date("d/m/Y", strtotime($etat["date_fin_periode"])) ?>
                    </h2>

                    <p>
                        Année académique :
                        <strong><?= htmlspecialchars($etat["libelle_annee"]) ?></strong>
                    </p>

                    <p>
                        Statut :
                        <strong><?= htmlspecialchars($etat["statut_paiement"]) ?></strong>
                    </p>
                </div>
            </div>

            <div class="cards">

                <div class="card">
                    <h3>Enseignants concernés</h3>
                    <p><?= $etat["total_enseignants"] ?></p>
                </div>

                <div class="card">
                    <h3>Volume horaire total</h3>
                    <p><?= number_format($etat["total_volume_horaire"], 2, ',', ' ') ?></p>
                    <small>heures</small>
                </div>

                <div class="card">
                    <h3>Heures payables</h3>
                    <p><?= number_format($etat["total_heures_payables"], 2, ',', ' ') ?></p>
                    <small>heures</small>
                </div>

                <div class="card">
                    <h3>Montant global</h3>
                    <p><?= number_format($etat["montant_global"], 0, ',', ' ') ?></p>
                    <small>FCFA</small>
                </div>

            </div>

            <br>

            <div class="table-card">

                <div class="table-header">
                    <h2>Détail individuel</h2>

                    <a href="paiements.php" class="btn-secondary">
                        Retour
                    </a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Enseignant</th>
                            <th>Grade</th>
                            <th>Statut</th>
                            <th>Volume total</th>
                            <th>Charge statutaire</th>
                            <th>Heures complémentaires</th>
                            <th>Heures payables</th>
                            <th>Taux horaire</th>
                            <th>Montant</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if(count($details) > 0): ?>

                            <?php foreach($details as $d): ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($d["nom"] . " " . $d["prenoms"]) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($d["libelle_grade"]) ?>
                                    </td>

                                    <td>
                                        <?php if($d["statut_enseignant"] === "PERMANENT"): ?>
                                            <span class="badge success">PERMANENT</span>
                                        <?php else: ?>
                                            <span class="badge warning">VACATAIRE</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= number_format($d["total_volume_horaire"], 2, ',', ' ') ?> h
                                    </td>

                                    <td>
                                        <?= number_format($d["charge_statutaire"], 2, ',', ' ') ?> h
                                    </td>

                                    <td>
                                        <?= number_format($d["heures_complementaires"], 2, ',', ' ') ?> h
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format($d["heures_payables"], 2, ',', ' ') ?> h
                                        </strong>
                                    </td>

                                    <td>
                                        <?= number_format($d["taux_horaire"], 0, ',', ' ') ?> FCFA
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format($d["montant_individuel"], 0, ',', ' ') ?> FCFA
                                        </strong>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" class="empty">
                                    Aucun détail trouvé pour cet état.
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