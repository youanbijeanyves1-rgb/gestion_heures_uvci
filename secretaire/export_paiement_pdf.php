<?php

require_once "../config/database.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;

$sql = "
SELECT
    e.nom,
    e.prenoms,
    g.libelle_grade,
    e.statut,
    c.niveau,
    SUM(ap.volume_horaire_calcule) AS volume_total,
    th.montant

FROM activite_pedagogique ap

INNER JOIN enseignant e
    ON ap.id_enseignant = e.id_enseignant

LEFT JOIN grade g
    ON e.id_grade = g.id_grade

INNER JOIN cours c
    ON ap.id_cours = c.id_cours

LEFT JOIN taux_horaire th
    ON th.id_grade = e.id_grade
    AND th.statut = e.statut
    AND th.niveau = c.niveau

WHERE ap.statut_validation = 'VALIDEE'

GROUP BY
    e.id_enseignant,
    c.niveau

ORDER BY e.nom ASC
";

$donnees = $pdo->query($sql)->fetchAll();

$html = '

<h1 style="text-align:center;">
ÉTAT GLOBAL DES PAIEMENTS
</h1>

<table border="1" cellpadding="8" cellspacing="0" width="100%">

<thead>
<tr style="background:#eeeeee;">
    <th>Enseignant</th>
    <th>Grade</th>
    <th>Statut</th>
    <th>Niveau</th>
    <th>Volume</th>
    <th>Taux</th>
    <th>Montant</th>
</tr>
</thead>

<tbody>
';

$totalGeneral = 0;

foreach($donnees as $d){

    $taux = $d["montant"] ?? 0;

    if($d["statut"] === "VACATAIRE"){
        $heuresPayables = $d["volume_total"];
    }else{
        $heuresPayables = 0;
    }

    $montant = $heuresPayables * $taux;

    $totalGeneral += $montant;

    $html .= '

    <tr>
        <td>'.$d["nom"].' '.$d["prenoms"].'</td>
        <td>'.$d["libelle_grade"].'</td>
        <td>'.$d["statut"].'</td>
        <td>'.$d["niveau"].'</td>
        <td>'.number_format($d["volume_total"],2,","," ").' h</td>
        <td>'.number_format($taux,0,","," ").' FCFA</td>
        <td>'.number_format($montant,0,","," ").' FCFA</td>
    </tr>

    ';
}

$html .= '

</tbody>

</table>

<h2 style="margin-top:30px;">
Montant total : '.number_format($totalGeneral,0,","," ").' FCFA
</h2>

';

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper("A4", "landscape");

$dompdf->render();

$dompdf->stream(
    "etat_paiement.pdf",
    ["Attachment" => true]
);