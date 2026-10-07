<?php

namespace Deadbolt\Tags;

use Deadbolt\Support\Captcha;
use Deadbolt\Support\ReturnTarget;
use ExpressionEngine\Service\Addon\Controllers\Tag\AbstractRoute;

class Form extends AbstractRoute
{
    public function process()
    {
        $actionId = ee()->functions->fetch_action_id('Deadbolt', 'SubmitKey');
        if (empty($actionId)) {
            return '';
        }

        $lock = ee()->TMPL->fetch_param('lock');
        $return = ReturnTarget::normalize(ee()->TMPL->fetch_param('return'));
        // fetch_action_id() returns ExpressionEngine's {AID:...} token. Keep
        // it intact so EE can resolve it to the registered numeric action ID.
        $formAction = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $actionId;
        $class = ee()->TMPL->fetch_param('class');
        $id = ee()->TMPL->fetch_param('id');

        $open = ee()->functions->form_declaration([
            'action' => $formAction,
            'class' => is_string($class) ? $class : '',
            'id' => is_string($id) ? $id : '',
            'hidden_fields' => [
                'deadbolt_lock' => is_string($lock) ? $lock : '',
                'deadbolt_return' => $return,
            ],
        ]);

        $body = ee()->TMPL->tagdata;
        $captcha = Captcha::markup();
        $captchaMarker = strpos($body, '{deadbolt_captcha}');
        if ($captchaMarker !== false) {
            $body = substr_replace($body, $captcha, $captchaMarker, strlen('{deadbolt_captcha}'));
        } elseif ($captcha !== '') {
            $body .= $captcha;
        }

        ee()->output->set_header('Cache-Control: private, no-store, max-age=0');
        ee()->output->set_header('Referrer-Policy: no-referrer');

        return $open . $body . "\n</form>";
    }
}
