<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>

    <form action="/bonjour" method="post">
        <label for="name">Votre prénom :</label>
        <input
            type="text"
            id="name"
            name="name"
            value="<?= htmlspecialchars($data['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            maxlength="100"
            required
        >
        <button type="submit">Envoyer</button>
    </form>

    <?php if ($data['error'] !== ''): ?>
        <p role="alert"><?= htmlspecialchars($data['error'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($data['message'] !== ''): ?>
        <p><?= htmlspecialchars($data['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
    <?php endif; ?>

    <p><a href="/">Retour à l'accueil</a></p>
</body>
</html>
