<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Consignor;
use App\Models\Customer;
use App\Models\IntakeBatch;
use App\Models\Payout;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaviarWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_consignor_can_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $consignor = Consignor::create(['name' => 'Empty Consignor']);

        $this->actingAs($user, 'sanctum')->getJson('/api/consignors?search=Empty%20Consignor')
            ->assertOk()->assertJsonPath('data.0.can_delete', true);

        $this->actingAs($user, 'sanctum')->deleteJson("/api/consignors/{$consignor->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('consignors', ['id' => $consignor->id]);
    }

    public function test_consignor_with_related_history_cannot_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();

        $withProduct = Consignor::create(['name' => 'Product History']);
        Product::create([
            'code' => 'SAFE-DELETE-001', 'name' => 'Historical Product', 'consignor_id' => $withProduct->id,
            'category_id' => Category::firstOrFail()->id, 'purchase_price' => 100000,
            'selling_price' => 150000, 'condition' => 'Good', 'status' => 'available',
        ]);

        $withPayout = Consignor::create(['name' => 'Payout History']);
        Payout::create(['consignor_id' => $withPayout->id, 'amount' => 100000, 'status' => 'paid', 'paid_at' => now()]);

        $withIntake = Consignor::create(['name' => 'Intake History']);
        IntakeBatch::create(['reference' => 'SAFE-INTAKE-001', 'consignor_id' => $withIntake->id, 'quantity' => 1]);

        foreach ([$withProduct, $withPayout, $withIntake] as $consignor) {
            $this->actingAs($user, 'sanctum')->deleteJson("/api/consignors/{$consignor->id}")
                ->assertUnprocessable();
            $this->assertDatabaseHas('consignors', ['id' => $consignor->id]);
        }
    }

    public function test_seeded_admin_can_login_and_open_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/auth/login', ['email' => 'admin@claviar.test', 'password' => 'password']);
        $login->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'roles', 'permissions']]);

        $this->withToken($login->json('token'))->getJson('/api/dashboard')
            ->assertOk()->assertJsonPath('data.total_products', 0);
    }

    public function test_creating_a_sale_marks_the_product_as_sold(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $consignor = Consignor::create(['name' => 'Nadia Putri']);
        $product = Product::create([
            'code' => 'CLV-001', 'name' => 'Vintage Bag', 'consignor_id' => $consignor->id,
            'category_id' => Category::firstOrFail()->id, 'brand_id' => Brand::firstOrFail()->id,
            'purchase_price' => 1000000, 'selling_price' => 1500000, 'condition' => 'Excellent', 'status' => 'available',
        ]);

        $this->actingAs($user, 'sanctum')->postJson('/api/sales', [
            'product_id' => $product->id, 'customer_name' => 'Customer', 'customer_phone' => '08123456789',
            'sale_price' => 1500000, 'payment_method' => 'Transfer', 'sold_at' => now()->toDateTimeString(),
        ])->assertCreated()->assertJsonPath('data.product.status', 'sold');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'sold']);
    }

    public function test_product_image_is_converted_and_stored_as_webp(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $consignor = Consignor::create(['name' => 'Image Test Consignor']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'code' => 'CLV-IMG-001', 'name' => 'Image Test Product',
            'consignor_id' => $consignor->id, 'category_id' => Category::firstOrFail()->id,
            'brand_id' => Brand::firstOrFail()->id, 'purchase_price' => 100000,
            'selling_price' => 150000, 'condition' => 'Excellent', 'status' => 'available',
            'images' => [UploadedFile::fake()->image('product.jpg', 120, 120)], 'cover_index' => 0,
        ]);

        $response->assertCreated()->assertJsonPath('data.images.0.is_cover', true);
        $this->assertStringStartsWith('http://localhost/storage/products/', $response->json('data.images.0.url'));
        $path = Product::where('code', 'CLV-IMG-001')->firstOrFail()->images()->firstOrFail()->image;
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_two_products_can_be_created_sequentially_with_independent_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $consignor = Consignor::create(['name' => 'Sequential Test']);
        $base = ['consignor_id' => $consignor->id, 'category_id' => Category::firstOrFail()->id, 'brand_id' => Brand::firstOrFail()->id, 'purchase_price' => 100000, 'selling_price' => 150000, 'condition' => 'Excellent', 'status' => 'available'];

        $this->actingAs($user, 'sanctum')->postJson('/api/products', array_merge($base, ['code' => 'SEQ-001', 'name' => 'First Product']))->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson('/api/products', array_merge($base, ['code' => 'SEQ-002', 'name' => 'Second Product', 'selling_price' => 175000]))->assertCreated();

        $this->assertDatabaseHas('products', ['code' => 'SEQ-001', 'name' => 'First Product', 'selling_price' => 150000]);
        $this->assertDatabaseHas('products', ['code' => 'SEQ-002', 'name' => 'Second Product', 'selling_price' => 175000]);
    }

    public function test_product_can_be_created_without_a_brand(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $consignor = Consignor::create(['name' => 'No Brand Consignor']);

        $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'code' => 'NO-BRAND-001', 'name' => 'Unbranded Product',
            'consignor_id' => $consignor->id, 'category_id' => Category::firstOrFail()->id,
            'purchase_price' => 50000, 'selling_price' => 90000,
            'condition' => 'Good', 'status' => 'available',
        ])->assertCreated()->assertJsonPath('data.brand', null);

        $this->assertDatabaseHas('products', ['code' => 'NO-BRAND-001', 'brand_id' => null]);
    }

    public function test_bulk_intake_of_fifty_items_decreases_to_forty_nine_after_one_sale(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $consignor = Consignor::create(['name' => 'Consignor A', 'phone' => '08123456789']);

        $this->actingAs($user, 'sanctum')->postJson("/api/consignors/{$consignor->id}/intake", [
            'quantity' => 50, 'notes' => 'First bulk intake',
        ])->assertCreated()->assertJsonPath('data.quantity', 50);

        $this->assertSame(50, $consignor->products()->where('is_draft', true)->count());
        $draft = $consignor->products()->where('is_draft', true)->firstOrFail();
        $customer = Customer::create(['name' => 'Customer One', 'phone' => '08987654321']);

        $this->actingAs($user, 'sanctum')->postJson("/api/products/{$draft->id}/complete-sale", [
            'name' => 'Baju Vintage', 'category_id' => Category::firstOrFail()->id,
            'condition' => 'Excellent', 'purchase_price' => 50000, 'sale_price' => 90000,
            'customer_id' => $customer->id,
            'payment_method' => 'QRIS', 'sold_at' => now()->toDateTimeString(),
            'images' => [UploadedFile::fake()->image('sold-item.jpg', 200, 200)], 'cover_index' => 0,
        ])->assertCreated()->assertJsonPath('data.product.status', 'sold');

        $this->assertSame(49, $consignor->products()->where('status', 'available')->count());
        $this->actingAs($user, 'sanctum')->getJson("/api/products?consignor_id={$consignor->id}&is_draft=1&per_page=100")->assertOk()->assertJsonCount(49, 'data');
        $this->actingAs($user, 'sanctum')->getJson("/api/products?consignor_id={$consignor->id}&status=sold&is_draft=0&per_page=100")->assertOk()->assertJsonCount(1, 'data');
        $this->assertDatabaseHas('products', ['id' => $draft->id, 'is_draft' => false, 'brand_id' => null, 'selling_price' => 90000]);
        $this->assertDatabaseHas('sales', ['product_id' => $draft->id, 'customer_name' => 'Customer One', 'customer_phone' => '08987654321', 'payment_method' => 'QRIS']);

        $this->actingAs($user, 'sanctum')->getJson('/api/payouts/outstanding')
            ->assertOk()->assertJsonPath('data.0.consignor.id', $consignor->id)
            ->assertJsonPath('data.0.total_amount', 50000)
            ->assertJsonPath('data.0.total_sale_value', 90000)
            ->assertJsonPath('data.0.items.0.customer_name', 'Customer One');

        $payout = $this->actingAs($user, 'sanctum')->postJson('/api/payouts', ['consignor_id' => $consignor->id]);
        $payout->assertCreated()->assertJsonPath('data.amount', 50000)->assertJsonPath('data.items_count', 1);
        $this->assertDatabaseHas('sales', ['product_id' => $draft->id, 'payout_id' => $payout->json('data.id')]);
        $this->actingAs($user, 'sanctum')->getJson('/api/payouts/outstanding')->assertJsonCount(0, 'data');
        $this->actingAs($user, 'sanctum')->postJson('/api/payouts', ['consignor_id' => $consignor->id])->assertUnprocessable();
    }

    public function test_selected_customer_is_saved_once_with_complete_purchase_history(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $consignor = Consignor::create(['name' => 'Customer Test Consignor']);
        $this->actingAs($user, 'sanctum')->postJson("/api/consignors/{$consignor->id}/intake", ['quantity' => 2])->assertCreated();
        $customerResponse = $this->actingAs($user, 'sanctum')->postJson('/api/customers', ['name' => 'Customer Selected', 'phone' => '0812-3456-7890']);
        $customerResponse->assertCreated();
        $customerId = $customerResponse->json('data.id');

        foreach ($consignor->products()->orderBy('id')->get() as $index => $draft) {
            $this->actingAs($user, 'sanctum')->postJson("/api/products/{$draft->id}/complete-sale", [
                'name' => 'Customer Product '.($index + 1), 'category_id' => Category::firstOrFail()->id,
                'condition' => 'Good', 'purchase_price' => 50000, 'sale_price' => 80000 + ($index * 10000),
                'customer_id' => $customerId, 'payment_method' => 'Transfer',
                'images' => [UploadedFile::fake()->image("customer-product-{$index}.jpg")],
            ])->assertCreated();
        }

        $this->assertDatabaseCount('customers', 1);
        $customer = Customer::firstOrFail();
        $this->assertSame('081234567890', $customer->phone);
        $this->assertSame('Customer Selected', $customer->name);
        $this->assertSame(2, $customer->sales()->count());
        $this->actingAs($user, 'sanctum')->getJson("/api/customers/{$customer->id}")
            ->assertOk()->assertJsonPath('data.purchases_count', 2)
            ->assertJsonPath('data.total_spent', 170000)
            ->assertJsonCount(2, 'data.purchases');
    }

    public function test_one_payment_sells_five_cards_from_different_consignors(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'admin@claviar.test')->firstOrFail();
        $customer = $this->actingAs($user, 'sanctum')->postJson('/api/customers', ['name' => 'No Phone Customer'])->assertCreated()->json('data');
        $a = Consignor::create(['name' => 'Consignor A']);
        $b = Consignor::create(['name' => 'Consignor B']);
        $this->actingAs($user, 'sanctum')->postJson("/api/consignors/{$a->id}/intake", ['quantity' => 2])->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson("/api/consignors/{$b->id}/intake", ['quantity' => 4])->assertCreated();
        $ids = Product::whereIn('consignor_id', [$a->id, $b->id])->pluck('id')->all();
        $order = $this->actingAs($user, 'sanctum')->postJson('/api/orders', ['customer_id' => $customer['id'], 'product_ids' => $ids]);
        $order->assertCreated()->assertJsonPath('data.status', 'pending');
        $orderId = $order->json('data.id');
        $removedId = array_shift($ids);
        $this->actingAs($user, 'sanctum')->deleteJson("/api/orders/{$orderId}/items/{$removedId}")->assertOk();
        $this->assertDatabaseHas('products', ['id' => $removedId, 'status' => 'available', 'is_draft' => true]);
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, Product::where('status', 'reserved')->count());
        foreach (Product::whereIn('id', $ids)->get() as $index => $product) {
            $this->actingAs($user, 'sanctum')->postJson("/api/orders/{$orderId}/items/{$product->id}", ['name' => 'Order Item '.($index + 1), 'purchase_price' => 50000, 'sale_price' => 80000])->assertOk();
        }
        $this->assertDatabaseCount('sales', 0);
        $this->actingAs($user, 'sanctum')->getJson('/api/consignors?search=Consignor%20A')
            ->assertOk()->assertJsonPath('data.0.products_count', 2)
            ->assertJsonPath('data.0.stock_count', 2)->assertJsonPath('data.0.sold_count', 0);
        $this->actingAs($user, 'sanctum')->postJson("/api/orders/{$orderId}/pay", ['payment_method' => 'QRIS'])->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertSame(5, Sale::where('order_id', $orderId)->count());
        $this->assertSame(5, Product::whereIn('id', $ids)->where('status', 'sold')->count());
        $this->actingAs($user, 'sanctum')->getJson('/api/consignors?search=Consignor%20A')
            ->assertOk()->assertJsonPath('data.0.products_count', 2)
            ->assertJsonPath('data.0.stock_count', 1)->assertJsonPath('data.0.sold_count', 1);
        $this->actingAs($user, 'sanctum')->getJson("/api/customers?consignor_id={$a->id}")
            ->assertOk()->assertJsonPath('data.0.id', $customer['id'])
            ->assertJsonPath('data.0.purchases_count', 1)->assertJsonPath('data.0.total_spent', 80000);
        $this->actingAs($user, 'sanctum')->getJson("/api/customers?consignor_id={$b->id}")
            ->assertOk()->assertJsonPath('data.0.purchases_count', 4)->assertJsonPath('data.0.total_spent', 320000);

        $secondOrder = $this->actingAs($user, 'sanctum')->postJson('/api/orders', ['customer_id' => $customer['id'], 'product_ids' => [$removedId]])
            ->assertCreated();
        $secondOrderId = $secondOrder->json('data.id');
        $this->actingAs($user, 'sanctum')->postJson("/api/orders/{$secondOrderId}/items/{$removedId}", ['name' => 'Second Checkout Item', 'purchase_price' => 40000, 'sale_price' => 70000])->assertOk();
        $this->actingAs($user, 'sanctum')->postJson("/api/orders/{$secondOrderId}/pay", ['payment_method' => 'Cash'])->assertOk();
        $checkoutResponse = $this->actingAs($user, 'sanctum')->getJson('/api/checkouts')->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing([1, 5], collect($checkoutResponse->json('data'))->pluck('items_count')->all());
        $this->assertEqualsCanonicalizing([70000, 400000], collect($checkoutResponse->json('data'))->pluck('total_amount')->all());
        $this->assertDatabaseHas('customers',['id' => $customer['id'], 'phone' => null]);
    }
}
