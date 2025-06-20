<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->products;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'image_url' => 'nullable|url',
        ]);

        $product = Product::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'image_url' => $request->image_url,
            'status' => 'Created',
        ]);

        $this->syncToWooCommerce($product);

        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'image_url' => 'nullable|url',
        ]);

        $product->update($request->only(['name', 'description', 'price', 'image_url']));
        $product->status = 'Created';
        $product->save();

        $this->syncToWooCommerce($product);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        if ($product->wc_product_id) {
            try {
                Http::withBasicAuth(
                    env('WOOCOMMERCE_CONSUMER_KEY'),
                    env('WOOCOMMERCE_CONSUMER_SECRET')
                )->delete(env('WOOCOMMERCE_URL') . "/wp-json/wc/v3/products/{$product->wc_product_id}");
            } catch (\Exception $e) {
                Log::error('WooCommerce delete failed: ' . $e->getMessage());
            }
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    private function syncToWooCommerce(Product $product)
    {
        try {
            $data = [
                'name' => $product->name,
                'type' => 'simple',
                'regular_price' => (string) $product->price,
                'description' => $product->description,
                'images' => [['src' => $product->image_url]],
            ];

            $url = env('WOOCOMMERCE_URL') . '/wp-json/wc/v3/products';

            if ($product->wc_product_id) {
                $url .= "/{$product->wc_product_id}";
                $response = Http::withBasicAuth(
                    env('WOOCOMMERCE_CONSUMER_KEY'),
                    env('WOOCOMMERCE_CONSUMER_SECRET')
                )->put($url, $data);
            } else {
                $response = Http::withBasicAuth(
                    env('WOOCOMMERCE_CONSUMER_KEY'),
                    env('WOOCOMMERCE_CONSUMER_SECRET')
                )->post($url, $data);
            }

            if ($response->successful()) {
                $product->update([
                    'status' => 'Synced',
                    'wc_product_id' => $response->json('id'),
                ]);
            } else {
                $product->update(['status' => 'Failed']);
                Log::error('WooCommerce sync failed', ['response' => $response->body()]);
            }
        } catch (\Exception $e) {
            $product->update(['status' => 'Failed']);
            Log::error('WooCommerce sync exception: ' . $e->getMessage());
        }
    }
}


