<?php

if (!function_exists('generate_jwt_secret')) {
    /**
     * Generate a cryptographically secure random string suitable for use as a JWT secret.
     *
     * Guarantees at least one uppercase letter, one lowercase letter,
     * one digit, and one special character.
     *
     * @param  int  $length  Total length (minimum 4)
     * @return string
     */
    function generate_jwt_secret(int $length = 12): string
    {
        $length = max($length, 4);

        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $digits  = '0123456789';
        $special = '!@#$%^&*';
        $all     = $upper . $lower . $digits . $special;

        // Guarantee at least one of each required character type
        $token  = $upper[random_int(0, strlen($upper) - 1)];
        $token .= $lower[random_int(0, strlen($lower) - 1)];
        $token .= $digits[random_int(0, strlen($digits) - 1)];
        $token .= $special[random_int(0, strlen($special) - 1)];

        // Fill remaining characters from the full pool
        for ($i = 4; $i < $length; $i++) {
            $token .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle so guaranteed chars aren't always at the start
        return str_shuffle($token);
    }
}
