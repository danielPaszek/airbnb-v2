<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfficeImageControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_createImageForOffice()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        Sanctum::actingAs($user, ['*']);
        $response = $this->post('/api/offices/'. $office->id .'/images', [
            'image' => UploadedFile::fake()->image('image.jpg')
        ]);
        $response->assertStatus(201);
        Storage::disk('public')->assertExists(
            $response->json('data.path')
        );
    }
}
