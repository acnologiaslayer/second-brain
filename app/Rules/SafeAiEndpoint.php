<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Spec section 11 (Network): remote AI endpoints must use HTTPS, and users must not be able to
 * point the server at arbitrary internal network targets (SSRF). Allowed:
 *  - https:// to a public host
 *  - http(s)://localhost / 127.0.0.1 / ::1 for local inference (Ollama, LM Studio), only when
 *    AI_ALLOW_LOCAL_ENDPOINTS is true (default true for local dev, set false in production)
 *  - hosts explicitly listed in AI_ALLOWED_HOSTS (comma separated), e.g. a LAN GPU box
 * Blocked: plain http to remote hosts, private/link-local/reserved IPs (incl. 169.254.169.254
 * cloud metadata), non-http schemes, and hostnames that resolve to private addresses.
 */
class SafeAiEndpoint implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) return;
        if ($error = self::check((string) $value)) $fail($error);
    }

    public static function check(string $url): ?string
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return 'The endpoint must be an http(s) URL.';
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'Do not put credentials in the endpoint URL. Use the API key field.';
        }

        $allowed = array_filter(array_map('trim', explode(',', strtolower((string) config('services.ai.allowed_hosts')))));
        if (in_array($host, $allowed, true)) return null;

        $isLoopback = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.localhost');
        if ($isLoopback) {
            return config('services.ai.allow_local_endpoints')
                ? null
                : 'Local endpoints are disabled on this server.';
        }

        if ($scheme !== 'https') {
            return 'Remote endpoints must use HTTPS.';
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);
        if ($ips === []) {
            return 'The endpoint host could not be resolved.';
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'The endpoint points to a private or reserved network address, which is not allowed.';
            }
        }
        return null;
    }

    /** @return string[] */
    protected static function resolve(string $host): array
    {
        if (app()->runningUnitTests() && ! config('services.ai.resolve_dns_in_tests', false)) {
            return ['93.184.216.34']; // tests must not depend on DNS; private-IP literals are still checked
        }
        $v4 = gethostbynamel($host) ?: [];
        $v6 = collect(@dns_get_record($host, DNS_AAAA) ?: [])->pluck('ipv6')->filter()->all();
        return array_values(array_unique([...$v4, ...$v6]));
    }
}
