<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="/asset/style/main.css">
    <title><?= htmlspecialchars($title ?? 'Erreur', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
</head>
<body>
    <main class="container">
        <h1><?= (int) $data['status'] ?> — <?= htmlspecialchars($data['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($data['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <?php if (!empty($data['link'])): ?>
            <a href="<?= htmlspecialchars($data['link'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <?= htmlspecialchars($data['linkText'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </a>
        <?php endif; ?>
    </main>
</body>
</html>
