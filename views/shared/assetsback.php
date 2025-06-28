<?php
/**
 * BACKUP: Archivo de respaldo para assets
 * El archivo principal es ahora assets.php con Material Design 3
 * 
 * @backup assets.php backup version
 */

// Este es un archivo de respaldo, el principal es assets.php
?>

<!-- FALLBACK: CSS básicos si assets.php no existe -->
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/core.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/components.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/material-theme.css">
<link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/layouts.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="shortcut icon" href="<?= BASE_PUBLIC_URL ?>/assets/images/favicon.ico" type="image/x-icon">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap">


<!-- CSS específicos de módulo (compatibilidad) -->
<?php if (isset($moduleCSS)): ?>
    <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/modules/<?= $moduleCSS ?>.css">
<?php endif; ?>

<!-- CSS adicionales específicos (compatibilidad) -->
<?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
    <?php foreach ($additionalCSS as $css): ?>
        <link rel="stylesheet" href="<?= BASE_PUBLIC_URL ?>/assets/css/<?= $css ?>">
    <?php endforeach; ?>
<?php endif; ?>

<!-- Material Design 3 Note -->
<script>
console.log('BACKUP: Loading assets from backup file. Main file is assets.php');
</script>