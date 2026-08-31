<?php

namespace App\Services;

use App\Models\AllowedNetwork;
use App\Models\Asset;
use App\Models\MunicipalitySetting;
use InvalidArgumentException;

class AllowedNetworkGuard
{
    /**
     * @param  list<string>  $hosts
     * @return list<string>  İzin verilmeyen IP'ler
     */
    public function unauthorizedHosts(array $hosts): array
    {
        $settings = MunicipalitySetting::current();
        if (! $settings->enforce_allowed_networks) {
            return [];
        }

        $networks = AllowedNetwork::query()
            ->where('is_active', true)
            ->pluck('cidr')
            ->all();

        if ($networks === []) {
            throw new InvalidArgumentException(
                'İzinli ağ envanteri boş. Tarama için yönetimin onaylı CIDR/IP tanımlaması gerekir.'
            );
        }

        $denied = [];
        foreach ($hosts as $host) {
            if (! $this->ipInAnyNetwork($host, $networks)) {
                $denied[] = $host;
            }
        }

        return $denied;
    }

    /**
     * @param  list<string>  $hosts
     */
    public function assertAllowed(array $hosts): void
    {
        $denied = $this->unauthorizedHosts($hosts);
        if ($denied !== []) {
            $sample = implode(', ', array_slice($denied, 0, 5));
            $more = count($denied) > 5 ? '…' : '';
            throw new InvalidArgumentException(
                "Hedef izinli ağ envanterinde değil: {$sample}{$more}. Yalnızca belediye onaylı IP/CIDR taranabilir."
            );
        }
    }

    /**
     * @param  list<string>  $hosts
     * @return array{requires: bool, reason: string|null, critical_assets: list<string>}
     */
    public function approvalRequirement(array $hosts): array
    {
        $settings = MunicipalitySetting::current();
        $reasons = [];
        $criticalNames = [];

        if (count($hosts) >= (int) $settings->approval_host_threshold) {
            $reasons[] = 'Geniş aralık ('.count($hosts).' host ≥ eşik '.$settings->approval_host_threshold.')';
        }

        if ($settings->require_approval_for_critical) {
            $assets = Asset::query()
                ->where('is_active', true)
                ->whereIn('criticality', ['high', 'critical'])
                ->whereIn('ip', $hosts)
                ->get(['name', 'ip', 'criticality']);

            foreach ($assets as $asset) {
                $criticalNames[] = $asset->name.' ('.$asset->ip.')';
            }

            if ($criticalNames !== []) {
                $reasons[] = 'Kritik varlık: '.implode(', ', array_slice($criticalNames, 0, 3));
            }
        }

        return [
            'requires' => $reasons !== [],
            'reason' => $reasons !== [] ? implode(' · ', $reasons) : null,
            'critical_assets' => $criticalNames,
        ];
    }

    /**
     * @param  list<string>  $networks
     */
    public function ipInAnyNetwork(string $ip, array $networks): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        foreach ($networks as $cidr) {
            if ($this->ipInCidr($ip, (string) $cidr)) {
                return true;
            }
        }

        return false;
    }

    public function ipInCidr(string $ip, string $cidr): bool
    {
        $cidr = trim($cidr);
        if ($cidr === '') {
            return false;
        }

        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        if (! filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || ! ctype_digit($mask)) {
            return false;
        }

        $mask = (int) $mask;
        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = $mask === 0 ? 0 : (~((1 << (32 - $mask)) - 1) & 0xFFFFFFFF);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
