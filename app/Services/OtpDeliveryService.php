<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\IntegrationSetting;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpDeliveryService
{
    /** @param array<int, array{channel: string, recipient: string}> $recipients */
    public function issue(User $user, string $purpose, array $recipients, ?string $requestedValue = null): string
    {
        if ($recipients === []) {
            throw ValidationException::withMessages(['contact' => 'Tambahkan alamat email atau nomor telepon sebelum meminta kode.']);
        }

        OtpChallenge::query()->where('user_id', $user->getKey())->where('purpose', $purpose)
            ->whereNull('verified_at')->where('expires_at', '>', now())->update(['expires_at' => now()]);

        $recentCount = OtpChallenge::query()
            ->where('user_id', $user->getKey())
            ->where('created_at', '>=', now()->subMinutes(2))
            ->count();

        if ($recentCount >= 5) {
            throw ValidationException::withMessages(['contact' => 'Terlalu banyak permintaan kode. Coba kembali beberapa menit lagi.']);
        }

        $batch = (string) Str::uuid();
        $challenges = [];

        foreach ($recipients as $target) {
            $channel = $target['channel'];
            $recipient = trim($target['recipient']);

            if (! in_array($channel, ['email', 'whatsapp'], true) || blank($recipient)) {
                continue;
            }

            $code = (string) random_int(100000, 999999);
            $challenge = OtpChallenge::query()->create([
                'batch_uuid' => $batch,
                'user_id' => $user->getKey(),
                'purpose' => $purpose,
                'channel' => $channel,
                'recipient' => $recipient,
                'requested_value' => $requestedValue,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
            ]);

            $challenges[] = [$challenge, $code];
        }

        if ($challenges === []) {
            throw ValidationException::withMessages(['contact' => 'Tujuan verifikasi tidak valid.']);
        }

        try {
            foreach ($challenges as [$challenge, $code]) {
                $this->deliver($challenge->channel, $challenge->recipient, $code, $purpose);
            }
        } catch (\Throwable $exception) {
            OtpChallenge::query()->where('batch_uuid', $batch)->delete();

            throw ValidationException::withMessages(['contact' => 'Kode verifikasi gagal dikirim. Periksa pengaturan gateway dan coba lagi.']);
        }

        ActivityLog::query()->create([
            'user_id' => $user->getKey(),
            'branch_id' => $user->branch_id,
            'event' => 'otp_requested',
            'description' => 'Kode verifikasi diminta untuk '.$purpose,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $batch;
    }

    public function verify(User $user, OtpChallenge $challenge, string $code): bool
    {
        if ((int) $challenge->user_id !== (int) $user->getKey() || $challenge->verified_at !== null || $challenge->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'Kode verifikasi sudah tidak berlaku. Minta kode baru.']);
        }

        if ($challenge->attempts >= 5) {
            throw ValidationException::withMessages(['code' => 'Batas percobaan kode telah tercapai. Minta kode baru.']);
        }

        $challenge->increment('attempts');

        if (! Hash::check($code, $challenge->code_hash)) {
            throw ValidationException::withMessages(['code' => 'Kode verifikasi salah.']);
        }

        DB::transaction(function () use ($challenge): void {
            $challenge->forceFill(['verified_at' => now()])->save();
            $remaining = OtpChallenge::query()->where('batch_uuid', $challenge->batch_uuid)
                ->whereNull('verified_at')->where('expires_at', '>', now())->exists();

            if ($remaining) {
                return;
            }

            $batch = OtpChallenge::query()->where('batch_uuid', $challenge->batch_uuid)->get();
            $owner = User::query()->find($challenge->user_id);

            if (! $owner) {
                return;
            }

            if ($challenge->purpose === 'initial_email') {
                $owner->forceFill(['email_verified_at' => now()])->saveQuietly();
            } elseif (in_array($challenge->purpose, ['email_change', 'phone_change'], true)) {
                $target = $batch->first(fn (OtpChallenge $item): bool => $item->requested_value !== null)?->requested_value;

                if ($challenge->purpose === 'email_change' && $target) {
                    $owner->forceFill(['email' => $target, 'email_verified_at' => now()])->saveQuietly();
                } elseif ($challenge->purpose === 'phone_change' && $target) {
                    $owner->forceFill(['phone' => $target, 'phone_verified_at' => now()])->saveQuietly();
                }
            }

            ActivityLog::query()->create([
                'user_id' => $owner->getKey(),
                'branch_id' => $owner->branch_id,
                'event' => 'otp_verified',
                'description' => 'Verifikasi kontak berhasil: '.$challenge->purpose,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        });

        return OtpChallenge::query()->where('batch_uuid', $challenge->batch_uuid)->whereNull('verified_at')->doesntExist();
    }

    private function deliver(string $channel, string $recipient, string $code, string $purpose): void
    {
        $message = "Kode verifikasi mynet Anda: {$code}. Kode berlaku 10 menit. Jangan bagikan kode ini kepada siapa pun.";

        if ($channel === 'email') {
            $smtp = IntegrationSetting::query()->where('section', 'smtp')->first()?->encrypted_value ?? [];

            if (is_array($smtp) && filled($smtp['host'] ?? null)) {
                $mailer = Mail::build([
                    'transport' => 'smtp',
                    'host' => $smtp['host'],
                    'port' => (int) ($smtp['port'] ?? 587),
                    'scheme' => ($smtp['encryption'] ?? null) === 'ssl' ? 'smtps' : 'smtp',
                    'username' => $smtp['username'] ?? null,
                    'password' => $smtp['password'] ?? null,
                    'timeout' => 10,
                    'local_domain' => parse_url(config('app.url'), PHP_URL_HOST),
                ]);
                $mailer->raw($message, function ($mail) use ($recipient, $smtp): void {
                    $mail->to($recipient)
                        ->from($smtp['from_address'] ?? config('mail.from.address'), $smtp['from_name'] ?? 'mynet')
                        ->subject('Kode verifikasi mynet');
                });

                return;
            }

            Mail::raw($message, fn ($mail) => $mail->to($recipient)->subject('Kode verifikasi mynet'));

            return;
        }

        $whatsapp = IntegrationSetting::query()->where('section', 'whatsapp')->first()?->encrypted_value ?? [];

        if (! is_array($whatsapp) || blank($whatsapp['endpoint'] ?? null) || blank($whatsapp['token'] ?? null)) {
            throw new \RuntimeException('WhatsApp gateway has not been configured.');
        }

        Http::withToken($whatsapp['token'])
            ->timeout(10)
            ->post($whatsapp['endpoint'], [
                'to' => $recipient,
                'sender' => $whatsapp['sender'] ?? null,
                'message' => $message,
                'purpose' => $purpose,
            ])
            ->throw();
    }
}
