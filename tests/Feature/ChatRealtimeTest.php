<?php

namespace Tests\Feature;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChatRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $midwife;

    protected User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->midwife = User::create(['name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
        $this->patient = User::create(['name' => 'Ana Reyes', 'email' => 'ana@example.com', 'password' => Hash::make('secret123'), 'role' => 'user']);
    }

    protected function reverbIsDown(): void
    {
        // Broadcasting is on, but nothing listens on the Reverb port.
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb' => [
                'driver' => 'reverb',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => '1',
                'options' => ['host' => '127.0.0.1', 'port' => 1, 'scheme' => 'http', 'useTLS' => false],
                'client_options' => ['timeout' => 2, 'connect_timeout' => 2],
            ],
        ]);
    }

    public function test_messages_are_broadcast_to_the_conversation(): void
    {
        Event::fake([MessageSent::class, MessageRead::class]);

        $this->actingAs($this->patient)->postJson('/messaging', ['message' => 'Hello po'])->assertOk();

        // Every message goes to the patient's own conversation channel, which all staff can join.
        Event::assertDispatched(MessageSent::class, fn (MessageSent $e) => $e->patientId === $this->patient->id
            && $e->broadcastOn()[0]->name === 'private-patient-chat.' . $this->patient->id);

        // Opening the chat marks it read and tells the patient.
        $this->actingAs($this->midwife)->get('/messaging?chat_user_id=' . $this->patient->id)->assertOk();
        Event::assertDispatched(MessageRead::class, fn (MessageRead $e) => $e->patientId === $this->patient->id && $e->readByUserId === $this->midwife->id);
    }

    public function test_chat_header_shows_only_the_live_status(): void
    {
        // The fixed "Active Patient" / "Online Support" labels looked like a second status.
        $this->actingAs($this->midwife)->get('/messaging?chat_user_id=' . $this->patient->id)
            ->assertOk()
            ->assertSee('id="active-user-status-text"', false)
            ->assertDontSee('Active Patient');

        $this->actingAs($this->patient)->get('/messaging')
            ->assertOk()
            ->assertSee('id="station-status-text"', false)
            ->assertDontSee('Online Support');
    }

    public function test_chat_still_works_when_the_realtime_server_is_down(): void
    {
        $this->reverbIsDown();

        $this->actingAs($this->patient)->postJson('/messaging', ['message' => 'Hello po'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->actingAs($this->midwife)->postJson('/messaging', ['message' => 'Hi Ana', 'receiver_id' => $this->patient->id])
            ->assertOk();

        $this->assertSame(2, ChatMessage::count());

        // Opening the chat (which marks messages read and announces it) still works too.
        $this->actingAs($this->midwife)->get('/messaging?chat_user_id=' . $this->patient->id)
            ->assertOk()
            ->assertSee('Hello po');
        $this->assertTrue((bool) ChatMessage::where('sender_id', $this->patient->id)->first()->is_read);
    }
}
