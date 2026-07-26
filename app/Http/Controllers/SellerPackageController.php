<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SellerPackage;
use App\Models\SellerPackagePayment;
use App\Models\SellerPackageTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class SellerPackageController extends Controller
{
    public function index()
    {
        $seller_packages = SellerPackage::all();

        if (View::exists('seller_packages.index')) {
            return view('seller_packages.index', compact('seller_packages'));
        }

        flash(translate('Seller package views are not installed.'))->warning();
        return back();
    }

    public function create()
    {
        if (View::exists('seller_packages.create')) {
            return view('seller_packages.create');
        }

        flash(translate('Seller package views are not installed.'))->warning();
        return back();
    }

    public function store(Request $request)
    {
        if ($request->amount == 0 && SellerPackage::where('amount', 0)->exists()) {
            flash(translate('You cannot Add more than one Free package'))->error();
            return back();
        }

        $seller_package = new SellerPackage;
        $seller_package->name = $request->name;
        $seller_package->amount = $request->amount;
        $seller_package->product_upload_limit = $request->product_upload_limit;
        $seller_package->duration = $request->duration;
        $seller_package->logo = $request->logo;
        $seller_package->save();

        SellerPackageTranslation::updateOrCreate(
            ['lang' => env('DEFAULT_LANGUAGE'), 'seller_package_id' => $seller_package->id],
            ['name' => $request->name]
        );

        flash(translate('Package has been inserted successfully'))->success();
        return redirect()->route('seller_packages.index');
    }

    public function edit(Request $request, $id)
    {
        $lang = $request->lang;
        $seller_package = SellerPackage::findOrFail($id);

        if (View::exists('seller_packages.edit')) {
            return view('seller_packages.edit', compact('seller_package', 'lang'));
        }

        flash(translate('Seller package views are not installed.'))->warning();
        return back();
    }

    public function update(Request $request, $id)
    {
        $seller_package = SellerPackage::findOrFail($id);

        if ($request->amount == 0 && SellerPackage::where('amount', 0)->where('id', '!=', $id)->exists()) {
            flash(translate('You cannot Add more than one Free package'))->error();
            return back();
        }

        if ($request->lang == env('DEFAULT_LANGUAGE')) {
            $seller_package->name = $request->name;
        }

        $seller_package->amount = $request->amount;
        $seller_package->product_upload_limit = $request->product_upload_limit;
        $seller_package->duration = $request->duration;
        $seller_package->logo = $request->logo;
        $seller_package->save();

        SellerPackageTranslation::updateOrCreate(
            ['lang' => $request->lang, 'seller_package_id' => $seller_package->id],
            ['name' => $request->name]
        );

        flash(translate('Package has been updated successfully'))->success();
        return back();
    }

    public function destroy($id)
    {
        $seller_package = SellerPackage::findOrFail($id);
        $seller_package->seller_package_translations()->delete();
        $seller_package->delete();

        flash(translate('Package has been deleted successfully'))->success();
        return redirect()->route('seller_packages.index');
    }

    public function seller_packages_list()
    {
        $seller_packages = SellerPackage::all();

        foreach (['seller_packages.seller.index', 'seller_packages.seller.packages', 'seller_packages.index'] as $view) {
            if (View::exists($view)) {
                return view($view, compact('seller_packages'));
            }
        }

        flash(translate('Seller package views are not installed.'))->warning();
        return redirect()->route('seller.dashboard');
    }

    public function packages_payment_list()
    {
        $package_payment_list = SellerPackagePayment::where('user_id', Auth::id())->latest()->paginate(10);

        foreach (['seller_packages.seller.payment_history', 'seller_packages.payment_history'] as $view) {
            if (View::exists($view)) {
                return view($view, compact('package_payment_list'));
            }
        }

        flash(translate('Seller package payment history view is not installed.'))->warning();
        return redirect()->route('seller.dashboard');
    }

    public function purchase_package(Request $request)
    {
        $data = [
            'seller_package_id' => $request->seller_package_id ?? $request->package_id,
            'payment_method' => $request->payment_option,
        ];

        Session::put('payment_type', 'seller_package_payment');
        Session::put('payment_data', $data);

        $seller_package = SellerPackage::findOrFail($data['seller_package_id']);

        if ($seller_package->amount == 0) {
            return $this->purchase_payment_done($data, null);
        }

        if ($this->isDowngradeBlocked($seller_package)) {
            flash(translate('You have more uploaded products than this package limit. You need to remove excessive products to downgrade.'))->warning();
            return back();
        }

        $decorator = __NAMESPACE__ . '\\Payment\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $request->payment_option))) . 'Controller';

        if (class_exists($decorator)) {
            return (new $decorator)->pay($request);
        }

        flash(translate('Selected payment option is not available.'))->error();
        return back();
    }

    public function purchase_payment_done($payment_data, $payment = null)
    {
        $data = is_array($payment_data) ? $payment_data : (array) $payment_data;
        $seller_package_id = $data['seller_package_id'] ?? $data['package_id'] ?? null;
        $payment_method = $data['payment_method'] ?? $data['payment_option'] ?? 'Manual';
        $user_id = $data['user_id'] ?? Auth::id();

        seller_purchase_payment_done($user_id, $seller_package_id, $payment_method, $payment);

        flash(translate('Package purchasing successful'))->success();
        return redirect()->route('seller.dashboard');
    }

    public function purchase_package_offline(Request $request)
    {
        $seller_package = SellerPackage::findOrFail($request->package_id);

        if ($this->isDowngradeBlocked($seller_package)) {
            flash(translate('You have more uploaded products than this package limit. You need to remove excessive products to downgrade.'))->warning();
            return back();
        }

        $package_payment = new SellerPackagePayment;
        $package_payment->user_id = Auth::id();
        $package_payment->seller_package_id = $request->package_id;
        $package_payment->amount = $seller_package->amount;
        $package_payment->payment_method = $request->payment_option;
        $package_payment->payment_details = $request->trx_id;
        $package_payment->approval = 0;
        $package_payment->offline_payment = 1;
        $package_payment->reciept = $request->photo ?: '';
        $package_payment->save();

        flash(translate('Offline payment has been done. Please wait for response.'))->success();
        return redirect()->route('seller.seller_packages_list');
    }

    public function unpublish_products()
    {
        $shop = Auth::user()->shop ?? null;

        if (!$shop || !$shop->seller_package) {
            return 0;
        }

        $limit = (int) $shop->seller_package->product_upload_limit;
        $products = Product::where('user_id', Auth::id())->latest()->get();

        foreach ($products->skip($limit) as $product) {
            $product->published = 0;
            $product->save();
        }

        return 1;
    }

    protected function isDowngradeBlocked(SellerPackage $seller_package)
    {
        $current_package = optional(optional(Auth::user())->shop)->seller_package;

        return $current_package != null && $seller_package->product_upload_limit < $current_package->product_upload_limit;
    }
}
