<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Category;
use App\Models\Collection;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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
        $admin = $this->owner();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'One Minute Product',
                'product_type' => 'single',
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

    public function test_admin_can_securely_update_their_profile(): void
    {
        $admin = $this->owner();
        $admin->forceFill(['password' => Hash::make('CurrentPassword!123')])->save();

        $this->actingAs($admin)->put(route('admin.settings.profile.update'), [
            'name' => 'Updated Owner',
            'username' => 'updated.owner',
            'email' => 'owner@example.com',
            'current_password' => 'CurrentPassword!123',
            'password' => 'NewSecurePassword!456',
            'password_confirmation' => 'NewSecurePassword!456',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $admin->refresh();
        $this->assertSame('Updated Owner', $admin->name);
        $this->assertSame('updated.owner', $admin->username);
        $this->assertSame('owner@example.com', $admin->email);
        $this->assertTrue(Hash::check('NewSecurePassword!456', $admin->password));
    }

    public function test_admin_seeder_preserves_existing_credentials(): void
    {
        $admin = $this->owner();
        $admin->forceFill([
            'name' => 'Existing Owner',
            'username' => 'existing-owner',
            'email' => 'existing@example.com',
            'password' => Hash::make('ExistingPassword!123'),
        ])->save();

        $this->seed();

        $admin->refresh();
        $this->assertSame('Existing Owner', $admin->name);
        $this->assertSame('existing-owner', $admin->username);
        $this->assertSame('existing@example.com', $admin->email);
        $this->assertTrue(Hash::check('ExistingPassword!123', $admin->password));
    }

    public function test_admin_can_create_single_product_with_an_image(): void
    {
        Storage::fake('public');
        $admin = $this->owner();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Product With Image',
            'product_type' => 'single',
            'regular_price' => 4500,
            'status' => 'published',
            'image' => UploadedFile::fake()->image('product.jpg', 580, 670),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::query()->where('name', 'Product With Image')->firstOrFail();
        $media = $product->media()->firstOrFail();
        Storage::disk('public')->assertExists(str($media->path)->after('storage/')->toString());
    }

    public function test_admin_can_create_variant_product_and_vendor_codes_are_unique(): void
    {
        $admin = $this->owner();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Colour Test Product',
            'product_type' => 'variant',
            'status' => 'published',
            'vendor_code' => 'VENDOR-MAIN-01',
            'variants' => [[
                'color' => 'Emerald',
                'swatch' => '#0d5d45',
                'size' => 'Medium',
                'sku' => 'TB-COLOUR-EMERALD-M',
                'vendor_code' => 'VENDOR-VARIANT-01',
                'regular_price' => 2500,
                'sale_price' => 2200,
                'stock' => 6,
                'enabled' => 1,
            ]],
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
        $product = Product::query()->where('vendor_code', 'VENDOR-MAIN-01')->firstOrFail();
        $this->assertSame('variant', $product->product_type);
        $this->assertSame(6, $product->stock);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'vendor_code' => 'VENDOR-VARIANT-01',
            'sku' => 'TB-COLOUR-EMERALD-M',
            'is_enabled' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Duplicate Vendor Code',
            'product_type' => 'single',
            'regular_price' => 1000,
            'status' => 'draft',
            'vendor_code' => 'VENDOR-VARIANT-01',
        ])->assertSessionHasErrors('vendor_code');
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
        $paymentAccount = PaymentAccount::query()->firstOrFail();

        $this->post(route('cart.add', $product->slug), ['quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutPayload([
            'payment_method' => 'manual',
            'payment_account_id' => $paymentAccount->id,
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

    public function test_admin_can_delete_unused_products_and_categories(): void
    {
        $admin = $this->owner();
        $category = Category::query()->create(['name' => 'Temporary Category', 'slug' => 'temporary-category']);
        $product = Product::query()->create(['name' => 'Temporary Product', 'regular_price' => 100, 'stock' => 0, 'status' => 'draft']);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertSessionHasNoErrors();
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_a_category_that_is_in_use(): void
    {
        $admin = $this->owner();
        $product = Product::query()->whereNotNull('category_id')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $product->category_id))->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }

    public function test_admin_can_delete_a_collection_without_deleting_its_products(): void
    {
        $admin = $this->owner();
        $product = Product::query()->firstOrFail();
        $collection = Collection::query()->create(['name' => 'Temporary Collection', 'slug' => 'temporary-collection']);
        $collection->products()->attach($product);

        $this->actingAs($admin)->delete(route('admin.collections.destroy', $collection))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_selected_category_can_be_the_root_page_while_super_store_remains_available(): void
    {
        $category = Category::query()->firstOrFail();
        StoreSettings::put('show_super_store', true, 'homepage', 'boolean');
        StoreSettings::put('homepage_category_slug', $category->slug, 'homepage');

        $this->get('/')->assertOk()->assertSee($category->name);
        $this->get('/super-store')->assertOk()->assertSee('Super Store');
    }

    public function test_super_store_can_be_disabled_for_a_single_category_store(): void
    {
        $category = Category::query()->firstOrFail();
        Category::query()->whereKeyNot($category->id)->update(['is_active' => false]);
        StoreSettings::put('show_super_store', false, 'homepage', 'boolean');
        StoreSettings::put('homepage_category_slug', null, 'homepage');

        $this->get('/')->assertOk()->assertSee($category->name);
        $this->get('/super-store')->assertNotFound();
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

    private function owner(): User
    {
        return User::query()->whereHas('role', fn ($query) => $query->where('slug', 'owner'))->firstOrFail();
    }
}
