<?php

require_once "../auth/verifier_session.php";
require_once "../config/database.php";

if($_SESSION["role"] !== "ADMINISTRATEUR"){
    header("Location: ../auth/login.php");
    exit;
}

$recherche = trim($_GET["recherche"] ?? "");

if($recherche !== ""){
    $sql = "SELECT u.id_utilisateur, u.login, u.actif, u.date_creation, r.libelle_role
            FROM utilisateur u
            JOIN role r ON r.id_role = u.id_role
            WHERE u.login LIKE :recherche
               OR r.libelle_role LIKE :recherche
            ORDER BY u.date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "recherche" => "%".$recherche."%"
    ]);
}else{
    $sql = "SELECT u.id_utilisateur, u.login, u.actif, u.date_creation, r.libelle_role
            FROM utilisateur u
            JOIN role r ON r.id_role = u.id_role
            ORDER BY u.date_creation DESC";

    $stmt = $pdo->query($sql);
}

$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once "../includes/header.php"; ?>

<div class="wrapper">

    <?php require_once "../includes/sidebar_admin.php"; ?>

    <main class="main">

        <header class="topbar">
            <div>
                <h1>Gestion des comptes utilisateurs</h1>
                <p>Consulter, rechercher, activer ou désactiver les comptes.</p>
            </div>

            <div class="user-box">
                <span><?= date("d/m/Y") ?></span>
                <strong><?= htmlspecialchars($_SESSION["login"]) ?></strong>
                <small>ADMINISTRATEUR</small>
            </div>
        </header>

        <section class="content">

            <div class="table-card">

                <div class="table-header">
                    <form method="GET" class="search-form">
                        <input
                            type="text"
                            name="recherche"
                            placeholder="Rechercher un utilisateur..."
                            value="<?= htmlspecialchars($recherche) ?>"
                        >

                        <button type="submit" class="btn-primary">
                            Rechercher
                        </button>

                        <a href="liste_utilisateurs.php" class="btn-secondary">
                            Réinitialiser
                        </a>
                    </form>

                    <a href="creer_utilisateur.php" class="btn-primary">
                        + Nouveau compte
                    </a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Login</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Date création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if(count($utilisateurs) > 0): ?>

                            <?php foreach($utilisateurs as $utilisateur): ?>
                                <tr>
                                    <td><?= $utilisateur["id_utilisateur"] ?></td>

                                    <td>
                                        <?= htmlspecialchars($utilisateur["login"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($utilisateur["libelle_role"]) ?>
                                    </td>

                                    <td>
                                        <?php if($utilisateur["actif"]): ?>
                                            <span class="badge success">ACTIF</span>
                                        <?php else: ?>
                                            <span class="badge danger">INACTIF</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= date("d/m/Y H:i", strtotime($utilisateur["date_creation"])) ?>
                                    </td>

                                    <td class="actions">

                                        <a href="modifier_utilisateur.php?id=<?= $utilisateur["id_utilisateur"] ?>"
                                           class="btn-small">
                                            Modifier
                                        </a>

                                        <?php if($utilisateur["id_utilisateur"] != $_SESSION["id_utilisateur"]): ?>

                                            <?php if($utilisateur["actif"]): ?>
                                                <a href="toggle_utilisateur.php?id=<?= $utilisateur["id_utilisateur"] ?>&action=desactiver"
                                                   class="btn-small danger"
                                                   onclick="return confirm('Voulez-vous vraiment désactiver ce compte ?');">
                                                    Désactiver
                                                </a>
                                            <?php else: ?>
                                                <a href="toggle_utilisateur.php?id=<?= $utilisateur["id_utilisateur"] ?>&action=activer"
                                                   class="btn-small success"
                                                   onclick="return confirm('Voulez-vous vraiment activer ce compte ?');">
                                                    Activer
                                                </a>
                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Compte courant
                                            </span>

                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="empty">
                                    Aucun utilisateur trouvé.
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