<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

class HostMatcher
{
    public function matches(string $host, string $patternList): bool
    {
        $patterns = array_filter(array_map('trim', explode(',', $patternList)));
        if ($patterns === []) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern === '*') {
                return true;
            }
            $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';
            if (preg_match($regex, $host)) {
                return true;
            }
        }

        return false;
    }
}
