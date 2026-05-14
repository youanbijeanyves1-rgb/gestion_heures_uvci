<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$typeMessage = "";

$annees = $pdo->query("
    SELECT id_annee, libelle_annee
    FROM annee_academique
    ORDER BY libelle_annee DESC
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $idAnnee = $_POST["id_annee"] ?? "";
    $dateDebut = $_POST["date_debut_periode"] ?? "";
    $dateFin = $_POST["date_fin_periode"] ?? "";

    if($idAnnee === "" || $dateDebut === "" || $dateFin === ""){
        $message = "Veuillez renseigner l’année académique et la période.";
        $typeMessage = "error";
    }
    elseif($dateFin < $dateDebut){
        $message = "La date de fin doit être supérieure ou égale à la date de début.";
        $typeMessage = "error";
    }
    else{

        try{

            $pdo->beginTransaction();

            $verif = $pdo->prepare("
                SELECT COUNT(*)
                FROM etat_paiement
                WHERE id_annee = ?
                  AND date_debut_periode = ?
                  AND date_fin_periode = ?
            ");
            $verif->execute([$idAnnee, $dateDebut, $dateFin]);

            if($verif->fetchColumn() > 0){
                throw new Exception("Un état de paiement existe déjà pour cette période.");
            }

            $sqlSynthese = "
                SELECT
                    e.id_enseignant,
                    e.statut,
                    COALESCE(g.charge_statutaire, 0) AS charge_statutaire,
                    th.montant AS taux_horaire,
                    SUM(ap.volume_horaire_calcule) AS total_volume_horaire
                FROM activite_pedagogique ap
                JOIN enseignant e ON e.id_enseignant = ap.id_enseignant
                JOIN grade g ON g.id_grade = e.id_grade
                JOIN taux_horaire th ON th.id_taux = e.id_taux
                WHERE ap.statut_validation = 'VALIDEE'
                  AND ap.id_annee = :id_annee
                  AND DATE(ap.date_saisie) BETWEEN :date_debut AND :date_fin
                GROUP BY
                    e.id_enseignant,
                    e.statut,
                    g.charge_statutaire,
                    th.montant
            ";

            $stmtSynthese = $pdo->prepare($sqlSynthese);
            $stmtSynthese->execute([
                "id_annee" => $idAnnee,
                "date_debut" => $dateDebut,
                "date_fin" => $dateFin
            ]);

            $lignes = $stmtSynthese->fetchAll(PDO::FETCH_ASSOC);

            if(count($lignes) === 0){
                throw new Exception("Aucune activité validée trouvée pour cette période.");
            }

            $stmtEtat = $pdo->prepare("
                INSERT INTO etat_paiement(
                    id_annee,
                    date_debut_periode,
                    date_fin_periode,
                    total_enseignants,
                    total_volume_horaire,
                    total_heures_payables,
                    montant_global,
                    statut_paiement
                )
                VALUES(
                    :id_annee,
                    :date_debut_periode,
                    :date_fin_periode,
                    0,
                    0,
                    0,
                    0,
                    'PREPARE'
                )
            ");

            $stmtEtat->execute([
                "id_annee" => $idAnnee,
                "date_debut_periode" => $dateDebut,
                "date_fin_periode" => $dateFin
            ]);

            $idEtat = $pdo->lastInsertId();

            $totalEnseignants = 0;
            $totalVolume = 0;
            $totalHeuresPayables = 0;
            $montantGlobal = 0;

            $stmtDetail = $pdo->prepare("
                INSERT INTO etat_paiement_detail(
                    id_etat,
                    id_enseignant,
                    statut_enseignant,
                    total_volume_horaire,
                    charge_statutaire,
                    heures_complementaires,
                    heures_payables,
                    taux_horaire,
                    montant_individuel
                )
                VALUES(
                    :id_etat,
                    :id_enseignant,
                    :statut_enseignant,
                    :total_volume_horaire,
                    :charge_statutaire,
                    :heures_complementaires,
                    :heures_payables,
                    :taux_horaire,
                    :montant_individuel
                )
            ");

            foreach($lignes as $ligne){

                $totalVolumeEnseignant = (float)$ligne["total_volume_horaire"];
                $chargeStatutaire = (float)$ligne["charge_statutaire"];
                $tauxHoraire = (float)$ligne["taux_horaire"];
                $statut = $ligne["statut"];

                if($statut === "VACATAIRE"){
                    $heuresComplementaires = 0;
                    $heuresPayables = $totalVolumeEnseignant;
                }else{
                    $heuresComplementaires = max(0, $totalVolumeEnseignant - $chargeStatutaire);
                    $heuresPayables = $heuresComplementaires;
                }

                $montantIndividuel = $heuresPayables * $tauxHoraire;

                $stmtDetail->execute([
                    "id_etat" => $idEtat,
                    "id_enseignant" => $ligne["id_enseignant"],
                    "statut_enseignant" => $statut,
                    "total_volume_horaire" => $totalVolumeEnseignant,
                    "charge_statutaire" => $chargeStatutaire,
                    "heures_complementaires" => $heuresComplementaires,
                    "heures_payables" => $heuresPayables,
                    "taux_horaire" => $tauxHoraire,
                    "montant_individuel" => $montantIndividuel
                ]);

                $totalEnseignants++;
                $totalVolume += $totalVolumeEnseignant;
                $totalHeuresPayables += $heuresPayables;
                $montantGlobal += $montantIndividuel;
            }

            $stmtUpdateEtat = $pdo->prepare("
                UPDATE etat_paiement
                SET
                    total_enseignants = :total_enseignants,
                    total_volume_horaire = :total_volume_horaire,
                    total_heures_payables = :total_heures_payables,
                    montant_global = :montant_global
                WHERE id_etat = :id_etat
            ");

            $stmtUpdateEtat->execute([
                "total_enseignants" => $totalEnseignants,
                "total_volume_horaire" => $totalVolume,
                "total_heures_payables" => $totalHeuresPayables,
                "montant_global" => $montantGlobal,
                "id_etat" => $idEtat
            ]);

            $pdo->commit();

            header("Location: voir_etat_paiement.php?id=" . $idEtat);
            exit;

        }catch(Exception $e){

            $pdo->rollBack();

            $message = $e->getMessage();
            $typeMessage = "error";
        }
    }
}

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_secretaire.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Générer un état de paiement</h1>
                <p>Calculer les montants à payer sur une période donnée.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>SECRÉTAIRE PRINCIPAL</small>
            </div>
        </header>

        <section class="content">

            <div class="form-card">

                <?php if($message !== ""): ?>
                    <div class="alert <?= $typeMessage ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label>Année académique <span>*</span></label>
                        <select name="id_annee" required>
                            <option value="">-- Sélectionner une année --</option>

                            <?php foreach($annees as $annee): ?>
                                <option value="<?= $annee["id_annee"] ?>">
                                    <?= htmlspecialchars($annee["libelle_annee"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date début période <span>*</span></label>
                        <input type="date" name="date_debut_periode" required>
                    </div>

                    <div class="form-group">
                        <label>Date fin période <span>*</span></label>
                        <input type="date" name="date_fin_periode" required>
                    </div>

                    <p class="info-text">
                        Seules les activités pédagogiques validées seront prises en compte.
                    </p>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Générer l’état de paiement
                        </button>

                        <a href="paiements.php" class="btn-secondary">
                            Retour
                        </a>
                    </div>

                </form>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>