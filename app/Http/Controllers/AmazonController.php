<?php

namespace App\Http\Controllers;

use App\Jobs\Amazon\BulkUploadAmazonJob;
use App\Jobs\Amazon\ImportAmazonOrdersJob;
use App\Jobs\Amazon\SyncAmazonInventoryJob;
use App\Jobs\Amazon\SyncAmazonPriceJob;
use App\Jobs\Amazon\UploadProductToAmazonJob;
use App\Models\AmazonAccount;
use App\Models\AmazonCategoryMapping;
use App\Models\AmazonOrder;
use App\Models\AmazonProduct;
use App\Models\AmazonSyncLog;
use App\Models\AmazonToken;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class AmazonController extends Controller
{
    // ─── Dashboard / Settings ────────────────────────────────────────────────

    public function index()
    {
        $account  = AmazonAccount::first();
        $stats    = [
            'total'    => AmazonProduct::count(),
            'active'   => AmazonProduct::where('status', 'active')->count(),
            'pending'  => AmazonProduct::where('status', 'pending')->count(),
            'error'    => AmazonProduct::where('status', 'error')->count(),
            'orders'   => AmazonOrder::count(),
        ];
        return view('backend.amazon.index', compact('account', 'stats'));
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'seller_id'         => 'required|string|max:100',
            'marketplace_id'    => 'required|string|max:50',
            'lwa_client_id'     => 'required|string',
            'lwa_client_secret' => 'required|string',
            'aws_access_key'    => 'nullable|string',
            'aws_secret_key'    => 'nullable|string',
            'refresh_token'     => 'required|string',
        ]);

        $account = AmazonAccount::updateOrCreate(
            ['seller_id' => $validated['seller_id']],
            array_except($validated, ['refresh_token'])
        );

        AmazonToken::updateOrCreate(
            ['account_id' => $account->id],
            ['refresh_token' => $validated['refresh_token']]
        );

        flash(translate('Amazon account saved successfully.'))->success();
        return back();
    }

    // ─── Category Mapping ────────────────────────────────────────────────────

    public function categoryMapping()
    {
        $categories = Category::where('level', 0)->with('categories.categories')->get();
        $mappings   = AmazonCategoryMapping::pluck('amazon_category_name', 'website_category_id');
        return view('backend.amazon.category_mapping', compact('categories', 'mappings'));
    }

    public function saveCategoryMapping(Request $request)
    {
        $mappings = $request->input('mappings', []);

        foreach ($mappings as $categoryId => $data) {
            if (empty($data['amazon_category_id'])) {
                continue;
            }
            AmazonCategoryMapping::updateOrCreate(
                ['website_category_id' => $categoryId],
                [
                    'amazon_category_id'   => $data['amazon_category_id'],
                    'amazon_category_name' => $data['amazon_category_name'] ?? '',
                    'amazon_product_type'  => $data['amazon_product_type'] ?? 'PRODUCT',
                ]
            );
        }

        flash(translate('Category mappings saved.'))->success();
        return back();
    }

    // ─── Product List ─────────────────────────────────────────────────────────

    public function products(Request $request)
    {
        $query = AmazonProduct::with('product', 'account')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('backend.amazon.product_list', ['amazonProducts' => $query]);
    }

    // ─── Single Product Upload ────────────────────────────────────────────────

    public function upload($id)
    {
        $product = Product::findOrFail($id);
        $account = AmazonAccount::where('is_active', 1)->firstOrFail();

        UploadProductToAmazonJob::dispatch($product, $account);

        flash(translate('Product queued for Amazon upload.'))->success();
        return back();
    }

    // ─── Bulk Upload ──────────────────────────────────────────────────────────

    public function bulkUpload(Request $request)
    {
        $request->validate(['product_ids' => 'required|array', 'product_ids.*' => 'integer']);

        $account = AmazonAccount::where('is_active', 1)->firstOrFail();

        BulkUploadAmazonJob::dispatch($request->product_ids, $account);

        flash(translate(count($request->product_ids) . ' products queued for Amazon upload.'))->success();
        return back();
    }

    // ─── Deactivate Listing ───────────────────────────────────────────────────

    public function deactivate($id)
    {
        $amazonProduct = AmazonProduct::findOrFail($id);

        dispatch(function () use ($amazonProduct) {
            app(\App\Services\Amazon\AmazonListingService::class)->deactivateListing($amazonProduct);
        })->afterResponse();

        flash(translate('Listing deactivation queued.'))->success();
        return back();
    }

    // ─── Sync ─────────────────────────────────────────────────────────────────

    public function syncInventory(Request $request)
    {
        $request->validate(['amazon_product_id' => 'required|exists:amazon_products,id']);
        $amazonProduct = AmazonProduct::findOrFail($request->amazon_product_id);

        SyncAmazonInventoryJob::dispatch($amazonProduct);

        flash(translate('Inventory sync queued.'))->success();
        return back();
    }

    public function syncPrice(Request $request)
    {
        $request->validate(['amazon_product_id' => 'required|exists:amazon_products,id']);
        $amazonProduct = AmazonProduct::findOrFail($request->amazon_product_id);

        SyncAmazonPriceJob::dispatch($amazonProduct);

        flash(translate('Price sync queued.'))->success();
        return back();
    }

    // ─── Orders ───────────────────────────────────────────────────────────────

    public function orders(Request $request)
    {
        $orders = AmazonOrder::with('account')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('backend.amazon.orders', compact('orders'));
    }

    public function importOrders()
    {
        $account = AmazonAccount::where('is_active', 1)->firstOrFail();

        ImportAmazonOrdersJob::dispatch($account);

        flash(translate('Order import queued.'))->success();
        return back();
    }

    // ─── Logs ─────────────────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $logs = AmazonSyncLog::with('product')
            ->when($request->action, fn($q) => $q->where('action', $request->action))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(30);

        return view('backend.amazon.logs', compact('logs'));
    }
}
