<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Customer, Order, Product, Sale};
use App\Services\ProductImageService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class OrderController extends Controller
{
    public function __construct(private ProductImageService $images) {}
    public function index(Request $request): JsonResponse { abort_unless($request->user()->can('manage sales'),403); return response()->json(['data'=>Order::with(['customer','items.product.consignor','items.product.images'])->latest()->limit(50)->get()]); }
    public function show(Request $request, Order $order): JsonResponse { abort_unless($request->user()->can('manage sales'),403); return response()->json(['data'=>$order->load(['customer','items.product.consignor','items.product.category','items.product.brand','items.product.images'])]); }
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('manage sales'),403);
        $data=$request->validate(['customer_id'=>['required','exists:customers,id'],'product_ids'=>['required','array','min:1','max:50'],'product_ids.*'=>['integer','distinct','exists:products,id']]);
        $order=DB::transaction(function() use($data){
            $products=Product::whereIn('id',$data['product_ids'])->lockForUpdate()->get();
            abort_if($products->count()!==count($data['product_ids'])||$products->contains(fn($p)=>!$p->is_draft||$p->status!=='available'),422,'Satu atau lebih kartu sudah tidak tersedia.');
            $order=Order::create(['code'=>'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),'customer_id'=>$data['customer_id'],'status'=>'pending']);
            foreach($products as $product){$order->items()->create(['product_id'=>$product->id]);$product->update(['status'=>'reserved']);}
            return $order;
        });
        return response()->json(['message'=>'Order pending berhasil dibuat.','data'=>$order->load(['customer','items.product.consignor'])],201);
    }
    public function completeItem(Request $request, Order $order, Product $product): JsonResponse
    {
        abort_unless($request->user()->can('manage sales'),403); abort_unless($order->status==='pending',422,'Order sudah selesai.');
        $data=$request->validate(['name'=>['required','string','max:255'],'description'=>['nullable','string'],'purchase_price'=>['required','numeric','min:0'],'sale_price'=>['required','numeric','gte:purchase_price'],'images'=>['nullable','array','max:10'],'images.*'=>['image','max:10240'],'cover_index'=>['nullable','integer','min:0']]);
        $item=$order->items()->where('product_id',$product->id)->firstOrFail();
        DB::transaction(function() use($request,$data,$product,$item,$order){$product->update(['name'=>$data['name'],'category_id'=>null,'brand_id'=>null,'condition'=>null,'description'=>$data['description']??null,'purchase_price'=>$data['purchase_price'],'selling_price'=>$data['sale_price'],'is_draft'=>false]);if($request->hasFile('images')){$this->images->store($product,$request->file('images'),$request->integer('cover_index'));}$item->update(['purchase_price'=>$data['purchase_price'],'sale_price'=>$data['sale_price'],'completed_at'=>now()]);$order->update(['total_amount'=>$order->items()->sum('sale_price')]);});
        return response()->json(['message'=>'Detail kartu berhasil disimpan.','data'=>$order->fresh()->load(['customer','items.product.consignor','items.product.images'])]);
    }
    public function pay(Request $request, Order $order): JsonResponse
    {
        abort_unless($request->user()->can('manage sales'),403);$data=$request->validate(['payment_method'=>['required','string','max:50']]);
        DB::transaction(function() use($order,$data){$order=Order::with(['items.product','customer'])->lockForUpdate()->findOrFail($order->id);abort_unless($order->status==='pending',422,'Order bukan pending.');abort_if($order->items->contains(fn($i)=>!$i->completed_at),422,'Lengkapi semua detail kartu sebelum pembayaran.');foreach($order->items as $item){$item->product->update(['status'=>'sold','is_draft'=>false]);Sale::create(['order_id'=>$order->id,'product_id'=>$item->product_id,'customer_id'=>$order->customer_id,'customer_name'=>$order->customer->name,'customer_phone'=>$order->customer->phone,'sale_price'=>$item->sale_price,'payment_method'=>$data['payment_method'],'sold_at'=>now()]);}$order->update(['status'=>'paid','payment_method'=>$data['payment_method'],'paid_at'=>now(),'total_amount'=>$order->items->sum('sale_price')]);});
        return response()->json(['message'=>'Pembayaran berhasil. Semua kartu menjadi sold.','data'=>$order->fresh()->load(['customer','items.product'])]);
    }
    public function removeItem(Request $request, Order $order, Product $product): JsonResponse
    {
        abort_unless($request->user()->can('manage sales'),403); abort_unless($order->status==='pending',422,'Item hanya dapat dilepas dari order pending.');
        DB::transaction(function() use($order,$product){$item=$order->items()->where('product_id',$product->id)->firstOrFail();$this->images->delete($product);$product->images()->delete();$product->update(['name'=>null,'category_id'=>null,'brand_id'=>null,'condition'=>null,'description'=>null,'purchase_price'=>null,'selling_price'=>null,'status'=>'available','is_draft'=>true]);$item->delete();$order->update(['total_amount'=>$order->items()->sum('sale_price')]);if(!$order->items()->exists()){$order->update(['status'=>'cancelled']);}});
        return response()->json(['message'=>'Kartu dilepas dari order dan kembali tersedia.']);
    }
    public function cancel(Request $request, Order $order): JsonResponse { abort_unless($request->user()->can('manage sales'),403);abort_unless($order->status==='pending',422,'Order tidak dapat dibatalkan.');DB::transaction(function() use($order){foreach($order->items()->with('product')->get() as $item){$item->product->update(['status'=>'available']);}$order->update(['status'=>'cancelled']);});return response()->json(['message'=>'Order dibatalkan.']); }
}
