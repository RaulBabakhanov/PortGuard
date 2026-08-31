<?php

namespace App\Services;

class NmapScanner
{
    /**
     * @return array{available: bool, binary: ?string, version: ?string, reason: ?string, mode: string}
     */
    public function status(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('pg.nmap.status', 120, function () {
            return $this->detectStatus();
        });
    }

    /**
     * @return array{available: bool, binary: ?string, version: ?string, reason: ?string, mode: string}
     */
    private function detectStatus(): array
    {
        $binary = $this->resolveBinary();

        if ($binary !== null && $this->canRunExternalCommands()) {
            $result = $this->runCommand([$binary, '-V'], 8);
            $output = trim(($result['stdout'] ?? '').($result['stderr'] ?? ''));
            preg_match('/Nmap version\s+([0-9.]+)/i', $output, $m);
            $ok = ($result['ok'] ?? false) || str_contains($output, 'Nmap');

            if ($ok) {
                return [
                    'available' => true,
                    'binary' => $binary,
                    'version' => $m[1] ?? null,
                    'reason' => null,
                    'mode' => 'nmap',
                ];
            }
        }

        $reason = ! $this->canRunExternalCommands()
            ? 'Sunucuda proc_open/exec kapalı (Natro shared). PHP soket taraması kullanılacak. Özel ağ (192.168.x) hosting’den taralanamaz.'
            : ($binary
                ? 'Proje Nmap binary çalıştırılamadı; PHP soket taraması kullanılacak.'
                : 'Nmap binary bulunamadı; PHP soket taraması kullanılacak.');

        return [
            'available' => true,
            'binary' => $binary,
            'version' => null,
            'reason' => $reason,
            'mode' => 'php',
        ];
    }

    /**
     * @return array{success: bool, output: string, error: ?string}
     */
    public function scanHost(string $ip, string $ports, int $timeout = 90): array
    {
        $status = $this->status();
        $ports = $this->sanitizePorts($ports);

        // CVE-Scanner ile aynı çekirdek: nmap -sV -p <ports> <ip>
        if (($status['mode'] ?? '') === 'nmap' && $status['binary']) {
            $result = $this->runCommand([
                $status['binary'],
                '-sV',
                '-p',
                $ports,
                $ip,
            ], $timeout);

            $output = (string) ($result['stdout'] ?? '');
            $error = trim((string) ($result['stderr'] ?? ''));
            $combined = trim($output."\n".$error);

            if ($combined !== '' && (str_contains($combined, 'Nmap') || preg_match('/\d+\/\w+/', $combined))) {
                $clean = $this->toUtf8($output !== '' ? $output : $error);

                return [
                    'success' => true,
                    'output' => $clean,
                    'error' => null,
                ];
            }
        }

        return $this->phpScanHost($ip, $ports);
    }

    public function isHostUp(string $output): bool
    {
        if (str_contains($output, 'Host unreachable') || str_contains($output, 'erişilemez')) {
            return false;
        }

        return str_contains($output, 'Host is up')
            || preg_match('/^\d+\/\w+\s+open/mi', $output) === 1;
    }

    /**
     * CVE-Scanner servisleri_bul() ile aynı: satırda keyword varsa servis sayılır
     * (filtered satırlar dahil — lokal Nmap çıktısıyla uyum).
     *
     * @return list<array{name: string, product: ?string, version: ?string, port: ?int, protocol: ?string, raw_line: string}>
     */
    public function parseServices(string $output): array
    {
        $found = [];
        $keywords = [
            'Apache' => 'apache',
            'MariaDB' => 'mariadb',
            'MySQL' => 'mysql',
            'OpenSSH' => 'openssh',
            'Nginx' => 'nginx',
            'FTP' => 'ftp',
        ];

        foreach (preg_split("/\r\n|\n|\r/", $output) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $low = strtolower($line);
            $port = null;
            $protocol = null;

            if (preg_match('/^(\d+)\/(tcp|udp)\s+(\S+)/i', $line, $m)) {
                $port = (int) $m[1];
                $protocol = strtolower($m[2]);
            }

            foreach ($keywords as $name => $needle) {
                if (! str_contains($low, $needle)) {
                    continue;
                }

                // HTTP/1.1 gibi protokol sürümlerini ürün versiyonu sanma
                $version = null;
                if (preg_match('/(?:httpd|'.$needle.')[\/\s_-]+([\d]+\.[\d]+(?:\.[\d]+)*)/i', $line, $vm)) {
                    $candidate = $vm[1];
                    if (! preg_match('/^1\.[01]$/', $candidate)) {
                        $version = $candidate;
                    }
                }

                $found[] = [
                    'name' => $name,
                    'product' => $name,
                    'version' => $version,
                    'port' => $port,
                    'protocol' => $protocol,
                    'raw_line' => $line,
                ];
            }
        }

        // CVE-Scanner gibi servis adına göre tekilleştir (CVE araması ürün adı)
        $unique = [];
        foreach ($found as $item) {
            $unique[$item['name']] = $item;
        }

        return array_values($unique);
    }

    public function sanitizePorts(string $ports): string
    {
        $ports = trim($ports);
        if ($ports === '') {
            return '22,80,443,3306';
        }

        if (! preg_match('/^[0-9,\-\s]+$/', $ports)) {
            throw new \InvalidArgumentException('Port listesi yalnızca sayı, virgül ve tire içerebilir.');
        }

        return preg_replace('/\s+/', '', $ports) ?: '22,80,443,3306';
    }

    public function isPrivateOrReservedIp(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * @return array{success: bool, output: string, error: ?string}
     */
    private function phpScanHost(string $ip, string $ports): array
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'output' => '', 'error' => 'Geçersiz IP.'];
        }

        // Hosting'den LAN taranamaz — CVE-Scanner yerel ağda çalışıyordu
        if ($this->isPrivateOrReservedIp($ip)) {
            $msg = "Host unreachable (private IP).\n"
                ."Hedef {$ip} özel ağ adresidir. Natro/shared hosting bu IP'ye ulaşamaz.\n"
                ."CVE-Scanner bilgisayarında yerel Nmap ile çalıştığı için sonuçlar farklı çıkar.\n"
                ."Public hedef deneyin (örn. scanme.nmap.org) veya Nmap'in ağı gören bir VPS/yerel ortam kullanın.";

            return [
                'success' => true,
                'output' => $msg,
                'error' => null,
            ];
        }

        $portList = $this->expandPorts($ports);
        sort($portList, SORT_NUMERIC);

        $portNames = [
            21 => 'ftp',
            22 => 'ssh',
            25 => 'smtp',
            80 => 'http',
            443 => 'https',
            3306 => 'mysql',
            5432 => 'postgresql',
            8080 => 'http',
        ];

        $rows = [];
        $open = 0;

        foreach ($portList as $port) {
            $probe = $this->probePort($ip, $port);
            $svcHint = $portNames[$port] ?? 'unknown';
            $state = $probe['state'];

            if ($state !== 'open') {
                $rows[] = [
                    'port' => $port,
                    'line' => sprintf('%d/tcp %-8s %s', $port, $state, $svcHint),
                ];

                continue;
            }

            $open++;
            $banner = (string) ($probe['banner'] ?? '');
            $service = $this->guessService($port, $banner);
            $product = $service['name'];
            $ver = $service['version'] ? ' '.$service['version'] : '';

            if (str_contains(strtolower($product), 'apache') && in_array($port, [443, 8443], true)) {
                $line = sprintf('%d/tcp open  ssl/http Apache httpd%s', $port, $ver);
            } elseif (str_contains(strtolower($product), 'apache')) {
                $line = sprintf('%d/tcp open  http    Apache httpd%s', $port, $ver);
            } elseif (str_contains(strtolower($product), 'nginx')) {
                $line = sprintf('%d/tcp open  http    nginx%s', $port, $ver);
            } elseif (in_array($port, [443, 8443], true)) {
                $line = sprintf('%d/tcp open  ssl/http Apache httpd', $port);
            } elseif (str_contains(strtolower($product), 'ssh') || $port === 22) {
                $line = sprintf('%d/tcp open  ssh     OpenSSH%s', $port, $ver);
            } elseif (str_contains(strtolower($product), 'mysql') || str_contains(strtolower($product), 'mariadb')) {
                $line = sprintf('%d/tcp open  mysql   %s%s', $port, $product, $ver);
            } else {
                $line = sprintf('%d/tcp open  %s    %s%s', $port, $svcHint, $product, $ver);
            }

            $rows[] = ['port' => $port, 'line' => $line];
        }

        usort($rows, static fn (array $a, array $b): int => $a['port'] <=> $b['port']);
        $portLines = array_column($rows, 'line');

        if ($open === 0) {
            return [
                'success' => true,
                'output' => implode("\n", array_merge(
                    ['Host seems down or all probed ports are closed/filtered.', 'PORT     STATE SERVICE VERSION'],
                    $portLines
                )),
                'error' => null,
            ];
        }

        return [
            'success' => true,
            'output' => implode("\n", array_merge(
                ['Host is up.', 'PORT     STATE SERVICE VERSION'],
                $portLines
            )),
            'error' => null,
        ];
    }

    /**
     * @return list<int>
     */
    private function expandPorts(string $ports): array
    {
        $out = [];
        foreach (explode(',', $ports) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (str_contains($part, '-')) {
                [$a, $b] = array_map('intval', explode('-', $part, 2));
                if ($a > 0 && $b >= $a && ($b - $a) <= 200) {
                    for ($p = $a; $p <= $b; $p++) {
                        $out[] = $p;
                    }
                }
            } else {
                $p = (int) $part;
                if ($p > 0 && $p <= 65535) {
                    $out[] = $p;
                }
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array{state: 'open'|'closed'|'filtered', banner: ?string}
     */
    private function probePort(string $ip, int $port): array
    {
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($ip, $port, $errno, $errstr, 2.5);
        if (! is_resource($fp)) {
            // refused → closed; timeout/other → filtered (Nmap benzeri)
            $closedCodes = [111, 10061, 61];

            return [
                'state' => in_array((int) $errno, $closedCodes, true) ? 'closed' : 'filtered',
                'banner' => null,
            ];
        }

        stream_set_timeout($fp, 2);

        if (in_array($port, [80, 8080, 8000, 8888], true)) {
            fwrite($fp, "HEAD / HTTP/1.0\r\nHost: {$ip}\r\nConnection: close\r\n\r\n");
        } elseif (in_array($port, [443, 8443], true)) {
            fclose($fp);

            return ['state' => 'open', 'banner' => 'https'];
        }

        $banner = trim((string) @fread($fp, 1024));
        fclose($fp);

        $banner = preg_replace("/\r\n|\n|\r/", ' ', $banner) ?? '';

        return ['state' => 'open', 'banner' => mb_substr($banner, 0, 180)];
    }

    /**
     * @return array{name: string, version: ?string}
     */
    private function guessService(int $port, string $banner): array
    {
        $low = strtolower($banner);
        $map = [
            22 => 'OpenSSH',
            21 => 'FTP',
            80 => 'HTTP',
            443 => 'HTTPS',
            3306 => 'MySQL',
            8080 => 'HTTP',
        ];

        $name = $map[$port] ?? 'unknown';
        $version = null;

        if (str_contains($low, 'apache')) {
            $name = 'Apache';
            // Server: Apache/2.4.52 — HTTP/1.1'i versiyon sayma
            if (preg_match('/apache[\/\s]+([\d]+\.[\d]+(?:\.[\d]+)*)/i', $banner, $m)) {
                if (! preg_match('/^1\.[01]$/', $m[1])) {
                    $version = $m[1];
                }
            }
        } elseif (str_contains($low, 'nginx')) {
            $name = 'Nginx';
            if (preg_match('/nginx[\/\s]+([\d.]+)/i', $banner, $m)) {
                $version = $m[1];
            }
        } elseif (str_contains($low, 'openssh') || ($port === 22 && str_contains($low, 'ssh'))) {
            $name = 'OpenSSH';
            if (preg_match('/openssh[_\s]*([\d.]+)/i', $banner, $m)) {
                $version = $m[1];
            }
        } elseif (str_contains($low, 'mariadb')) {
            $name = 'MariaDB';
        } elseif (str_contains($low, 'mysql')) {
            $name = 'MySQL';
        } elseif ($banner === 'https' || $port === 443) {
            $name = 'Apache'; // lokal nmap gibi ssl/http Apache varsayımı
        } elseif (str_contains($low, 'server:')) {
            if (preg_match('/server:\s*([^\s\/]+)/i', $banner, $m)) {
                $name = $m[1];
            }
        }

        return ['name' => $name, 'version' => $version];
    }

    private function canRunExternalCommands(): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        foreach (['proc_open', 'exec', 'shell_exec'] as $fn) {
            if (function_exists($fn) && ! in_array($fn, $disabled, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $command
     * @return array{ok: bool, stdout: string, stderr: string}
     */
    private function runCommand(array $command, int $timeout = 60): array
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (function_exists('proc_open') && ! in_array('proc_open', $disabled, true)) {
            return $this->runWithProcOpen($command, $timeout);
        }

        $cmd = $this->buildShellCommand($command);
        $binary = $command[0] ?? null;
        if (is_string($binary) && $this->isBundledBinary($binary) && ($nmapDir = $this->bundledDataDir())) {
            $cmd = 'NMAPDIR='.escapeshellarg($nmapDir).' '.$cmd;
        }

        if (function_exists('exec') && ! in_array('exec', $disabled, true)) {
            $lines = [];
            $code = 1;
            @exec($cmd.' 2>&1', $lines, $code);

            return [
                'ok' => $code === 0,
                'stdout' => implode("\n", $lines),
                'stderr' => '',
            ];
        }

        if (function_exists('shell_exec') && ! in_array('shell_exec', $disabled, true)) {
            $output = (string) @shell_exec($cmd.' 2>&1');

            return [
                'ok' => $output !== '',
                'stdout' => $output,
                'stderr' => '',
            ];
        }

        return ['ok' => false, 'stdout' => '', 'stderr' => 'Komut çalıştırma yok.'];
    }

    /**
     * @param  list<string>  $command
     * @return array{ok: bool, stdout: string, stderr: string}
     */
    private function runWithProcOpen(array $command, int $timeout): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cwd = null;
        $env = null;
        $binary = $command[0] ?? null;

        if (is_string($binary) && is_file($binary)) {
            $cwd = dirname($binary);
        }

        // Yalnızca projedeki Linux static Nmap için NMAPDIR; Windows Nmap'i bozar
        if (is_string($binary) && $this->isBundledBinary($binary)) {
            $nmapDir = $this->bundledDataDir();
            if ($nmapDir) {
                $env = [
                    'NMAPDIR' => $nmapDir,
                    'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
                    'HOME' => getenv('HOME') ?: sys_get_temp_dir(),
                    'LANG' => 'C.UTF-8',
                    'LC_ALL' => 'C',
                ];
            }
        } else {
            // Windows Nmap: Türkçe locale ("Türkiye") latin1 bayt basabiliyor
            $env = [
                'LANG' => 'C',
                'LC_ALL' => 'C',
                'PATH' => getenv('PATH') ?: '',
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            ];
        }

        $process = @proc_open($command, $descriptors, $pipes, $cwd, $env, [
            'bypass_shell' => true,
        ]);

        if (! is_resource($process)) {
            return ['ok' => false, 'stdout' => '', 'stderr' => 'proc_open başarısız.'];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = time();

        while (true) {
            $status = proc_get_status($process);
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';

            if (! $status['running']) {
                break;
            }

            if ((time() - $start) >= $timeout) {
                proc_terminate($process);
                break;
            }

            usleep(100000);
        }

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        return [
            'ok' => $code === 0,
            'stdout' => $this->toUtf8($stdout),
            'stderr' => $this->toUtf8($stderr),
        ];
    }

    /**
     * Windows Nmap bazen CP1254/latin1 basıyor; MariaDB utf8mb4 reddediyor.
     */
    private function toUtf8(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (! mb_check_encoding($text, 'UTF-8')) {
            $converted = @mb_convert_encoding($text, 'UTF-8', 'Windows-1254, ISO-8859-9, Windows-1252, ISO-8859-1');
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        return is_string($clean) ? $clean : $text;
    }

    /**
     * @param  list<string>  $command
     */
    private function buildShellCommand(array $command): string
    {
        return implode(' ', array_map(static function (string $part): string {
            return escapeshellarg($part);
        }, $command));
    }

    private function resolveBinary(): ?string
    {
        $candidates = array_filter([
            env('NMAP_PATH'),
            base_path('bin/nmap/nmap'),
            base_path('bin/nmap/nmap.exe'),
            '/usr/bin/nmap',
            '/usr/local/bin/nmap',
            '/bin/nmap',
            'C:\\Program Files (x86)\\Nmap\\nmap.exe',
            'C:\\Program Files\\Nmap\\nmap.exe',
        ]);

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        if ($this->canRunExternalCommands()) {
            $result = $this->runCommand(
                PHP_OS_FAMILY === 'Windows' ? ['cmd', '/c', 'where', 'nmap'] : ['/bin/sh', '-c', 'command -v nmap'],
                5
            );
            $path = trim($result['stdout'] ?? '');
            if ($path !== '') {
                $first = preg_split("/\r\n|\n|\r/", $path)[0] ?? '';
                if ($first !== '' && is_file($first)) {
                    return $first;
                }
            }

            $probe = $this->runCommand(['nmap', '-V'], 5);
            if (($probe['ok'] ?? false) || str_contains(($probe['stdout'] ?? '').($probe['stderr'] ?? ''), 'Nmap')) {
                return 'nmap';
            }
        }

        return null;
    }

    private function bundledDataDir(): ?string
    {
        $dir = base_path('bin/nmap/data');

        return is_dir($dir) ? $dir : null;
    }

    private function isBundledBinary(string $binary): bool
    {
        $normalized = str_replace('\\', '/', $binary);
        $bundled = str_replace('\\', '/', base_path('bin/nmap/nmap'));
        $bundledExe = str_replace('\\', '/', base_path('bin/nmap/nmap.exe'));

        return $normalized === $bundled
            || $normalized === $bundledExe
            || str_ends_with($normalized, '/bin/nmap/nmap')
            || str_ends_with($normalized, '/bin/nmap/nmap.exe');
    }
}
