<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePoliciesRequest;
use App\Http\Resources\PolicyResource;
use App\Services\Policy\PolicyService;
use Illuminate\Http\Request;

class AdminPolicyController extends Controller
{
    use \App\Traits\HasApiResponse;

    public function __construct(private readonly PolicyService $policies) {}

    public function index(Request $request)
    {
        $result = $this->policies->listPolicies();
        $version = $result['version'];

        // ETag opcional (W/ weak)
        $etag = 'W/"policy-v' . $version . '"';

        $items = array_values($result['items']);
        $grouped = [];

        foreach ($items as $item) {
            $group = $item['meta']['group'] ?? 'general';
            $grouped[$group][] = (new PolicyResource($item))->resolve();
        }

        return $this->success([
            'policy_version' => $version,
            'degraded' => (bool) ($result['degraded'] ?? false),
            'groups' => $grouped,
        ])->withHeaders(['ETag' => $etag]);
    }

    public function show(string $key)
    {
        $p = $this->policies->getPolicy($key);

        // redact sensitive in API output
        $meta = $p['meta'];
        $isSensitive = (bool) ($meta['sensitive'] ?? false);

        return $this->success([
            'policy_version' => $p['version'],
            'policy' => (new PolicyResource([
                'key' => $key,
                'value' => $isSensitive ? null : $p['value'],
                'meta' => array_merge($meta, [
                    'redacted' => $isSensitive,
                    'is_set' => ($meta['source'] ?? null) === 'db',
                ]),
            ]))->resolve(),
        ]);
    }

    public function update(UpdatePoliciesRequest $request)
    {
        $updates = $request->validated()['policies'];

        try {
            $result = $this->policies->updatePolicies($updates, $request->user());
        } catch (\InvalidArgumentException $e) {
            // per-key “validation-like” errors => VALIDATION_ERROR (422)
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return $this->success([
            'policy_version' => $result['version'],
            'degraded' => (bool) ($result['degraded'] ?? false),
            'items' => array_values($result['items']),
        ]);
    }
}
