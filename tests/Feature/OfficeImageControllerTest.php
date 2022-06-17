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
    public function test_deleteImageForOffice()
    {
        Storage::fake('public');
        Storage::disk('public')->put('office_image.jpg', 'empty');
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();
        $office->images()->create([
            'path' => 'image.jpg'
        ]);
        $image = $office->images()->create([
            'path' => 'office_image.jpg'
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->deleteJson("/api/offices/{$office->id}/images/{$image->id}");
        $response->assertStatus(200);
        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing('office_image.jpg');

    }
}
