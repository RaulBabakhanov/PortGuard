<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\Scan;
use App\Models\ScanReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ScanPdfStore
{
    private const DISK_DIR = 'scan-reports';

    public function __construct(
        private readonly ScanResultPresenter $presenter,
    ) {}

    public function generateAndStore(Scan $scan, ?AdminUser $admin = null, bool $force = false): ScanReport
    {
        if (! $force) {
            $existing = $this->metaForScan($scan->id);
            if ($existing) {
                return $existing;
            }
        }

        $previous = $this->metaForScan($scan->id);

        try {
            $binary = $this->renderPdfBinary($scan);
        } catch (Throwable $e) {
            report($e);
            throw new RuntimeException('PDF oluşturulamadı.');
        }

        $byteSize = strlen($binary);
        $sha256 = hash('sha256', $binary);
        $hmac = hash_hmac('sha256', $binary, (string) config('app.key'));
        $token = Str::random(64);
        $filename = 'portguard-tarama-'.$scan->id.'-'.substr($sha256, 0, 8).'.pdf';
        $relativePath = self::DISK_DIR.'/'.$scan->id.'-'.$sha256.'.enc';

        try {
            $encrypted = $this->encryptBinary($binary);
            unset($binary);
            $this->writeEncryptedFile($relativePath, $encrypted);
            unset($encrypted);
        } catch (Throwable $e) {
            report($e);
            throw new RuntimeException('PDF şifreli olarak diske yazılamadı.');
        }

        DB::table('scan_reports')->updateOrInsert(
            ['scan_id' => $scan->id],
            [
                'download_token' => $token,
                'filename' => $filename,
                'mime_type' => 'application/pdf',
                'byte_size' => $byteSize,
                'content_sha256' => $sha256,
                'content_hmac' => $hmac,
                'storage_path' => $relativePath,
                'content_encrypted' => null,
                'created_by_admin_id' => $admin?->id,
                'created_at' => $previous?->created_at ?? now(),
                'updated_at' => now(),
            ]
        );

        if ($previous?->storage_path && $previous->storage_path !== $relativePath) {
            $this->deleteEncryptedFile($previous->storage_path);
        }

        $report = $this->metaForScan($scan->id);
        if (! $report) {
            throw new RuntimeException('PDF kaydı oluşturulamadı.');
        }

        return $report;
    }

    public function decryptVerified(ScanReport $report): string
    {
        if (filled($report->storage_path)) {
            $encrypted = $this->readEncryptedFile((string) $report->storage_path);
        } else {
            $encrypted = DB::table('scan_reports')
                ->where('id', $report->id)
                ->value('content_encrypted');
        }

        if (! is_string($encrypted) || $encrypted === '') {
            throw new RuntimeException('PDF kaydı bulunamadı.');
        }

        try {
            $binary = $this->decryptBinary($encrypted);
        } catch (Throwable $e) {
            report($e);
            throw new RuntimeException('PDF şifresi çözülemedi. Kayıt bozuk olabilir.');
        } finally {
            unset($encrypted);
        }

        if ($binary === '') {
            throw new RuntimeException('PDF içeriği boş.');
        }

        $sha256 = hash('sha256', $binary);
        $hmac = hash_hmac('sha256', $binary, (string) config('app.key'));

        if (! hash_equals((string) $report->content_sha256, $sha256)) {
            throw new RuntimeException('PDF bütünlük hatası (SHA-256 uyuşmuyor).');
        }

        if (! hash_equals((string) $report->content_hmac, $hmac)) {
            throw new RuntimeException('PDF bütünlük hatası (HMAC uyuşmuyor).');
        }

        return $binary;
    }

    private function metaForScan(int $scanId): ?ScanReport
    {
        return ScanReport::query()
            ->where('scan_id', $scanId)
            ->first([
                'id',
                'scan_id',
                'download_token',
                'filename',
                'mime_type',
                'byte_size',
                'content_sha256',
                'content_hmac',
                'storage_path',
                'created_by_admin_id',
                'created_at',
                'updated_at',
            ]);
    }

    private function renderPdfBinary(Scan $scan): string
    {
        $scan->loadMissing(['user:id,name,email']);
        $data = $this->presenter->present($scan);

        return Pdf::loadView('admin.scans.pdf', [
            'scan' => $scan,
            'onlineHosts' => $data['onlineHosts'],
            'stats' => $data['stats'],
            'hostBadges' => $data['hostBadges'],
            'hostCves' => $data['hostCves'],
        ])->setPaper('a4')->output();
    }

    private function encryptBinary(string $binary): string
    {
        $key = $this->encryptionKey();
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($binary, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($cipher === false) {
            throw new RuntimeException('AES şifreleme başarısız.');
        }

        return base64_encode($iv.$cipher);
    }

    private function decryptBinary(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw !== false && strlen($raw) >= 17) {
            $iv = substr($raw, 0, 16);
            $cipher = substr($raw, 16);
            $plain = openssl_decrypt($cipher, 'AES-256-CBC', $this->encryptionKey(), OPENSSL_RAW_DATA, $iv);
            if ($plain !== false) {
                return $plain;
            }
        }

        // Eski Laravel Crypt blob (DB'de kalan kayıtlar)
        return Crypt::decrypt($payload);
    }

    private function encryptionKey(): string
    {
        $appKey = (string) config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                $appKey = $decoded;
            }
        }

        return hash('sha256', $appKey, true);
    }

    private function absolutePath(string $relativePath): string
    {
        return storage_path('app/private/'.$relativePath);
    }

    private function writeEncryptedFile(string $relativePath, string $encrypted): void
    {
        $path = $this->absolutePath($relativePath);
        File::ensureDirectoryExists(dirname($path));
        if (File::put($path, $encrypted) === false) {
            throw new RuntimeException('Dosya yazılamadı.');
        }
    }

    private function readEncryptedFile(string $relativePath): string
    {
        $path = $this->absolutePath($relativePath);
        if (! File::isFile($path)) {
            throw new RuntimeException('Şifreli PDF dosyası yok.');
        }

        $content = File::get($path);
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Şifreli PDF dosyası boş.');
        }

        return $content;
    }

    private function deleteEncryptedFile(string $relativePath): void
    {
        $path = $this->absolutePath($relativePath);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }
}
