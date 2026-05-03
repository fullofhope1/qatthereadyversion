<?php
/**
 * includes/classes/UrlHelper.php
 *
 * A utility class to help obfuscate (encrypt) URL parameters.
 */

class UrlHelper {
    private static $key = 'qat_secure_key_2026'; // Change this to something unique

    /**
     * Obfuscates a value for use in a URL
     */
    public static function encode($value) {
        $encoded = base64_encode($value . '|' . self::$key);
        return str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
    }

    /**
     * Decodes an obfuscated value from a URL
     */
    public static function decode($encoded) {
        $data = str_replace(['-', '_'], ['+', '/'], $encoded);
        $decoded = base64_decode($data);
        if (!$decoded) {
            return null;
        }

        $parts = explode('|', $decoded);
        if (count($parts) === 2 && $parts[1] === self::$key) {
            return $parts[0];
        }
        return null;
    }

    /**
     * Generates a clean URL with encoded parameters
     * Example: UrlHelper::url('closing', ['date' => '2026-05-01'])
     * Result: closing?q=...encoded_string...
     */
    public static function url($page, $params = []) {
        if (empty($params)) {
            return $page;
        }

        $queryString = http_build_query($params);
        $encoded = self::encode($queryString);
        return $page . '?q=' . $encoded;
    }

    /**
     * Decodes all parameters from the 'q' parameter if it exists
     */
    public static function resolveParams() {
        if (isset($_GET['q'])) {
            $decoded = self::decode($_GET['q']);
            if ($decoded) {
                parse_str($decoded, $params);
                foreach ($params as $key => $value) {
                    $_GET[$key] = $value;
                    $_REQUEST[$key] = $value;
                }
            }
        }
    }
}
