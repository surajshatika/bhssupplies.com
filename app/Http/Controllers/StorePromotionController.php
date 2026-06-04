<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\StorePromotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StorePromotionController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // Admin
    // ──────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');

        $promotions = StorePromotion::query()
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('section', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('backend.marketing.store_promotions.index', [
            'promotions'  => $promotions,
            'sort_search' => $search,
        ]);
    }

    public function create()
    {
        return view('backend.marketing.store_promotions.create');
    }

    public function store(Request $request)
    {
        $this->validateBlock($request);

        StorePromotion::create($this->payload($request));

        flash(translate('Promotion has been added successfully'))->success();
        return redirect()->route('store_promotions.index');
    }

    public function edit($id)
    {
        $promotion = StorePromotion::findOrFail($id);
        return view('backend.marketing.store_promotions.edit', compact('promotion'));
    }

    public function update(Request $request, $id)
    {
        $promotion = StorePromotion::findOrFail($id);
        $this->validateBlock($request);

        $promotion->update($this->payload($request));

        flash(translate('Promotion has been updated successfully'))->success();
        return redirect()->route('store_promotions.index');
    }

    /** A banner block needs an image; a content block needs HTML content. */
    protected function validateBlock(Request $request): void
    {
        if ($request->input('type') === 'content') {
            $request->validate(['content' => 'required'], ['content.required' => translate('Please add some content.')]);
        } else {
            $request->validate(['image' => 'required'], ['image.required' => translate('Please choose a promotion image.')]);
        }
    }

    /** Persist a new top-to-bottom order from the admin drag & drop list. */
    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids)) {
            return response()->json(['success' => false], 422);
        }
        foreach (array_values($ids) as $position => $id) {
            StorePromotion::where('id', (int) $id)->update(['sort_order' => $position]);
        }
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        StorePromotion::where('id', $id)->delete();

        flash(translate('Promotion has been deleted successfully'))->success();
        return redirect()->route('store_promotions.index');
    }

    /** Clone an existing block (same image/content/settings) as a new draft-able copy. */
    public function duplicate($id)
    {
        $original = StorePromotion::findOrFail($id);

        $copy = $original->replicate();
        $copy->title      = trim(($original->title ?: translate('Promotion')) . ' (' . translate('Copy') . ')');
        $copy->sort_order = (int) StorePromotion::max('sort_order') + 1;
        $copy->save();

        flash(translate('Promotion duplicated successfully'))->success();
        return redirect()->route('store_promotions.edit', $copy->id);
    }

    public function updateStatus(Request $request)
    {
        $promotion = StorePromotion::find($request->input('id'));
        if (!$promotion) {
            return 0;
        }
        $promotion->published = (int) $request->input('status') === 1;
        $promotion->save();

        return 1;
    }

    protected function payload(Request $request): array
    {
        $type = in_array($request->input('type'), ['banner', 'content'], true) ? $request->input('type') : 'banner';

        return [
            'type'       => $type,
            'title'      => $request->input('title'),
            'subtitle'   => $request->input('subtitle'),
            'image'      => $type === 'banner' ? $request->input('image') : null,
            'content'    => $type === 'content' ? $request->input('content') : null,
            'link_url'   => $request->input('link_url'),
            'section'    => $request->input('section'),
            'width'      => in_array($request->input('width'), ['full', 'half', 'third'], true) ? $request->input('width') : 'full',
            'sort_order' => (int) $request->input('sort_order', 0),
            'featured'   => $request->boolean('featured'),
            'published'  => $request->has('published') ? $request->boolean('published') : true,
            'starts_at'  => $request->input('starts_at') ?: null,
            'ends_at'    => $request->input('ends_at') ?: null,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Store Page Settings (like Homepage Settings)
    // ──────────────────────────────────────────────────────────────────────

    public function settings()
    {
        return view('backend.marketing.store_promotions.settings');
    }

    public function saveSettings(Request $request)
    {
        $pairs = [
            'store_page_enabled'          => $request->boolean('enabled') ? 1 : 0,
            'store_page_show_breadcrumb'  => $request->boolean('show_breadcrumb') ? 1 : 0,
            'store_page_heading'          => $request->input('heading'),
            'store_page_subheading'       => $request->input('subheading'),
            'store_page_intro'            => $request->input('intro'),
            'store_page_meta_title'       => $request->input('meta_title'),
            'store_page_meta_description' => $request->input('meta_description'),
        ];

        foreach ($pairs as $type => $value) {
            $this->putSetting($type, $value);
        }

        Cache::forget('business_settings');

        flash(translate('Store page settings updated successfully'))->success();
        return redirect()->route('store_promotions.settings');
    }

    protected function putSetting(string $type, $value): void
    {
        $setting = BusinessSetting::firstOrNew(['type' => $type]);
        $setting->value = $value;
        $setting->save();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public store page
    // ──────────────────────────────────────────────────────────────────────

    public function publicIndex()
    {
        if ((int) get_setting('store_page_enabled', 1) !== 1) {
            abort(404);
        }

        // Top-to-bottom ordered blocks (banners + content), exactly as arranged
        // in the admin drag-and-drop manager.
        $blocks = StorePromotion::activeNow()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('frontend.store_promotions.index', compact('blocks'));
    }
}
