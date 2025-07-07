<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo e(asset('assets')); ?>/images/logo.png">
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets')); ?>/images/logo.png">

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

    <!-- Scripts -->
    <script @cspNonce src="<?php echo e(mix('js/app.js', 'themes/admin')); ?>" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link @cspNonce href="<?php echo e(mix('css/app.css', 'themes/admin')); ?>" rel="stylesheet">
</head>

<body>
    <div id="app">
        <?php echo $__env->make('layouts.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <main class="py-4">
            <?php echo e($slot); ?>

        </main>
    </div>
</body>

</html>
<?php /**PATH C:\Users\episo\Documents\AptitudeTest\themes\admin\views/layouts/app.blade.php ENDPATH**/ ?>