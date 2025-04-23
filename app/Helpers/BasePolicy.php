<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policies\Basic;
use Spatie\Csp\Scheme;
use Spatie\Csp\Value;

class BasePolicy extends Basic
{
    public function configure()
    {
        // parent::configure();
        if (config('app.debug') && config('app.env') != 'production') {
            $this->addDirective(Directive::CONNECT, 'ws:');
        }

        $this
            ->addDirective(Directive::BASE, Keyword::SELF)
            ->addDirective(Directive::CONNECT, Keyword::SELF)
            ->addDirective(Directive::DEFAULT, Keyword::SELF)
            ->addDirective(Directive::FORM_ACTION, Keyword::SELF)
            ->addDirective(Directive::MEDIA, Keyword::SELF)
            ->addDirective(Directive::OBJECT, Keyword::NONE)
            ->addDirective(Directive::SCRIPT, Keyword::SELF)
            ->addDirective(Directive::STYLE, Keyword::SELF)
            // ->addDirective(Directive::CONNECT, Scheme::WSS)
            ->addDirective(Directive::UPGRADE_INSECURE_REQUESTS, Value::NO_VALUE)
            ->addDirective(Directive::BLOCK_ALL_MIXED_CONTENT, Value::NO_VALUE)
            ->addDirective(Directive::IMG, [
                'self',
                '*',
                'unsafe-inline',
                'data:',
            ])
            ->addDirective(Directive::OBJECT, 'none')
            ->addDirective(Directive::SCRIPT, [
                'fonts.googleapis.com',
                'fonts.gstatic.com',
                // 'cdn.jsdelivr.net',
                // 'cdnjs.cloudflare.com',
                'cdn.datatables.net',
                'cdn.rawgit.com',
                'maxcdn.bootstrapcdn.com',
                // Keyword::UNSAFE_INLINE,
                // Keyword::UNSAFE_EVAL
            ])
            ->addDirective(Directive::STYLE, [
                'fonts.googleapis.com',
                'fonts.gstatic.com',
                // 'cdnjs.cloudflare.com',
                'fontawesome.com',
                'cdn.datatables.net',
                'data:',
                'code.ionicframework.com',
                'unpkg.com',
                'fonts.bunny.net',
                'maxcdn.bootstrapcdn.com',
                'cdn.jsdelivr.net',
                Keyword::UNSAFE_INLINE,

            ])
            ->addDirective(Directive::FONT, [
                Keyword::SELF,
                'fonts.googleapis.com',
                'fonts.gstatic.com',
                'fontawesome.com',
                'code.ionicframework.com',
                'fonts.bunny.net',
                'data:',
                'maxcdn.bootstrapcdn.com',
                Keyword::UNSAFE_INLINE,

            ])
            ->addDirective(Directive::WORKER, [
                'blob:',
            ])
            ->addNonceForDirective(Directive::SCRIPT)
            ->addNonceForDirective(Directive::STYLE);

        if (str(request()->path())->startsWith(config('horizon.path', 'horizon')) || env('IGNORE_POLICY', false)) {
            $this->reportOnly();
        }

        // ->reportOnly();
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return bool
     */
    public function shouldBeApplied(Request $request, Response $response): bool
    {
        if (config('app.debug') && ($response->isClientError() || $response->isServerError())) {
            return false;
        }

        return parent::shouldBeApplied($request, $response);
    }
}
