<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class CveLookupService
{
    /**
     * @return list<array{cve_id: string, description: string, severity: ?string, raw: array}>
     */
    public function search(string $service, int $limit = 5): array
    {
        $cacheKey = 'cve:'.md5(strtolower($service).'|'.$limit);

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($service, $limit) {
            try {
                $response = Http::timeout(12)
                    ->acceptJson()
                    ->get('https://services.nvd.nist.gov/rest/json/cves/2.0', [
                        'keywordSearch' => $service,
                        'resultsPerPage' => $limit,
                    ]);

                if (! $response->successful()) {
                    return [];
                }

                $items = [];
                foreach ($response->json('vulnerabilities', []) as $row) {
                    $cve = $row['cve'] ?? [];
                    $id = $cve['id'] ?? null;
                    if (! $id) {
                        continue;
                    }

                    $description = '';
                    foreach ($cve['descriptions'] ?? [] as $desc) {
                        if (($desc['lang'] ?? '') === 'en') {
                            $description = $desc['value'] ?? '';
                            break;
                        }
                    }
                    if ($description === '' && ! empty($cve['descriptions'][0]['value'])) {
                        $description = $cve['descriptions'][0]['value'];
                    }

                    $items[] = [
                        'cve_id' => $id,
                        'description' => mb_substr($description, 0, 500),
                        'severity' => $this->extractSeverity($cve),
                        'raw' => $cve,
                    ];
                }

                return $items;
            } catch (Throwable) {
                return [];
            }
        });
    }

    private function extractSeverity(array $cve): ?string
    {
        $metrics = $cve['metrics'] ?? [];

        foreach (['cvssMetricV31', 'cvssMetricV30', 'cvssMetricV2'] as $key) {
            if (! empty($metrics[$key][0]['cvssData']['baseSeverity'])) {
                return strtoupper($metrics[$key][0]['cvssData']['baseSeverity']);
            }
            if (! empty($metrics[$key][0]['baseSeverity'])) {
                return strtoupper($metrics[$key][0]['baseSeverity']);
            }
        }

        return null;
    }
}
