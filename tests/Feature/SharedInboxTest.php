<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The health station's chat is one shared inbox: every healthcare worker sees and answers every
 * patient's conversation, and the patient sees one conversation with replies from any of them.
 */
class SharedInboxTest extends TestCase
{
    use RefreshDatabase;

    protected User $rosa;

    protected User $elena;

    protected User $ana;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rosa = User::create(['name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
        $this->elena = User::create(['name' => 'BHW Elena', 'email' => 'elena@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
        $this->ana = $this->patientLogin('Ana Reyes', 'ana@example.com');
    }

    protected function patientLogin(string $name, string $email): User
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make('secret123'), 'role' => 'user']);
        [$first, $last] = explode(' ', $name);
        Patient::create([
            'user_id' => $user->id, 'first_name' => $first, 'last_name' => $last, 'dob' => '1998-04-12', 'gender' => 'Female',
            'phone' => '0917', 'address' => 'Purok 2', 'emergency_contact_name' => 'X', 'emergency_contact_phone' => '0918',
            'registration_type' => 'Maternal', 'barangay' => 'Bicao', 'status' => 'Active',
        ]);

        return $user;
    }

    public function test_every_healthcare_worker_sees_a_patients_message(): void
    {
        $this->actingAs($this->ana)->postJson('/messaging', ['message' => 'May lagnat ang bata'])->assertOk();

        // Elena is not the account recorded as receiver, but the conversation is the station's.
        $this->assertSame(1, $this->rosa->unreadChatCount());
        $this->assertSame(1, $this->elena->unreadChatCount());

        $this->actingAs($this->elena)->get('/messaging?chat_user_id=' . $this->ana->id)
            ->assertOk()
            ->assertSee('May lagnat ang bata');

        // Read by one healthcare worker means read for the team.
        $this->assertSame(0, $this->rosa->unreadChatCount());
        $this->assertTrue(ChatMessage::sole()->is_read);
    }

    public function test_the_patient_sees_replies_from_any_healthcare_worker_in_one_conversation(): void
    {
        $this->actingAs($this->rosa)->post('/messaging', ['message' => 'Kumusta po?', 'receiver_id' => $this->ana->id]);
        $this->actingAs($this->elena)->post('/messaging', ['message' => 'Dalhin po ang bata bukas.', 'receiver_id' => $this->ana->id]);

        $this->assertSame(2, $this->ana->unreadChatCount());

        $this->actingAs($this->ana)->get('/messaging')
            ->assertOk()
            ->assertSee('Barangay Bicao Health Station')
            ->assertSeeInOrder(['Kumusta po?', 'Midwife Rosa', 'Dalhin po ang bata bukas.', 'BHW Elena']);

        $this->assertSame(0, $this->ana->unreadChatCount());
    }

    public function test_a_colleagues_reply_shows_on_the_staff_side_with_their_name(): void
    {
        $this->actingAs($this->elena)->post('/messaging', ['message' => 'Dalhin po ang bata bukas.', 'receiver_id' => $this->ana->id]);

        $this->actingAs($this->rosa)->get('/messaging?chat_user_id=' . $this->ana->id)
            ->assertOk()
            ->assertSee('Dalhin po ang bata bukas.')
            ->assertSee('BHW Elena &middot;', false);
    }

    public function test_the_portal_shows_replies_from_any_healthcare_worker(): void
    {
        $this->actingAs($this->elena)->post('/messaging', ['message' => 'Bakuna sa Huwebes.', 'receiver_id' => $this->ana->id]);

        $this->actingAs($this->ana)->get('/portal')
            ->assertOk()
            ->assertSee('Bakuna sa Huwebes.')
            ->assertSee('BHW Elena');
    }

    public function test_patients_waiting_for_a_reply_are_listed_first_with_a_badge(): void
    {
        $bea = $this->patientLogin('Bea Cruz', 'bea@example.com');
        $this->actingAs($bea)->postJson('/messaging', ['message' => 'Hello']);
        $this->actingAs($bea)->postJson('/messaging', ['message' => 'Paki-reply po']);

        $this->actingAs($this->rosa)->get('/messaging?chat_user_id=' . $this->ana->id)
            ->assertOk()
            ->assertSeeInOrder(['Bea Cruz', 'Ana Reyes'])
            ->assertSee('title="Unread messages">2</span>', false);
    }

    public function test_staff_only_write_to_patients(): void
    {
        $this->actingAs($this->rosa)->post('/messaging', ['message' => 'Hi', 'receiver_id' => $this->elena->id])
            ->assertSessionHasErrors('receiver_id');

        // And can't open a "conversation" with another staff account.
        $this->actingAs($this->rosa)->get('/messaging?chat_user_id=' . $this->elena->id)
            ->assertOk()
            ->assertViewHas('activeChatUser', null);
    }

    public function test_a_patient_cannot_send_when_no_staff_account_is_active(): void
    {
        User::where('role', 'admin')->update(['is_active' => false]);

        $this->actingAs($this->ana)->postJson('/messaging', ['message' => 'Hello'])->assertStatus(503);
        $this->assertSame(0, ChatMessage::count());
    }

    public function test_only_the_patient_and_staff_can_listen_to_a_conversation(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb' => [
                'driver' => 'reverb', 'key' => 'test-key', 'secret' => 'test-secret', 'app_id' => 'test-app-id',
                'options' => ['host' => 'localhost', 'port' => 8080, 'scheme' => 'http'],
            ],
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $auth = fn (User $user) => $this->actingAs($user)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-patient-chat.' . $this->ana->id,
            'socket_id' => '1234.1234',
        ]);

        $auth($this->ana)->assertOk();
        $auth($this->elena)->assertOk();
        $auth($this->patientLogin('Bea Cruz', 'bea@example.com'))->assertForbidden();
    }
}
