<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\ScanHost;
use Illuminate\Support\Collection;

class ScanResultPresenter
{
    /**
     * @return array{
     *     onlineHosts: Collection<int, ScanHost>,
     *     stats: array{total: int, active: int, apache: int, mariadb: int, openssh: int},
     *     hostBadges: array<int, list<string>>,
     *     hostCves: array<int, Collection>
     * }
     */
    public function present(Scan $scan): array
    {
        $scan->loadMissing(['hosts.services.cveFindings', 'cveFindings']);

        $onlineHosts = $scan->hosts->where('is_up', true)->values();

        $stats = [
            'total' => (int) $scan->total_hosts,
            'active' => (int) $scan->active_hosts,
            'apache' => 0,
            'mariadb' => 0,
            'openssh' => 0,
        ];

        $hostBadges = [];
        $hostCves = [];

        foreach ($onlineHosts as $host) {
            $labels = $this->serviceLabelsForHost($host);
            $hostBadges[$host->id] = $labels;

            if (in_array('Apache', $labels, true)) {
                $stats['apache']++;
            }
            if (in_array('MariaDB', $labels, true)) {
                $stats['mariadb']++;
            }
            if (in_array('OpenSSH', $labels, true)) {
                $stats['openssh']++;
            }

            $grouped = [];
            foreach ($host->services as $service) {
                $name = trim((string) ($service->product ?: $service->name ?: 'Bilinmeyen'));
                $findings = $service->cveFindings;
                if ($findings->isEmpty()) {
                    $findings = $scan->cveFindings->where('scan_service_id', $service->id)->values();
                }
                if ($findings->isEmpty()) {
                    $findings = $scan->cveFindings
                        ->filter(fn ($cve) => strcasecmp((string) $cve->service_name, (string) $service->name) === 0
                            || strcasecmp((string) $cve->service_name, (string) $service->product) === 0
                            || strcasecmp((string) $cve->service_name, $name) === 0)
                        ->values();
                }

                if (! isset($grouped[$name])) {
                    $grouped[$name] = collect();
                }
                $grouped[$name] = $grouped[$name]->merge($findings)->unique('id')->values();
            }

            foreach ($labels as $label) {
                if (! isset($grouped[$label])) {
                    $matched = $scan->cveFindings
                        ->filter(fn ($cve) => str_contains(strtolower((string) $cve->service_name), strtolower($label)))
                        ->values();
                    $grouped[$label] = $matched;
                }
            }

            if ($grouped === [] && $scan->cveFindings->isNotEmpty() && $onlineHosts->count() === 1) {
                $grouped = $scan->cveFindings
                    ->groupBy(fn ($cve) => $cve->service_name ?: 'Diğer')
                    ->all();
            }

            ksort($grouped);
            $hostCves[$host->id] = collect($grouped);
        }

        return compact('onlineHosts', 'stats', 'hostBadges', 'hostCves');
    }

    /**
     * @return list<string>
     */
    public function serviceLabelsForHost(ScanHost $host): array
    {
        $labels = [];
        $blob = strtolower(($host->raw_output ?? '').' '.$host->services->map(
            fn ($s) => trim($s->name.' '.$s->product.' '.$s->version)
        )->implode(' '));

        foreach ([
            'Apache' => 'apache',
            'MariaDB' => 'mariadb',
            'MySQL' => 'mysql',
            'OpenSSH' => 'openssh',
            'Nginx' => 'nginx',
            'FTP' => 'ftp',
        ] as $label => $needle) {
            if (str_contains($blob, $needle)) {
                $labels[$label] = true;
            }
        }

        if ($labels === []) {
            foreach ($host->services as $service) {
                $name = trim((string) ($service->product ?: $service->name));
                if ($name !== '') {
                    $labels[ucfirst($name)] = true;
                }
            }
        }

        return array_keys($labels);
    }
}
