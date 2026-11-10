<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VehicleLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.fipe.mode' => 'mock', 'services.fipe.token' => null]);
    }

    private function customer(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function lookup(array $extra = [])
    {
        return $this->postJson('/vehicles/lookup', ['license_plate' => 'xyz-1a23', ...$extra]);
    }

    public function test_mock_is_labeled_and_does_not_persist_or_use_network(): void
    {
        Http::fake();
        $this->customer();
        $this->lookup()->assertOk()->assertJsonPath('source', 'mock')->assertJsonPath('data.license_plate', 'XYZ1A23')->assertJsonMissingPath('data.manufacture_year')->assertJsonMissingPath('data.model_year');
        $this->assertDatabaseCount('vehicles', 0);
        Http::assertNothingSent();
    }

    public function test_guest_broker_inactive_and_invalid_plate_never_reach_provider(): void
    {
        Http::fake();
        $this->lookup()->assertUnauthorized();
        $this->actingAs(User::factory()->create(['role' => 'corretor']));
        $this->lookup()->assertForbidden();
        $this->actingAs(User::factory()->create(['is_active' => false]));
        $this->lookup()->assertRedirect('/login');
        $this->customer();
        $this->lookup(['license_plate' => 'invalid'])->assertStatus(422)->assertJsonPath('code', 'invalid_plate');
        Http::assertNothingSent();
    }

    public function test_administrator_can_lookup_and_client_cannot_select_live_mode(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create(['role' => 'administrador']));
        $this->lookup(['mode' => 'live'])->assertOk()->assertJsonPath('source', 'mock');
        Http::assertNothingSent();
    }

    public function test_rate_limit_is_three_per_user_and_distinct_from_provider_limit(): void
    {
        Http::fake();
        $this->customer();
        for ($i = 0; $i < 3; $i++) {
            $this->lookup()->assertOk();
        }
        $this->lookup()->assertStatus(429)->assertJsonPath('code', 'lookup_rate_limited');
        $this->customer();
        $this->lookup()->assertOk();
        Http::assertNothingSent();
    }

    public static function unavailableModes(): array
    {
        return [['disabled', 'token'], ['invalid', 'token'], ['live', null], ['live', '']];
    }

    #[DataProvider('unavailableModes')]
    public function test_disabled_invalid_or_tokenless_modes_use_no_network(string $mode, ?string $token): void
    {
        config(['services.fipe.mode' => $mode, 'services.fipe.token' => $token]);
        Http::fake();
        $this->customer();
        $this->lookup()->assertStatus(503)->assertJsonPath('code', 'lookup_unavailable');
        Http::assertNothingSent();
    }

    public function test_live_fake_contract_filters_payload_and_preserves_zero_quota(): void
    {
        config(['services.fipe.mode' => 'live', 'services.fipe.token' => 'fake-secret']);
        $this->customer();
        Http::fake(['*' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/fipe/complete.json')), true), 200, ['X-Plate-Quota-Limit' => '10', 'X-Plate-Quota-Remaining' => '0'])]);
        $response = $this->lookup();
        $response->assertOk()->assertJsonPath('source', 'fipeapi')->assertJsonPath('data.suggested_year', 2020)->assertJsonPath('quota.remaining', 0)->assertJsonMissingPath('data.chassis')->assertJsonMissingPath('data.fipePrices')->assertJsonMissingPath('data.model_year')->assertDontSee('fake-secret');
        Http::assertSent(fn ($request) => $request->url() === 'https://fipe.parallelum.com.br/api/v2/plates/XYZ1A23' && $request->method() === 'GET' && $request->hasHeader('X-Subscription-Token', 'fake-secret'));
        Http::assertSentCount(1);
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_partial_response_keeps_missing_fields_null_and_quota_unknown(): void
    {
        config(['services.fipe.mode' => 'live', 'services.fipe.token' => 'fake']);
        $this->customer();
        Http::fake(['*' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/fipe/partial.json')), true))]);
        $this->lookup()->assertOk()->assertJsonPath('data.model', null)->assertJsonPath('data.color', null)->assertJsonPath('data.suggested_year', null)->assertJsonPath('quota.remaining', null);
    }

    public static function errors(): array
    {
        return [[400, 422, 'provider_invalid_plate'], [404, 404, 'vehicle_not_found'], [429, 429, 'provider_limit_reached'], [401, 503, 'lookup_unavailable'], [403, 503, 'lookup_unavailable'], [503, 503, 'lookup_unavailable'], [500, 503, 'lookup_unavailable'], [302, 503, 'lookup_unavailable']];
    }

    #[DataProvider('errors')]
    public function test_provider_failures_do_not_retry_or_leak_body(int $provider, int $status, string $code): void
    {
        config(['services.fipe.mode' => 'live', 'services.fipe.token' => 'fake']);
        $this->customer();
        Http::fake(['*' => Http::response(['secret' => 'do-not-expose'], $provider, ['Location' => 'https://example.test/redirect'])]);
        $this->lookup()->assertStatus($status)->assertJsonPath('code', $code)->assertDontSee('do-not-expose');
        Http::assertSentCount(1);
    }

    public static function invalidBodies(): array
    {
        return [['<html>invalid</html>'], [['plate' => 'ABC1234']], [['brand' => 'No plate']], [['plate' => ['XYZ1A23']]]];
    }

    #[DataProvider('invalidBodies')]
    public function test_invalid_provider_response_is_rejected(mixed $body): void
    {
        config(['services.fipe.mode' => 'live', 'services.fipe.token' => 'fake']);
        $this->customer();
        Http::fake(['*' => Http::response($body)]);
        $this->lookup()->assertStatus(502)->assertJsonPath('code', 'invalid_provider_response');
    }

    public function test_timeout_returns_manual_fallback_without_retry(): void
    {
        config(['services.fipe.mode' => 'live', 'services.fipe.token' => 'fake']);
        $this->customer();
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('sensitive details');
        });
        $this->lookup()->assertStatus(503)->assertJsonPath('code', 'lookup_unavailable')->assertDontSee('sensitive details');
        $this->assertSame(1, $attempts);
    }

    public function test_save_still_works_after_lookup_failure_without_another_request(): void
    {
        config(['services.fipe.mode' => 'live', 'services.fipe.token' => 'fake']);
        $this->customer();
        Http::fake(['*' => Http::response([], 503)]);
        $this->lookup()->assertStatus(503);
        $this->post('/vehicles', ['license_plate' => 'XYZ1A23', 'brand' => 'Manual', 'model' => 'Manual', 'manufacture_year' => 2020])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('vehicles', 1);
        Http::assertSentCount(1);
    }
}
