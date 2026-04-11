<?php
// SIDIK-TI - Centralized Head Meta & Assets
$pageTitle = $pageTitle ?? 'SIDIK-TI | Asset Management';
?>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo $pageTitle; ?></title>

<!-- Dark Mode Init (runs before CSS paint to prevent flash) -->
<script>
(function(){
    var t=localStorage.getItem('theme');
    if(t==='dark'||(!t&&window.matchMedia('(prefers-color-scheme:dark)').matches)){
        document.documentElement.classList.add('dark');
    }
})();
</script>

<!-- Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>

<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<!-- Compiled Tailwind CSS -->
<link href="<?php echo $base_url ?? '../'; ?>public/css/style.css" rel="stylesheet"/>

<!-- Inline style for material symbols (fallback/alignment) -->
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        vertical-align: middle;
    }
</style>
