<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserReservationControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_ListsUsersReservations()
    {
        $user = User::factory()->create();

        $reservation = Reservation::factory()->for($user)->create();
        Reservation::factory()->count(2)->create();

        $image = $reservation->office->images()->create([
            'path' => 'image1.jpg'
        ]);
        $reservation->office()->update([
            'featured_image_id' => $image->id
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/reservations');
        // $response->dump();
        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.office.featured_image.id', $image->id);

    }
}
