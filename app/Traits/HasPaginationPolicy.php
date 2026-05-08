<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HasPaginationPolicy
{
    protected function resolvePerPage(Request $request, ?int $override = null): int
    {
        $default = (int) config('kaan.pagination.default_per_page', 15);
        $max     = (int) config('kaan.pagination.max_per_page', 100);

        // Hard cap absoluto (protección anti OOM aunque config/policy se equivoque)
        $hardCap = 1000;
        $max = min(max($max, 1), $hardCap);

        $raw = $override ?? (int) $request->query('per_page', $default);

        return min(max($raw, 1), $max);
    }
}
