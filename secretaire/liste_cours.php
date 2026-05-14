<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$recherche = trim($_GET["recherche"] ?? "");

$sql = "
    SELECT 
        c.id_cours,
        c.code_cours,
        c.intitule_cours,
        c.nombre_heures,
        c.nb_sequences,
        c.nombre_credits,
        c.actif,
        e.nom,
        e.prenoms,
        f.nom_filiere,
        cf.niveau,
        cf.semestre
    FROM cours c
    LEFT JOIN enseignant e ON e.id_enseignant = c.id_enseignant
    LEFT JOIN cours_filiere cf ON cf.id_cours = c.id_cours
    LEFT JOIN filiere f ON f.id_filiere = cf.id_filiere
";

$params = [];

if($recherche !== ""){
    $sql .= "
        WHERE c.code_cours LIKE :recherche
           OR c.intitule_cours LIKE :recherche
           OR f.nom_filiere LIKE :recherche
           OR cf.niveau LIKE :recherche
           OR cf.semestre LIKE :recherche
           OR e.nom LIKE :recherche
           OR e.prenoms LIKE :recherche
    ";

    $params["recherche"] = "%".$recherche."%";
}

$sql .= " ORDER BY c.intitule_cours ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_secretaire.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Gestion des cours</h1>
                <p>Consulter, rechercher, modifier, activer ou désactiver les cours.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>SECRÉTAIRE PRINCIPAL</small>
            </div>
        </header>

        <section class="content">

            <div class="table-card">

                <div class="table-header">

                    <form method="GET" class="search-form">
                        <input
                            type="text"
                            name="recherche"
                            placeholder="Rechercher un cours..."
                            value="<?= htmlspecialchars($recherche) ?>"
                        >

                        <button type="submit" class="btn-primary">Rechercher</button>

                        <a href="liste_cours.php" class="btn-secondary">Réinitialiser</a>
                    </form>

                    <a href="creer_cours.php" class="btn-primary">
                        + Nouveau cours
                    </a>

                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Intitulé</th>
                            <th>Enseignant</th>
                            <th>Filière</th>
                            <th>Niveau</th>
                            <th>Semestre</th>
                            <th>Heures</th>
                            <th>Séquences</th>
                            <th>Crédits</th>
                            <th>État</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if(count($cours) > 0): ?>

                            <?php foreach($cours as $c): ?>

                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($c["code_cours"]) ?></strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($c["intitule_cours"]) ?>
                                    </td>

                                    <td>
                                        <?php if($c["nom"]): ?>
                                            <?= htmlspecialchars($c["nom"] . " " . $c["prenoms"]) ?>
                                        <?php else: ?>
                                            <span class="badge neutral">Non affecté</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($c["nom_filiere"] ?? "Non rattaché") ?>
                                    </td>

                                    <td><?= htmlspecialchars($c["niveau"] ?? "-") ?></td>

                                    <td><?= htmlspecialchars($c["semestre"] ?? "-") ?></td>

                                    <td><?= htmlspecialchars($c["nombre_heures"]) ?> h</td>

                                    <td><?= htmlspecialchars($c["nb_sequences"]) ?></td>

                                    <td><?= htmlspecialchars($c["nombre_credits"]) ?></td>

                                    <td>
                                        <?php if($c["actif"]): ?>
                                            <span class="badge success">ACTIF</span>
                                        <?php else: ?>
                                            <span class="badge danger">INACTIF</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="actions">
                                        <a href="modifier_cours.php?id=<?= $c["id_cours"] ?>" class="btn-small">
                                            Modifier
                                        </a>

                                        <?php if($c["actif"]): ?>
                                            <a
                                                href="toggle_cours.php?id=<?= $c["id_cours"] ?>&action=desactiver"
                                                class="btn-small danger"
                                                onclick="return confirm('Voulez-vous vraiment désactiver ce cours ?');"
                                            >
                                                Désactiver
                                            </a>
                                        <?php else: ?>
                                            <a
                                                href="toggle_cours.php?id=<?= $c["id_cours"] ?>&action=activer"
                                                class="btn-small success"
                                                onclick="return confirm('Voulez-vous vraiment réactiver ce cours ?');"
                                            >
                                                Activer
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="11" class="empty">
                                    Aucun cours trouvé.
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