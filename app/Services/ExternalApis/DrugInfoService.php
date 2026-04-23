<?php

namespace App\Services\ExternalApis;

use Illuminate\Support\Str;

class DrugInfoService extends BaseApiService
{
    protected function apiConfigKey(): string
    {
        return 'open_fda';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDrugWarnings(string $drugName): ?array
    {
        $normalizedDrugName = trim($drugName);

        if ($normalizedDrugName === '') {
            return null;
        }

        foreach (['openfda.brand_name', 'openfda.generic_name'] as $field) {
            $payload = $this->get(
                endpoint: 'drug/label.json',
                query: [
                    'search' => sprintf('%s:"%s"', $field, addcslashes($normalizedDrugName, "\"\\")),
                    'limit' => 1,
                ],
                cacheKey: sprintf(
                    'external-api:open_fda:%s:%s',
                    $field,
                    sha1(Str::lower($normalizedDrugName)),
                ),
                cacheNull: true,
            );

            $result = data_get($payload, 'results.0');

            if (! is_array($result)) {
                continue;
            }

            $warnings = $this->normalizeWarnings($result);

            return [
                'matched_field' => $field,
                'drug_name' => $normalizedDrugName,
                'warnings' => $warnings === [] ? null : $warnings,
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalizeWarnings(array $result): array
    {
        $warningSections = [
            'boxed_warning',
            'warnings',
            'warnings_and_cautions',
            'do_not_use',
            'stop_use',
            'keep_out_of_reach_of_children',
        ];

        $normalizedWarnings = [];

        foreach ($warningSections as $section) {
            $value = $result[$section] ?? null;

            if (is_array($value) && $value !== []) {
                $normalizedWarnings[$section] = $value;
            }
        }

        return $normalizedWarnings;
    }

    protected function retryDelaySeconds(int $attempt): int
    {
        $rateLimitPerMinute = max((int) config('apis.open_fda.rate_limit_per_minute', 240), 1);
        $minimumDelaySeconds = max((int) ceil(60 / $rateLimitPerMinute), 1);

        return max($attempt, $minimumDelaySeconds);
    }
}
