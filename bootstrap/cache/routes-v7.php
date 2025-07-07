<?php

/*
|--------------------------------------------------------------------------
| Load The Cached Routes
|--------------------------------------------------------------------------
|
| Here we will decode and unserialize the RouteCollection instance that
| holds all of the route information for an application. This allows
| us to instantaneously load the entire route map into the router.
|
*/

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/_debugbar/open' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debugbar.openhandler',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_debugbar/assets/stylesheets' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debugbar.assets.css',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_debugbar/assets/javascript' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debugbar.assets.js',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_debugbar/queries/explain' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debugbar.queries.explain',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/env-editor' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/env-editor/key' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.key',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::2D4eE9fUFTyKMHFs',
          ),
          1 => NULL,
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'generated::3RvFGrC7nqxCdpSt',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/env-editor/clear-cache' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.clearConfigCache',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/env-editor/files' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.getBackups',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/env-editor/files/create-backup' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.createBackup',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/env-editor/files/upload' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.upload',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.stats.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/workload' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.workload.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/masters' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.masters.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/monitoring' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.monitoring.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.monitoring.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/metrics/jobs' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.jobs-metrics.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/metrics/queues' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.queues-metrics.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/batches' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.jobs-batches.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/jobs/pending' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.pending-jobs.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/jobs/completed' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.completed-jobs.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/jobs/silenced' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.silenced-jobs.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/horizon/api/jobs/failed' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.failed-jobs.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/sanctum/csrf-cookie' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sanctum.csrf-cookie',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Ud99HaFatfptGklT',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/templates' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'templateList',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/templates/new' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'selectNewTemplate',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'createNewTemplate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/templates/delete' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'deleteTemplate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/templates/update' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'updateTemplate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/templates/preview' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'previewTemplateMarkdownView',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/mailables' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'mailableList',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/mailables/parse/template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'parseTemplate',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/mailables/new' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'createMailable',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generateMailable',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/mailables/delete' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'deleteMailable',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/mailables/preview/template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'previewMarkdownView',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/mailables/preview/template/previewerror' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'templatePreviewError',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/maileclipse/mailables/send-test' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sendTestMail',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_ignition/health-check' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'ignition.healthCheck',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_ignition/execute-solution' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'ignition.executeSolution',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_ignition/update-config' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'ignition.updateConfig',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/user' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::G2CET4Sbp0iQoGIz',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/student/register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::qIV4e6eXg2DiUH2O',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/generate_qrcode' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::yuxImuUWNm4NBf60',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/addStudent' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Fp3D8GwvxNQF2fFf',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::AfD53QrIAihHITYk',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/available-courses' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'available-courses',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/application' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'application',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/form-responses' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/forms' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/forms/fetch' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.fetch',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/forms/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/form-responses' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/form-responses/fetch' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.fetch',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/form-responses/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/exam_category' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.category.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/add_new_category' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.category.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/edit_new_category' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.category.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage_exam' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/add_new_exam' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/edit_exam_sub' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/edit_question_inner' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.questions.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/add_new_question' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.questions.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/registered_students' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.student.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/student/admit' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admit_student',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/admit' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admit_user_ui',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/shortlisted_students' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.shortlisted_students',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/save_shortlisted_students' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.save_shortlisted_students',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage_students' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.manage_students',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/edit_students_final' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.student.update',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/add_new_students' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.student.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/confirm_attendance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::1xKFA5BPOgw7MQcr',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/view_attendance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.viewAttendanceByDate',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/generate_qrcode' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::cgnNi3LTC9ChoKI2',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::0YWYhhZksgOTAu9k',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/scan_qrcode' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::KmHZLeGl1SCS05Rp',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/verification' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.verification',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/verify_details' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.verify-details',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/reports' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.getReportView',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generateReport',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/update-admin-courses' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admin.update-admin-courses',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage_admins' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.manage_admins',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admins.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/add_new_admin' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::KS1mumXhF92ghzRp',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-branch' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.branch.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.branch.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-centre' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.centre.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.centre.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-programme' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.programme.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.programme.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-course' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-course/fetch-programme' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.fetch.programme',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-course/fetch/centre' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.fetch.centre',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sessions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.session.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.session.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sessions/fetch' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.session.fetch',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/sessions/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.session.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-class-schedule' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.class.schedule.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.class.schedule.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-sms-template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sms.template.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sms.template.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-sms-template/fetch_sms_template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.fetch.sms.template',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-sms-template/send-bulk-email' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.send_bulk_email',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-sms-template/send_bulk_sms' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.send_bulk_sms',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/manage-email-template' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.email.template.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.email.template.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/app-config' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.config.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.config.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/lists/fetch' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.fetch',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/lists/view-data' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.view-data',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/lists/get-table-columns' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.get-table-columns',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/lists' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/lists/create' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.create',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/dashboard' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.dashboard',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/application-status' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.application-status',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.profile',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/change-course' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.change-course',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/update-course' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.update-course',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/exam' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::6PDhptfrruULBgVi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/submit_questions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::Qb0mEuHiUoGgBeGy',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/attendance/record' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.attendance.record',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/attendance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.attendance.show',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/id-qrcode' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::X1QZ25pL77T5c7Mn',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/scan-qrcode' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::6OJG8LficUjtYRDs',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/meeting-link' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::5lZAWpfkmySWpeR0',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/update-details' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.updateDetails',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/mark_attendance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.mark-attendance',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/student/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::1Z5XKfqu4ZW7yLHe',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'profile.edit',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'profile.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PATCH' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'profile.destroy',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'login',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dvDPzZUZI2OrYyfP',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/forgot-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.request',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'password.email',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/reset-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/verify-email' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.notice',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/email/verification-notification' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.send',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/confirm-password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.confirm',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::JhoNPb7EcOKIjigE',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.update',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'logout',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.login',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::9YcZzlCg8ylvadDi',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.logout',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/admin/register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.register',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::Gu8FrrAQX39KnFn1',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/_debugbar/c(?|lockwork/([^/]++)(*:39)|ache/([^/]++)(?:/([^/]++))?(*:73))|/a(?|dmin/env\\-editor/files/(?|restore\\-backup(?:/([^/]++))?(*:141)|d(?|estroy\\-backup(?:/([^/]++))?(*:181)|ownload(?:/([^/]++))?(*:210)))|ssets(.*)(*:229))|/uploads(.*)(*:250)|/builder/manage(.*)(*:277)|/horizon(?|/api/(?|m(?|onitoring/(?|([^/]++)(*:329)|(.*)(*:341))|etrics/(?|jobs/([^/]++)(*:373)|queues/([^/]++)(*:396)))|batches/(?|([^/]++)(*:425)|retry/([^/]++)(*:447))|jobs/(?|failed/([^/]++)(*:479)|retry/([^/]++)(*:501)|([^/]++)(*:517)))|(?:/((?:.*)))?(*:541))|/maileclipse/(?|templates/(?|new/([^/]++)/([^/]++)/([^/]++)(*:609)|edit/([^/]++)(*:630))|mailables/(?|view/([^/]++)(*:665)|edit/template/([^/]++)(*:695)|preview/([^/]++)(*:719)))|/(cybersecurity-course|ai-course|data-protection-course|protection-expert-course|protection-sup-course|certified-dpf-course|cnst-course)(*:865)|/forms/([^/]++)(*:888)|/admin/(?|form(?|s/([^/]++)/(?|e(?|dit(*:934)|xport(*:947))|update(*:962)|preview(*:977)|responses(*:994)|destroy(*:1009))|\\-responses/([^/]++)/(?|edit(*:1047)|update(*:1062)|view(*:1075)|destroy(*:1091)))|category_status/([^/]++)(*:1126)|e(?|dit_(?|category/([^/]++)(*:1163)|exam/([^/]++)(*:1185)|admin/([^/]++)/edit(*:1213))|xam_status/([^/]++)(*:1242))|delete_(?|category/([^/]++)(*:1279)|exam/([^/]++)(*:1301)|question/([^/]++)(*:1327)|students/([^/]++)(*:1353)|registered_students/([^/]++)(*:1390))|a(?|d(?|d_questions/([^/]++)(*:1428)|min_view_result/([^/]++)(*:1461))|pply_exam/([^/]++)(*:1489))|question_status/([^/]++)(*:1523)|update_question/([^/]++)(*:1556)|re(?|set\\-(?|exam/([^/]++)/student/([^/]++)(*:1608)|verify/([^/]++)(*:1632))|move\\-attendance/([^/]++)(*:1667))|login_as_student/([^/]++)(*:1702)|student_status/([^/]++)(*:1734)|v(?|iew_answer/([^/]++)(*:1766)|erify\\-student/([^/]++)(*:1798))|get\\-admin\\-courses/([^/]++)(*:1836)|([^/]++)/(?|update(*:1863)|delete(*:1878))|is_super_admin_status/([^/]++)(*:1918)|manage\\-(?|branch/(?|([^/]++)/(?|edit(*:1964)|update(*:1979)|delete(*:1994))|branch_status/([^/]++)(*:2026))|c(?|entre/(?|centre_status/([^/]++)(*:2071)|([^/]++)/(?|edit(*:2096)|update(*:2111)|delete(*:2126)))|ourse/(?|course_status/([^/]++)(*:2168)|([^/]++)/(?|edit(*:2193)|update(*:2208)|delete(*:2223)))|lass\\-schedule/([^/]++)/(?|edit(*:2265)|update(*:2280)|delete(*:2295)))|programme/(?|programme_status/([^/]++)(*:2344)|([^/]++)/(?|edit(*:2369)|update(*:2384)|delete(*:2399)))|sms\\-template/([^/]++)/(?|edit(*:2440)|update(*:2455)|delete(*:2470))|email\\-template/([^/]++)/(?|edit(*:2512)|update(*:2527)|delete(*:2542)))|sessions/([^/]++)/(?|edit(*:2578)|update(*:2593)|delete(*:2608))|lists/([^/]++)(?|(*:2635)|/edit(*:2649)|(*:2658)))|/student/(?|s(?|elect\\-session/([^/]++)(?|(*:2711))|how_result/([^/]++)(*:2740)|tart\\-exam/([^/]++)(*:2768))|delete\\-student\\-admission/([^/]++)(*:2813)|join_exam/([^/]++)(*:2840)|apply_exam/([^/]++)(*:2868))|/reset\\-password/([^/]++)(*:2903)|/verify\\-email/([^/]++)/([^/]++)(*:2944)|/builder/([^/]++)(*:2970)|/pages/(.*)(*:2990))/?$}sDu',
    ),
    3 => 
    array (
      39 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debugbar.clockwork',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      73 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'debugbar.cache.delete',
            'tags' => NULL,
          ),
          1 => 
          array (
            0 => 'key',
            1 => 'tags',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      141 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.restoreBackup',
            'filename' => NULL,
          ),
          1 => 
          array (
            0 => 'filename',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      181 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.destroyBackup',
            'filename' => NULL,
          ),
          1 => 
          array (
            0 => 'filename',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      210 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'env-editor.download',
            'filename' => NULL,
          ),
          1 => 
          array (
            0 => 'filename',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      229 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::NynqUbTyECBmZk6i',
          ),
          1 => 
          array (
            0 => 'any',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
            'POST' => 2,
            'PUT' => 3,
            'PATCH' => 4,
            'DELETE' => 5,
            'OPTIONS' => 6,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      250 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lHxiZPs6vGycdzdk',
          ),
          1 => 
          array (
            0 => 'any',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
            'POST' => 2,
            'PUT' => 3,
            'PATCH' => 4,
            'DELETE' => 5,
            'OPTIONS' => 6,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      277 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::nfcrIY60jxFY1MCo',
          ),
          1 => 
          array (
            0 => 'any',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
            'POST' => 2,
            'PUT' => 3,
            'PATCH' => 4,
            'DELETE' => 5,
            'OPTIONS' => 6,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      329 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.monitoring-tag.paginate',
          ),
          1 => 
          array (
            0 => 'tag',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      341 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.monitoring-tag.destroy',
          ),
          1 => 
          array (
            0 => 'tag',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      373 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.jobs-metrics.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      396 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.queues-metrics.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      425 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.jobs-batches.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      447 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.jobs-batches.retry',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      479 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.failed-jobs.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      501 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.retry-jobs.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      517 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.jobs.show',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      541 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'horizon.index',
            'view' => NULL,
          ),
          1 => 
          array (
            0 => 'view',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      609 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'newTemplate',
          ),
          1 => 
          array (
            0 => 'type',
            1 => 'name',
            2 => 'skeleton',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      630 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'viewTemplate',
          ),
          1 => 
          array (
            0 => 'templatename',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      665 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'viewMailable',
          ),
          1 => 
          array (
            0 => 'name',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      695 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'editMailable',
          ),
          1 => 
          array (
            0 => 'name',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      719 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'previewMailable',
          ),
          1 => 
          array (
            0 => 'name',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      865 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'dynamic-course',
          ),
          1 => 
          array (
            0 => 'course',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      888 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'register',
          ),
          1 => 
          array (
            0 => 'formCode',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      934 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.edit',
          ),
          1 => 
          array (
            0 => 'form',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      947 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.export',
          ),
          1 => 
          array (
            0 => 'form',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      962 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.update',
          ),
          1 => 
          array (
            0 => 'form',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      977 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.preview',
          ),
          1 => 
          array (
            0 => 'form',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      994 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.show',
          ),
          1 => 
          array (
            0 => 'form',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1009 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form.destroy',
          ),
          1 => 
          array (
            0 => 'form',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1047 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.edit',
          ),
          1 => 
          array (
            0 => 'response',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1062 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.update',
          ),
          1 => 
          array (
            0 => 'response',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1075 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.show',
          ),
          1 => 
          array (
            0 => 'response',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1091 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.form_responses.destroy',
          ),
          1 => 
          array (
            0 => 'response',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1126 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.category.status',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1163 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.category.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1185 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1213 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admins.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1242 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.status',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1279 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.category.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1301 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1327 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.questions.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1353 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.student.destroy',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1390 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.student.destroy_registered',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1428 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.questions',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1461 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::TluatPx5gUKipRtR',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1489 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::lshqoxD5qgM6Cdzs',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1523 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.questions.status',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1556 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.exam.questions.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1608 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reset-exam',
          ),
          1 => 
          array (
            0 => 'exam_id',
            1 => 'user_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1632 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.reset-verify',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1667 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.remove-attendance',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1702 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.login_as_student',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1734 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.student.status',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1766 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1798 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.verify-student',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1836 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admin.get-admin-courses',
          ),
          1 => 
          array (
            0 => 'admin',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1863 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admins.update',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1878 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.admins.delete',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1918 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.generated::QZbGCPa133LOemuG',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1964 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.branch.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1979 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.branch.update',
          ),
          1 => 
          array (
            0 => 'branch',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1994 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.branch.destroy',
          ),
          1 => 
          array (
            0 => 'branch',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2026 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.branch.status',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2071 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.centre.',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2096 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.centre.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2111 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.centre.update',
          ),
          1 => 
          array (
            0 => 'centre',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2126 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.centre.destroy',
          ),
          1 => 
          array (
            0 => 'centre',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2168 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2193 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2208 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.update',
          ),
          1 => 
          array (
            0 => 'course',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2223 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.course.destroy',
          ),
          1 => 
          array (
            0 => 'course',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2265 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.class.schedule.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2280 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.class.schedule.update',
          ),
          1 => 
          array (
            0 => 'course',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2295 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.class.schedule.destroy',
          ),
          1 => 
          array (
            0 => 'course',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2344 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.programme.status',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2369 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.programme.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2384 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.programme.update',
          ),
          1 => 
          array (
            0 => 'programme',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2399 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.programme.destroy',
          ),
          1 => 
          array (
            0 => 'programme',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2440 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sms.template.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2455 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sms.template.update',
          ),
          1 => 
          array (
            0 => 'template',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2470 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.sms.template.destroy',
          ),
          1 => 
          array (
            0 => 'template',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2512 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.email.template.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2527 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.email.template.update',
          ),
          1 => 
          array (
            0 => 'template',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2542 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.email.template.destroy',
          ),
          1 => 
          array (
            0 => 'template',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2578 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.session.edit',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2593 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.session.update',
          ),
          1 => 
          array (
            0 => 'session',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2608 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.session.destroy',
          ),
          1 => 
          array (
            0 => 'session',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2635 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.show',
          ),
          1 => 
          array (
            0 => 'list',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2649 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.edit',
          ),
          1 => 
          array (
            0 => 'list',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      2658 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.update',
          ),
          1 => 
          array (
            0 => 'list',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'admin.lists.destroy',
          ),
          1 => 
          array (
            0 => 'list',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2711 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.',
          ),
          1 => 
          array (
            0 => 'user_id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'student.select-session',
          ),
          1 => 
          array (
            0 => 'user_id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2740 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::UGImM2OsTklOsibH',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2768 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::WMlvvbegNUbGLlso',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2813 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.delete-student-admission',
          ),
          1 => 
          array (
            0 => 'user_id',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2840 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::jEF4QqLogtkZw5eY',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2868 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'student.generated::0KwIDkPb6Zn2Rjuu',
          ),
          1 => 
          array (
            0 => 'id',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2903 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'password.reset',
          ),
          1 => 
          array (
            0 => 'token',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2944 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'verification.verify',
          ),
          1 => 
          array (
            0 => 'id',
            1 => 'hash',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2970 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Z5nBUwdOijmyqwXZ',
          ),
          1 => 
          array (
            0 => 'any',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
            'POST' => 2,
            'PUT' => 3,
            'PATCH' => 4,
            'DELETE' => 5,
            'OPTIONS' => 6,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      2990 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::jXxHi85d8Ydy01Aq',
          ),
          1 => 
          array (
            0 => 'any',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
            'POST' => 2,
            'PUT' => 3,
            'PATCH' => 4,
            'DELETE' => 5,
            'OPTIONS' => 6,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'debugbar.openhandler' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '_debugbar/open',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'Barryvdh\\Debugbar\\Middleware\\DebugbarEnabled',
        ),
        'uses' => 'Barryvdh\\Debugbar\\Controllers\\OpenHandlerController@handle',
        'as' => 'debugbar.openhandler',
        'controller' => 'Barryvdh\\Debugbar\\Controllers\\OpenHandlerController@handle',
        'namespace' => 'Barryvdh\\Debugbar\\Controllers',
        'prefix' => '_debugbar',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debugbar.clockwork' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '_debugbar/clockwork/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'Barryvdh\\Debugbar\\Middleware\\DebugbarEnabled',
        ),
        'uses' => 'Barryvdh\\Debugbar\\Controllers\\OpenHandlerController@clockwork',
        'as' => 'debugbar.clockwork',
        'controller' => 'Barryvdh\\Debugbar\\Controllers\\OpenHandlerController@clockwork',
        'namespace' => 'Barryvdh\\Debugbar\\Controllers',
        'prefix' => '_debugbar',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debugbar.assets.css' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '_debugbar/assets/stylesheets',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'Barryvdh\\Debugbar\\Middleware\\DebugbarEnabled',
        ),
        'uses' => 'Barryvdh\\Debugbar\\Controllers\\AssetController@css',
        'as' => 'debugbar.assets.css',
        'controller' => 'Barryvdh\\Debugbar\\Controllers\\AssetController@css',
        'namespace' => 'Barryvdh\\Debugbar\\Controllers',
        'prefix' => '_debugbar',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debugbar.assets.js' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '_debugbar/assets/javascript',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'Barryvdh\\Debugbar\\Middleware\\DebugbarEnabled',
        ),
        'uses' => 'Barryvdh\\Debugbar\\Controllers\\AssetController@js',
        'as' => 'debugbar.assets.js',
        'controller' => 'Barryvdh\\Debugbar\\Controllers\\AssetController@js',
        'namespace' => 'Barryvdh\\Debugbar\\Controllers',
        'prefix' => '_debugbar',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debugbar.cache.delete' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => '_debugbar/cache/{key}/{tags?}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'Barryvdh\\Debugbar\\Middleware\\DebugbarEnabled',
        ),
        'uses' => 'Barryvdh\\Debugbar\\Controllers\\CacheController@delete',
        'as' => 'debugbar.cache.delete',
        'controller' => 'Barryvdh\\Debugbar\\Controllers\\CacheController@delete',
        'namespace' => 'Barryvdh\\Debugbar\\Controllers',
        'prefix' => '_debugbar',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'debugbar.queries.explain' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => '_debugbar/queries/explain',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'Barryvdh\\Debugbar\\Middleware\\DebugbarEnabled',
        ),
        'uses' => 'Barryvdh\\Debugbar\\Controllers\\QueriesController@explain',
        'as' => 'debugbar.queries.explain',
        'controller' => 'Barryvdh\\Debugbar\\Controllers\\QueriesController@explain',
        'namespace' => 'Barryvdh\\Debugbar\\Controllers',
        'prefix' => '_debugbar',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/env-editor',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@index',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@index',
        'namespace' => NULL,
        'prefix' => '/admin/env-editor',
        'where' => 
        array (
        ),
        'as' => 'env-editor.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.key' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/env-editor/key',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@addKey',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@addKey',
        'namespace' => NULL,
        'prefix' => '/admin/env-editor',
        'where' => 
        array (
        ),
        'as' => 'env-editor.key',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::2D4eE9fUFTyKMHFs' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'admin/env-editor/key',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@editKey',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@editKey',
        'namespace' => NULL,
        'prefix' => '/admin/env-editor',
        'where' => 
        array (
        ),
        'as' => 'generated::2D4eE9fUFTyKMHFs',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::3RvFGrC7nqxCdpSt' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/env-editor/key',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@deleteKey',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@deleteKey',
        'namespace' => NULL,
        'prefix' => '/admin/env-editor',
        'where' => 
        array (
        ),
        'as' => 'generated::3RvFGrC7nqxCdpSt',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.clearConfigCache' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/env-editor/clear-cache',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@clearConfigCache',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@clearConfigCache',
        'namespace' => NULL,
        'prefix' => '/admin/env-editor',
        'where' => 
        array (
        ),
        'as' => 'env-editor.clearConfigCache',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.getBackups' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/env-editor/files',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@getBackupFiles',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@getBackupFiles',
        'namespace' => NULL,
        'prefix' => 'admin/env-editor/files',
        'where' => 
        array (
        ),
        'as' => 'env-editor.getBackups',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.createBackup' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/env-editor/files/create-backup',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@createBackup',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@createBackup',
        'namespace' => NULL,
        'prefix' => 'admin/env-editor/files',
        'where' => 
        array (
        ),
        'as' => 'env-editor.createBackup',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.restoreBackup' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/env-editor/files/restore-backup/{filename?}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@restoreBackup',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@restoreBackup',
        'namespace' => NULL,
        'prefix' => 'admin/env-editor/files',
        'where' => 
        array (
        ),
        'as' => 'env-editor.restoreBackup',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.destroyBackup' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/env-editor/files/destroy-backup/{filename?}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@destroyBackup',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@destroyBackup',
        'namespace' => NULL,
        'prefix' => 'admin/env-editor/files',
        'where' => 
        array (
        ),
        'as' => 'env-editor.destroyBackup',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.download' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/env-editor/files/download/{filename?}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@download',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@download',
        'namespace' => NULL,
        'prefix' => 'admin/env-editor/files',
        'where' => 
        array (
        ),
        'as' => 'env-editor.download',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'env-editor.upload' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/env-editor/files/upload',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'role:super-admin,admin',
        ),
        'uses' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@upload',
        'controller' => 'GeoSot\\EnvEditor\\Controllers\\EnvController@upload',
        'namespace' => NULL,
        'prefix' => 'admin/env-editor/files',
        'where' => 
        array (
        ),
        'as' => 'env-editor.upload',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::NynqUbTyECBmZk6i' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
        2 => 'POST',
        3 => 'PUT',
        4 => 'PATCH',
        5 => 'DELETE',
        6 => 'OPTIONS',
      ),
      'uri' => 'assets{any}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:158:"function() {

    $builder = new \\HansSchouten\\LaravelPageBuilder\\LaravelPageBuilder(config(\'pagebuilder\'));
    $builder->handlePageBuilderAssetRequest();

}";s:5:"scope";s:34:"Illuminate\\Support\\ServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000004c50000000000000000";}}',
        'as' => 'generated::NynqUbTyECBmZk6i',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'any' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lHxiZPs6vGycdzdk' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
        2 => 'POST',
        3 => 'PUT',
        4 => 'PATCH',
        5 => 'DELETE',
        6 => 'OPTIONS',
      ),
      'uri' => 'uploads{any}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:154:"function() {

    $builder = new \\HansSchouten\\LaravelPageBuilder\\LaravelPageBuilder(config(\'pagebuilder\'));
    $builder->handleUploadedFileRequest();

}";s:5:"scope";s:34:"Illuminate\\Support\\ServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000004cd0000000000000000";}}',
        'as' => 'generated::lHxiZPs6vGycdzdk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'any' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::nfcrIY60jxFY1MCo' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
        2 => 'POST',
        3 => 'PUT',
        4 => 'PATCH',
        5 => 'DELETE',
        6 => 'OPTIONS',
      ),
      'uri' => 'builder/manage{any}',
      'action' => 
      array (
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:154:"function() {

        $builder = new \\HansSchouten\\LaravelPageBuilder\\LaravelPageBuilder(config(\'pagebuilder\'));
        $builder->handleRequest();

    }";s:5:"scope";s:34:"Illuminate\\Support\\ServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000004cf0000000000000000";}}',
        'as' => 'generated::nfcrIY60jxFY1MCo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'any' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.stats.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/stats',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\DashboardStatsController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\DashboardStatsController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.stats.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.workload.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/workload',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\WorkloadController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\WorkloadController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.workload.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.masters.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/masters',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\MasterSupervisorController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\MasterSupervisorController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.masters.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.monitoring.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/monitoring',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.monitoring.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.monitoring.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'horizon/api/monitoring',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@store',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@store',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.monitoring.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.monitoring-tag.paginate' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/monitoring/{tag}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@paginate',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@paginate',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.monitoring-tag.paginate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.monitoring-tag.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'horizon/api/monitoring/{tag}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@destroy',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\MonitoringController@destroy',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.monitoring-tag.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'tag' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.jobs-metrics.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/metrics/jobs',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\JobMetricsController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\JobMetricsController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.jobs-metrics.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.jobs-metrics.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/metrics/jobs/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\JobMetricsController@show',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\JobMetricsController@show',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.jobs-metrics.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.queues-metrics.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/metrics/queues',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\QueueMetricsController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\QueueMetricsController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.queues-metrics.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.queues-metrics.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/metrics/queues/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\QueueMetricsController@show',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\QueueMetricsController@show',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.queues-metrics.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.jobs-batches.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/batches',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\BatchesController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\BatchesController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.jobs-batches.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.jobs-batches.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/batches/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\BatchesController@show',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\BatchesController@show',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.jobs-batches.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.jobs-batches.retry' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'horizon/api/batches/retry/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\BatchesController@retry',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\BatchesController@retry',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.jobs-batches.retry',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.pending-jobs.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/jobs/pending',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\PendingJobsController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\PendingJobsController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.pending-jobs.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.completed-jobs.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/jobs/completed',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\CompletedJobsController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\CompletedJobsController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.completed-jobs.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.silenced-jobs.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/jobs/silenced',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\SilencedJobsController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\SilencedJobsController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.silenced-jobs.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.failed-jobs.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/jobs/failed',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\FailedJobsController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\FailedJobsController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.failed-jobs.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.failed-jobs.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/jobs/failed/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\FailedJobsController@show',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\FailedJobsController@show',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.failed-jobs.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.retry-jobs.show' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'horizon/api/jobs/retry/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\RetryController@store',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\RetryController@store',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.retry-jobs.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.jobs.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/api/jobs/{id}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\JobsController@show',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\JobsController@show',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon/api',
        'where' => 
        array (
        ),
        'as' => 'horizon.jobs.show',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'horizon.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'horizon/{view?}',
      'action' => 
      array (
        'domain' => NULL,
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'Laravel\\Horizon\\Http\\Controllers\\HomeController@index',
        'controller' => 'Laravel\\Horizon\\Http\\Controllers\\HomeController@index',
        'namespace' => 'Laravel\\Horizon\\Http\\Controllers',
        'prefix' => 'horizon',
        'where' => 
        array (
        ),
        'as' => 'horizon.index',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'view' => '(.*)',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sanctum.csrf-cookie' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sanctum/csrf-cookie',
      'action' => 
      array (
        'uses' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'controller' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'namespace' => NULL,
        'prefix' => 'sanctum',
        'where' => 
        array (
        ),
        'middleware' => 
        array (
          0 => 'web',
        ),
        'as' => 'sanctum.csrf-cookie',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Ud99HaFatfptGklT' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@toMailablesList',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@toMailablesList',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse',
        'where' => 
        array (
        ),
        'as' => 'generated::Ud99HaFatfptGklT',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'templateList' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/templates',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@index',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@index',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'templateList',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'selectNewTemplate' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/templates/new',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@select',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@select',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'selectNewTemplate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'newTemplate' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/templates/new/{type}/{name}/{skeleton}',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@new',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@new',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'newTemplate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'viewTemplate' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/templates/edit/{templatename}',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@view',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@view',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'viewTemplate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'createNewTemplate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/templates/new',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@create',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@create',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'createNewTemplate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'deleteTemplate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/templates/delete',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@delete',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@delete',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'deleteTemplate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'updateTemplate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/templates/update',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@update',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@update',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'updateTemplate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'previewTemplateMarkdownView' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/templates/preview',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@previewTemplateMarkdownView',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\TemplatesController@previewTemplateMarkdownView',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/templates',
        'where' => 
        array (
        ),
        'as' => 'previewTemplateMarkdownView',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'mailableList' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/mailables',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@index',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@index',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'mailableList',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'viewMailable' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/mailables/view/{name}',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@viewMailable',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@viewMailable',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'viewMailable',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'editMailable' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/mailables/edit/template/{name}',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@editMailable',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@editMailable',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'editMailable',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'parseTemplate' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/mailables/parse/template',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@parseTemplate',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@parseTemplate',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'parseTemplate',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'createMailable' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/mailables/new',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@createMailable',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@createMailable',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'createMailable',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generateMailable' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/mailables/new',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@generateMailable',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@generateMailable',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'generateMailable',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'deleteMailable' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/mailables/delete',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@delete',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@delete',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'deleteMailable',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'previewMarkdownView' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/mailables/preview/template',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesPreviewController@markdownView',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesPreviewController@markdownView',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables/preview',
        'where' => 
        array (
        ),
        'as' => 'previewMarkdownView',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'templatePreviewError' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/mailables/preview/template/previewerror',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesPreviewController@previewError',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesPreviewController@previewError',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables/preview',
        'where' => 
        array (
        ),
        'as' => 'templatePreviewError',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'previewMailable' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'maileclipse/mailables/preview/{name}',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesPreviewController@mailable',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesPreviewController@mailable',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables/preview',
        'where' => 
        array (
        ),
        'as' => 'previewMailable',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'sendTestMail' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'maileclipse/mailables/send-test',
      'action' => 
      array (
        'middleware' => 'maileclipse',
        'uses' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@sendTest',
        'controller' => 'Qoraiche\\MailEclipse\\Http\\Controllers\\MailablesController@sendTest',
        'namespace' => 'Qoraiche\\MailEclipse\\Http\\Controllers',
        'prefix' => 'maileclipse/mailables',
        'where' => 
        array (
        ),
        'as' => 'sendTestMail',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'ignition.healthCheck' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '_ignition/health-check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'Spatie\\LaravelIgnition\\Http\\Middleware\\RunnableSolutionsEnabled',
        ),
        'uses' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\HealthCheckController@__invoke',
        'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\HealthCheckController',
        'as' => 'ignition.healthCheck',
        'namespace' => NULL,
        'prefix' => '_ignition',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'ignition.executeSolution' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => '_ignition/execute-solution',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'Spatie\\LaravelIgnition\\Http\\Middleware\\RunnableSolutionsEnabled',
        ),
        'uses' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\ExecuteSolutionController@__invoke',
        'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\ExecuteSolutionController',
        'as' => 'ignition.executeSolution',
        'namespace' => NULL,
        'prefix' => '_ignition',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'ignition.updateConfig' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => '_ignition/update-config',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'Spatie\\LaravelIgnition\\Http\\Middleware\\RunnableSolutionsEnabled',
        ),
        'uses' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\UpdateConfigController@__invoke',
        'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\UpdateConfigController',
        'as' => 'ignition.updateConfig',
        'namespace' => NULL,
        'prefix' => '_ignition',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::G2CET4Sbp0iQoGIz' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/user',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:79:"function (\\Illuminate\\Http\\Request $request) {
    return $request->user();
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000059b0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::G2CET4Sbp0iQoGIz',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::qIV4e6eXg2DiUH2O' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/student/register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'apikey.check',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@add_new_students',
        'controller' => 'App\\Http\\Controllers\\AdminController@add_new_students',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::qIV4e6eXg2DiUH2O',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::yuxImuUWNm4NBf60' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/generate_qrcode',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'apikey.check',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@generate_qrcode_page',
        'controller' => 'App\\Http\\Controllers\\AdminController@generate_qrcode_page',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::yuxImuUWNm4NBf60',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Fp3D8GwvxNQF2fFf' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/addStudent',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\FormResponseController@store',
        'controller' => 'App\\Http\\Controllers\\FormResponseController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::Fp3D8GwvxNQF2fFf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::AfD53QrIAihHITYk' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\LandingPageController@index',
        'controller' => 'App\\Http\\Controllers\\LandingPageController@index',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::AfD53QrIAihHITYk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'available-courses' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'available-courses',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\LandingPageController@availableCourses',
        'controller' => 'App\\Http\\Controllers\\LandingPageController@availableCourses',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'available-courses',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'application' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'application',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\LandingPageController@application',
        'controller' => 'App\\Http\\Controllers\\LandingPageController@application',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'application',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'dynamic-course' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '{course}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\LandingPageController@courseView',
        'controller' => 'App\\Http\\Controllers\\LandingPageController@courseView',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'dynamic-course',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'course' => 'cybersecurity-course|ai-course|data-protection-course|protection-expert-course|protection-sup-course|certified-dpf-course|cnst-course',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'register' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'forms/{formCode}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@submitForm',
        'controller' => 'App\\Http\\Controllers\\FormController@submitForm',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'register',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'form-responses',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'App\\Http\\Controllers\\FormResponseController@store',
        'controller' => 'App\\Http\\Controllers\\FormResponseController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'admin.form_responses.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/forms',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@index',
        'controller' => 'App\\Http\\Controllers\\FormController@index',
        'as' => 'admin.form.index',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.fetch' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/forms/fetch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@fetch',
        'controller' => 'App\\Http\\Controllers\\FormController@fetch',
        'as' => 'admin.form.fetch',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/forms/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
          3 => 'permission:form.create',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@create',
        'controller' => 'App\\Http\\Controllers\\FormController@create',
        'as' => 'admin.form.create',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/forms',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
          3 => 'permission:form.create',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@store',
        'controller' => 'App\\Http\\Controllers\\FormController@store',
        'as' => 'admin.form.store',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/forms/{form}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
          3 => 'permission:form.update',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@edit',
        'controller' => 'App\\Http\\Controllers\\FormController@edit',
        'as' => 'admin.form.edit',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/forms/{form}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
          3 => 'permission:form.update',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@update',
        'controller' => 'App\\Http\\Controllers\\FormController@update',
        'as' => 'admin.form.update',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.preview' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/forms/{form}/preview',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@preview',
        'controller' => 'App\\Http\\Controllers\\FormController@preview',
        'as' => 'admin.form.preview',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/forms/{form}/responses',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@show',
        'controller' => 'App\\Http\\Controllers\\FormController@show',
        'as' => 'admin.form.show',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.export' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/forms/{form}/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
          3 => 'permission:form.create',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@export',
        'controller' => 'App\\Http\\Controllers\\FormController@export',
        'as' => 'admin.form.export',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/forms/{form}/destroy',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form.read',
          3 => 'permission:form.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@destroy',
        'controller' => 'App\\Http\\Controllers\\FormController@destroy',
        'as' => 'admin.form.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/forms',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/form-responses',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form-response.read',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@index',
        'controller' => 'App\\Http\\Controllers\\FormController@index',
        'as' => 'admin.form_responses.index',
        'namespace' => NULL,
        'prefix' => 'admin/form-responses',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.fetch' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/form-responses/fetch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form-response.read',
        ),
        'uses' => 'App\\Http\\Controllers\\FormResponseController@fetch',
        'controller' => 'App\\Http\\Controllers\\FormResponseController@fetch',
        'as' => 'admin.form_responses.fetch',
        'namespace' => NULL,
        'prefix' => 'admin/form-responses',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/form-responses/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form-response.read',
          3 => 'permission:form-response.create',
        ),
        'uses' => 'App\\Http\\Controllers\\FormController@create',
        'controller' => 'App\\Http\\Controllers\\FormController@create',
        'as' => 'admin.form_responses.create',
        'namespace' => NULL,
        'prefix' => 'admin/form-responses',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/form-responses/{response}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form-response.read',
          3 => 'permission:form-response.update',
        ),
        'uses' => 'App\\Http\\Controllers\\FormResponseController@edit',
        'controller' => 'App\\Http\\Controllers\\FormResponseController@edit',
        'as' => 'admin.form_responses.edit',
        'namespace' => NULL,
        'prefix' => 'admin/form-responses',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/form-responses/{response}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form-response.read',
          3 => 'permission:form-response.update',
        ),
        'uses' => 'App\\Http\\Controllers\\FormResponseController@update',
        'controller' => 'App\\Http\\Controllers\\FormResponseController@update',
        'as' => 'admin.form_responses.update',
        'namespace' => NULL,
        'prefix' => 'admin/form-responses',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/form-responses/{response}/view',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form-response.read',
        ),
        'uses' => 'App\\Http\\Controllers\\FormResponseController@show',
        'controller' => 'App\\Http\\Controllers\\FormResponseController@show',
        'as' => 'admin.form_responses.show',
        'namespace' => NULL,
        'prefix' => 'admin/form-responses',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.form_responses.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/form-responses/{response}/destroy',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth:admin',
          2 => 'permission:form-response.read',
          3 => 'permission:form-response.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\FormResponseController@destroy',
        'controller' => 'App\\Http\\Controllers\\FormResponseController@destroy',
        'as' => 'admin.form_responses.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/form-responses',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@index',
        'controller' => 'App\\Http\\Controllers\\AdminController@index',
        'as' => 'admin.dashboard',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.category.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/exam_category',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:category.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@exam_category',
        'controller' => 'App\\Http\\Controllers\\AdminController@exam_category',
        'as' => 'admin.category.index',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.category.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/category_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:category.read',
          4 => 'permission:category.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@category_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@category_status',
        'as' => 'admin.category.status',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.category.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/add_new_category',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:category.read',
          4 => 'permission:category.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@add_new_category',
        'controller' => 'App\\Http\\Controllers\\AdminController@add_new_category',
        'as' => 'admin.category.store',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.category.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/edit_category/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:category.read',
          4 => 'permission:category.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@edit_category',
        'controller' => 'App\\Http\\Controllers\\AdminController@edit_category',
        'as' => 'admin.category.edit',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.category.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/edit_new_category',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:category.read',
          4 => 'permission:category.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@edit_new_category',
        'controller' => 'App\\Http\\Controllers\\AdminController@edit_new_category',
        'as' => 'admin.category.update',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.category.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/delete_category/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:category.read',
          4 => 'permission:category.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@delete_category',
        'controller' => 'App\\Http\\Controllers\\AdminController@delete_category',
        'as' => 'admin.category.destroy',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage_exam',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@manage_exam',
        'controller' => 'App\\Http\\Controllers\\AdminController@manage_exam',
        'as' => 'admin.exam.index',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/exam_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@exam_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@exam_status',
        'as' => 'admin.exam.status',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/add_new_exam',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@add_new_exam',
        'controller' => 'App\\Http\\Controllers\\AdminController@add_new_exam',
        'as' => 'admin.exam.store',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/edit_exam/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@edit_exam',
        'controller' => 'App\\Http\\Controllers\\AdminController@edit_exam',
        'as' => 'admin.exam.edit',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/edit_exam_sub',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@edit_exam_sub',
        'controller' => 'App\\Http\\Controllers\\AdminController@edit_exam_sub',
        'as' => 'admin.exam.update',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/delete_exam/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@delete_exam',
        'controller' => 'App\\Http\\Controllers\\AdminController@delete_exam',
        'as' => 'admin.exam.destroy',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.questions' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/add_questions/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@add_questions',
        'controller' => 'App\\Http\\Controllers\\AdminController@add_questions',
        'as' => 'admin.exam.questions',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.questions.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/question_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@question_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@question_status',
        'as' => 'admin.exam.questions.status',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.questions.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/delete_question/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@delete_question',
        'controller' => 'App\\Http\\Controllers\\AdminController@delete_question',
        'as' => 'admin.exam.questions.destroy',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.questions.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/update_question/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@update_question',
        'controller' => 'App\\Http\\Controllers\\AdminController@update_question',
        'as' => 'admin.exam.questions.edit',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.questions.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/edit_question_inner',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@edit_question_inner',
        'controller' => 'App\\Http\\Controllers\\AdminController@edit_question_inner',
        'as' => 'admin.exam.questions.update',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.exam.questions.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/add_new_question',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
          4 => 'permission:exam.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@add_new_question',
        'controller' => 'App\\Http\\Controllers\\AdminController@add_new_question',
        'as' => 'admin.exam.questions.store',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reset-exam' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reset-exam/{exam_id}/student/{user_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:exam.read',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@reset_exam',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@reset_exam',
        'as' => 'admin.reset-exam',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.student.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/registered_students',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.admit',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@registered_students',
        'controller' => 'App\\Http\\Controllers\\AdminController@registered_students',
        'as' => 'admin.student.index',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admit_student' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/student/admit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.admit',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@admit_student',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@admit_student',
        'as' => 'admin.admit_student',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admit_user_ui' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/admit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.admit',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@admit_student',
        'controller' => 'App\\Http\\Controllers\\AdminController@admit_student',
        'as' => 'admin.admit_user_ui',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.shortlisted_students' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/shortlisted_students',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.admit',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@shortlisted_students',
        'controller' => 'App\\Http\\Controllers\\AdminController@shortlisted_students',
        'as' => 'admin.shortlisted_students',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.save_shortlisted_students' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/save_shortlisted_students',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.admit',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@saveShortlistedStudents',
        'controller' => 'App\\Http\\Controllers\\AdminController@saveShortlistedStudents',
        'as' => 'admin.save_shortlisted_students',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.manage_students' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage_students',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read|student.bulk-sms|student.admit|student.email|student.shortlist',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@manage_students',
        'controller' => 'App\\Http\\Controllers\\AdminController@manage_students',
        'as' => 'admin.manage_students',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.login_as_student' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/login_as_student/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:user.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@login_as_student',
        'controller' => 'App\\Http\\Controllers\\AdminController@login_as_student',
        'as' => 'admin.login_as_student',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.student.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/student_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@student_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@student_status',
        'as' => 'admin.student.status',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.student.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/delete_students/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@delete_students',
        'controller' => 'App\\Http\\Controllers\\AdminController@delete_students',
        'as' => 'admin.student.destroy',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.student.destroy_registered' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/delete_registered_students/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@delete_registered_students',
        'controller' => 'App\\Http\\Controllers\\AdminController@delete_registered_students',
        'as' => 'admin.student.destroy_registered',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.student.update' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/edit_students_final',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@edit_students_final',
        'controller' => 'App\\Http\\Controllers\\AdminController@edit_students_final',
        'as' => 'admin.student.update',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.student.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/add_new_students',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
          4 => 'permission:student.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@add_new_students',
        'controller' => 'App\\Http\\Controllers\\AdminController@add_new_students',
        'as' => 'admin.student.store',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/view_answer/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.read',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@view_answer',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@view_answer',
        'as' => 'admin.',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::1xKFA5BPOgw7MQcr' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/confirm_attendance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
          4 => 'permission:attendance.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AttendanceController@confirmAttendance',
        'controller' => 'App\\Http\\Controllers\\AttendanceController@confirmAttendance',
        'as' => 'admin.generated::1xKFA5BPOgw7MQcr',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.viewAttendanceByDate' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/view_attendance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@viewAttendanceByDate',
        'controller' => 'App\\Http\\Controllers\\AdminController@viewAttendanceByDate',
        'as' => 'admin.viewAttendanceByDate',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.remove-attendance' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/remove-attendance/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
          4 => 'permission:attendance.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\AttendanceController@removeAttendance',
        'controller' => 'App\\Http\\Controllers\\AttendanceController@removeAttendance',
        'as' => 'admin.remove-attendance',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::cgnNi3LTC9ChoKI2' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/generate_qrcode',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
          4 => 'permission:attendance.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@generate_qrcode_page',
        'controller' => 'App\\Http\\Controllers\\AdminController@generate_qrcode_page',
        'as' => 'admin.generated::cgnNi3LTC9ChoKI2',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::0YWYhhZksgOTAu9k' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/generate_qrcode',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
          4 => 'permission:attendance.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AttendanceController@generateQRCodeData',
        'controller' => 'App\\Http\\Controllers\\AttendanceController@generateQRCodeData',
        'as' => 'admin.generated::0YWYhhZksgOTAu9k',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::KmHZLeGl1SCS05Rp' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/scan_qrcode',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
          4 => 'permission:attendance.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@scan_qrcode_page',
        'controller' => 'App\\Http\\Controllers\\AdminController@scan_qrcode_page',
        'as' => 'admin.generated::KmHZLeGl1SCS05Rp',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.verification' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/verification',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@verification_page',
        'controller' => 'App\\Http\\Controllers\\AdminController@verification_page',
        'as' => 'admin.verification',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.verify-details' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/verify_details',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@verifyDetails',
        'controller' => 'App\\Http\\Controllers\\AdminController@verifyDetails',
        'as' => 'admin.verify-details',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.reset-verify' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reset-verify/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
          4 => 'permission:student.update',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@reset_verify',
        'controller' => 'App\\Http\\Controllers\\AdminController@reset_verify',
        'as' => 'admin.reset-verify',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.verify-student' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/verify-student/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:attendance.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@verifyStudent',
        'controller' => 'App\\Http\\Controllers\\AdminController@verifyStudent',
        'as' => 'admin.verify-student',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.getReportView' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/reports',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:report.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@getReportView',
        'controller' => 'App\\Http\\Controllers\\AdminController@getReportView',
        'as' => 'admin.getReportView',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generateReport' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/reports',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:report.read',
          4 => 'permission:report.create',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@generateReport',
        'controller' => 'App\\Http\\Controllers\\AdminController@generateReport',
        'as' => 'admin.generateReport',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::lshqoxD5qgM6Cdzs' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/apply_exam/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@apply_exam',
        'controller' => 'App\\Http\\Controllers\\AdminController@apply_exam',
        'as' => 'admin.generated::lshqoxD5qgM6Cdzs',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::TluatPx5gUKipRtR' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/admin_view_result/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:result.read',
          4 => 'admin.super',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@admin_view_result',
        'controller' => 'App\\Http\\Controllers\\AdminController@admin_view_result',
        'as' => 'admin.generated::TluatPx5gUKipRtR',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admin.get-admin-courses' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/get-admin-courses/{admin}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@getAdminCourses',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@getAdminCourses',
        'as' => 'admin.admin.get-admin-courses',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admin.update-admin-courses' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/update-admin-courses',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@updateAdminCourses',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@updateAdminCourses',
        'as' => 'admin.admin.update-admin-courses',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.manage_admins' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage_admins',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@index',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@index',
        'as' => 'admin.manage_admins',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admins.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
          4 => 'permission:admin.create',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@create',
        'as' => 'admin.admins.create',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::KS1mumXhF92ghzRp' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/add_new_admin',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
          4 => 'permission:admin.create',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@store',
        'as' => 'admin.generated::KS1mumXhF92ghzRp',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admins.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/edit_admin/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
          4 => 'permission:admin.update',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@edit',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@edit',
        'as' => 'admin.admins.edit',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admins.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/{id}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
          4 => 'permission:admin.update',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@update',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@update',
        'as' => 'admin.admins.update',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.admins.delete' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/{id}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
          4 => 'permission:admin.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@destroy',
        'as' => 'admin.admins.delete',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::QZbGCPa133LOemuG' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/is_super_admin_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:admin.read',
          4 => 'permission:admin.status',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@is_super_admin_status',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@is_super_admin_status',
        'as' => 'admin.generated::QZbGCPa133LOemuG',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.branch.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-branch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:branch.read',
        ),
        'uses' => 'App\\Http\\Controllers\\BranchController@index',
        'controller' => 'App\\Http\\Controllers\\BranchController@index',
        'as' => 'admin.branch.index',
        'namespace' => NULL,
        'prefix' => 'admin/manage-branch',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.branch.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-branch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:branch.read',
          4 => 'permission:branch.create',
        ),
        'uses' => 'App\\Http\\Controllers\\BranchController@store',
        'controller' => 'App\\Http\\Controllers\\BranchController@store',
        'as' => 'admin.branch.store',
        'namespace' => NULL,
        'prefix' => 'admin/manage-branch',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.branch.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-branch/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:branch.read',
          4 => 'permission:branch.update',
        ),
        'uses' => 'App\\Http\\Controllers\\BranchController@edit',
        'controller' => 'App\\Http\\Controllers\\BranchController@edit',
        'as' => 'admin.branch.edit',
        'namespace' => NULL,
        'prefix' => 'admin/manage-branch',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.branch.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/manage-branch/{branch}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:branch.read',
          4 => 'permission:branch.update',
        ),
        'uses' => 'App\\Http\\Controllers\\BranchController@update',
        'controller' => 'App\\Http\\Controllers\\BranchController@update',
        'as' => 'admin.branch.update',
        'namespace' => NULL,
        'prefix' => 'admin/manage-branch',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.branch.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-branch/{branch}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:branch.read',
          4 => 'permission:branch.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\BranchController@destroy',
        'controller' => 'App\\Http\\Controllers\\BranchController@destroy',
        'as' => 'admin.branch.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/manage-branch',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.branch.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-branch/branch_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:branch.read',
          4 => 'permission:branch.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@branch_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@branch_status',
        'as' => 'admin.branch.status',
        'namespace' => NULL,
        'prefix' => 'admin/manage-branch',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.centre.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-centre',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:centre.read',
        ),
        'uses' => 'App\\Http\\Controllers\\CentreController@index',
        'controller' => 'App\\Http\\Controllers\\CentreController@index',
        'as' => 'admin.centre.index',
        'namespace' => NULL,
        'prefix' => 'admin/manage-centre',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.centre.' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-centre/centre_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:centre.read',
          4 => 'permission:centre.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@centre_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@centre_status',
        'as' => 'admin.centre.',
        'namespace' => NULL,
        'prefix' => 'admin/manage-centre',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.centre.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-centre',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:centre.read',
          4 => 'permission:centre.create',
        ),
        'uses' => 'App\\Http\\Controllers\\CentreController@store',
        'controller' => 'App\\Http\\Controllers\\CentreController@store',
        'as' => 'admin.centre.store',
        'namespace' => NULL,
        'prefix' => 'admin/manage-centre',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.centre.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-centre/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:centre.read',
          4 => 'permission:centre.update',
        ),
        'uses' => 'App\\Http\\Controllers\\CentreController@edit',
        'controller' => 'App\\Http\\Controllers\\CentreController@edit',
        'as' => 'admin.centre.edit',
        'namespace' => NULL,
        'prefix' => 'admin/manage-centre',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.centre.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/manage-centre/{centre}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:centre.read',
          4 => 'permission:centre.update',
        ),
        'uses' => 'App\\Http\\Controllers\\CentreController@update',
        'controller' => 'App\\Http\\Controllers\\CentreController@update',
        'as' => 'admin.centre.update',
        'namespace' => NULL,
        'prefix' => 'admin/manage-centre',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.centre.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-centre/{centre}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:centre.read',
          4 => 'permission:centre.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\CentreController@destroy',
        'controller' => 'App\\Http\\Controllers\\CentreController@destroy',
        'as' => 'admin.centre.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/manage-centre',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.programme.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-programme',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:programme.read',
        ),
        'uses' => 'App\\Http\\Controllers\\ProgrammeController@index',
        'controller' => 'App\\Http\\Controllers\\ProgrammeController@index',
        'as' => 'admin.programme.index',
        'namespace' => NULL,
        'prefix' => 'admin/manage-programme',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.programme.status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-programme/programme_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:programme.read',
          4 => 'permission:programme.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@programme_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@programme_status',
        'as' => 'admin.programme.status',
        'namespace' => NULL,
        'prefix' => 'admin/manage-programme',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.programme.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-programme',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:programme.read',
          4 => 'permission:programme.create',
        ),
        'uses' => 'App\\Http\\Controllers\\ProgrammeController@store',
        'controller' => 'App\\Http\\Controllers\\ProgrammeController@store',
        'as' => 'admin.programme.store',
        'namespace' => NULL,
        'prefix' => 'admin/manage-programme',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.programme.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-programme/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:programme.read',
          4 => 'permission:programme.update',
        ),
        'uses' => 'App\\Http\\Controllers\\ProgrammeController@edit',
        'controller' => 'App\\Http\\Controllers\\ProgrammeController@edit',
        'as' => 'admin.programme.edit',
        'namespace' => NULL,
        'prefix' => 'admin/manage-programme',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.programme.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/manage-programme/{programme}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:programme.read',
          4 => 'permission:programme.update',
        ),
        'uses' => 'App\\Http\\Controllers\\ProgrammeController@update',
        'controller' => 'App\\Http\\Controllers\\ProgrammeController@update',
        'as' => 'admin.programme.update',
        'namespace' => NULL,
        'prefix' => 'admin/manage-programme',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.programme.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-programme/{programme}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:programme.read',
          4 => 'permission:programme.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\ProgrammeController@destroy',
        'controller' => 'App\\Http\\Controllers\\ProgrammeController@destroy',
        'as' => 'admin.programme.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/manage-programme',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-course',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
        ),
        'uses' => 'App\\Http\\Controllers\\CourseController@index',
        'controller' => 'App\\Http\\Controllers\\CourseController@index',
        'as' => 'admin.course.index',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-course/course_status/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
          4 => 'permission:course.status',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@course_status',
        'controller' => 'App\\Http\\Controllers\\AdminController@course_status',
        'as' => 'admin.course.',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-course',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
          4 => 'permission:course.create',
        ),
        'uses' => 'App\\Http\\Controllers\\CourseController@store',
        'controller' => 'App\\Http\\Controllers\\CourseController@store',
        'as' => 'admin.course.store',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-course/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
          4 => 'permission:course.update',
        ),
        'uses' => 'App\\Http\\Controllers\\CourseController@edit',
        'controller' => 'App\\Http\\Controllers\\CourseController@edit',
        'as' => 'admin.course.edit',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/manage-course/{course}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
        ),
        'uses' => 'App\\Http\\Controllers\\CourseController@update',
        'controller' => 'App\\Http\\Controllers\\CourseController@update',
        'as' => 'admin.course.update',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-course/{course}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
          4 => 'permission:course.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\CourseController@destroy',
        'controller' => 'App\\Http\\Controllers\\CourseController@destroy',
        'as' => 'admin.course.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.fetch.programme' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-course/fetch-programme',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
        ),
        'uses' => 'App\\Http\\Controllers\\CourseController@fetchProgrammeDetails',
        'controller' => 'App\\Http\\Controllers\\CourseController@fetchProgrammeDetails',
        'as' => 'admin.course.fetch.programme',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.course.fetch.centre' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-course/fetch/centre',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:course.read',
        ),
        'uses' => 'App\\Http\\Controllers\\CourseController@fetchCentre',
        'controller' => 'App\\Http\\Controllers\\CourseController@fetchCentre',
        'as' => 'admin.course.fetch.centre',
        'namespace' => NULL,
        'prefix' => 'admin/manage-course',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.session.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sessions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:session.read',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@index',
        'controller' => 'App\\Http\\Controllers\\SessionController@index',
        'as' => 'admin.session.index',
        'namespace' => NULL,
        'prefix' => 'admin/sessions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.session.fetch' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sessions/fetch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:session.read',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@fetch',
        'controller' => 'App\\Http\\Controllers\\SessionController@fetch',
        'as' => 'admin.session.fetch',
        'namespace' => NULL,
        'prefix' => 'admin/sessions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.session.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sessions/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:session.read',
          4 => 'permission:session.create',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@create',
        'controller' => 'App\\Http\\Controllers\\SessionController@create',
        'as' => 'admin.session.create',
        'namespace' => NULL,
        'prefix' => 'admin/sessions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.session.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/sessions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:session.read',
          4 => 'permission:session.create',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@store',
        'controller' => 'App\\Http\\Controllers\\SessionController@store',
        'as' => 'admin.session.store',
        'namespace' => NULL,
        'prefix' => 'admin/sessions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.session.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sessions/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:session.read',
          4 => 'permission:session.update',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@edit',
        'controller' => 'App\\Http\\Controllers\\SessionController@edit',
        'as' => 'admin.session.edit',
        'namespace' => NULL,
        'prefix' => 'admin/sessions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.session.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/sessions/{session}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:session.read',
          4 => 'permission:session.update',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@update',
        'controller' => 'App\\Http\\Controllers\\SessionController@update',
        'as' => 'admin.session.update',
        'namespace' => NULL,
        'prefix' => 'admin/sessions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.session.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/sessions/{session}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:session.read',
          4 => 'permission:session.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\SessionController@destroy',
        'controller' => 'App\\Http\\Controllers\\SessionController@destroy',
        'as' => 'admin.session.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/sessions',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.class.schedule.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-class-schedule',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ClassScheduleController@index',
        'controller' => 'App\\Http\\Controllers\\ClassScheduleController@index',
        'as' => 'admin.class.schedule.index',
        'namespace' => NULL,
        'prefix' => 'admin/manage-class-schedule',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.class.schedule.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-class-schedule',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ClassScheduleController@store',
        'controller' => 'App\\Http\\Controllers\\ClassScheduleController@store',
        'as' => 'admin.class.schedule.store',
        'namespace' => NULL,
        'prefix' => 'admin/manage-class-schedule',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.class.schedule.edit' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-class-schedule/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ClassScheduleController@edit',
        'controller' => 'App\\Http\\Controllers\\ClassScheduleController@edit',
        'as' => 'admin.class.schedule.edit',
        'namespace' => NULL,
        'prefix' => 'admin/manage-class-schedule',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.class.schedule.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/manage-class-schedule/{course}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ClassScheduleController@update',
        'controller' => 'App\\Http\\Controllers\\ClassScheduleController@update',
        'as' => 'admin.class.schedule.update',
        'namespace' => NULL,
        'prefix' => 'admin/manage-class-schedule',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.class.schedule.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-class-schedule/{course}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\ClassScheduleController@destroy',
        'controller' => 'App\\Http\\Controllers\\ClassScheduleController@destroy',
        'as' => 'admin.class.schedule.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/manage-class-schedule',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sms.template.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-sms-template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
        ),
        'uses' => 'App\\Http\\Controllers\\SmsTemplateController@index',
        'controller' => 'App\\Http\\Controllers\\SmsTemplateController@index',
        'as' => 'admin.sms.template.index',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sms.template.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-sms-template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
          4 => 'permission:sms-template.create',
        ),
        'uses' => 'App\\Http\\Controllers\\SmsTemplateController@store',
        'controller' => 'App\\Http\\Controllers\\SmsTemplateController@store',
        'as' => 'admin.sms.template.store',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sms.template.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-sms-template/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
          4 => 'permission:sms-template.update',
        ),
        'uses' => 'App\\Http\\Controllers\\SmsTemplateController@edit',
        'controller' => 'App\\Http\\Controllers\\SmsTemplateController@edit',
        'as' => 'admin.sms.template.edit',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sms.template.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/manage-sms-template/{template}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
          4 => 'permission:sms-template.update',
        ),
        'uses' => 'App\\Http\\Controllers\\SmsTemplateController@update',
        'controller' => 'App\\Http\\Controllers\\SmsTemplateController@update',
        'as' => 'admin.sms.template.update',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.sms.template.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-sms-template/{template}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
          4 => 'permission:sms-template.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\SmsTemplateController@destroy',
        'controller' => 'App\\Http\\Controllers\\SmsTemplateController@destroy',
        'as' => 'admin.sms.template.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.fetch.sms.template' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-sms-template/fetch_sms_template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@fetch_sms_template',
        'controller' => 'App\\Http\\Controllers\\AdminController@fetch_sms_template',
        'as' => 'admin.fetch.sms.template',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.send_bulk_email' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-sms-template/send-bulk-email',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
          4 => 'permission:student.bulk-email',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@sendBulkEmail',
        'controller' => 'App\\Http\\Controllers\\AdminController@sendBulkEmail',
        'as' => 'admin.send_bulk_email',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.send_bulk_sms' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-sms-template/send_bulk_sms',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:sms-template.read',
          4 => 'permission:student.bulk-sms',
        ),
        'uses' => 'App\\Http\\Controllers\\AdminController@sendBulkSMS',
        'controller' => 'App\\Http\\Controllers\\AdminController@sendBulkSMS',
        'as' => 'admin.send_bulk_sms',
        'namespace' => NULL,
        'prefix' => 'admin/manage-sms-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.email.template.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-email-template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:email-template.read',
        ),
        'uses' => 'App\\Http\\Controllers\\EmailTemplateController@index',
        'controller' => 'App\\Http\\Controllers\\EmailTemplateController@index',
        'as' => 'admin.email.template.index',
        'namespace' => NULL,
        'prefix' => 'admin/manage-email-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.email.template.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/manage-email-template',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:email-template.read',
          4 => 'permission:email-template.create',
        ),
        'uses' => 'App\\Http\\Controllers\\EmailTemplateController@store',
        'controller' => 'App\\Http\\Controllers\\EmailTemplateController@store',
        'as' => 'admin.email.template.store',
        'namespace' => NULL,
        'prefix' => 'admin/manage-email-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.email.template.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-email-template/{id}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:email-template.read',
          4 => 'permission:email-template.update',
        ),
        'uses' => 'App\\Http\\Controllers\\EmailTemplateController@edit',
        'controller' => 'App\\Http\\Controllers\\EmailTemplateController@edit',
        'as' => 'admin.email.template.edit',
        'namespace' => NULL,
        'prefix' => 'admin/manage-email-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.email.template.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/manage-email-template/{template}/update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:email-template.read',
          4 => 'permission:email-template.update',
        ),
        'uses' => 'App\\Http\\Controllers\\EmailTemplateController@update',
        'controller' => 'App\\Http\\Controllers\\EmailTemplateController@update',
        'as' => 'admin.email.template.update',
        'namespace' => NULL,
        'prefix' => 'admin/manage-email-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.email.template.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/manage-email-template/{template}/delete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:email-template.read',
          4 => 'permission:email-template.delete',
        ),
        'uses' => 'App\\Http\\Controllers\\EmailTemplateController@destroy',
        'controller' => 'App\\Http\\Controllers\\EmailTemplateController@destroy',
        'as' => 'admin.email.template.destroy',
        'namespace' => NULL,
        'prefix' => 'admin/manage-email-template',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.config.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/app-config',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:manage.monitor',
          4 => 'admin.super',
        ),
        'uses' => 'App\\Http\\Controllers\\AppConfigController@index',
        'controller' => 'App\\Http\\Controllers\\AppConfigController@index',
        'as' => 'admin.config.index',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.config.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'admin/app-config',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:manage.monitor',
          4 => 'admin.super',
        ),
        'uses' => 'App\\Http\\Controllers\\AppConfigController@update',
        'controller' => 'App\\Http\\Controllers\\AppConfigController@update',
        'as' => 'admin.config.update',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.fetch' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/lists/fetch',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'uses' => 'App\\Http\\Controllers\\ListController@fetch',
        'controller' => 'App\\Http\\Controllers\\ListController@fetch',
        'as' => 'admin.lists.fetch',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.view-data' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/lists/view-data',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'uses' => 'App\\Http\\Controllers\\ListController@viewData',
        'controller' => 'App\\Http\\Controllers\\ListController@viewData',
        'as' => 'admin.lists.view-data',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.get-table-columns' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/lists/get-table-columns',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'uses' => 'App\\Http\\Controllers\\ListController@getTableColumns',
        'controller' => 'App\\Http\\Controllers\\ListController@getTableColumns',
        'as' => 'admin.lists.get-table-columns',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/lists',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'as' => 'admin.lists.index',
        'uses' => 'App\\Http\\Controllers\\ListController@index',
        'controller' => 'App\\Http\\Controllers\\ListController@index',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.create' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/lists/create',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'as' => 'admin.lists.create',
        'uses' => 'App\\Http\\Controllers\\ListController@create',
        'controller' => 'App\\Http\\Controllers\\ListController@create',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/lists',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'as' => 'admin.lists.store',
        'uses' => 'App\\Http\\Controllers\\ListController@store',
        'controller' => 'App\\Http\\Controllers\\ListController@store',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/lists/{list}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'as' => 'admin.lists.show',
        'uses' => 'App\\Http\\Controllers\\ListController@show',
        'controller' => 'App\\Http\\Controllers\\ListController@show',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/lists/{list}/edit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'as' => 'admin.lists.edit',
        'uses' => 'App\\Http\\Controllers\\ListController@edit',
        'controller' => 'App\\Http\\Controllers\\ListController@edit',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'admin/lists/{list}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'as' => 'admin.lists.update',
        'uses' => 'App\\Http\\Controllers\\ListController@update',
        'controller' => 'App\\Http\\Controllers\\ListController@update',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.lists.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'admin/lists/{list}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:admin',
          3 => 'permission:student.bulk-email|sudent.bulk-sms',
        ),
        'as' => 'admin.lists.destroy',
        'uses' => 'App\\Http\\Controllers\\ListController@destroy',
        'controller' => 'App\\Http\\Controllers\\ListController@destroy',
        'namespace' => NULL,
        'prefix' => 'admin/',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/select-session/{user_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth',
          3 => 'is_admitted',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@select_session_view',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@select_session_view',
        'as' => 'student.',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.select-session' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'student/select-session/{user_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth',
          3 => 'is_admitted',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@confirm_session',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@confirm_session',
        'as' => 'student.select-session',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.delete-student-admission' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'student/delete-student-admission/{user_id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth',
          3 => 'is_admitted',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@delete_admission',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@delete_admission',
        'as' => 'student.delete-student-admission',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.dashboard' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/dashboard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@dashboard',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@dashboard',
        'as' => 'student.dashboard',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.application-status' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/application-status',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@application_status',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@application_status',
        'as' => 'student.application-status',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.profile' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
          3 => 'is_not_admitted',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@profile',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@profile',
        'as' => 'student.profile',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.change-course' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/change-course',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
          3 => 'is_not_admitted',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@change_course',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@change_course',
        'as' => 'student.change-course',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.update-course' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'student/update-course',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
          3 => 'is_not_admitted',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@update_course',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@update_course',
        'as' => 'student.update-course',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::6PDhptfrruULBgVi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/exam',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@exam',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@exam',
        'as' => 'student.generated::6PDhptfrruULBgVi',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::jEF4QqLogtkZw5eY' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/join_exam/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@join_exam',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@join_exam',
        'as' => 'student.generated::jEF4QqLogtkZw5eY',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::Qb0mEuHiUoGgBeGy' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'student/submit_questions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@submit_questions',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@submit_questions',
        'as' => 'student.generated::Qb0mEuHiUoGgBeGy',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::UGImM2OsTklOsibH' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/show_result/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@show_result',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@show_result',
        'as' => 'student.generated::UGImM2OsTklOsibH',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::0KwIDkPb6Zn2Rjuu' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/apply_exam/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@apply_exam',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@apply_exam',
        'as' => 'student.generated::0KwIDkPb6Zn2Rjuu',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.attendance.record' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'student/attendance/record',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
          3 => 'is_admitted:true',
        ),
        'uses' => 'App\\Http\\Controllers\\AttendanceController@recordAttendance',
        'controller' => 'App\\Http\\Controllers\\AttendanceController@recordAttendance',
        'as' => 'student.attendance.record',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.attendance.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/attendance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
          3 => 'is_admitted:true',
        ),
        'uses' => 'App\\Http\\Controllers\\AttendanceController@viewAttendance',
        'controller' => 'App\\Http\\Controllers\\AttendanceController@viewAttendance',
        'as' => 'student.attendance.show',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::X1QZ25pL77T5c7Mn' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/id-qrcode',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
          3 => 'is_admitted:true',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@get_details_page',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@get_details_page',
        'as' => 'student.generated::X1QZ25pL77T5c7Mn',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::6OJG8LficUjtYRDs' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/scan-qrcode',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@get_scanner_page',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@get_scanner_page',
        'as' => 'student.generated::6OJG8LficUjtYRDs',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::5lZAWpfkmySWpeR0' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/meeting-link',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@get_meeting_link_page',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@get_meeting_link_page',
        'as' => 'student.generated::5lZAWpfkmySWpeR0',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.updateDetails' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'student/update-details',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
          3 => 'is_admitted',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@updateDetails',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@updateDetails',
        'as' => 'student.updateDetails',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::WMlvvbegNUbGLlso' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'student/start-exam/{id}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\StudentOperation@start_exam',
        'controller' => 'App\\Http\\Controllers\\StudentOperation@start_exam',
        'as' => 'student.generated::WMlvvbegNUbGLlso',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.mark-attendance' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/mark_attendance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\AttendanceController@recordAttendance',
        'controller' => 'App\\Http\\Controllers\\AttendanceController@recordAttendance',
        'as' => 'student.mark-attendance',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'student.generated::1Z5XKfqu4ZW7yLHe' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'student/logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:dashboard',
          2 => 'auth:web',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'as' => 'student.generated::1Z5XKfqu4ZW7yLHe',
        'namespace' => NULL,
        'prefix' => '/student',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.edit' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@edit',
        'controller' => 'App\\Http\\Controllers\\ProfileController@edit',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.edit',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.update' => 
    array (
      'methods' => 
      array (
        0 => 'PATCH',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@update',
        'controller' => 'App\\Http\\Controllers\\ProfileController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'profile.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\ProfileController@destroy',
        'controller' => 'App\\Http\\Controllers\\ProfileController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'profile.destroy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'login' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'login',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dvDPzZUZI2OrYyfP' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::dvDPzZUZI2OrYyfP',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.request' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.request',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.email' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'forgot-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.email',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.reset' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'reset-password/{token}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@create',
        'controller' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@create',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.reset',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'reset-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'guest',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\NewPasswordController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.store',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.notice' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'verify-email',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\EmailVerificationPromptController@__invoke',
        'controller' => 'App\\Http\\Controllers\\Auth\\EmailVerificationPromptController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.notice',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.verify' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'verify-email/{id}/{hash}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'signed',
          3 => 'throttle:6,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\VerifyEmailController@__invoke',
        'controller' => 'App\\Http\\Controllers\\Auth\\VerifyEmailController',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.verify',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'verification.send' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'email/verification-notification',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
          2 => 'throttle:6,1',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\EmailVerificationNotificationController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\EmailVerificationNotificationController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'verification.send',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.confirm' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'confirm-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@show',
        'controller' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@show',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.confirm',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::JhoNPb7EcOKIjigE' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'confirm-password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@store',
        'controller' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController@store',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::JhoNPb7EcOKIjigE',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'password.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\PasswordController@update',
        'controller' => 'App\\Http\\Controllers\\Auth\\PasswordController@update',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'password.update',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'logout' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'auth',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController@destroy',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'logout',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.login' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:admin',
          2 => 'guest:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AuthenticatedSessionController@create',
        'controller' => 'App\\Http\\Controllers\\Admin\\AuthenticatedSessionController@create',
        'as' => 'admin.login',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::9YcZzlCg8ylvadDi' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:admin',
          2 => 'guest:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AuthenticatedSessionController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\AuthenticatedSessionController@store',
        'as' => 'admin.generated::9YcZzlCg8ylvadDi',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.logout' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:admin',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\AuthenticatedSessionController@destroy',
        'controller' => 'App\\Http\\Controllers\\Admin\\AuthenticatedSessionController@destroy',
        'as' => 'admin.logout',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.register' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'admin/register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:admin',
          2 => 'auth:admin',
        ),
        'uses' => '\\Illuminate\\Routing\\ViewController@__invoke',
        'controller' => '\\Illuminate\\Routing\\ViewController',
        'as' => 'admin.register',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
        'view' => 'auth.register',
        'data' => 
        array (
        ),
        'status' => 200,
        'headers' => 
        array (
        ),
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'admin.generated::Gu8FrrAQX39KnFn1' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'admin/register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'theme:admin',
          2 => 'auth:admin',
        ),
        'uses' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@store',
        'controller' => 'App\\Http\\Controllers\\Admin\\RegisteredUserController@store',
        'as' => 'admin.generated::Gu8FrrAQX39KnFn1',
        'namespace' => NULL,
        'prefix' => '/admin',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Z5nBUwdOijmyqwXZ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
        2 => 'POST',
        3 => 'PUT',
        4 => 'PATCH',
        5 => 'DELETE',
        6 => 'OPTIONS',
      ),
      'uri' => 'builder/{any}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
          1 => 'admin.super',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:168:"function () {
            $builder = new \\HansSchouten\\LaravelPageBuilder\\LaravelPageBuilder(config(\'pagebuilder\'));
            $builder->handleRequest();
        }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"0000000000000a150000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '/builder',
        'where' => 
        array (
        ),
        'as' => 'generated::Z5nBUwdOijmyqwXZ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::jXxHi85d8Ydy01Aq' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
        2 => 'POST',
        3 => 'PUT',
        4 => 'PATCH',
        5 => 'DELETE',
        6 => 'OPTIONS',
      ),
      'uri' => 'pages/{any}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:266:"function () {

        $builder = new \\HansSchouten\\LaravelPageBuilder\\LaravelPageBuilder(config(\'pagebuilder\'));
        $hasPageReturned = $builder->handlePublicRequest();

        if (! $hasPageReturned) {
            return redirect(\'/\');
        }
    }";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000005a60000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::jXxHi85d8Ydy01Aq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
        'any' => '.*',
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
