<?php

namespace Deployer;

// Only run storage:link and migrations on the web server
task('artisan:storage:link')->select('role=web');
// task('artisan:migrate')->select('role=web');

// deploy/web.php

set('npm_path', 'npm');
set('node_env', 'production');

task('deploy:npm:install', function () {
    writeln('<info>📦 Installing NPM dependencies...</info>');
    writeln('<info>📦 Installing NPM dependencies...</info>');

    // Do NOT copy node_modules from previous release
    // It carries over broken platform-specific binaries (rollup issue)

    // Clean install from scratch every time
    run('cd {{release_path}} && rm -rf node_modules package-lock.json');
    run('cd {{release_path}} && {{npm_path}} install --no-audit --no-progress');

    // Explicitly install rollup linux binary after clean install
    run('cd {{release_path}} && {{npm_path}} install @rollup/rollup-linux-x64-gnu --save-optional --no-audit');

    writeln('<info>✅ NPM dependencies installed</info>');
})->select('role=web');

task('deploy:npm:build', function () {
    $hasChanges = true;

    if (has('previous_release')) {
        $previousRevision = run('cat {{previous_release}}/REVISION 2>/dev/null || echo ""');
        $currentRevision  = run('cat {{release_path}}/REVISION 2>/dev/null || echo ""');

        // Only diff if we have both revisions
        if (!empty(trim($previousRevision)) && !empty(trim($currentRevision))) {
            $changedFiles = run(
                'cd {{release_path}} && git diff --name-only ' .
                    escapeshellarg(trim($previousRevision)) . ' ' .
                    escapeshellarg(trim($currentRevision)) . ' -- ' .
                    'resources/js resources/css resources/sass resources/views ' .
                    'vite.config.js webpack.mix.js package.json package-lock.json ' .
                    '2>/dev/null || echo ""'
            );
            $hasChanges = !empty(trim($changedFiles));
        }

        // Force rebuild if no manifest exists in previous release
        if (!$hasChanges) {
            $hasChanges = !test('[ -f {{previous_release}}/public/build/manifest.json ]') &&
                !test('[ -f {{previous_release}}/public/mix-manifest.json ]');
        }
    }

    if ($hasChanges) {
        writeln('<info>🔨 Frontend files changed, building assets...</info>');

        if (test('[ -f {{release_path}}/vite.config.js ]')) {
            run('cd {{release_path}} && NODE_ENV={{node_env}} {{npm_path}} run build');
        } elseif (test('[ -f {{release_path}}/webpack.mix.js ]')) {
            run('cd {{release_path}} && NODE_ENV={{node_env}} {{npm_path}} run production');
        } else {
            writeln('<comment>⚠ No build config found (vite.config.js or webpack.mix.js)</comment>');
        }

        writeln('<info>✅ Assets built successfully</info>');
    } else {
        writeln('<comment>🎨 Frontend unchanged, copying assets from previous release...</comment>');

        foreach (['build', 'css', 'js', 'fonts', 'images', '.vite'] as $dir) {
            run("cp -r {{previous_release}}/public/{$dir} {{release_path}}/public/{$dir} 2>/dev/null || true");
        }

        // Copy manifest files
        run('cp {{previous_release}}/public/mix-manifest.json {{release_path}}/public/mix-manifest.json 2>/dev/null || true');
        run('cp {{previous_release}}/public/build/manifest.json {{release_path}}/public/build/manifest.json 2>/dev/null || true');
    }
})->select('role=web');

task('deploy:assets:clean', function () {
    // Remove node_modules from old releases, keeping last 3
    run('cd {{deploy_path}}/releases && ls -t | tail -n +4 | xargs -I {} rm -rf {}/node_modules 2>/dev/null || true');
})->select('role=web');

task('deploy:assets', function () {
    writeln('<info>🎨 Processing frontend assets...</info>');
    invoke('deploy:npm:install');
    invoke('deploy:npm:build');
    invoke('deploy:assets:clean');
})->select('role=web');

task('deploy:web', function () {
    writeln('<info>🌐 Deploying to web server...</info>');
    invoke('deploy:assets');

    // Reset OPcache on the server (not local function_exists check)
    run('{{bin/php}} -r "function_exists(\'opcache_reset\') && opcache_reset();"');

    run('sudo systemctl reload php-fpm');
    // run('sudo systemctl reload nginx');

    writeln('<info>✅ Web server deployment complete</info>');
})->select('role=web');
