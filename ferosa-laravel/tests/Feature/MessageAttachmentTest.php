<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Support\MessageAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageAttachmentTest extends TestCase
{
    use RefreshDatabase;

    /** The chat posts over fetch(), so the controller only answers JSON here. */
    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];

    private function customer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_customer_can_send_a_picture_without_any_caption(): void
    {
        Storage::fake('public');
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post('/messages', ['attachment' => UploadedFile::fake()->image('garden.png')], self::AJAX)
            ->assertCreated()
            ->assertJsonPath('message.attachment.is_image', true);

        $message = Conversation::where('customer_id', $customer->id)->firstOrFail()->messages()->sole();

        $this->assertNull($message->body);
        $this->assertSame('garden.png', $message->attachment_name);
        Storage::disk('public')->assertExists($message->attachment_path);
    }

    public function test_customer_can_send_a_document_with_a_caption(): void
    {
        Storage::fake('public');
        $customer = $this->customer();

        $this->actingAs($customer)->post('/messages', [
            'body' => 'Here is the quote',
            'attachment' => UploadedFile::fake()->create('quote.pdf', 200, 'application/pdf'),
        ], self::AJAX)->assertCreated()->assertJsonPath('message.attachment.is_image', false);

        $message = Conversation::where('customer_id', $customer->id)->firstOrFail()->messages()->sole();

        $this->assertSame('Here is the quote', $message->body);
        $this->assertSame('quote.pdf', $message->attachment_name);
    }

    public function test_a_message_with_neither_text_nor_file_is_rejected(): void
    {
        $this->actingAs($this->customer())
            ->post('/messages', [], self::AJAX)
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_disallowed_file_type_is_rejected_and_nothing_is_stored(): void
    {
        Storage::fake('public');

        $this->actingAs($this->customer())
            ->post('/messages', ['attachment' => UploadedFile::fake()->create('shell.php', 10, 'text/x-php')], self::AJAX)
            ->assertStatus(422)
            ->assertJsonValidationErrors('attachment');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('public');

        // Derived from the limit itself so raising the cap cannot silently
        // turn this into a test that uploads an allowed file.
        $tooBig = MessageAttachment::MAX_KB + 512;

        $this->actingAs($this->customer())
            ->post('/messages', ['attachment' => UploadedFile::fake()->create('huge.pdf', $tooBig, 'application/pdf')], self::AJAX)
            ->assertStatus(422)
            ->assertJsonValidationErrors('attachment');
    }

    public function test_a_typical_phone_photo_sized_file_is_accepted(): void
    {
        Storage::fake('public');

        // 8 MB is a normal camera photo and used to be rejected by the old cap.
        $this->actingAs($this->customer())
            ->post('/messages', ['attachment' => UploadedFile::fake()->create('photo.jpg', 8192, 'image/jpeg')], self::AJAX)
            ->assertCreated();
    }

    public function test_admin_can_reply_with_an_attachment_and_customer_sees_it(): void
    {
        Storage::fake('public');
        $customer = $this->customer();
        $conversation = Conversation::create(['customer_id' => $customer->id, 'last_message_at' => now()]);

        $this->actingAs($this->admin())
            ->post("/admin/conversations/{$conversation->id}/reply", [
                'attachment' => UploadedFile::fake()->image('plan.png'),
            ])->assertRedirect();

        $this->assertTrue($conversation->messages()->sole()->attachmentIsImage());

        // The customer's polling endpoint must expose it too.
        $this->actingAs($customer)
            ->getJson('/messages/poll')
            ->assertOk()
            ->assertJsonPath('messages.0.attachment.name', 'plan.png');
    }

    public function test_admin_can_send_an_attachment_with_no_reply_text(): void
    {
        Storage::fake('public');
        $customer = $this->customer();
        $conversation = Conversation::create(['customer_id' => $customer->id, 'last_message_at' => now()]);

        $this->actingAs($this->admin())
            ->post("/admin/conversations/{$conversation->id}/reply", [
                'attachment' => UploadedFile::fake()->image('site.png'),
            ], self::AJAX)
            ->assertCreated()
            ->assertJsonPath('message.attachment.is_image', true);

        $this->assertNull($conversation->messages()->sole()->body);
    }

    public function test_admin_reply_reports_a_rejected_upload_instead_of_redirecting(): void
    {
        Storage::fake('public');
        $conversation = Conversation::create(['customer_id' => $this->customer()->id, 'last_message_at' => now()]);

        $this->actingAs($this->admin())
            ->post("/admin/conversations/{$conversation->id}/reply", [
                'attachment' => UploadedFile::fake()->create('shell.php', 10, 'text/x-php'),
            ], self::AJAX)
            ->assertStatus(422)
            ->assertJsonValidationErrors('attachment');

        $this->assertSame(0, $conversation->messages()->count());
    }

    public function test_admin_inbox_returns_attachment_details(): void
    {
        Storage::fake('public');
        $customer = $this->customer();

        $this->actingAs($customer)->post('/messages', [
            'attachment' => UploadedFile::fake()->image('before.png'),
        ], self::AJAX)->assertCreated();

        $conversation = Conversation::where('customer_id', $customer->id)->firstOrFail();

        $this->actingAs($this->admin())
            ->getJson("/admin/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('messages.0.attachment.name', 'before.png')
            ->assertJsonPath('messages.0.attachment.is_image', true);
    }

    public function test_plain_text_messages_still_work(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)
            ->post('/messages', ['body' => 'Just text'], self::AJAX)
            ->assertCreated()
            ->assertJsonPath('message.attachment', null);

        $this->assertSame(
            'Just text',
            Conversation::where('customer_id', $customer->id)->firstOrFail()->messages()->sole()->body
        );
    }
}
