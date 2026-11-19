<?php

namespace App\Libraries;

use Config\Services;
use RuntimeException;

class WhatsappGateway
{
    public function send(string $to, string $message, string $reference = ''): array
    {
        $config = (new IntegrationConfig())->get('whatsapp');
        $provider = strtolower((string) ($config['provider'] ?? ''));
        if ($provider === 'ngirimwa') {
            $key = (string) ($config['ngirimwa_api_key'] ?? '');
            if ($key === '') throw new RuntimeException('NGIRIMWA_API_KEY belum diatur.');
            $url = 'https://dash.ngirimwa.com/api/v1/messages/send';
            $headers = ['x-api-key' => $key, 'Content-Type' => 'application/json'];
            $body = ['to' => $to, 'message' => $message, 'ref_id' => $reference ?: null];
        } elseif ($provider === 'fonnte') {
            $key = (string) ($config['fonnte_token'] ?? '');
            if ($key === '') throw new RuntimeException('FONNTE_TOKEN belum diatur.');
            $url = 'https://api.fonnte.com/send';
            $headers = ['Authorization' => $key];
            $body = ['target' => $to, 'message' => $message];
            if (! str_contains($to, '@g.us')) $body['countryCode'] = '62';
        } else {
            throw new RuntimeException('WA_PROVIDER harus bernilai ngirimwa atau fonnte.');
        }

        try {
            $client = Services::curlrequest(['timeout' => 20, 'http_errors' => false, 'verify' => true]);
            $request = ['headers' => $headers];
            if ($provider === 'fonnte') {
                $request['multipart'] = [];
                foreach ($body as $name => $value) $request['multipart'][] = ['name' => $name, 'contents' => (string) $value];
            } else {
                $request['body'] = json_encode($body, JSON_THROW_ON_ERROR);
            }
            $response = $client->request('POST', $url, $request);
            $result = json_decode((string) $response->getBody(), true);
            if ((int) $response->getStatusCode() >= 400 || ! is_array($result) || ($result['success'] ?? $result['status'] ?? false) !== true) {
                throw new RuntimeException('Penyedia WhatsApp menolak pesan.');
            }
            return ['provider' => $provider, 'reference' => $result['data']['tracking_id'] ?? $result['requestid'] ?? null, 'response' => $result];
        } catch (\Throwable $e) {
            throw new RuntimeException('Pesan WhatsApp gagal dikirim: ' . $e->getMessage(), 0, $e);
        }
    }
}
