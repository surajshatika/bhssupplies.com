<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Console\Commands\EnhanceProductDescriptions;
use Illuminate\Http\Request;

class DescriptionEnhancerController extends Controller
{
    /** Show the enhancer page */
    public function index()
    {
        $stats = [
            'total'      => Product::count(),
            'with_desc'  => Product::whereNotNull('description')->where('description', '!=', '')->count(),
            'has_table'  => Product::where('description', 'like', '%<table%')->count(),
            'has_list'   => Product::where('description', 'like', '%<ul%')->count(),
        ];
        return view('backend.tools.description_enhancer', compact('stats'));
    }

    /**
     * Process one batch of products (called via AJAX polling).
     * Returns JSON with progress so the browser can show a live bar.
     */
    public function processBatch(Request $request)
    {
        $batchSize = 20;
        $offset    = (int) $request->input('offset', 0);

        $products = Product::whereNotNull('description')
                           ->where('description', '!=', '')
                           ->orderBy('id')
                           ->skip($offset)
                           ->take($batchSize)
                           ->get();

        $updated = 0;
        foreach ($products as $product) {
            $original = $product->description;
            $enhanced = EnhanceProductDescriptions::enhanceHtml($original);
            if ($enhanced !== $original) {
                $product->description = $enhanced;
                $product->save();
                $updated++;
            }
        }

        $total = Product::whereNotNull('description')->where('description', '!=', '')->count();

        return response()->json([
            'processed' => $offset + $products->count(),
            'total'     => $total,
            'updated'   => $updated,
            'done'      => $products->count() < $batchSize,
        ]);
    }
}
