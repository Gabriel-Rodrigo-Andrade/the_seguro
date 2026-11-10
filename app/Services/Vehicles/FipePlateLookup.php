<?php

namespace App\Services\Vehicles;

use App\Models\Vehicle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class FipePlateLookup
{
    public function lookup(string $plate): array
    {
        $mode = config('services.fipe.mode');
        if ($mode === 'mock') {
            $payload = ['plate' => $plate, 'brand' => 'Marca Simulada', 'model' => 'Modelo Demonstração', 'color' => 'Prata', 'year' => '2020'];
            if ($plate === 'DEF5678') {
                $payload = ['plate' => $plate, 'brand' => 'Marca Simulada'];
            }

            return $this->normalize($payload, $plate, 'mock');
        }
        $token = config('services.fipe.token');
        if ($mode !== 'live' || ! is_string($token) || trim($token) === '') {
            return $this->error(503, 'lookup_unavailable', 'Consulta indisponível. Preencha os dados manualmente.');
        }

        return $this->request($plate, $token);
    }

    private function request(string $plate, string $token): array
    {
        try {
            $response = Http::acceptJson()->withHeaders(['X-Subscription-Token' => $token])
                ->connectTimeout(max(1, min(10, (int) config('services.fipe.connect_timeout'))))
                ->timeout(max(1, min(20, (int) config('services.fipe.timeout'))))
                ->withoutRedirecting()->get('https://fipe.parallelum.com.br/api/v2/plates/'.$plate);
        } catch (ConnectionException) {
            return $this->error(503, 'lookup_unavailable', 'Não foi possível consultar agora. Preencha manualmente.');
        }
        $errors = [
            400 => [422, 'provider_invalid_plate', 'A placa foi rejeitada pelo fornecedor.'],
            404 => [404, 'vehicle_not_found', 'Veículo não encontrado. Preencha manualmente.'],
            429 => [429, 'provider_limit_reached', 'Cota do fornecedor atingida. Preencha manualmente.'],
        ];
        if (isset($errors[$response->status()])) {
            return $this->error(...$errors[$response->status()]);
        }
        if ($response->status() !== 200) {
            return $this->error(503, 'lookup_unavailable', 'Consulta indisponível. Preencha manualmente.');
        }
        $result = $this->normalize($response->json(), $plate, 'fipeapi');
        if ($result['status'] === 200) {
            $result['body']['quota'] = [
                'limit' => $this->quota($response->header('X-Plate-Quota-Limit')),
                'remaining' => $this->quota($response->header('X-Plate-Quota-Remaining')),
            ];
        }

        return $result;
    }

    private function normalize(mixed $payload, string $plate, string $source): array
    {
        if (! is_array($payload) || ! is_string($payload['plate'] ?? null) || Vehicle::normalizePlate($payload['plate']) !== $plate) {
            return $this->error(502, 'invalid_provider_response', 'A resposta da consulta é inválida. Preencha manualmente.');
        }
        $data = [
            'license_plate' => $plate,
            'brand' => $this->text($payload['brand'] ?? null, 100),
            'model' => $this->text($payload['model'] ?? null, 100),
            'color' => $this->text($payload['color'] ?? null, 50),
            'suggested_year' => $this->year($payload['year'] ?? null),
        ];
        $warnings = ['Revise as sugestões antes de salvar. Confirme manualmente o ano de fabricação e o ano-modelo.'];
        if (in_array(null, $data, true)) {
            $warnings[] = 'Resposta parcial: complete os campos ausentes manualmente.';
        }
        if ($source === 'mock') {
            array_unshift($warnings, 'Simulação: dados fictícios, sem consulta externa.');
        }

        return ['status' => 200, 'body' => ['source' => $source, 'data' => $data, 'warnings' => $warnings]];
    }

    private function text(mixed $value, int $limit): ?string
    {
        if (! is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $limit) {
            return null;
        }

        return trim($value);
    }

    private function year(mixed $value): ?int
    {
        if ((! is_string($value) && ! is_int($value)) || ! preg_match('/^[0-9]{4}$/', (string) $value)) {
            return null;
        }
        $year = (int) $value;
        if ($year < 1886 || $year > now('America/Sao_Paulo')->year + 1) {
            return null;
        }

        return $year;
    }

    private function quota(?string $value): ?int
    {
        if ($value === null || ! ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    private function error(int $status, string $code, string $message): array
    {
        return ['status' => $status, 'body' => compact('code', 'message')];
    }
}
