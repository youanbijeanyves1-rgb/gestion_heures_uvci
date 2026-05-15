<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if($_SESSION["role"] !== "ENSEIGNANT"){
    header("Location: ../auth/login.php");
    exit;
}

$idUtilisateur = $_SESSION["id_utilisateur"];

$dateDebut = $_GET["date_debut"] ?? "";
$dateFin = $_GET["date_fin"] ?? "";

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
    die("Aucun enseignant associé à ce compte utilisateur.");
}

$idEnseignant = $enseignant["id_enseignant"];
$statut = $enseignant["statut"];
$chargeStatutaire = (float)($enseignant["charge_statutaire"] ?? 0);

$where = "ap.id_enseignant = ? AND ap.statut_validation = 'VALIDEE'";
$params = [$idEnseignant];

if($dateDebut !== ""){
    $where .= " AND DATE(ap.date_saisie) >= ?";
    $params[] = $dateDebut;
}

if($dateFin !== ""){
    $where .= " AND DATE(ap.date_saisie) <= ?";
    $params[] = $dateFin;
}

$stmtActivites = $pdo->prepare("
    SELECT 
        ap.date_saisie,
        ap.type_activite,
        ap.niveau_complexite,
        ap.nb_sequences,
        ap.volume_horaire_calcule,
        ap.observation,
        c.intitule_cours AS cours
    FROM activite_pedagogique ap
    LEFT JOIN cours c ON c.id_cours = ap.id_cours
    WHERE $where
    ORDER BY ap.date_saisie ASC
");
$stmtActivites->execute($params);
$activites = $stmtActivites->fetchAll(PDO::FETCH_ASSOC);

$totalVolume = 0;

foreach($activites as $activite){
    $totalVolume += (float)$activite["volume_horaire_calcule"];
}

$heuresComplementaires = 0;

if($statut === "PERMANENT"){
    $heuresComplementaires = max(0, $totalVolume - $chargeStatutaire);
}

$periode = "Toutes les périodes";

if($dateDebut !== "" && $dateFin !== ""){
    $periode = "Du " . date("d/m/Y", strtotime($dateDebut)) . " au " . date("d/m/Y", strtotime($dateFin));
}
elseif($dateDebut !== ""){
    $periode = "À partir du " . date("d/m/Y", strtotime($dateDebut));
}
elseif($dateFin !== ""){
    $periode = "Jusqu’au " . date("d/m/Y", strtotime($dateFin));
}

ob_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">

    <style>
        body{
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color:#111827;
        }

        .header{
            text-align:center;
            border-bottom:2px solid #1e3a8a;
            padding-bottom:12px;
            margin-bottom:20px;
        }

        h1{
            color:#1e3a8a;
            margin:0;
            font-size:22px;
        }

        .subtitle{
            margin-top:6px;
            color:#475569;
        }

        .info{
            margin-bottom:18px;
            padding:12px;
            background:#f1f5f9;
            border-radius:8px;
        }

        .info p{
            margin:4px 0;
        }

        .stats{
            width:100%;
            margin-bottom:20px;
            border-collapse:collapse;
        }

        .stats td{
            border:1px solid #cbd5e1;
            padding:10px;
            text-align:center;
            font-weight:bold;
        }

        .stats .label{
            background:#e0f2fe;
            color:#1e3a8a;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            background:#1e1b4b;
            color:white;
            padding:8px;
            font-size:11px;
        }

        td{
            border:1px solid #cbd5e1;
            padding:7px;
            font-size:10.5px;
        }

        .right{
            text-align:right;
        }

        .footer{
            margin-top:25px;
            font-size:10px;
            color:#64748b;
            text-align:center;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Récapitulatif des activités pédagogiques</h1>
        <div class="subtitle">Université Virtuelle de Côte d’Ivoire — Gestion des heures</div>
    </div>

    <div class="info">
        <p><strong>Enseignant :</strong> <?= htmlspecialchars($enseignant["nom"] . " " . $enseignant["prenoms"]) ?></p>
        <p><strong>Grade :</strong> <?= htmlspecialchars($enseignant["libelle_grade"] ?? "Non défini") ?></p>
        <p><strong>Statut :</strong> <?= htmlspecialchars($statut) ?></p>
        <p><strong>Période :</strong> <?= htmlspecialchars($periode) ?></p>
    </div>

    <table class="stats">
        <tr>
            <td class="label">Volume validé</td>
            <td class="label">Charge statutaire</td>
            <td class="label">Heures complémentaires</td>
        </tr>
        <tr>
            <td><?= number_format($totalVolume, 2, ",", " ") ?> h</td>

            <td>
                <?php if($statut === "VACATAIRE"): ?>
                    Non concerné
                <?php else: ?>
                    <?= number_format($chargeStatutaire, 2, ",", " ") ?> h
                <?php endif; ?>
            </td>

            <td>
                <?php if($statut === "VACATAIRE"): ?>
                    Non concerné
                <?php else: ?>
                    <?= number_format($heuresComplementaires, 2, ",", " ") ?> h
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Cours</th>
                <th>Type activité</th>
                <th>Niveau</th>
                <th>Séquences</th>
                <th>Volume</th>
                <th>Observation</th>
            </tr>
        </thead>

        <tbody>
            <?php if(empty($activites)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">
                        Aucune activité validée trouvée pour cette période.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($activites as $activite): ?>
                    <tr>
                        <td><?= htmlspecialchars(date("d/m/Y", strtotime($activite["date_saisie"]))) ?></td>
                        <td><?= htmlspecialchars($activite["cours"] ?? "Non renseigné") ?></td>
                        <td><?= htmlspecialchars($activite["type_activite"]) ?></td>
                        <td><?= htmlspecialchars($activite["niveau_complexite"]) ?></td>
                        <td class="right"><?= htmlspecialchars($activite["nb_sequences"]) ?></td>
                        <td class="right"><?= number_format((float)$activite["volume_horaire_calcule"], 2, ",", " ") ?> h</td>
                        <td><?= htmlspecialchars($activite["observation"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Document généré automatiquement le <?= date("d/m/Y à H:i") ?>.
    </div>

</body>
</html>

<?php

$html = ob_get_clean();

$options = new Options();
$options->set("isHtml5ParserEnabled", true);
$options->set("isRemoteEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$filename = "recapitulatif_" . $enseignant["nom"] . "_" . date("Ymd_His") . ".pdf";

$dompdf->stream($filename, ["Attachment" => true]);
exit;