<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_account_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Compte');
        $response->assertSee('Tournois');
        $response->assertSee('Clique sur le rond');
        $response->assertDontSee('Mes tournois');
    }

    public function test_user_can_view_account_tournaments_page(): void
    {
        $user = User::factory()->create();

        Tournament::create([
            'creator_id' => $user->id,
            'name' => 'Tournoi compte',
            'starts_on' => now()->addDay()->toDateString(),
            'courts_count' => 2,
            'round_duration_minutes' => 12,
            'status' => 'draft',
            'description' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.tournaments'));

        $response->assertOk();
        $response->assertSee('Mes tournois');
        $response->assertSee('Tournoi compte');
    }

    public function test_user_can_update_profile_with_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Ancien Nom',
            'email' => 'ancien@example.com',
        ]);

        $response = $this->actingAs($user)->patch(route('dashboard.profile.update'), [
            'name' => 'Nouveau Nom',
            'email' => 'nouveau@example.com',
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ]);

        $response->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('Nouveau Nom', $user->name);
        $this->assertSame('nouveau@example.com', $user->email);
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }
}
