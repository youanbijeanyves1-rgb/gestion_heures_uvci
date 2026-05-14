<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

$message = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if($login === "" || $password === ""){
        $message = "Veuillez renseigner le login et le mot de passe.";
    }else{

        $stmt = $pdo->prepare("
            SELECT 
                u.id_utilisateur,
                u.login,
                u.mot_de_passe_hash,
                u.actif,
                r.libelle_role
            FROM utilisateur u
            JOIN role r ON r.id_role = u.id_role
            WHERE u.login = ?
            LIMIT 1
        ");

        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$user){
            $message = "Identifiant ou mot de passe incorrect.";
        }
        elseif((int)$user["actif"] !== 1){
            $message = "Ce compte utilisateur est désactivé.";
        }
        elseif(!password_verify($password, $user["mot_de_passe_hash"])){
            $message = "Identifiant ou mot de passe incorrect.";
        }
        else{

            $role = trim($user["libelle_role"]);

            $_SESSION["id_utilisateur"] = $user["id_utilisateur"];
            $_SESSION["login"] = $user["login"];
            $_SESSION["role"] = $role;

            if($role === "ADMINISTRATEUR"){
                header("Location: ../admin/dashboard.php");
                exit;
            }

            if($role === "SECRETAIRE_PRINCIPAL"){
                header("Location: ../secretaire/dashboard.php");
                exit;
            }

            if($role === "ENSEIGNANT"){
                header("Location: ../enseignant/dashboard.php");
                exit;
            }

            session_unset();
            session_destroy();
            $message = "Rôle utilisateur non reconnu.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion UVCI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        body{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg,#1e40af,#06b6d4);
        }

        .login-card{
            width:100%;
            max-width:430px;
            background:white;
            border-radius:22px;
            padding:40px 35px;
            box-shadow:0 25px 60px rgba(0,0,0,.25);
            text-align:center;
        }

        .logo{
            width:95px;
            height:95px;
            object-fit:contain;
            margin-bottom:15px;
        }

        h1{
            font-size:22px;
            color:#1e293b;
            margin-bottom:8px;
            text-transform:uppercase;
            letter-spacing:.5px;
        }

        .subtitle{
            color:#64748b;
            margin-bottom:28px;
            font-size:14px;
        }

        .alert{
            background:#fee2e2;
            color:#991b1b;
            padding:12px;
            border-radius:10px;
            margin-bottom:20px;
            font-size:14px;
            text-align:left;
        }

        .form-group{
            text-align:left;
            margin-bottom:18px;
        }

        label{
            display:block;
            font-weight:bold;
            color:#334155;
            margin-bottom:7px;
            font-size:14px;
        }

        input{
            width:100%;
            padding:14px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            font-size:15px;
            outline:none;
        }

        input:focus{
            border-color:#06b6d4;
            box-shadow:0 0 0 3px rgba(6,182,212,.15);
        }

        .btn-login{
            width:100%;
            border:none;
            padding:14px;
            border-radius:10px;
            background:linear-gradient(135deg,#0891b2,#22c55e);
            color:white;
            font-size:15px;
            font-weight:bold;
            cursor:pointer;
            margin-top:8px;
        }

        .btn-login:hover{
            opacity:.92;
        }

        .back-link{
            display:inline-block;
            margin-top:22px;
            color:#334155;
            text-decoration:none;
            font-size:14px;
        }

        .back-link:hover{
            text-decoration:underline;
        }

        .footer-text{
            margin-top:25px;
            color:#94a3b8;
            font-size:12px;
        }

        @media(max-width:500px){
            body{
                padding:20px;
            }

            .login-card{
                padding:30px 22px;
            }
        }
    </style>
</head>

<body>

    <div class="login-card">

        <img 
            src="../assets/img/logo_uvci.png" 
            alt="Logo UVCI" 
            class="logo"
        >

        <h1>Connexion espace utilisateur</h1>

        <p class="subtitle">
            Accès sécurisé à la plateforme de gestion des heures
        </p>

        <?php if($message !== ""): ?>
            <div class="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Login</label>
                <input 
                    type="text" 
                    name="login" 
                    placeholder="Votre identifiant"
                    required
                >
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Votre mot de passe"
                    required
                >
            </div>

            <button type="submit" class="btn-login">
                Connexion
            </button>

        </form>

        <a href="../index.php" class="back-link">
            Retour à l’accueil
        </a>

        <div class="footer-text">
            © <?= date("Y") ?> UVCI — Gestion des Heures des Enseignants
        </div>

    </div>

</body>
</html>