<?php

namespace App\Services\Policy;

use App\Domain\Policy\Exceptions\PolicyConflictException;
use App\Domain\Policy\Exceptions\PolicyNotFoundException;
use App\Domain\Policy\Exceptions\PolicyReadOnlyException;
use App\Models\PolicySetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class PolicyService
{
    private const VERSION_KEY = 'policy:version';
    private const SNAPSHOT_PREFIX = 'policy:snapshot:v';
    private const DEGRADED_LOG_SILENCE_KEY = 'policy:degraded:log_silence';
    private const DEGRADED_LAST_SEEN_KEY = 'policy:degraded:last_seen';

    public function registry(): array
    {
        return (array) config('kaan_policies', []);
    }

    public function getVersion(): int
    {
        $v = Cache::get(self::VERSION_KEY);
        if (!is_numeric($v)) {
            Cache::forever(self::VERSION_KEY, 1);
            return 1;
        }
        return (int) $v;
    }

    public function getSnapshotOrNull(): ?array
    {
        try {
            $version = $this->getVersion();
            $cacheKey = self::SNAPSHOT_PREFIX . $version;

            $snapshot = Cache::get($cacheKey);
            if (is_array($snapshot)) {
                return $snapshot;
            }

            // build + cache
            $snapshot = $this->buildSnapshot();
            Cache::put($cacheKey, $snapshot, now()->addMinutes(30));

            return $snapshot;
        } catch (\Throwable $e) {
            $this->markDegradedOnce($e);
            return null;
        }
    }

    /**
     * Snapshot devuelve:
     * - effective: [key => value] (ya tipado y listo para aplicar a config())
     * - meta: [key => ['source' => env|db|default, 'editable' => bool, 'sensitive' => bool, ...]]
     * - version: int
     */
    private function buildSnapshot(): array
    {
        $registry = $this->registry();

        /** @var \Illuminate\Support\Collection<int,PolicySetting> $rows */
        $rows = PolicySetting::query()->get()->keyBy('key');

        $effective = [];
        $meta = [];
        $applyMap = [];
        $version = $this->getVersion();

        foreach ($registry as $key => $def) {
            $defType = $def['type'] ?? 'string';
            $default = $def['default'] ?? null;

            $source = 'default';
            $value = $default;

            // ENV source for read-only features
            if (($def['source'] ?? null) === 'env' && isset($def['config_path'])) {
                $configPath = $def['config_path'];
                $value = (bool) config($configPath, $default);
                $source = 'env';
            } else {
                // DB override
                if ($rows->has($key)) {
                    $row = $rows->get($key);

                    if (($def['sensitive'] ?? false) === true) {
                        $value = $this->decryptValueOrNull($row->value_encrypted);
                    } else {
                        $value = $row->value_json;
                    }

                    $source = 'db';
                }
            }

            $value = $this->coerceType($key, $value, $defType);

            $effective[$key] = $value;
            $meta[$key] = [
                'group' => $def['group'] ?? 'general',
                'type' => $defType,
                'editable' => (bool) ($def['editable'] ?? false),
                'sensitive' => (bool) ($def['sensitive'] ?? false),
                'source' => $source,
                'description' => (string) ($def['description'] ?? ''),
                'rules' => $def['rules'] ?? null,
            ];

            $configPath = (string) ($def['config_path'] ?? $key);

            if (isset($applyMap[$configPath]) && $configPath !== $key) {
                // Prevent duplicate targets
                throw new \RuntimeException("Duplicate config_path mapping: {$configPath}");
            }

            $applyMap[$configPath] = $value;
        }

        return [
            'version' => $version,
            'effective' => $effective,
            'meta' => $meta,
            'apply_map' => $applyMap,
        ];
    }

    private function decryptValueOrNull(?string $cipher): mixed
    {
        if (!$cipher) return null;
        try {
            return json_decode(Crypt::decryptString($cipher), true);
        } catch (\Throwable) {
            return null;
        }
    }

    private function encryptValue(mixed $value): string
    {
        return Crypt::encryptString(json_encode($value));
    }

    private function coerceType(string $key, mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => is_numeric($value) ? (int) $value : 0,
            'string'  => is_null($value) ? '' : (string) $value,
            'array'   => is_array($value) ? $value : [],
            default   => $value,
        };
    }

    public function getPolicy(string $key): array
    {
        $reg = $this->registry();
        if (!array_key_exists($key, $reg)) {
            throw new PolicyNotFoundException($key);
        }

        $snapshot = $this->getSnapshotOrNull();
        $version = $snapshot['version'] ?? $this->getVersion();

        $effectiveValue = $snapshot['effective'][$key] ?? $this->coerceType($key, $reg[$key]['default'] ?? null, $reg[$key]['type'] ?? 'string');
        $meta = $snapshot['meta'][$key] ?? [];

        if (!$meta) {
            $def = $reg[$key];
            $isSensitive = (bool) ($def['sensitive'] ?? false);
            
            $meta = [
                'group' => $def['group'] ?? 'general',
                'type' => $def['type'] ?? 'string',
                'editable' => (bool) ($def['editable'] ?? false),
                'sensitive' => $isSensitive,
                'source' => (($def['source'] ?? null) === 'env') ? 'env' : 'default',
                'description' => (string) ($def['description'] ?? ''),
                'rules' => $def['rules'] ?? null,
                'is_set' => false,
                'redacted' => $isSensitive,
            ];
            
            if ($isSensitive) {
                $effectiveValue = null;
            }
        }

        return [
            'version' => $version,
            'key' => $key,
            'value' => $effectiveValue,
            'meta' => $meta,
        ];
    }

    public function listPolicies(): array
    {
        $snapshot = $this->getSnapshotOrNull();
        if (!$snapshot) {
            // degraded: return registry defaults (no crash)
            $items = [];
            foreach ($this->registry() as $key => $def) {
                $isSensitive = (bool) ($def['sensitive'] ?? false);
                $value = $this->coerceType($key, $def['default'] ?? null, $def['type'] ?? 'string');

                $items[$key] = [
                    'key' => $key,
                    'value' => $isSensitive ? null : $value,
                    'meta' => [
                        'group' => $def['group'] ?? 'general',
                        'type' => $def['type'] ?? 'string',
                        'editable' => (bool) ($def['editable'] ?? false),
                        'sensitive' => $isSensitive,
                        'source' => (($def['source'] ?? null) === 'env') ? 'env' : 'default',
                        'description' => (string) ($def['description'] ?? ''),
                        'rules' => $def['rules'] ?? null,
                        'is_set' => false,
                        'redacted' => $isSensitive,
                    ],
                ];
            }

            return [
                'version' => $this->getVersion(),
                'items' => $items,
                'degraded' => true,
            ];
        }

        return [
            'version' => (int) $snapshot['version'],
            'items' => $this->inflateItemsForApi($snapshot),
            'degraded' => false,
        ];
    }

    private function inflateItemsForApi(array $snapshot): array
    {
        $items = [];
        foreach ($snapshot['effective'] as $key => $value) {
            $meta = $snapshot['meta'][$key] ?? [];
            $isSensitive = (bool) ($meta['sensitive'] ?? false);

            $items[$key] = [
                'key' => $key,
                'value' => $isSensitive ? null : $value,
                'meta' => array_merge($meta, [
                    'is_set' => ($meta['source'] ?? null) === 'db',
                    'redacted' => $isSensitive,
                ]),
            ];
        }
        return $items;
    }

    public function updatePolicies(array $updates, ?User $actor = null): array
    {
        $registry = $this->registry();

        // Validate keys exist + editable
        foreach ($updates as $key => $value) {
            if (!array_key_exists($key, $registry)) {
                throw new PolicyNotFoundException((string) $key);
            }
            if (!($registry[$key]['editable'] ?? false)) {
                throw new PolicyReadOnlyException((string) $key);
            }

            $type = $registry[$key]['type'] ?? 'string';
            $value = $this->coerceType($key, $value, $type);

            // Per-key rules (min/max) as "validation-like"
            $rules = $registry[$key]['rules'] ?? null;
            if (is_array($rules) && $type === 'integer') {
                if (isset($rules['min']) && $value < (int)$rules['min']) {
                    throw new \InvalidArgumentException("Policy {$key} must be >= {$rules['min']}");
                }
                if (isset($rules['max']) && $value > (int)$rules['max']) {
                    throw new \InvalidArgumentException("Policy {$key} must be <= {$rules['max']}");
                }
            }

            $updates[$key] = $value;
        }

        // Cross-rules: validate new state (POLICY_CONFLICT)
        $this->assertNoConflicts($updates);

        DB::transaction(function () use ($updates, $registry, $actor) {
            foreach ($updates as $key => $value) {
                $def = $registry[$key];
                $isSensitive = (bool) ($def['sensitive'] ?? false);

                PolicySetting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value_json' => $isSensitive ? null : $value,
                        'value_encrypted' => $isSensitive ? $this->encryptValue($value) : null,
                        'updated_by_user_id' => $actor?->id,
                    ]
                );
            }

            DB::afterCommit(function () {
                $this->bumpVersion();
            });
        });

        return $this->listPolicies();
    }

    private function assertNoConflicts(array $updates): void
    {
        // Example cross-rule: default_per_page <= max_per_page
        // Build effective current snapshot (or defaults), then apply updates and validate.
        $snapshot = $this->getSnapshotOrNull();
        $effective = $snapshot['effective'] ?? [];

        // fallback to defaults if degraded
        if (!$effective) {
            foreach ($this->registry() as $k => $def) {
                $effective[$k] = $this->coerceType($k, $def['default'] ?? null, $def['type'] ?? 'string');
            }
        }

        foreach ($updates as $k => $v) {
            $effective[$k] = $v;
        }

        $default = (int) ($effective['api.pagination.default_per_page'] ?? 15);
        $max = (int) ($effective['api.pagination.max_per_page'] ?? 100);

        if ($default > $max) {
            throw new PolicyConflictException('api.pagination.default_per_page cannot be greater than api.pagination.max_per_page.');
        }
    }

    private function bumpVersion(): void
    {
        try {
            if (!Cache::has(self::VERSION_KEY)) {
                Cache::forever(self::VERSION_KEY, 1);
            }
            Cache::increment(self::VERSION_KEY);
        } catch (\Throwable $e) {
            // no hard failure; worst case: stale snapshot until cache TTL
        }
    }

    private function markDegradedOnce(\Throwable $e): void
    {
        try {
            if (!Cache::has(self::DEGRADED_LOG_SILENCE_KEY)) {
                Cache::put(self::DEGRADED_LOG_SILENCE_KEY, true, now()->addSeconds(300));
                Cache::put(self::DEGRADED_LAST_SEEN_KEY, now()->toIso8601String(), now()->addMinutes(30));
                logger()->warning('Policy Center degraded: cannot load snapshot. Using defaults.', [
                    'error' => $e->getMessage(),
                ]);
            } else {
                Cache::put(self::DEGRADED_LAST_SEEN_KEY, now()->toIso8601String(), now()->addMinutes(30));
            }
        } catch (\Throwable) {
            // ignore logging failures
        }
    }

    /**
     * CLI/Jobs safe scope: apply overrides, run fn, then restore.
     */
    public function runWithConfigOverrides(callable $fn): mixed
    {
        $snapshot = $this->getSnapshotOrNull();
        if (!$snapshot) {
            return $fn();
        }

        $original = [];
        $apply = $snapshot['apply_map'] ?? [];

        foreach ($apply as $path => $value) {
            $original[$path] = config($path);
        }

        try {
            foreach ($apply as $path => $value) {
                config()->set($path, $value);
            }

            return $fn();
        } finally {
            foreach ($original as $k => $v) {
                config()->set($k, $v);
            }
        }
    }
}
