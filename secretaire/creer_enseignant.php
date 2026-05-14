<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "SECRETAIRE_PRINCIPAL"){
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$typeMessage = "";

$grades = $pdo->query("SELECT id_grade, libelle_grade FROM grade ORDER BY libelle_grade")
              ->fetchAll(PDO::FETCH_ASSOC);

$departements = $pdo->query("SELECT id_departement, nom_departement FROM departement WHERE actif = 1 ORDER BY nom_departement")
                    ->fetchAll(PDO::FETCH_ASSOC);

$tauxHoraires = $pdo->query("SELECT id_taux, categorie, montant FROM taux_horaire WHERE actif = 1 ORDER BY categorie")
                    ->fetchAll(PDO::FETCH_ASSOC);

$comptesEnseignants = $pdo->query("
    SELECT u.id_utilisateur, u.login
    FROM utilisateur u
    JOIN role r ON r.id_role = u.id_role
    LEFT JOIN enseignant e ON e.id_utilisateur = u.id_utilisateur
    WHERE r.libelle_role = 'ENSEIGNANT'
      AND u.actif = 1
      AND e.id_enseignant IS NULL
    ORDER BY u.login
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $nom = trim($_POST["nom"] ?? "");
    $prenoms = trim($_POST["prenoms"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $statut = $_POST["statut"] ?? "";
    $idGrade = $_POST["id_grade"] ?? "";
    $idDepartement = $_POST["id_departement"] ?? "";
    $idTaux = $_POST["id_taux"] ?? "";
    $idUtilisateur = $_POST["id_utilisateur"] ?? null;

    if($idUtilisateur === ""){
        $idUtilisateur = null;
    }

    if(
        $nom === "" ||
        $prenoms === "" ||
        $email === "" ||
        $telephone === "" ||
        $statut === "" ||
        $idGrade === "" ||
        $idDepartement === "" ||
        $idTaux === ""
    ){
        $message = "Veuillez remplir tous les champs obligatoires.";
        $typeMessage = "error";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $message = "L'adresse email n'est pas valide.";
        $typeMessage = "error";
    }
    elseif(!in_array($statut, ["PERMANENT", "VACATAIRE"])){
        $message = "Statut invalide.";
        $typeMessage = "error";
    }
    else{
        $verifEmail = $pdo->prepare("SELECT COUNT(*) FROM enseignant WHERE email = ?");
        $verifEmail->execute([$email]);

        if($verifEmail->fetchColumn() > 0){
            $message = "Cet email est déjà utilisé par un autre enseignant.";
            $typeMessage = "error";
        }else{
            $sql = "INSERT INTO enseignant(
                        nom,
                        prenoms,
                        email,
                        telephone,
                        statut,
                        actif,
                        id_departement,
                        id_grade,
                        id_taux,
                        id_utilisateur
                    )
                    VALUES(
                        :nom,
                        :prenoms,
                        :email,
                        :telephone,
                        :statut,
                        1,
                        :id_departement,
                        :id_grade,
                        :id_taux,
                        :id_utilisateur
                    )";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                "nom" => $nom,
                "prenoms" => $prenoms,
                "email" => $email,
                "telephone" => $telephone,
                "statut" => $statut,
                "id_departement" => $idDepartement,
                "id_grade" => $idGrade,
                "id_taux" => $idTaux,
                "id_utilisateur" => $idUtilisateur
            ]);

            $message = "Enseignant enregistré avec succès.";
            $typeMessage = "success";
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
                <h1>Création d’un enseignant</h1>
                <p>Enregistrer les informations administratives d’un enseignant.</p>
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
                        <label>Nom <span>*</span></label>
                        <input type="text" name="nom" required>
                    </div>

                    <div class="form-group">
                        <label>Prénoms <span>*</span></label>
                        <input type="text" name="prenoms" required>
                    </div>

                    <div class="form-group">
                        <label>Email <span>*</span></label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Téléphone <span>*</span></label>
                        <input type="text" name="telephone" required>
                    </div>

                    <div class="form-group">
                        <label>Statut <span>*</span></label>
                        <select name="statut" required>
                            <option value="">-- Sélectionner le statut --</option>
                            <option value="PERMANENT">Permanent</option>
                            <option value="VACATAIRE">Vacataire</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Grade <span>*</span></label>
                        <select name="id_grade" required>
                            <option value="">-- Sélectionner un grade --</option>

                            <?php foreach($grades as $grade): ?>
                                <option value="<?= $grade["id_grade"] ?>">
                                    <?= htmlspecialchars($grade["libelle_grade"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Département <span>*</span></label>
                        <select name="id_departement" required>
                            <option value="">-- Sélectionner un département --</option>

                            <?php foreach($departements as $departement): ?>
                                <option value="<?= $departement["id_departement"] ?>">
                                    <?= htmlspecialchars($departement["nom_departement"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Taux horaire <span>*</span></label>
                        <select name="id_taux" required>
                            <option value="">-- Sélectionner un taux horaire --</option>

                            <?php foreach($tauxHoraires as $taux): ?>
                                <option value="<?= $taux["id_taux"] ?>">
                                    <?= htmlspecialchars($taux["categorie"]) ?>
                                    —
                                    <?= number_format($taux["montant"], 0, ',', ' ') ?> FCFA
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Compte utilisateur lié</label>
                        <select name="id_utilisateur">
                            <option value="">Aucun compte lié pour l’instant</option>

                            <?php foreach($comptesEnseignants as $compte): ?>
                                <option value="<?= $compte["id_utilisateur"] ?>">
                                    <?= htmlspecialchars($compte["login"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Enregistrer l’enseignant
                        </button>

                        <a href="enseignants.php" class="btn-secondary">
                            Retour
                        </a>
                    </div>

                </form>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>