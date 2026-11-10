<?php

namespace Tests\Feature;

use App\Actions\Proposals\ReviewProposal;
use App\Enums\UserRole;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ProposalReviewSeeder;
use Database\Seeders\ProposalSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProposalReviewTest extends TestCase
{
    use RefreshDatabase;

    private Proposal $proposal;

    private User $broker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, VehicleSeeder::class, ServiceSeeder::class, PlanSeeder::class, ProposalSeeder::class]);
        $this->proposal = Proposal::where('status', 'submitted')->firstOrFail();
        $this->broker = User::where('role', 'corretor')->firstOrFail();
        $this->actingAs($this->broker);
    }

    private function url(): string
    {
        return '/proposals/'.$this->proposal->id.'/review';
    }

    public function test_queue_only_shows_submitted_and_approval_records_server_actor_and_date(): void
    {
        $before = $this->proposal->only(['quoted_amount', 'services_snapshot', 'submitted_at']);
        $this->get('/proposal-reviews')->assertOk()->assertSee('DEF5678')->assertDontSee('ABC1D23');
        $this->get('/proposals/'.$this->proposal->id)->assertOk()->assertSee('Registrar decisão');
        $this->patch($this->url(), ['decision' => 'approved', 'reviewed_by' => -1, 'reviewed_at' => '2000-01-01', 'quoted_amount' => '1.00'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('proposals', ['id' => $this->proposal->id, 'status' => 'approved', 'reviewed_by' => $this->broker->id, 'review_notes' => null]);
        $this->assertNotNull($this->proposal->fresh()->reviewed_at);
        $this->assertTrue($this->proposal->fresh()->reviewed_at->isToday());
        $this->assertEquals($before, $this->proposal->fresh()->only(array_keys($before)));
        $this->get('/proposal-reviews')->assertOk()->assertDontSee('DEF5678');
    }

    public function test_rejection_requires_notes_and_customer_sees_result(): void
    {
        foreach (['', '   '] as $notes) {
            $this->patch($this->url(), ['decision' => 'rejected', 'review_notes' => $notes])->assertSessionHasErrors('review_notes');
        }
        $this->assertDatabaseHas('proposals', ['id' => $this->proposal->id, 'status' => 'submitted', 'reviewed_by' => null]);
        $this->patch($this->url(), ['decision' => 'rejected', 'review_notes' => 'Documento ausente'])->assertSessionHasNoErrors();
        $this->actingAs($this->proposal->customer)->get('/proposals/'.$this->proposal->id)->assertOk()->assertSee('Recusada')->assertSee('Documento ausente')->assertDontSee('Registrar decisão');
        $this->actingAs(User::factory()->create())->get('/proposals/'.$this->proposal->id)->assertForbidden();
    }

    public function test_customer_inactive_and_guest_cannot_review(): void
    {
        $this->actingAs($this->proposal->customer)->get('/proposal-reviews')->assertForbidden();
        $this->patch($this->url(), ['decision' => 'approved'])->assertForbidden();
        $this->broker->forceFill(['is_active' => false])->save();
        $this->actingAs($this->broker)->patch($this->url(), ['decision' => 'approved'])->assertRedirect('/login');
        $this->get('/proposal-reviews')->assertRedirect('/login');
        $this->assertDatabaseHas('proposals', ['id' => $this->proposal->id, 'status' => 'submitted']);
    }

    public function test_admin_can_review_but_second_decision_cannot_replace_first(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $this->actingAs($admin)->patch($this->url(), ['decision' => 'approved', 'review_notes' => 'Conferido'])->assertSessionHasNoErrors();
        $before = $this->proposal->fresh()->getAttributes();
        $this->actingAs($this->broker)->patch($this->url(), ['decision' => 'rejected', 'review_notes' => 'Outra decisão'])->assertForbidden();
        $this->assertSame($before, $this->proposal->fresh()->getAttributes());
        // A Action reconsulta o estado; mesmo um objeto antigo não autoriza outra decisão.
        try {
            app(ReviewProposal::class)->handle($this->broker, $this->proposal, 'rejected', 'Objeto antigo');
            $this->fail('Deveria rejeitar a decisão repetida.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->proposal->fresh()->getAttributes());
        }
    }

    public function test_draft_invalid_decision_and_long_notes_are_rejected(): void
    {
        $draft = Proposal::where('status', 'draft')->firstOrFail();
        $this->patch('/proposals/'.$draft->id.'/review', ['decision' => 'approved'])->assertForbidden();
        $this->patch($this->url(), ['decision' => 'contracted'])->assertSessionHasErrors('decision');
        $this->patch($this->url(), ['decision' => 'rejected', 'review_notes' => str_repeat('x', 5001)])->assertSessionHasErrors('review_notes');
        $this->assertNull($this->proposal->fresh()->reviewed_at);
    }

    public function test_failed_write_rolls_back_analysis(): void
    {
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'update "proposals"')) {
                throw new \RuntimeException('Falha simulada');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->patch($this->url(), ['decision' => 'approved']);
            $this->fail('Deveria falhar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha simulada', $exception->getMessage());
        }
        $this->assertDatabaseHas('proposals', ['id' => $this->proposal->id, 'status' => 'submitted', 'reviewed_by' => null, 'reviewed_at' => null]);
    }

    public function test_review_seed_creates_dedicated_examples_without_modifying_e4(): void
    {
        $before = Proposal::orderBy('id')->get()->map->getAttributes()->all();
        $this->seed(ProposalReviewSeeder::class);
        $this->assertDatabaseCount('proposals', 4);
        $this->assertSame($before, Proposal::orderBy('id')->limit(2)->get()->map->getAttributes()->all());
        $this->assertSame(1, Proposal::where('status', 'approved')->count());
        $this->assertSame(1, Proposal::where('status', 'rejected')->whereNotNull('review_notes')->count());
        $all = Proposal::orderBy('id')->get()->map->getAttributes()->all();
        $this->seed(ProposalReviewSeeder::class);
        $this->assertSame($all, Proposal::orderBy('id')->get()->map->getAttributes()->all());
    }
}
