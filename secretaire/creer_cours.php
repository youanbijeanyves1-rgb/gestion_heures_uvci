<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$typeMessage = "";

$filieres = $pdo->query("
    SELECT id_filiere, nom_filiere 
    FROM filiere 
    WHERE actif = 1 
    ORDER BY nom_filiere
")->fetchAll(PDO::FETCH_ASSOC);

$enseignants = $pdo->query("
    SELECT id_enseignant, nom, prenoms
    FROM enseignant
    WHERE actif = 1
    ORDER BY nom, prenoms
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $codeCours = trim($_POST["code_cours"] ?? "");
    $intituleCours = trim($_POST["intitule_cours"] ?? "");
    $idEnseignant = $_POST["id_enseignant"] ?? null;
    $idFiliere = $_POST["id_filiere"] ?? "";
    $niveau = $_POST["niveau"] ?? "";
    $semestre = $_POST["semestre"] ?? "";
    $nombreHeures = $_POST["nombre_heures"] ?? "";
    $nombreCredits = $_POST["nombre_credits"] ?? "";

    if($idEnseignant === ""){
        $idEnseignant = null;
    }

    if(
        $codeCours === "" ||
        $intituleCours === "" ||
        $idFiliere === "" ||
        $niveau === "" ||
        $semestre === "" ||
        $nombreHeures === "" ||
        $nombreCredits === ""
    ){
        $message = "Veuillez remplir tous les champs obligatoires.";
        $typeMessage = "error";
    }
    elseif(!is_numeric($nombreHeures) || $nombreHeures <= 0){
        $message = "Le nombre d’heures doit être supérieur à 0.";
        $typeMessage = "error";
    }
    elseif(!is_numeric($nombreCredits) || $nombreCredits < 0){
        $message = "Le nombre de crédits doit être supérieur ou égal à 0.";
        $typeMessage = "error";
    }
    else{

        $verif = $pdo->prepare("
            SELECT COUNT(*) 
            FROM cours 
            WHERE code_cours = ?
        ");
        $verif->execute([$codeCours]);

        if($verif->fetchColumn() > 0){
            $message = "Ce code cours existe déjà.";
            $typeMessage = "error";
        }else{

            try{

                $pdo->beginTransaction();
                $nbSequences = $nombreHeures*4;

                $sql = "
                    INSERT INTO cours(
                        code_cours,
                        intitule_cours,
                        id_enseignant,
                        nombre_heures,
                        nb_sequences,
                        nombre_credits,
                        actif
                    )
                    VALUES(
                        :code_cours,
                        :intitule_cours,
                        :id_enseignant,
                        :nombre_heures,
                        :nb_sequences,
                        :nombre_credits,
                        1
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    "code_cours" => $codeCours,
                    "intitule_cours" => $intituleCours,
                    "id_enseignant" => $idEnseignant,
                    "nombre_heures" => $nombreHeures,
                    "nombre_credits" => $nombreCredits,
                    "nb_sequences" => $nbSequences
                    ]);

                $idCours = $pdo->lastInsertId();

                $sqlAssoc = "
                    INSERT INTO cours_filiere(
                        id_cours,
                        id_filiere,
                        niveau,
                        semestre
                    )
                    VALUES(
                        :id_cours,
                        :id_filiere,
                        :niveau,
                        :semestre
                    )
                ";

                $stmtAssoc = $pdo->prepare($sqlAssoc);

                $stmtAssoc->execute([
                    "id_cours" => $idCours,
                    "id_filiere" => $idFiliere,
                    "niveau" => $niveau,
                    "semestre" => $semestre
                ]);

                $pdo->commit();

                $message = "Cours enregistré avec succès.";
                $typeMessage = "success";

            }catch(Exception $e){

                $pdo->rollBack();

                $message = "Erreur lors de l’enregistrement du cours : " . $e->getMessage();
                $typeMessage = "error";
            }
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
                <h1>Création d’un cours</h1>
                <p>Enregistrer un cours, l’affecter à un enseignant et le rattacher à une filière.</p>
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
                        <label>Code du cours <span>*</span></label>
                        <input type="text" name="code_cours" required placeholder="Ex : INF-L1-BD">
                    </div>

                    <div class="form-group">
                        <label>Intitulé du cours <span>*</span></label>
                        <input type="text" name="intitule_cours" required>
                    </div>

                    <div class="form-group">
                        <label>Enseignant responsable</label>
                        <select name="id_enseignant">
                            <option value="">Aucun enseignant affecté pour l’instant</option>

                            <?php foreach($enseignants as $enseignant): ?>
                                <option value="<?= $enseignant["id_enseignant"] ?>">
                                    <?= htmlspecialchars($enseignant["nom"] . " " . $enseignant["prenoms"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Filière <span>*</span></label>
                        <select name="id_filiere" required>
                            <option value="">-- Sélectionner une filière --</option>

                            <?php foreach($filieres as $filiere): ?>
                                <option value="<?= $filiere["id_filiere"] ?>">
                                    <?= htmlspecialchars($filiere["nom_filiere"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Niveau <span>*</span></label>
                        <select name="niveau" required>
                            <option value="">-- Sélectionner le niveau --</option>
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                            <option value="M1">M1</option>
                            <option value="M2">M2</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Semestre <span>*</span></label>
                        <select name="semestre" required>
                            <option value="">-- Sélectionner le semestre --</option>
                            <option value="S1">S1</option>
                            <option value="S2">S2</option>
                            <option value="S3">S3</option>
                            <option value="S4">S4</option>
                            <option value="S5">S5</option>
                            <option value="S6">S6</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nombre d’heures <span>*</span></label>
                        <input type="number" name="nombre_heures" min="1" step="0.5" required>
                        <p class="info-text">Le nombre de séquences est calculé automatiquement : 1 heure = 4 séquences.</p>
                    </div>

                    <div class="form-group">
                        <label>Nombre de crédits <span>*</span></label>
                        <input type="number" name="nombre_credits" min="0" required>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Enregistrer le cours
                        </button>

                        <a href="cours.php" class="btn-secondary">
                            Retour
                        </a>
                    </div>

                </form>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>