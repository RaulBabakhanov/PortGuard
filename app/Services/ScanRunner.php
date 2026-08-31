<?php

namespace App\Services;

use App\Models\CveFinding;
use App\Models\Scan;
use App\Models\ScanHost;
use App\Models\ScanService;
use App\Models\UserNotification;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ScanRunner
{
    public function __construct(
        private TargetResolver $targets,
        private NmapScanner $nmap,
        private CveLookupService $cves,
        private ActivityLogger $logger,
    ) {}

    public function run(Scan $scan): Scan
    {
        $scan->refresh();
        $settings = UserSetting::query()->firstOrCreate(
            ['user_id' => $scan->user_id],
            []
        );

        $scan->update([
            'status' => 'running',
            'started_at' => now(),
            'error_message' => null,
        ]);

        $this->logger->log(
            'scan.started',
            "Tarama başlatıldı (#{$scan->id})",
            $scan,
            ['ports' => $scan->ports],
            $scan->user
        );

        try {
            $nmapStatus = $this->nmap->status();
            // available her zaman true (nmap veya PHP yedek); mode bilgisini logla
            $this->logger->log(
                'scan.engine',
                'Tarama motoru: '.(($nmapStatus['mode'] ?? 'php') === 'nmap' ? 'Nmap' : 'PHP soket'),
                $scan,
                $nmapStatus,
                $scan->user
            );

            $hosts = $this->targets->resolve(
                $scan->ip,
                $scan->cidr,
                $scan->start_ip,
                $scan->end_ip,
                $settings->max_hosts_per_scan ?: TargetResolver::MAX_HOSTS
            );

            $scan->update(['total_hosts' => count($hosts)]);

            $active = 0;
            $serviceCount = 0;
            $cveCount = 0;
            $uniqueServices = [];

            foreach ($hosts as $hostIp) {
                $result = $this->nmap->scanHost($hostIp, $scan->ports);
                $output = $result['output'] ?? '';
                $isUp = $result['success'] && $this->nmap->isHostUp($output);

                /** @var ScanHost $host */
                $host = ScanHost::query()->create([
                    'scan_id' => $scan->id,
                    'ip' => $hostIp,
                    'is_up' => $isUp,
                    'raw_output' => $output !== '' ? $output : ($result['error'] ?? null),
                ]);

                if (! $isUp) {
                    continue;
                }

                $active++;
                $services = $this->nmap->parseServices($output);

                foreach ($services as $service) {
                    $row = ScanService::query()->create([
                        'scan_host_id' => $host->id,
                        'scan_id' => $scan->id,
                        'name' => $service['name'],
                        'product' => $service['product'],
                        'version' => $service['version'],
                        'port' => $service['port'],
                        'protocol' => $service['protocol'],
                        'raw_line' => $service['raw_line'],
                    ]);
                    $serviceCount++;
                    $uniqueServices[$service['name']] = $row;
                }
            }

            foreach ($uniqueServices as $serviceName => $serviceRow) {
                $keyword = trim($serviceName.' '.($serviceRow->version ?? ''));
                foreach ($this->cves->search($keyword !== '' ? $keyword : $serviceName, 3) as $cve) {
                    CveFinding::query()->create([
                        'user_id' => $scan->user_id,
                        'scan_id' => $scan->id,
                        'scan_service_id' => $serviceRow->id,
                        'service_name' => $serviceName,
                        'cve_id' => $cve['cve_id'],
                        'description' => $cve['description'],
                        'severity' => $cve['severity'],
                        'raw' => $cve['raw'],
                    ]);
                    $cveCount++;
                }
            }

            $scan->update([
                'status' => 'completed',
                'active_hosts' => $active,
                'service_count' => $serviceCount,
                'cve_count' => $cveCount,
                'finished_at' => now(),
            ]);

            if ($settings->notify_on_scan_complete) {
                UserNotification::query()->create([
                    'user_id' => $scan->user_id,
                    'type' => 'scan.completed',
                    'title' => 'Tarama tamamlandı',
                    'body' => "#{$scan->id} taraması bitti. Aktif host: {$active}, CVE: {$cveCount}",
                    'data' => ['scan_id' => $scan->id],
                ]);
            }

            if ($cveCount > 0 && $settings->notify_on_cve_found) {
                UserNotification::query()->create([
                    'user_id' => $scan->user_id,
                    'type' => 'cve.found',
                    'title' => 'CVE bulgusu',
                    'body' => "#{$scan->id} taramasında {$cveCount} CVE kaydı bulundu.",
                    'data' => ['scan_id' => $scan->id, 'cve_count' => $cveCount],
                ]);
            }

            $this->logger->log(
                'scan.completed',
                "Tarama tamamlandı (#{$scan->id})",
                $scan,
                [
                    'active_hosts' => $active,
                    'service_count' => $serviceCount,
                    'cve_count' => $cveCount,
                ],
                $scan->user
            );

            $this->bustUserCaches((int) $scan->user_id);
        } catch (Throwable $e) {
            $scan->update([
                'status' => 'failed',
                'error_message' => mb_substr(
                    @iconv('UTF-8', 'UTF-8//IGNORE', $e->getMessage()) ?: 'Tarama hatası',
                    0,
                    2000
                ),
                'finished_at' => now(),
            ]);

            UserNotification::query()->create([
                'user_id' => $scan->user_id,
                'type' => 'scan.failed',
                'title' => 'Tarama başarısız',
                'body' => "#{$scan->id}: ".$e->getMessage(),
                'data' => ['scan_id' => $scan->id],
            ]);

            $this->logger->log(
                'scan.failed',
                "Tarama başarısız (#{$scan->id})",
                $scan,
                ['error' => $e->getMessage()],
                $scan->user
            );

            $this->bustUserCaches((int) $scan->user_id);
        }

        return $scan->refresh();
    }

    private function bustUserCaches(int $userId): void
    {
        Cache::forget('pg.unread.'.$userId);
        Cache::forget('pg.dash.stats.'.$userId);
        Cache::forget('pg.cve.stats.'.$userId);
        Cache::forget('pg.cve.severities.'.$userId);
        Cache::forget('pg.cve.services.'.$userId);
    }
}
