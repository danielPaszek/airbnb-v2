<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OfficeControllerTest extends TestCase
{
    // use RefreshDatabase;

    public function test_ListsPaginatedOffices()
    {
        //Office::factory(3)->create();
        $response = $this->get('/api/offices');
        //dd($response->json());
        $response->assertStatus(200);
        // $response->assertJsonCount(3, 'data');
        $this->assertNotNull($response->json('data')[0]['id']);
        $this->assertNotNull($response->json('meta'));
        $this->assertNotNull($response->json('links'));
    }

    public function test_filterByHost() {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        $response = $this->get('/api/offices?host_id='.$host->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    public function test_filterByUser() {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get('/api/offices?user_id='.$user->id);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    // public function test_IncludeImages() {
    //     $user = User::factory()->create();

    //     $office = Office::factory()->for($user)->has(Tag::factory()->count(1))->has(Image::factory()->count(1))->create();

    //     $response = $this->get('/api/offices');

    //     $response->assertStatus(200);
    //     $this->assertIsArray($response->json('data')[0]['tags']);
    //     $this->assertIsArray($response->json('data')[0]['images']);
    //     // $this->assertEquals($user->id, $response->json('data')[0]['user']['id']);
    // }

    public function test_OrdersByDistance() {
        //52.40323778296758,
        //16.919121789818384
        $office1 = Office::factory()->create([
            'lat' => 52.38613747130224,
            'lng' => 16.74467782830996,
            'title' => 'Near Test'
        ]);
        $office2 = Office::factory()->create([
            'lat' => 52.35416762, 
            'lng' => 16.51637491,
            'title' => 'Further Test'
        ]);

        $response = $this->get('/api/offices?lat=52.40323778&lng=16.91912178');
        $response->assertStatus(200);
        $arr = $response->json('data');
        $inxNear = 0;
        $inxFurther = 0;
        foreach($arr as $key => $el) {
            if($el['title'] === 'Near Test') {
                $inxNear = $key;
            }
            if($el['title'] === 'Further Test') {
                $inxFurther = $key;
            }
        }
        $ok = $inxNear < $inxFurther;
        $this->assertTrue($ok);
    }

}
