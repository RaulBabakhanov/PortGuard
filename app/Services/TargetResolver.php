<?php

namespace App\Services;

use InvalidArgumentException;

class TargetResolver
{
    public const MAX_HOSTS = 256;

    /**
     * @return list<string>
     */
    public function resolve(?string $ip, ?string $cidr, ?string $startIp, ?string $endIp, int $maxHosts = self::MAX_HOSTS): array
    {
        $ip = trim((string) $ip);
        $cidr = trim((string) $cidr);
        $startIp = trim((string) $startIp);
        $endIp = trim((string) $endIp);

        if ($cidr !== '') {
            return $this->fromCidr($cidr, $maxHosts);
        }

        if ($startIp !== '' && $endIp !== '') {
            return $this->fromRange($startIp, $endIp, $maxHosts);
        }

        if ($ip !== '') {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return [$ip];
            }

            // Hostname (örn. scanme.nmap.org) → A kaydı
            if (preg_match('/^[a-z0-9.-]+$/i', $ip) && str_contains($ip, '.')) {
                $resolved = gethostbyname($ip);
                if ($resolved !== $ip && filter_var($resolved, FILTER_VALIDATE_IP)) {
                    return [$resolved];
                }
            }

            throw new InvalidArgumentException('Geçersiz IP adresi veya çözülemeyen hostname.');
        }

        throw new InvalidArgumentException('Tek IP, CIDR veya IP aralığından birini doldurmalısınız.');
    }

    public function detectType(?string $ip, ?string $cidr, ?string $startIp, ?string $endIp): string
    {
        if (trim((string) $cidr) !== '') {
            return 'cidr';
        }

        if (trim((string) $startIp) !== '' && trim((string) $endIp) !== '') {
            return 'range';
        }

        return 'ip';
    }

    /**
     * @return list<string>
     */
    private function fromCidr(string $cidr, int $maxHosts): array
    {
        if (! str_contains($cidr, '/')) {
            throw new InvalidArgumentException('CIDR formatı geçersiz. Örn: 192.168.1.0/24');
        }

        [$network, $prefix] = explode('/', $cidr, 2);
        if (! filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || ! ctype_digit($prefix)) {
            throw new InvalidArgumentException('CIDR formatı geçersiz.');
        }

        $prefix = (int) $prefix;
        if ($prefix < 16 || $prefix > 32) {
            throw new InvalidArgumentException('Güvenlik için CIDR /16 ile /32 arasında olmalıdır.');
        }

        $hosts = [];
        $long = ip2long($network);
        if ($long === false) {
            throw new InvalidArgumentException('CIDR ağ adresi geçersiz.');
        }

        $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
        $networkLong = $long & $mask;
        $broadcast = $networkLong | (~$mask & 0xFFFFFFFF);
        $start = $networkLong + 1;
        $end = $broadcast - 1;

        if ($prefix >= 31) {
            $start = $networkLong;
            $end = $broadcast;
        }

        for ($i = $start; $i <= $end; $i++) {
            $hosts[] = long2ip($i);
            if (count($hosts) > $maxHosts) {
                throw new InvalidArgumentException("Tek seferde en fazla {$maxHosts} host taranabilir.");
            }
        }

        if ($hosts === []) {
            throw new InvalidArgumentException('CIDR içinde taranacak host bulunamadı.');
        }

        return $hosts;
    }

    /**
     * @return list<string>
     */
    private function fromRange(string $startIp, string $endIp, int $maxHosts): array
    {
        if (! filter_var($startIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || ! filter_var($endIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new InvalidArgumentException('IP aralığı için geçerli IPv4 girin.');
        }

        $start = ip2long($startIp);
        $end = ip2long($endIp);

        if ($start === false || $end === false) {
            throw new InvalidArgumentException('IP aralığı geçersiz.');
        }

        if ($start > $end) {
            throw new InvalidArgumentException('Başlangıç IP, bitiş IP’den büyük olamaz.');
        }

        if (($end - $start + 1) > $maxHosts) {
            throw new InvalidArgumentException("Tek seferde en fazla {$maxHosts} host taranabilir.");
        }

        $hosts = [];
        for ($i = $start; $i <= $end; $i++) {
            $hosts[] = long2ip($i);
        }

        return $hosts;
    }
}
