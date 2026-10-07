<?php

namespace Deadbolt\Support;

class ResultStore
{
    const TTL_SECONDS = 300;

    public static function store($lock, $keyValid, $captchaError)
    {
        if (! is_string($lock) || strlen($lock) > 191) {
            $lock = '';
        }

        if ($captchaError) {
            $keyValid = false;
        }

        $sessionHash = self::sessionHash();
        if ($sessionHash === null) {
            return null;
        }

        try {
            $nonce = bin2hex(random_bytes(32));
        } catch (\Throwable $exception) {
            return null;
        }

        $now = (int) ee()->localize->now;
        ee()->db->where('expires_at <', $now)->delete('deadbolt_results');

        $saved = ee()->db->insert('deadbolt_results', [
            'nonce_hash' => hash('sha256', $nonce),
            'session_hash' => $sessionHash,
            'lock_name' => $lock,
            'key_valid' => $keyValid ? 1 : 0,
            'captcha_error' => $captchaError ? 1 : 0,
            'expires_at' => $now + self::TTL_SECONDS,
        ]);

        return $saved ? $nonce : null;
    }

    public static function consume($nonce)
    {
        if (! is_string($nonce) || ! preg_match('/^[a-f0-9]{64}$/D', $nonce)) {
            return null;
        }

        $sessionHash = self::sessionHash();
        if ($sessionHash === null) {
            return null;
        }

        $nonceHash = hash('sha256', $nonce);
        $now = (int) ee()->localize->now;
        $table = ee()->db->dbprefix('deadbolt_results');

        ee()->db->trans_begin();
        $query = ee()->db->query(
            "SELECT nonce_hash, session_hash, lock_name, key_valid, captcha_error, expires_at "
            . "FROM {$table} WHERE nonce_hash = ? AND session_hash = ? AND expires_at >= ? FOR UPDATE",
            [$nonceHash, $sessionHash, $now]
        );
        $row = $query->row_array();

        if (empty($row)) {
            ee()->db->trans_commit();

            return null;
        }

        ee()->db->where('nonce_hash', $nonceHash)
            ->where('session_hash', $sessionHash)
            ->delete('deadbolt_results');

        if (! ee()->db->trans_status()) {
            ee()->db->trans_rollback();

            return null;
        }

        ee()->db->trans_commit();

        $result = [
            'lock_name' => (string) $row['lock_name'],
            'key_valid' => (bool) $row['key_valid'],
            'captcha_error' => (bool) $row['captcha_error'],
        ];

        return $result;
    }

    private static function sessionHash()
    {
        $sessionId = ee()->session->userdata('session_id');
        $encryptionKey = ee()->config->item('encryption_key');

        if (! is_string($sessionId) || $sessionId === '' || ! is_string($encryptionKey) || $encryptionKey === '') {
            return null;
        }

        return hash_hmac('sha256', $sessionId, $encryptionKey);
    }
}
