<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$typeMessage = "";

$cours = $pdo->query("
    SELECT id_cours, code_cours, intitule_cours
    FROM cours
    WHERE actif = 1
    ORDER BY intitule_cours
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $titreRessource = trim($_POST["titre_ressource"] ?? "");
    $typeRessource = $_POST["type_ressource"] ?? "";
    $description = trim($_POST["description"] ?? "");
    $idCours = $_POST["id_cours"] ?? "";

    if($titreRessource === "" || $typeRessource === "" || $idCours === ""){
        $message = "Veuillez remplir tous les champs obligatoires.";
        $typeMessage = "error";
    }else{

        $sql = "
            INSERT INTO ressource_pedagogique(
                titre_ressource,
                type_ressource,
                description,
                actif,
                id_cours
            )
            VALUES(
                :titre_ressource,
                :type_ressource,
                :description,
                1,
                :id_cours
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            "titre_ressource" => $titreRessource,
            "type_ressource" => $typeRessource,
            "description" => $description,
            "id_cours" => $idCours
        ]);

        $message = "Ressource pédagogique enregistrée avec succès.";
        $typeMessage = "success";
    }
}

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_secretaire.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Création d’une ressource pédagogique</h1>
                <p>Créer une ressource et la rattacher à un cours.</p>
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
                        <label>Cours concerné <span>*</span></label>
                        <select name="id_cours" required>
                            <option value="">-- Sélectionner un cours --</option>

                            <?php foreach($cours as $c): ?>
                                <option value="<?= $c["id_cours"] ?>">
                                    <?= htmlspecialchars($c["code_cours"] . " - " . $c["intitule_cours"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Titre de la ressource <span>*</span></label>
                        <input type="text" name="titre_ressource" required>
                    </div>

                    <div class="form-group">
                        <label>Type de ressource pédagogique <span>*</span></label>
                        <select name="type_ressource" required>
                            <option value="">-- Sélectionner un type --</option>
                            <option value="CONTENU_TEXTUEL">Contenu textuel</option>
                            <option value="VIDEO_PEDAGOGIQUE">Vidéo pédagogique</option>
                            <option value="DOCUMENT_PEDAGOGIQUE">Document pédagogique</option>
                            <option value="QUIZ">Quiz</option>
                            <option value="ACTIVITE_INTERACTIVE">Activité interactive</option>
                            <option value="EVALUATION">Évaluation</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Enregistrer la ressource
                        </button>

                        <a href="activites.php" class="btn-secondary">
                            Retour
                        </a>
                    </div>

                </form>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>