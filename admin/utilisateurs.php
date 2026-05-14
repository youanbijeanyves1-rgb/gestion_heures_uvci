<?php

require_once "../auth/verifier_session.php";

if($_SESSION["role"] !== "ADMINISTRATEUR"){
    header("Location: ../auth/login.php");
    exit;
}

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_admin.php"; ?>

    <div class="main">

        <div class="topbar">

            <div>
                <h1>Comptes utilisateurs</h1>
                <p>
                    Gestion des accès et des utilisateurs du système
                </p>
            </div>

            <div class="user-box">

                <span><?= date("d/m/Y") ?></span>

                <strong>
                    <?= htmlspecialchars($_SESSION["login"]) ?>
                </strong>

                <small>ADMINISTRATEUR</small>

            </div>

        </div>

        <div class="content">

            <div class="module-grid">

                <!-- CREATION -->

                <a href="creer_utilisateur.php"
                   class="module-link">

                    <div class="module-card">

                        <div class="module-icon purple">
                            👤
                        </div>

                        <h3>
                            Création des comptes utilisateurs
                        </h3>

                        <p>
                            Ajouter de nouveaux utilisateurs
                            et attribuer leurs rôles.
                        </p>
                        <span class="module-btn">Accéder</span>

                    </div>

                </a>

                <!-- GESTION -->

                <a href="liste_utilisateurs.php"
                   class="module-link">

                    <div class="module-card">

                        <div class="module-icon blue">
                            ⚙️
                        </div>

                        <h3>
                            Gestion des comptes utilisateurs
                        </h3>

                        <p>
                            Consulter, modifier,
                            activer ou désactiver
                            les comptes existants.
                        </p>
                        <span class="module-btn">Accéder</span>

                    </div>

                </a>

            </div>

        </div>

        <?php require_once "../includes/footer.php"; ?>

    </div>

</div>