<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
/*
Tout le code doit se faire dans ce fichier PHP

Réalisez un formulaire HTML contenant :
- firstname
- lastname
- email
- pwd
- pwdConfirm

Créer une table "user" dans la base de données, regardez le .env à la racine et faites un build de docker
si vous n'arrivez pas à les récupérer pour qu'il les prenne en compte

Lors de la validation du formulaire vous devez :
- Nettoyer les valeurs, exemple trim sur l'email et lowercase (5 points)
- Attention au mot de passe (3 points)
- Attention à l'unicité de l'email (4 points)
- Vérifier les champs sachant que le prénom et le nom sont facultatifs
- Insérer en BDD avec PDO et des requêtes préparées si tout est OK (4 points)
- Sinon afficher les erreurs et remettre les valeurs pertinentes dans les inputs (4 points)

Le design je m'en fiche mais pas la sécurité

Bonus de 3 points si vous arrivez à envoyer un mail via un compte SMTP de votre choix
pour valider l'adresse email en bdd

Pour le : 22 Octobre 2025 - 8h
M'envoyer un lien par mail de votre repo sur y.skrzypczyk@gmail.com
Objet du mail : TP1 - 2IW3 - Nom Prénom
Si vous ne savez pas mettre votre code sur un repo envoyez moi une archive
*/

// config
$host = 'db'; $dbname = 'postgres'; $user = 'devuser'; $pass = 'devpass';

try {
    // objet PDO (pour se connecter à la base de donnees)
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);

    // exceptions s'il y a des erreurs
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // flux
} catch (PDOException $e) {
    // connexion echouee, script arrete et erreur affichee
    die("Connexion à la base échouée : " . $e->getMessage());
}

$errors = []; // tab d'erreurs
$success = '';
$firstname = $lastname = $email = ''; // vr pour les champs du formulaire

// ================= TRAITEMENT =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // formulaire soumis

    $firstname = trim($_POST['firstname'] ?? ''); // nettoyage
    $lastname = trim($_POST['lastname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pwd = $_POST['pwd'] ?? '';
    $pwdConfirm = $_POST['pwdConfirm'] ?? '';

    // ======== email ===========
    if (empty($email)) {
        $errors[] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    } else { // mail unique
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM "user" WHERE email = :email');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }

    // ======== mdp =========== 
    if (empty($pwd) || empty($pwdConfirm)) {
        $errors[] = "Le mot de passe et la confirmation sont obligatoires.";
    } elseif ($pwd !== $pwdConfirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($pwd) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }


    // ======== insertion si tout est bon ======== 
    if (empty($errors)) {
        $password_hash = password_hash($pwd, PASSWORD_DEFAULT);

       
      
        $stmt = $pdo->prepare('
            INSERT INTO "user" (firstname, lastname, email, password_hash) 
            VALUES (:firstname, :lastname, :email, :password_hash)
        ');
        $stmt->execute([
            'firstname' => $firstname ?: null, // firstname et lastname sont facultatifs : s'ils sont vides, on enregistre NULL
            'lastname' => $lastname ?: null,
            'email' => $email,
            'password_hash' => $password_hash,
            
        ]);

        

        $mail = new PHPMailer(true);

        try{
            //trop de trucs a configurer
            //smtp
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = 'smtp.gmail.com';
            $mail->Username = 'ourkitchency@gmail.com'; //mail déja utilise sur un autre projet
            $mail->Password = 'llxpspneydlofsxe';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('zedjiga@gmail.com','Pour le TP de PHP');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Incription confirmée ';
            $mail->Body = "
            <p>(et donc jai les points bonus :) ?? )</p> ";
            $mail->send();
            $success = "Inscription réussie, tu as reçu un mail de confirmation";

        } catch (Exception $e){
            $success = "Inscription réussie, mais l'envoi de mai a echouee";


        }

        
        $firstname = $lastname = $email = '';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP1 - PHP</title>
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #ffe4ec, #ffd6f4);
            margin: 0;
            padding: 50px;
            color: #444;
        }

        h2 {
            text-align: center;
            color: #d63384;
            margin-bottom: 20px;
            font-size: 28px;
        }

        form {
            background: white;
            padding: 50px;
            border-radius: 20px;
            width: 400px;
            margin: auto;
            box-shadow: 0 0 15px rgba(214, 51, 132, 0.2);
            border: 2px solid #ffc8dd;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: 600;
            color: #d63384;
        }

        input {
            width: 95%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #f3a8c6;
            border-radius: 8px;
            background: #fff0f6;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #ff99c8;
            background: #ffeaf3;
            box-shadow: 0 0 5px #ffb3d9;
        }

        button {
            margin-top: 20px;
            padding: 12px;
            width: 100%;
            background: linear-gradient(135deg, #ff9ecb, #ff66a3);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            font-size: 15px;
        }

        button:hover {
            background: linear-gradient(135deg, #ff66a3, #ff4d94);
            transform: translateY(-2px);
        }

        .error {
            color: #e63946;
            background: #ffe5ec;
            border-left: 4px solid #ff99c8;
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 5px;
        }

        .success {
            color: #2b9348;
            background: #d8f3dc;
            border-left: 4px solid #95d5b2;
            padding: 8px;
            border-radius: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>

    <h2>Formulaire d'inscription</h2>

    <?php
    // affichage erreur
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p class='error'>$error</p>";
        }
    }

    // affichage succès
    if (!empty($success)) {
        echo "<p class='success'>$success</p>";
    }
    ?>

    <!-- formulaire -->
    <form method="POST" action="">
        <label> Prénom : </label>
        <input type="text" name="firstname" value="<?= htmlspecialchars($firstname) ?>">

        <label> Nom : </label>
        <input type="text" name="lastname" value="<?= htmlspecialchars($lastname) ?>">
        <!-- sécuriser l’affichage de données dans le HTML -->

        <label> Adresse mail : </label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label> Mot de passe : </label>
        <input type="password" name="pwd" required>

        <label> Confirmation du mot de passe : </label>
        <input type="password" name="pwdConfirm" required>

        <button type="submit"> Inscription </button>
    </form>

</body>

</html>