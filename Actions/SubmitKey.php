<?php

namespace Deadbolt\Actions;

use Deadbolt\Support\Captcha;
use Deadbolt\Support\Locks;
use Deadbolt\Support\ResultStore;
use Deadbolt\Support\ReturnTarget;
use ExpressionEngine\Service\Addon\Controllers\Action\AbstractRoute;

class SubmitKey extends AbstractRoute
{
    public function process()
    {
        $lock = ee()->input->post('deadbolt_lock');
        $return = ee()->input->post('deadbolt_return');
        $captchaError = Captcha::isRequired() && ! Captcha::validatePosted();
        $submittedKey = ee()->input->post('deadbolt_key');
        $keyValid = ! $captchaError && Locks::matches($lock, $submittedKey);

        $nonce = ResultStore::store($lock, $keyValid, $captchaError);
        if ($nonce === null) {
            ee()->output->set_status_header(503);
            ee()->output->fatal_error('Deadbolt could not complete this request. Please try again.');

            return false;
        }

        $destination = ReturnTarget::withResultNonce($return, $nonce);
        ee()->output->set_header('Cache-Control: private, no-store, max-age=0');
        ee()->output->set_header('Referrer-Policy: no-referrer');
        ee()->functions->redirect($destination, false, 303);

        return true;
    }
}
