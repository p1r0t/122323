<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Ministerstvo vnútra SR'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="gov-header">
        <div class="gov-logo">🇸🇰 Slovenská Republika</div>
        <div class="gov-title">Ministerstvo vnútra SR</div>
        <?php if (isset($pageSubtitle)): ?>
        <div class="gov-subtitle"><?php echo $pageSubtitle; ?></div>
        <?php endif; ?>
    </div>
