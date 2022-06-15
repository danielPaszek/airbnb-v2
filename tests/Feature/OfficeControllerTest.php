<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\OfficePendingApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfficeControllerTest extends TestCase
{
    // use RefreshDatabase;

    public function test_ListsPaginatedOffices()
    {
        Office::factory(3)->create();
        $response = $this->get('/api/offices');
        //dd($response->json());
        $response->assertStatus(200);
        // $response->assertJsonCount(3, 'data');
        $this->assertNotNull($response->json('data')[0]['id']);
        $this->assertNotNull($response->json('meta'));
        $this->assertNotNull($response->json('links'));
    }

    public function test_filterByUser() {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        $response = $this->get('/api/offices?user_id='.$host->id);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    public function test_filterByVisitor() {
        $user = User::factory()->create();
        $office = Office::factory()->create();
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get('/api/offices?visitor_id='.$user->id);
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    public function test_OrdersByDistance() {
        //52.40323778296758,
        //16.919121789818384
        Office::factory()->create([
            'lat' => 52.38613747130224,
            'lng' => 16.74467782830996,
            'title' => 'Near Test'
        ]);
        Office::factory()->create([
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
    public function test_showOffice()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->tags()->attach($tag);
        $office->images()->create(['path' => 'image.jpg']);

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);


        $response = $this->get('/api/offices/'.$office->id);
        $response->assertStatus(200);
        $this->assertEquals($office->id, $response->json('data')['id']);
        $this->assertIsArray($response->json('data')['tags']);
        $this->assertIsArray($response->json('data')['images']);
        $this->assertEquals($user->id, $response->json('data')['user']['id']);
    }
    public function test_createOffice() {
        $admin = User::find(1);
        Notification::fake();
        
        $user = User::factory()->create();
        $tags = Tag::factory(2)->create();

        Sanctum::actingAs($user, ['*']);

        $reponse = $this->postJson('/api/offices/', Office::factory()->raw([
            'tags' => $tags->pluck('id')->toArray()
        ]));
        // $reponse->dump();
        $reponse->assertJsonPath('data.approval_status', Office::APPROVAL_PENDING);
        $this->assertDatabaseHas('offices', [
            'id' => $reponse->json('data.id')
        ]);
        Notification::assertSentTo($admin, OfficePendingApproval::class);
    }
    public function test_CantCreateOffice() {
        $user = User::factory()->create();

        $token = $user->createToken('test', []);


        $response = $this->postJson('/api/offices/',[],[
            'Authorization' => 'Bearer '.$token->plainTextToken
        ]);
        // dd($response->status());
        $response->assertStatus(403);
    }
    public function test_UpdateOffice() {
        $admin = User::find(1);
        $user = User::factory()->create();
        $tags = Tag::factory(4)->create();
        $otherTag = Tag::factory()->create();
        $office = Office::factory()->for($user)->create();

        Notification::fake();
        $office->tags()->attach($tags);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/offices/'. $office->id, 
        [
            'title' => 'Updated title',
            'tags' => [$tags[0]->id, $otherTag->id]
        ]);
        $response->assertStatus(200);
        $response->assertJsonPath('data.title','Updated title' );
        $response->assertJsonCount(2, 'data.tags');
        $this->assertDatabaseHas('offices', [
            'id' => $response->json('data.id'),
            'title' => 'Updated title',
            'approval_status' => Office::APPROVAL_PENDING
        ]);
        Notification::assertSentTo($admin, OfficePendingApproval::class);
    }
    
    public function test_UpdateNotYourOffice() {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $office = Office::factory()->for($anotherUser)->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/offices/'. $office->id, 
        [
            'title' => 'Updated title',
        ]);
        $response->assertStatus(403);
        $this->assertDatabaseHas('offices', [
            'id' => $office->id,
            //title has not change
            'title' => $office->title
        ]);
    }
}
