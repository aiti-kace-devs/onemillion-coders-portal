<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo e(asset('assets')); ?>/images/logo.png">
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets')); ?>/images/logo.png">
    <?php echo csp_meta_tag(\App\Helpers\BasePolicy::class) ?>
    <meta property="csp-nonce" content="<?php echo e(csp_nonce()); ?>">
    <title inertia><?php echo e(config('app.name', 'Laravel')); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="/DataTables-1.13.8/css/jquery.dataTables.css">
    
    <link rel="stylesheet" href="<?php echo e(url('/assets/plugin/toastr/toastr.min.css')); ?>">


    <!-- Scripts -->
    
    <script src="<?php echo e(url('/assets/plugin/jquery/jquery.min.js')); ?>" referrerpolicy="no-referrer"></script>
    <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(nonce: csp_nonce()); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"]); ?>
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
</head>

<body class="font-sans antialiased">
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>

    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?> src="<?php echo e(asset('assets')); ?>/js/core/jquery.min.js"></script>
    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?> src="<?php echo e(asset('assets')); ?>/js/core/popper.min.js"></script>
    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?> src="<?php echo e(asset('assets')); ?>/js/core/bootstrap.min.js"></script>
    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?> src="<?php echo e(asset('assets')); ?>/plugins/toastr/toastr.min.js"></script>
    <script <?php echo "nonce=\"" . csp_nonce() . "\""; ?> type="text/javascript" src="/DataTables-1.13.8/js/jquery.dataTables.js"></script>
    
</body>

</html>
<?php /**PATH C:\Users\episo\Documents\AptitudeTest\resources\views/app.blade.php ENDPATH**/ ?>