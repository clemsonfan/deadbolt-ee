<?php

namespace Deadbolt\Tags;

use Deadbolt\Support\ResultStore;
use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute;

class Check extends AbstractRoute
{
    public function process()
    {
        $nonce = ee()->input->get('deadbolt_result');
        $requestedLock = ee()->TMPL->fetch_param('lock');
        $result = ResultStore::consume($nonce);

        $matchesLock = is_array($result)
            && is_string($requestedLock)
            && $requestedLock !== ''
            && hash_equals($result['lock_name'], $requestedLock);

        $variables = [
            'key_valid' => $matchesLock && $result['key_valid'] && ! $result['captcha_error'] ? 'y' : '',
            'captcha_error' => $matchesLock && $result['captcha_error'] ? 'y' : '',
        ];

        ee()->output->set_header('Cache-Control: private, no-store, max-age=0');
        ee()->output->set_header('Referrer-Policy: no-referrer');

        $output = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $variables);

        if (is_string($nonce) && $nonce !== '') {
            // The result has already been consumed server-side. Remove its
            // opaque identifier from the current browser history entry.
            $output .= '<script>(function(){var u=new URL(window.location.href);'
                . 'u.searchParams.delete("deadbolt_result");'
                . 'window.history.replaceState(window.history.state,"",u.pathname+u.search+u.hash);'
                . '}());</script>';
        }

        return $output;
    }
}
