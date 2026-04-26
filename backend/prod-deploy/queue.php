<?php

namespace Deployer;

task('deploy:queue', function () {
    run('sudo supervisorctl restart all');
    writeln('<info>✅ Queue workers restarted</info>');
})->select('role=queue');
