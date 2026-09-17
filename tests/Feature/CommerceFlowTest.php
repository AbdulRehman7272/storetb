<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_storefront_and_root_category_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('TBrand');
        $this->get('/ladies-suiting')->assertOk()->assertSee('Ladies Suiting');
    }

    public function test_admin_can_publish_product_with_only_name_and_price(): void
    {
        $admin = User::query()->where('email', 'admin@tbrand.pk')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'One Minute Product',
                'regular_price' => 1200,
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'One Minute Product',
            'status' => 'published',
            'regular_price' => 1200,
        ]);
    }

    public function test_guest_can_checkout_with_cod(): void
    {
        $product = Product::query()->where('status', 'published')->firstOrFail();

        $this->post(route('cart.add', $product->slug), ['quantity' => 1])->assertRedirect(route('cart.index'));
        $this->post(route('checkout.store'), $this->checkoutPayload())->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test Customer',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_manual_payment_upload_is_private_and_pending_verification(): void
    {
        Storage::fake('local');
        $product = Product::query()->where('status', 'published')->firstOrFail();

        $this->post(route('cart.add', $product->slug), ['quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutPayload([
            'payment_method' => 'manual',
            'payment_account_id' => 1,
            'transaction_reference' => 'TX-123',
            'payment_screenshot' => UploadedFile::fake()->image('proof.jpg'),
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('orders', ['payment_status' => 'verification_pending']);
        $this->assertDatabaseHas('payment_proofs', ['original_name' => 'proof.jpg']);
        $proofPath = Order::query()->latest()->firstOrFail()->proofs()->firstOrFail()->path;
        Storage::disk('local')->assertExists($proofPath);
    }

    public function test_vue_storefront_checkout_creates_a_real_order(): void
    {
        $product = Product::query()->where('status', 'published')->firstOrFail();

        $this->postJson(route('checkout.frontend'), [
            'full_name' => 'Vue Customer',
            'mobile' => '03001112222',
            'email' => null,
            'province' => 'Punjab',
            'city' => 'Lahore',
            'address' => '12 Storefront Street',
            'payment_method' => 'cod',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertOk()->assertJsonStructure(['order_number', 'order_id']);

        $this->assertDatabaseHas('orders', ['customer_name' => 'Vue Customer', 'payment_method' => 'cod']);
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_replace([
            'full_name' => 'Test Customer',
            'mobile' => '03000000000',
            'secondary_mobile' => '',
            'email' => 'customer@example.com',
            'province' => 'Punjab',
            'city' => 'Lahore',
            'address' => 'Street 1, Test Area',
            'landmark' => '',
            'notes' => '',
            'payment_method' => 'cod',
        ], $overrides);
    }
}
