<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\Order;
use App\Models\Cart;
use App\Utility\SupportBoardUtility;
use Cache;
use Auth;

class SupportBoardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin'])->only([
            'settings', 'updateSettings', 'importAdmins', 'syncSellers',
        ]);
    }

    // ─── Admin ────────────────────────────────────────────────────────────────

    public function settings()
    {
        return view('backend.support_board.settings');
    }

    public function updateSettings(Request $request)
    {
        $fields = [
            'support_board_status',
            'support_board_url',
            'support_board_secret',
            'support_board_chat_visibility',
            'support_board_tickets_status',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $setting = BusinessSetting::firstOrNew(['type' => $field]);
                $setting->value = $request->input($field);
                $setting->save();
            }
        }

        Cache::forget('business_settings');

        flash(translate('Support Board settings updated successfully'))->success();
        return redirect()->route('support_board.settings');
    }

    public function updateActivation(Request $request)
    {
        $setting = BusinessSetting::firstOrNew(['type' => $request->type]);
        $setting->value = $request->value;
        $setting->save();
        Cache::forget('business_settings');
        return '1';
    }

    public function importAdmins()
    {
        $result = SupportBoardUtility::importAdmins();

        if ($result['success']) {
            flash(translate($result['message']))->success();
        } else {
            flash(translate($result['message']))->error();
        }

        return redirect()->route('support_board.settings');
    }

    public function syncSellers()
    {
        $result = SupportBoardUtility::syncSellers();

        if ($result['success']) {
            flash(translate($result['message']))->success();
        } else {
            flash(translate($result['message']))->error();
        }

        return redirect()->route('support_board.settings');
    }

    // ─── API – called by Support Board "Active eCommerce" app ─────────────────

    public function shopDetails(Request $request)
    {
        $secret = get_setting('support_board_secret');
        if (!$secret || $request->header('SB-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $email = $request->input('email');
        if (!$email) {
            return response()->json(['error' => 'Email required'], 422);
        }

        $user = User::where('email', $email)->where('user_type', 'customer')->first();
        if (!$user) {
            return response()->json(['orders' => [], 'cart' => [], 'total_spend' => 0, 'total_orders' => 0]);
        }

        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($order) {
                return [
                    'id'             => $order->id,
                    'code'           => $order->code,
                    'date'           => date('D M d Y g:i a', $order->date),
                    'grand_total'    => format_price($order->grand_total),
                    'payment_status' => $order->payment_status,
                    'delivery_status'=> $order->delivery_status ?? 'pending',
                    'url'            => route('purchase_history.details', $order->id),
                ];
            });

        $totalSpend = Order::where('user_id', $user->id)
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'id'       => $item->product_id,
                    'name'     => $item->product->name ?? 'N/A',
                    'quantity' => $item->quantity,
                    'price'    => format_price($item->price),
                ];
            });

        return response()->json([
            'total_orders' => $orders->count(),
            'total_spend'  => format_price($totalSpend),
            'orders'       => $orders,
            'cart'         => $cartItems,
        ]);
    }

    // ─── Frontend ─────────────────────────────────────────────────────────────

    public function chat()
    {
        if (!addon_is_activated('active_ecommerce_support_board') || !get_setting('support_board_status')) {
            abort(404);
        }
        return view('frontend.support_board.chat');
    }

    public function tickets()
    {
        if (!addon_is_activated('active_ecommerce_support_board') || !get_setting('support_board_tickets_status')) {
            abort(404);
        }
        if (!Auth::check()) {
            return redirect()->route('user.login');
        }
        return view('frontend.support_board.tickets');
    }

    // ─── Login from chat widget ────────────────────────────────────────────────

    public function chatLogin(Request $request)
    {
        $secret = get_setting('support_board_secret');
        if (!$secret || $request->input('secret') !== $secret) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            SupportBoardUtility::syncUser($user);
            return response()->json([
                'success' => true,
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
    }
}
