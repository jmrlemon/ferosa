<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\Product;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FunctionalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ar_cart_requires_login_and_adds_to_the_shared_cart_without_creating_an_order(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $product = $this->product(stock: 5);

        $this->getJson('/api/ar/products')->assertUnauthorized();

        $this->actingAs($customer)
            ->postJson('/api/ar/cart/add', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cart_count', 2)
            ->assertJsonPath('items.0.id', $product->id);

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'qty' => 2]);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 5]);
    }

    public function test_admin_can_upload_an_ar_model_from_the_product_edit_page(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        $product = $this->product(stock: 5);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSeeText('AR 3D Model')
            ->assertSee('name="ar_model"', false)
            ->assertSee('name="height_cm"', false);

        $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
            'ar_model' => UploadedFile::fake()->createWithContent(
                'test-plant.glb',
                $this->validGlb()
            ),
            'height_cm' => 125.5,
        ])->assertRedirect(route('admin.products.edit', $product));

        $model = $product->fresh()->plantModel;
        $this->assertNotNull($model);
        $this->assertSame('test-plant.glb', $model->file_name);
        $this->assertSame('125.5', $model->height_cm);
        Storage::disk('public')->assertExists($model->file_path);

        $this->actingAs($customer)
            ->getJson('/api/ar/products')
            ->assertOk()
            ->assertJsonPath('0.id', $product->id)
            ->assertJsonPath('0.height_cm', 125.5);
    }

    public function test_invalid_glb_does_not_replace_the_working_ar_model(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(stock: 5);
        $validGlb = $this->validGlb();

        $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
            'ar_model' => UploadedFile::fake()->createWithContent('working.glb', $validGlb),
            'height_cm' => 90,
        ])->assertSessionHasNoErrors();

        $workingPath = $product->fresh()->plantModel->file_path;

        $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
            'ar_model' => UploadedFile::fake()->createWithContent('broken.glb', 'not a glb'),
            'height_cm' => 120,
        ])->assertSessionHasErrors('ar_model');

        $model = $product->fresh()->plantModel;
        $this->assertSame($workingPath, $model->file_path);
        $this->assertSame('working.glb', $model->file_name);
        Storage::disk('public')->assertExists($workingPath);
    }

    public function test_truncated_and_bad_length_glbs_do_not_replace_the_working_model(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(stock: 5);
        $validGlb = $this->validGlb();

        $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
            'ar_model' => UploadedFile::fake()->createWithContent('working.glb', $validGlb),
            'height_cm' => 90,
        ])->assertSessionHasNoErrors();

        $workingPath = $product->fresh()->plantModel->file_path;
        $truncated = substr($validGlb, 0, -2);
        $truncated = substr($truncated, 0, 8).pack('V', strlen($truncated)).substr($truncated, 12);
        $badLength = substr($validGlb, 0, 8).pack('V', strlen($validGlb) + 4).substr($validGlb, 12);

        foreach (['truncated.glb' => $truncated, 'bad-length.glb' => $badLength] as $name => $contents) {
            $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
                'ar_model' => UploadedFile::fake()->createWithContent($name, $contents),
                'height_cm' => 120,
            ])->assertSessionHasErrors('ar_model');

            $model = $product->fresh()->plantModel;
            $this->assertSame($workingPath, $model->file_path);
            $this->assertSame('working.glb', $model->file_name);
            Storage::disk('public')->assertExists($workingPath);
        }
    }

    public function test_glbs_with_external_buffers_or_images_are_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(stock: 5);

        $externalBuffer = $this->validGlb([
            'asset' => ['version' => '2.0'],
            'buffers' => [['byteLength' => 4, 'uri' => 'model.bin']],
        ]);
        $externalImage = $this->validGlb([
            'asset' => ['version' => '2.0'],
            'buffers' => [['byteLength' => 4]],
            'images' => [['uri' => 'textures/plant.png']],
        ]);

        foreach (['external-buffer.glb' => $externalBuffer, 'external-image.glb' => $externalImage] as $name => $contents) {
            $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
                'ar_model' => UploadedFile::fake()->createWithContent($name, $contents),
                'height_cm' => 120,
            ])->assertSessionHasErrors('ar_model');
        }

        $this->assertNull($product->fresh()->plantModel);
        $this->assertSame([], Storage::disk('public')->allFiles('ar-models'));
    }

    public function test_required_extensions_are_allowlisted_without_rejecting_optional_extensions(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(stock: 5);

        $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
            'ar_model' => UploadedFile::fake()->createWithContent(
                'webp-required.glb',
                $this->validGlbWithOverrides(['extensionsRequired' => ['EXT_texture_webp']])
            ),
            'height_cm' => 120,
        ])->assertSessionHasErrors('ar_model')
            ->assertSessionHas('errors', function ($errors): bool {
                return str_contains($errors->first('ar_model'), 'EXT_texture_webp');
            });

        $meshoptResponse = $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
            'ar_model' => UploadedFile::fake()->createWithContent(
                'meshopt-required.glb',
                $this->validGlbWithOverrides(['extensionsRequired' => ['EXT_meshopt_compression']])
            ),
            'height_cm' => 120,
        ])->assertSessionHasNoErrors();

        $meshoptResponse->assertSessionHas('ar_model_warnings', function (array $warnings): bool {
            return collect($warnings)->contains(
                fn (string $warning): bool => str_contains($warning, 'EXT_meshopt_compression')
            );
        });

        $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
            'ar_model' => UploadedFile::fake()->createWithContent(
                'emissive-used.glb',
                $this->validGlbWithOverrides(['extensionsUsed' => ['KHR_materials_emissive_strength']])
            ),
            'height_cm' => 120,
        ])->assertSessionHasNoErrors();

        $this->assertSame('emissive-used.glb', $product->fresh()->plantModel->file_name);
    }

    public function test_glbs_without_reachable_geometry_or_measurable_height_are_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(stock: 5);

        $withoutGeometry = $this->validGlb([
            'asset' => ['version' => '2.0'],
            'buffers' => [['byteLength' => 4]],
        ]);
        $withoutReachableMesh = $this->validGlb([
            'asset' => ['version' => '2.0'],
            'scene' => 0,
            'scenes' => [['nodes' => [0]]],
            'nodes' => [[]],
            'meshes' => [[
                'primitives' => [[
                    'attributes' => ['POSITION' => 0],
                ]],
            ]],
            'accessors' => [[
                'componentType' => 5126,
                'count' => 3,
                'type' => 'VEC3',
                'min' => [0, 0, 0],
                'max' => [1, 1, 1],
            ]],
            'buffers' => [['byteLength' => 4]],
        ]);
        $withoutHeight = $this->validGlb([
            'asset' => ['version' => '2.0'],
            'scene' => 0,
            'scenes' => [['nodes' => [0]]],
            'nodes' => [['mesh' => 0]],
            'meshes' => [[
                'primitives' => [[
                    'attributes' => ['POSITION' => 0],
                ]],
            ]],
            'accessors' => [[
                'componentType' => 5126,
                'count' => 3,
                'type' => 'VEC3',
                'min' => [0, 1, 0],
                'max' => [1, 1, 1],
            ]],
            'buffers' => [['byteLength' => 4]],
        ]);

        foreach ([
            'no-geometry.glb' => $withoutGeometry,
            'orphan-mesh.glb' => $withoutReachableMesh,
            'zero-height.glb' => $withoutHeight,
        ] as $name => $contents) {
            $this->actingAs($admin)->post(route('admin.ar-models.upload', $product), [
                'ar_model' => UploadedFile::fake()->createWithContent($name, $contents),
                'height_cm' => 120,
            ])->assertSessionHasErrors('ar_model');
        }

        $this->assertNull($product->fresh()->plantModel);
        $this->assertSame([], Storage::disk('public')->allFiles('ar-models'));
    }

    public function test_legacy_cart_merge_does_not_double_existing_quantities(): void
    {
        $customer = User::factory()->create(['role' => 'user']);
        $product = $this->product(stock: 10);

        $this->actingAs($customer)->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk();

        $this->actingAs($customer)->postJson('/api/cart/sync', [
            'items' => [['id' => $product->id, 'qty' => 2]],
        ])->assertJsonPath('cart_count', 2);

        $this->actingAs($customer)->postJson('/api/cart/sync', [
            'items' => [['id' => $product->id, 'qty' => 4]],
        ])->assertJsonPath('cart_count', 4);
    }

    public function test_checkout_token_prevents_duplicate_orders_and_stock_decrements(): void
    {
        Mail::fake();
        Notification::fake();
        $customer = User::factory()->create(['role' => 'user']);
        $product = $this->product(stock: 8);
        $token = (string) Str::uuid();

        $this->actingAs($customer)->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertOk();

        $payload = [
            'checkout_token' => $token,
            'delivery_method' => 'pickup',
            'payment_method' => 'cod',
        ];

        $this->actingAs($customer)->post(route('checkout.store'), $payload)->assertRedirect();
        $this->actingAs($customer)->post(route('checkout.store'), $payload)->assertRedirect();

        $this->assertSame(1, Order::query()->where('checkout_token', $token)->count());
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 5]);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_invalid_order_transition_is_blocked_and_cancellation_restores_stock_once(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        $product = $this->product(stock: 3);
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'FRS-TEST-1',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_amount' => 100,
        ]);
        $order->orderItems()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 100,
            'qty' => 2,
        ]);

        $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'delivered',
            'payment_status' => 'unpaid',
        ])->assertSessionHasErrors('status');
        $this->assertSame('pending', $order->fresh()->status);

        $payload = ['status' => 'cancelled', 'payment_status' => 'unpaid'];
        $this->actingAs($admin)->put(route('admin.orders.status', $order), $payload)->assertRedirect();
        $this->actingAs($admin)->put(route('admin.orders.status', $order), $payload)->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 5]);
    }

    public function test_cancelled_appointment_slot_can_be_rebooked(): void
    {
        Mail::fake();
        Notification::fake();
        $firstCustomer = User::factory()->create(['role' => 'user']);
        $secondCustomer = User::factory()->create(['role' => 'user']);
        $service = ServiceType::query()->create([
            'name' => 'Garden Consultation',
            'default_fee' => 500,
            'is_active' => true,
        ]);
        $at = Carbon::now()->addDays(4)->setTime(9, 0)->seconds(0);

        $this->actingAs($firstCustomer)->post(route('schedule.store'), [
            'service_type_id' => $service->id,
            'appointment_at' => $at->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $appointment = Appointment::query()->where('user_id', $firstCustomer->id)->firstOrFail();
        $this->actingAs($firstCustomer)->delete(route('appointments.cancel', $appointment), [
            'cancel_reason' => 'Plans changed',
        ])->assertRedirect();
        $this->assertNull($appointment->fresh()->slot_key);

        $this->actingAs($secondCustomer)->post(route('schedule.store'), [
            'service_type_id' => $service->id,
            'appointment_at' => $at->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Appointment::query()->count());
    }

    public function test_estimator_shows_concrete_examples_for_every_quality_tier(): void
    {
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($customer)
            ->get(route('estimator'))
            ->assertOk()
            ->assertSeeText('Starter Garden')
            ->assertSeeText('Enhanced Garden')
            ->assertSeeText('Signature Landscape')
            ->assertSeeText('Common shrubs and groundcover')
            ->assertSeeText('Custom hardscape and irrigation');
    }

    public function test_dispatch_and_delivery_require_separate_proof_and_people_details(): void
    {
        Storage::fake('public');
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'FRS-DELIVERY-1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'total_amount' => 500,
        ]);

        $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'out_for_delivery',
            'payment_status' => 'paid',
            'driver_name' => 'Juan Rider',
        ])->assertSessionHasErrors('dispatch_proof');

        $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'out_for_delivery',
            'payment_status' => 'paid',
            'driver_name' => 'Juan Rider',
            'driver_phone' => '09171234567',
            'dispatch_proof' => $this->fakePng('dispatch.png'),
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('out_for_delivery', $order->status);
        $this->assertNotNull($order->dispatch_proof_url);
        $this->assertNotNull($order->dispatched_at);

        $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ])->assertSessionHasErrors('delivery_proof');

        $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'delivery_recipient_name' => 'Maria Customer',
            'delivery_proof' => $this->fakePng('delivered.png'),
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame('Maria Customer', $order->delivery_recipient_name);
        $this->assertNotNull($order->delivery_proof_url);
        $this->assertNotNull($order->delivered_at);

        $this->actingAs($customer)
            ->post(route('orders.confirm-received', $order))
            ->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        Notification::assertSentTo($admin, \App\Notifications\WorkCreatedNotice::class);
    }

    public function test_pickup_orders_hide_and_skip_delivery_only_requirements(): void
    {
        Storage::fake('public');
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'FRS-PICKUP-1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'delivery_method' => 'pickup',
            'total_amount' => 500,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSeeText('Ready for Pickup')
            ->assertDontSeeText('Driver or Rider Name')
            ->assertDontSeeText('Dispatch Proof')
            ->assertDontSeeText('Delivery Proof');

        $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'out_for_delivery',
            'payment_status' => 'paid',
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('out_for_delivery', $order->status);
        $this->assertNull($order->dispatch_proof_url);
        $this->assertNull($order->driver_name);

        $this->actingAs($admin)->put(route('admin.orders.status', $order), [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertNull($order->delivery_proof_url);
        $this->assertNotNull($order->delivered_at);
    }

    private function product(int $stock): Product
    {
        return Product::query()->create([
            'name' => 'Test Plant',
            'price' => 100,
            'stock_qty' => $stock,
            'category' => 'Plants',
            'is_active' => true,
        ]);
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true)
        );
    }

    /**
     * Build a small, structurally complete GLB 2.0 fixture.
     *
     * @param  array<string, mixed>|null  $document
     */
    private function validGlb(?array $document = null, string $binary = "\0\0\0\0"): string
    {
        if ($document === null) {
            $binary = pack(
                'g*',
                0.0, 0.0, 0.0,
                1.0, 0.0, 0.0,
                0.0, 1.0, 0.1,
            ).pack('v*', 0, 1, 2);
            $document = [
                'asset' => ['version' => '2.0'],
                'scene' => 0,
                'scenes' => [['nodes' => [0]]],
                'nodes' => [['mesh' => 0]],
                'meshes' => [[
                    'primitives' => [[
                        'attributes' => ['POSITION' => 0],
                        'indices' => 1,
                    ]],
                ]],
                'accessors' => [
                    [
                        'bufferView' => 0,
                        'componentType' => 5126,
                        'count' => 3,
                        'type' => 'VEC3',
                        'min' => [0, 0, 0],
                        'max' => [1, 1, 0.1],
                    ],
                    [
                        'bufferView' => 1,
                        'componentType' => 5123,
                        'count' => 3,
                        'type' => 'SCALAR',
                    ],
                ],
                'bufferViews' => [
                    [
                        'buffer' => 0,
                        'byteOffset' => 0,
                        'byteLength' => 36,
                        'target' => 34962,
                    ],
                    [
                        'buffer' => 0,
                        'byteOffset' => 36,
                        'byteLength' => 6,
                        'target' => 34963,
                    ],
                ],
                'buffers' => [['byteLength' => strlen($binary)]],
            ];
        }

        $json = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $json .= str_repeat(' ', (4 - (strlen($json) % 4)) % 4);
        $binaryChunk = $binary.str_repeat("\0", (4 - (strlen($binary) % 4)) % 4);
        $length = 12 + 8 + strlen($json) + 8 + strlen($binaryChunk);

        return 'glTF'
            .pack('V', 2)
            .pack('V', $length)
            .pack('V', strlen($json))
            .pack('V', 0x4E4F534A)
            .$json
            .pack('V', strlen($binaryChunk))
            .pack('V', 0x004E4942)
            .$binaryChunk;
    }

    /**
     * Build a valid fixture and override only the JSON members under test.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function validGlbWithOverrides(array $overrides): string
    {
        $binary = pack(
            'g*',
            0.0, 0.0, 0.0,
            1.0, 0.0, 0.0,
            0.0, 1.0, 0.1,
        ).pack('v*', 0, 1, 2);

        $document = [
            'asset' => ['version' => '2.0'],
            'scene' => 0,
            'scenes' => [['nodes' => [0]]],
            'nodes' => [['mesh' => 0]],
            'meshes' => [[
                'primitives' => [[
                    'attributes' => ['POSITION' => 0],
                    'indices' => 1,
                ]],
            ]],
            'accessors' => [
                [
                    'bufferView' => 0,
                    'componentType' => 5126,
                    'count' => 3,
                    'type' => 'VEC3',
                    'min' => [0, 0, 0],
                    'max' => [1, 1, 0.1],
                ],
                [
                    'bufferView' => 1,
                    'componentType' => 5123,
                    'count' => 3,
                    'type' => 'SCALAR',
                ],
            ],
            'bufferViews' => [
                [
                    'buffer' => 0,
                    'byteOffset' => 0,
                    'byteLength' => 36,
                    'target' => 34962,
                ],
                [
                    'buffer' => 0,
                    'byteOffset' => 36,
                    'byteLength' => 6,
                    'target' => 34963,
                ],
            ],
            'buffers' => [['byteLength' => strlen($binary)]],
        ];

        return $this->validGlb(array_replace($document, $overrides), $binary);
    }
}
