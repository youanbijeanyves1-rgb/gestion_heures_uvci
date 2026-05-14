<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "ADMINISTRATEUR"){
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$typeMessage = "";

$roles = $pdo
    ->query("SELECT id_role, libelle_role FROM role ORDER BY libelle_role")
    ->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $login = trim($_POST["login"] ?? "");
    $motDePasse = $_POST["mot_de_passe"] ?? "";
    $confirmation = $_POST["confirmation"] ?? "";
    $idRole = $_POST["id_role"] ?? "";
    $actif = isset($_POST["actif"]) ? 1 : 0;

    if($login === "" || $motDePasse === "" || $confirmation === "" || $idRole === ""){
        $message = "Veuillez remplir tous les champs obligatoires.";
        $typeMessage = "error";
    }
    elseif(strlen($login) < 3){
        $message = "Le login doit contenir au moins 3 caractères.";
        $typeMessage = "error";
    }
    elseif(strlen($motDePasse) < 6){
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
        $typeMessage = "error";
    }
    elseif($motDePasse !== $confirmation){
        $message = "Les mots de passe ne correspondent pas.";
        $typeMessage = "error";
    }
    else{
        $verif = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE login = ?");
        $verif->execute([$login]);

        if($verif->fetchColumn() > 0){
            $message = "Ce login existe déjà.";
            $typeMessage = "error";
        }else{
            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);

            $sql = "INSERT INTO utilisateur(login, mot_de_passe_hash, actif, id_role)
                    VALUES(:login, :mot_de_passe_hash, :actif, :id_role)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "login" => $login,
                "mot_de_passe_hash" => $hash,
                "actif" => $actif,
                "id_role" => $idRole
            ]);

            $message = "Compte utilisateur créé avec succès.";
            $typeMessage = "success";
        }
    }
}

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_admin.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Création des comptes utilisateurs</h1>
                <p>Ajouter un nouvel utilisateur et lui attribuer un rôle.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>ADMINISTRATEUR</small>
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
                        <label>Login <span>*</span></label>
                        <input type="text" name="login" required>
                    </div>

                    <div class="form-group">
                        <label>Mot de passe <span>*</span></label>
                        <input type="password" name="mot_de_passe" required>
                    </div>

                    <div class="form-group">
                        <label>Confirmer le mot de passe <span>*</span></label>
                        <input type="password" name="confirmation" required>
                    </div>

                    <div class="form-group">
                        <label>Rôle <span>*</span></label>
                        <select name="id_role" required>
                            <option value="">-- Sélectionner un rôle --</option>

                            <?php foreach($roles as $role): ?>
                                <option value="<?= $role["id_role"] ?>">
                                    <?= htmlspecialchars($role["libelle_role"]) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="actif" id="actif" checked>
                        <label for="actif">Compte actif</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            Créer le compte
                        </button>

                        <a href="utilisateurs.php" class="btn-secondary">
                            Retour
                        </a>
                    </div>

                </form>

            </div>

        </section>

        <?php require_once "../includes/footer.php"; ?>

    </main>

</div>