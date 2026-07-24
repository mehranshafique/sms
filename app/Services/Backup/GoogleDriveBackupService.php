<?php

namespace App\Services\Backup;

use App\Models\InstitutionSetting;
use App\Models\SchoolBackup;
use App\Models\SchoolBackupDriveAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleDriveBackupService
{
    public function clientId(): ?string
    {
        return InstitutionSetting::get(null, 'google_drive_client_id')
            ?: config('services.google.client_id')
            ?: null;
    }

    public function clientSecret(): ?string
    {
        $stored = InstitutionSetting::get(null, 'google_drive_client_secret');
        if ($stored) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Throwable) {
                return $stored;
            }
        }

        return config('services.google.client_secret') ?: null;
    }

    public function redirectUri(): string
    {
        return InstitutionSetting::get(null, 'google_drive_redirect_uri')
            ?: config('services.google.redirect')
            ?: (rtrim((string) config('app.url'), '/') . '/school-backups/drive/callback');
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId()) && filled($this->clientSecret()) && filled($this->redirectUri());
    }

    /**
     * Platform-level OAuth app credentials (one Digitex app).
     * Each school admin still connects their own Google user account.
     */
    public function savePlatformOAuth(?string $clientId, ?string $clientSecret, ?string $redirectUri): void
    {
        if ($clientId !== null && $clientId !== '') {
            InstitutionSetting::set(null, 'google_drive_client_id', trim($clientId), 'backup');
        }
        if ($clientSecret !== null && $clientSecret !== '') {
            InstitutionSetting::set(null, 'google_drive_client_secret', Crypt::encryptString($clientSecret), 'backup');
        }
        if ($redirectUri !== null && $redirectUri !== '') {
            InstitutionSetting::set(null, 'google_drive_redirect_uri', trim($redirectUri), 'backup');
        }
    }

    public function authorizationUrl(int $institutionId): string
    {
        $state = encrypt([
            'institution_id' => $institutionId,
            'nonce' => Str::random(16),
        ]);

        $query = http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/userinfo.email',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    public function handleCallback(string $code, string $state): SchoolBackupDriveAccount
    {
        $payload = decrypt($state);
        $institutionId = (int) ($payload['institution_id'] ?? 0);
        if (!$institutionId) {
            throw new \InvalidArgumentException('Invalid OAuth state.');
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse->successful()) {
            throw new \RuntimeException('Google OAuth token exchange failed: ' . $tokenResponse->body());
        }

        $tokens = $tokenResponse->json();
        $accessToken = $tokens['access_token'] ?? null;
        $refreshToken = $tokens['refresh_token'] ?? null;
        $expiresIn = (int) ($tokens['expires_in'] ?? 3600);

        $email = null;
        if ($accessToken) {
            $profile = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
            if ($profile->successful()) {
                $email = $profile->json('email');
            }
        }

        $account = SchoolBackupDriveAccount::query()->firstOrNew(['institution_id' => $institutionId]);
        if ($refreshToken) {
            $account->refresh_token = $refreshToken;
        }
        if ($accessToken) {
            $account->access_token = $accessToken;
            $account->token_expires_at = now()->addSeconds($expiresIn - 60);
        }
        if ($email) {
            $account->google_email = $email;
        }
        if (!$account->folder_name) {
            $account->folder_name = 'Digitex School Backups';
        }
        $account->save();

        return $account->fresh();
    }

    public function disconnect(int $institutionId): void
    {
        SchoolBackupDriveAccount::where('institution_id', $institutionId)->delete();
    }

    public function ensureAccessToken(SchoolBackupDriveAccount $account): string
    {
        if ($account->access_token && $account->token_expires_at && $account->token_expires_at->isFuture()) {
            return $account->access_token;
        }

        if (!$account->refresh_token) {
            throw new \RuntimeException('Google Drive is not connected.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Unable to refresh Google Drive token.');
        }

        $json = $response->json();
        $account->access_token = $json['access_token'];
        $account->token_expires_at = now()->addSeconds(((int) ($json['expires_in'] ?? 3600)) - 60);
        $account->save();

        return $account->access_token;
    }

    public function ensureBackupFolder(SchoolBackupDriveAccount $account): string
    {
        if ($account->folder_id) {
            return $account->folder_id;
        }

        $token = $this->ensureAccessToken($account);
        $response = Http::withToken($token)
            ->post('https://www.googleapis.com/drive/v3/files', [
                'name' => $account->folder_name ?: 'Digitex School Backups',
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Unable to create Google Drive folder.');
        }

        $folderId = $response->json('id');
        $account->folder_id = $folderId;
        $account->folder_name = $account->folder_name ?: 'Digitex School Backups';
        $account->save();

        return $folderId;
    }

    public function uploadBackup(SchoolBackup $backup): string
    {
        $account = SchoolBackupDriveAccount::where('institution_id', $backup->institution_id)->first();
        if (!$account || !$account->isConnected()) {
            throw new \RuntimeException('Google Drive is not connected for this school.');
        }

        if (!$backup->disk_path || !is_file(storage_path('app/' . $backup->disk_path))) {
            throw new \RuntimeException('Backup file is missing.');
        }

        $token = $this->ensureAccessToken($account);
        $folderId = $this->ensureBackupFolder($account);
        $absolute = storage_path('app/' . $backup->disk_path);
        $filename = basename($absolute);

        $metadata = json_encode([
            'name' => $filename,
            'parents' => [$folderId],
        ]);

        $boundary = 'digitex_' . Str::random(12);
        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . $metadata . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: application/zip\r\n\r\n"
            . file_get_contents($absolute) . "\r\n"
            . "--{$boundary}--";

        $response = Http::withToken($token)
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

        if (!$response->successful()) {
            throw new \RuntimeException('Google Drive upload failed: ' . $response->body());
        }

        $fileId = (string) $response->json('id');
        $backup->update(['drive_file_id' => $fileId]);

        return $fileId;
    }
}
