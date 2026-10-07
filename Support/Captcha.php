<?php

namespace Deadbolt\Support;

class Captcha
{
    public static function isRequired()
    {
        // ExpressionEngine documents that superadmins are exempt. The native
        // helper handles the site-wide and logged-in-member preferences.
        if ((int) ee()->session->userdata('group_id') === 1) {
            return false;
        }

        return ee('Captcha')->shouldRequireCaptcha();
    }

    public static function markup()
    {
        if (! self::isRequired()) {
            return '';
        }

        $captcha = ee('Captcha')->create();

        if (ee()->config->item('use_recaptcha') === 'y') {
            // EE emits the native reCAPTCHA v3 scripts and its hidden captcha
            // field, and verifies the token through its own Member action.
            return $captcha;
        }

        return '<fieldset class="deadbolt-captcha">'
            . '<legend>CAPTCHA</legend>'
            . $captcha
            . '<label for="deadbolt-captcha-answer">Enter the word shown</label>'
            . '<input type="text" id="deadbolt-captcha-answer" name="captcha" value="" '
            . 'size="20" maxlength="20" autocomplete="off" required>'
            . '</fieldset>';
    }

    public static function validatePosted()
    {
        if (! self::isRequired()) {
            return true;
        }

        $answer = ee()->input->post('captcha');
        if (! is_string($answer) || $answer === '') {
            self::deleteExpired();

            return false;
        }

        $now = (int) ee()->localize->now;
        $ipAddress = ee()->input->ip_address();
        $captcha = ee('Model')->get('Captcha')
            ->filter('word', $answer)
            ->filter('ip_address', $ipAddress)
            ->filter('date', '>', $now - 7200)
            ->first();

        self::deleteExpired();

        if (empty($captcha)) {
            return false;
        }

        // EE image challenges and its reCAPTCHA action both create a one-time
        // exp_captcha record tied to the request IP. Consume it on success.
        $captcha->delete();

        return true;
    }

    private static function deleteExpired()
    {
        $now = (int) ee()->localize->now;

        ee('Model')->get('Captcha')
            ->filter('date', '<', $now - 7200)
            ->delete();
    }
}
