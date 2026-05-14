<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=etat_paiements.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";

echo "
<table border='1'>
    <tr>
        <th>Enseignant</th>
        <th>Grade</th>
        <th>Statut</th>
        <th>Niveau</th>
        <th>Volume validé</th>
        <th>Charge statutaire</th>
        <th>Heures payables</th>
        <th>Taux horaire</th>
        <th>Montant à payer</th>
    </tr>
";

$sql = "
SELECT
    e.id_enseignant,
    e.nom,
    e.prenoms,
    e.statut,
    g.libelle_grade,
    g.charge_statutaire,
    c.niveau,
    SUM(ap.volume_horaire_calcule) AS volume_total,
    th.montant AS taux_horaire
FROM activite_pedagogique ap
JOIN enseignant e 
    ON e.id_enseignant = ap.id_enseignant
LEFT JOIN grade g 
    ON g.id_grade = e.id_grade
JOIN cours c 
    ON c.id_cours = ap.id_cours
LEFT JOIN taux_horaire th
    ON th.statut = e.statut
    AND th.id_grade = e.id_grade
    AND th.niveau = c.niveau
    AND th.actif = 1
WHERE ap.statut_validation = 'VALIDEE'
GROUP BY
    e.id_enseignant,
    e.nom,
    e.prenoms,
    e.statut,
    g.libelle_grade,
    g.charge_statutaire,
    c.niveau,
    th.montant
ORDER BY e.nom, e.prenoms, c.niveau
";

$requete = $pdo->query($sql);

while($ligne = $requete->fetch(PDO::FETCH_ASSOC)){

    $volumeValide = (float)$ligne["volume_total"];
    $charge = (float)($ligne["charge_statutaire"] ?? 0);
    $taux = (float)($ligne["taux_horaire"] ?? 0);

    if($ligne["statut"] === "VACATAIRE"){
        $heuresPayables = $volumeValide;
        $chargeAffichee = "Non concerné";
    }else{
        $heuresPayables = max(0, $volumeValide - $charge);
        $chargeAffichee = number_format($charge, 2, ",", " ") . " h";
    }

    $montantPayer = $heuresPayables * $taux;

    echo "
    <tr>
        <td>".htmlspecialchars($ligne["nom"] . " " . $ligne["prenoms"])."</td>
        <td>".htmlspecialchars($ligne["libelle_grade"] ?? "Non défini")."</td>
        <td>".htmlspecialchars($ligne["statut"])."</td>
        <td>".htmlspecialchars($ligne["niveau"])."</td>
        <td>".number_format($volumeValide, 2, ",", " ")." h</td>
        <td>".$chargeAffichee."</td>
        <td>".number_format($heuresPayables, 2, ",", " ")." h</td>
        <td>".number_format($taux, 0, ",", " ")." FCFA</td>
        <td>".number_format($montantPayer, 0, ",", " ")." FCFA</td>
    </tr>
    ";
}

echo "</table>";
exit;