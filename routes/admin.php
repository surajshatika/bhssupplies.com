<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\Report\EarningReportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\AizUploadController;
use App\Http\Controllers\StorePromotionController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandBulkUploadController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomAlertController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPackageController;
use App\Http\Controllers\CustomerProductController;
use App\Http\Controllers\CustomLabelController;
use App\Http\Controllers\CustomSaleAlertController;
use App\Http\Controllers\DigitalProductController;
use App\Http\Controllers\DynamicPopupController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\FlashDealController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MeasurementPointsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTypeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickupPointController;
use App\Http\Controllers\ProductBulkUploadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductQueryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerWithdrawRequestController;
use App\Http\Controllers\SizeChartController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TopBannerController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\Cybersource\CybersourceSettingController;
use App\Http\Controllers\ElementController;
use App\Http\Controllers\FinalUpdateController;
use App\Http\Controllers\NewUpdateController;
use App\Http\Controllers\PickupController;
use App\Http\Controllers\ShippingBoxSizeController;
use App\Http\Controllers\ShippingSystemController;
use App\Http\Controllers\Seo\AiSeoBoardController;
use App\Http\Controllers\Seo\GoogleAuthController;
use App\Http\Controllers\Seo\OnPageSeoController;
use App\Http\Controllers\Seo\OffPageSeoController;
use App\Http\Controllers\Seo\OptimizationController;
use App\Http\Controllers\Seo\SeoMonitoringController;
use App\Http\Controllers\SeoSuiteController;
use App\Http\Controllers\Import\ProductImportController;
use App\Http\Controllers\Admin\SocialMediaController;
use App\Http\Controllers\Admin\AiBlogController;
use App\Http\Controllers\AmazonController;

/*
  |--------------------------------------------------------------------------
  | Admin Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register admin routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */
//Update Routes
Route::controller(UpdateController::class)->group(function () {
    Route::post('/update', 'step0')->name('update');
    Route::get('/update/step1', 'step1')->name('update.step1');
    Route::get('/update/step2', 'step2')->name('update.step2');
    Route::get('/update/step3', 'step3')->name('update.step3');
    Route::post('/purchase_code', 'purchase_code')->name('update.code');
});

Route::get('/admin', [AdminController::class, 'admin_dashboard'])->name('admin.dashboard')->middleware(['auth', 'admin', 'prevent-back-history']);
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin', 'prevent-back-history']], function () {

    // cyber sources
    Route::controller(CybersourceSettingController::class)->group(function () {
        Route::get('/cybersource-configuration', 'configuration')->name('cybersource_configuration');
    });
    
    // category
    Route::resource('categories', CategoryController::class);
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories/edit/{id}', 'edit')->name('categories.admin.edit');
        Route::get('/categories/destroy/{id}', 'destroy')->name('categories.admin.destroy');
        Route::post('/categories/featured', 'updateFeatured')->name('categories.featured');
        Route::post('/categories/hot', 'updateHot')->name('categories.hot');
        Route::post('/categories/categoriesByType', 'categoriesByType')->name('categories.categories-by-type');
      
        //category-wise commission
        Route::get('/categories-wise-commission', 'categoriesWiseCommission')->name('categories_wise_commission');
        Route::post('/categories-wise-commission', 'categoriesWiseCommissionUpdate')->name('categories_wise_commission.update');

        // category-wise discount set
        Route::get('/categories-wise-product-discount', 'categoriesWiseProductDiscount')->name('categories_wise_product_discount');

        Route::get('/categories/filter/categories', 'get_categories_by_filter')->name('categories.filter');
        Route::post('/bulk-categories-delete', 'bulk_categories_delete')->name('bulk-categories-delete');
        Route::get('/categories/details/{id}', 'category_details')->name('categories.details');
        Route::post('/bulk-categories-featured', 'bulk_categories_featured')->name('bulk-categories-featured');
        Route::post('/bulk-categories-hot', 'bulk_categories_hot')->name('bulk-categories-hot');
    });

    // Brand
    Route::resource('brands', BrandController::class)->except(['edit', 'destroy']);
    Route::controller(BrandController::class)->group(function () {
        Route::get('/brands/edit/{id}', 'edit')->name('brands.edit');
        Route::get('/brands/destroy/{id}', 'destroy')->name('brands.destroy');
        Route::get('/brands/filter/brands', 'get_brands_by_filter')->name('brands.filter');
        Route::post('/bulk-brands-delete', 'bulk_brands_delete')->name('bulk-brands-delete');
        Route::get('/brand_category/show/{id}', 'showCategories')->name('brand_category.show');
    });

    // Warranty
    Route::resource('warranties', WarrantyController::class)->except(['edit', 'destroy']);
    Route::controller(WarrantyController::class)->group(function () {
        Route::get('/warranties/edit/{id}', 'edit')->name('warranties.edit');
        Route::get('/warranties/destroy/{id}', 'destroy')->name('warranties.destroy');
    });

    // custom label
    Route::controller(CustomLabelController::class)->group(function () {
        Route::get('/custom-label-list', 'index')->name('custom_label.index');
        Route::get('/custom-label-create', 'create')->name('custom_label.create');
        Route::post('/custom-label-store', 'store')->name('custom_label.store');
        Route::get('/custom-label-edit/{id}', 'edit')->name('custom_label.edit');
        Route::post('/custom-label-update/{id}', 'update')->name('custom_label.update');
        Route::get('/custom-label-delete/{id}', 'destroy')->name('custom_label.delete');
        Route::post('custom-label/update-seller-access', 'updateSellerAccess')->name('custom_label.update-seller-access');
        Route::post('/custom-label/products', 'products')->name('custom_label.products');
        Route::post('/custom-label-update-status', 'update_status')->name('custom-label.update-status');
    });

    Route::controller(BrandBulkUploadController::class)->group(function () {
        Route::get('/brand-bulk-upload', 'index')->name('brand_bulk_upload.index');
        Route::post('/brand-bulk-upload/store', 'bulk_upload')->name('brand_bulk_upload');
    });

    Route::controller(AdminController::class)->group(function () {
        Route::post('/dashboard/top-category-products-section', 'top_category_products_section')->name('dashboard.top_category_products_section');
        Route::post('/dashboard/inhouse-top-brands', 'inhouse_top_brands')->name('dashboard.inhouse_top_brands');
        Route::post('/dashboard/inhouse-top-categories', 'inhouse_top_categories')->name('dashboard.inhouse_top_categories');
        Route::post('/dashboard/top-sellers-products-section', 'top_sellers_products_section')->name('dashboard.top_sellers_products_section');
        Route::post('/dashboard/top-brands-products-section', 'top_brands_products_section')->name('dashboard.top_brands_products_section');
    });

    // Products
    Route::controller(ProductController::class)->group(function () {
        Route::get('/products/admin', 'admin_products')->name('products.admin');
        Route::get('/products/seller/{product_type}', 'seller_products')->name('products.seller');
        Route::get('/products/all', 'all_products')->name('products.all');
        Route::get('/products/filter/products', 'get_filter_products')->name('products.filter');
        Route::get('/products/create', 'create')->name('products.create');
        Route::post('/products/store/', 'store')->name('products.store');
        Route::get('/products/admin/{id}/edit', 'admin_product_edit')->name('products.admin.edit');
        Route::get('/products/seller/{id}/edit', 'seller_product_edit')->name('products.seller.edit');
        Route::post('/products/update/{product}', 'update')->name('products.update');
        Route::post('/products/todays_deal', 'updateTodaysDeal')->name('products.todays_deal');
        Route::post('/products/featured', 'updateFeatured')->name('products.featured');
        Route::post('/products/published', 'updatePublished')->name('products.published');
        Route::post('/products/approved', 'updateProductApproval')->name('products.approved');
        Route::post('/products/get_products_by_subcategory', 'get_products_by_subcategory')->name('products.get_products_by_subcategory');
        Route::get('/products/get_subcategories_by_category', 'get_subcategories_by_category')->name('products.get_subcategories_by_category');
        Route::get('/products/duplicate/{id}', 'duplicate')->name('products.duplicate');
        Route::get('/products/destroy/{id}', 'destroy')->name('products.destroy');
        Route::post('/bulk-product-delete', 'bulk_product_delete')->name('bulk-product-delete');
        Route::post('/bulk-product-publish', 'bulk_product_publish')->name('bulk-product-publish');
        Route::post('/bulk-product-featured', 'bulk_product_featured')->name('bulk-product-featured');
        Route::post('/bulk-product-todays-deal', 'bulk_product_todays_deal')->name('bulk-product-todays-deal');
        Route::post('/bulk-product-stock-update', 'bulk_product_stock_update')->name('bulk-product-stock-update');

        Route::post('/products/sku_combination', 'sku_combination')->name('products.sku_combination');
        Route::post('/products/sku_combination_edit', 'sku_combination_edit')->name('products.sku_combination_edit');
        Route::post('/products/add-more-choice-option', 'add_more_choice_option')->name('products.add-more-choice-option');
        Route::post('/product-search', 'product_search')->name('product.search');
        Route::post('/get-selected-products', 'get_selected_products')->name('get-selected-products');
        Route::post('/set-product-discount', 'setProductDiscount')->name('set_product_discount');
        Route::get('/smart/bar', 'smartBar')->name('smart.bar');
        Route::post('business-settings/smart-bar-status', 'updateBusinessSettings')->name('business_settings.smart_bar_status');
        Route::post('/products-search', 'products_search')->name('products.search');
        Route::post('/products-by-cat', 'get_products_byCategory')->name('get_products_byCategory');
        Route::post('/save-as-draft', 'store_as_draft')->name('products.store_as_draft');
        Route::get('/stock/show/{id}', 'stockShow')->name('stock.show');
        Route::post('/products/generate-with-ai', 'generateWithAI')->name('products.generate-with-ai');
    });

    // Digital Product
    Route::resource('digitalproducts', DigitalProductController::class)->except(['edit', 'destroy']);
    Route::controller(DigitalProductController::class)->group(function () {
        Route::get('/digitalproducts/edit/{id}', 'edit')->name('digitalproducts.edit');
        Route::get('/digitalproducts/destroy/{id}', 'destroy')->name('digitalproducts.destroy');
        Route::get('/digitalproducts/download/{id}', 'download')->name('digitalproducts.download');
        Route::get('/seller/digitalproducts/{id}/edit', 'edit')->name('digitalproducts.seller.edit');
    });

    Route::controller(ProductBulkUploadController::class)->group(function () {
        //Product Export
        Route::get('/product-bulk-export', 'export')->name('product_bulk_export.index');

        //Product Bulk Upload
        Route::get('/product-bulk-upload/index', 'index')->name('product_bulk_upload.index');
        Route::post('/bulk-product-upload', 'bulk_upload')->name('bulk_product_upload');
        Route::get('/product-csv-download/{type}', 'import_product')->name('product_csv.download');
        Route::get('/vendor-product-csv-download/{id}', 'import_vendor_product')->name('import_vendor_product.download');
        Route::group(['prefix' => 'bulk-upload/download'], function () {
            Route::get('/category', 'pdf_download_category')->name('pdf.download_category');
            Route::get('/brand', 'pdf_download_brand')->name('pdf.download_brand');
            Route::get('/seller', 'pdf_download_seller')->name('pdf.download_seller');
        });
    });

    // Note
    Route::resource('note', NoteController::class)->except(['edit']);
    Route::controller(NoteController::class)->group(function () {
        Route::get('/note/edit/{id}', 'edit')->name('note.edit');
        Route::get('note/delete/{note}', 'destroy')->name('note.delete');
        Route::post('note/update-seller-access', 'updateSelelrAccess')->name('note.update-seller-access');
    });

    // Seller
    Route::resource('sellers', SellerController::class)->except(['destroy']);
    Route::controller(SellerController::class)->group(function () {
        Route::get('/seller/rating-followers', 'index')->name('sellers.rating_followers');
        Route::get('sellers_ban/{id}', 'ban')->name('sellers.ban');
        Route::get('/sellers/destroy/{id}', 'destroy')->name('sellers.destroy');
        Route::post('/bulk-seller-delete', 'bulk_seller_delete')->name('bulk-seller-delete');
        Route::get('/sellers/view/{id}/verification', 'show_verification_request')->name('sellers.show_verification_request');
        Route::get('/sellers/approve/{id}', 'approve_seller')->name('sellers.approve');
        Route::get('/sellers/reject/{id}', 'reject_seller')->name('sellers.reject');
        Route::get('/sellers/login/{id}', 'login')->name('sellers.login');
        Route::post('/sellers/payment_modal', 'payment_modal')->name('sellers.payment_modal');
        Route::post('/sellers/verification_info_modal', 'verification_info_modal')->name('sellers.verification_info_modal');
        Route::post('/sellers/approved', 'updateApproved')->name('sellers.approved');
        Route::get('/seller-based-commission', 'sellerBasedCommission')->name('seller_based_commission');
        Route::post('/set-seller-based-commission', 'setSellerCommission')->name('set_seller_commission');
        Route::post('/sellers/set-commission', 'setSellerBasedCommission')->name('set_seller_based_commission');
        Route::post('/sellers/edit-custom-followers', 'editSellerCustomFollowers')->name('edit_Seller_custom_followers');
        Route::get('/sellers/registration/pending', 'pendingSellers')->name('sellers.registration_pending');
        Route::post('/sellers/registration/approve', 'UpdateSellerRegistration')->name('sellers.registration.approved');
        Route::get('/sellers/profile/{id}', 'sellerProfile')->name('sellers.profile');
        Route::get('/sellers/profile/tab/data/{shop}',  'getSellerProfileTab')->name('sellers.profile.tab');
        Route::get('seller-suspicious/{seller}', 'suspicious')->name('seller.suspicious');
        Route::get('/seller/verification-file/delete', 'deleteVerificationFile')->name('seller.verification.file.delete');
        Route::post('/sellers/gstin-update', 'sellers_gstin_update')->name('sellers.seller_gstin_update');
    });

    // Seller Payment
    Route::controller(PaymentController::class)->group(function () {
        Route::get('/seller/payments', 'payment_histories')->name('sellers.payment_histories');
        Route::get('/seller/payments/show/{id}', 'show')->name('sellers.payment_history');
    });

    // Seller Withdraw Request
    Route::resource('/withdraw_requests', SellerWithdrawRequestController::class);
    Route::controller(SellerWithdrawRequestController::class)->group(function () {
        Route::get('/withdraw_requests_all', 'index')->name('withdraw_requests_all');
        Route::post('/withdraw_request/payment_modal', 'payment_modal')->name('withdraw_request.payment_modal');
        Route::post('/withdraw_request/message_modal', 'message_modal')->name('withdraw_request.message_modal');
    });

    // Customer
    Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);
    Route::controller(CustomerController::class)->group(function () {
        Route::get('customers_ban/{customer}', 'ban')->name('customers.ban');
        Route::get('customers-suspicious/{customer}', 'suspicious')->name('customers.suspicious');
        Route::get('/customers/login/{id}', 'login')->name('customers.login');
        Route::get('/customers/destroy/{id}', 'destroy')->name('customers.destroy');
        Route::post('/bulk-customer-delete', 'bulk_customer_delete')->name('bulk-customer-delete');
        Route::get('/unverified-customers', 'unverifiedCustomers')->name('customers.unverified.index');
        Route::get('/customers-filter', 'filter_customer')->name('customers.filter');        
    });

    // Newsletter
    Route::controller(NewsletterController::class)->group(function () {
        Route::get('/newsletter', 'index')->name('newsletters.index');
        Route::post('/newsletter/send', 'send')->name('newsletters.send');
        Route::post('/newsletter/test/smtp', 'testEmail')->name('test.smtp');
    });

    // Dynamic Popup
    Route::resource('dynamic-popups', DynamicPopupController::class)->except(['destroy']);
    Route::controller(DynamicPopupController::class)->group(function () {
        Route::get('/dynamic-popups/destroy/{id}', 'destroy')->name('dynamic-popups.destroy');
        Route::post('/bulk-dynamic-popup-delete', 'bulk_dynamic_popup_delete')->name('bulk-dynamic-popup-delete');
        Route::post('/dynamic-popups-update-status', 'update_status')->name('dynamic-popups.update-status');
    });

    // Custom Alert
    Route::resource('custom-alerts', CustomAlertController::class)->except(['destroy']);
    Route::controller(CustomAlertController::class)->group(function () {
        Route::get('/custom-alerts/destroy/{id}', 'destroy')->name('custom-alerts.destroy');
        Route::post('/bulk-custom-alerts-delete', 'bulk_custom_alerts_delete')->name('bulk-custom-alerts-delete');
        Route::post('/custom-alerts-update-status', 'update_status')->name('custom-alerts.update-status');
        Route::get('/custom-sale-alert', 'sale_alert_edit')->name('custom-sale-alert.edit');
    });

    //Custom Sale Alert
    Route::controller(CustomSaleAlertController::class)->group(function () {
        Route::get('/custom-sale-alerts', 'index')->name('custom-sale-alerts.index');
        Route::post('/custom-sale-alert-products', 'products')->name('custom_sale_alerts.products');
        Route::post('/custom-sale-alert-products-update', 'products_update')->name('custom-sale-alerts.product_update');
    });

    //Contacts
    Route::controller(ContactController::class)->group(function () {
        Route::get('/contacts', 'index')->name('contacts');
        Route::post('/contact/query_modal', 'query_modal')->name('contact.query_modal');
        Route::post('/contact/reply_modal', 'reply_modal')->name('contact.reply_modal');
        Route::post('/contact/reply', 'reply')->name('contact.reply');
    });

    Route::resource('profile', ProfileController::class);

    // ── AI SEO Suite ──────────────────────────────────────────────────────────
    Route::controller(SeoSuiteController::class)->group(function () {
        // Dashboard & Run
        Route::get('/seo-suite',             'index')->name('admin.seo-suite.index');
        Route::post('/seo-suite/run',        'run')->name('admin.seo-suite.run');
        Route::post('/seo-suite/bulk-pending','bulkOptimizePendingUrls')->name('admin.seo-suite.bulk_pending');
        Route::post('/seo-suite/queue/recover','recoverSeoQueue')->middleware('seo.rate')->name('admin.seo-suite.queue.recover');

        // Settings
        Route::get('/seo-suite/settings',    'settings')->name('admin.seo-suite.settings.view');
        Route::post('/seo-suite/settings',   'saveSettings')->name('admin.seo-suite.settings');

        // Sitemaps
        Route::post('/seo-suite/sitemap',             'generateSitemap')->name('admin.seo-suite.sitemap');
        Route::post('/seo-suite/sitemap/smart',       'generateSmartSitemap')->name('admin.seo-suite.sitemap.smart');
        Route::post('/seo-suite/sitemap/video',       'generateVideoSitemap')->name('admin.seo-suite.sitemap.video');
        Route::post('/seo-suite/sitemap/news',        'generateNewsSitemap')->name('admin.seo-suite.sitemap.news');

        // Robots / RSS
        Route::post('/seo-suite/robots',     'generateRobots')->name('admin.seo-suite.robots');
        Route::post('/seo-suite/rss',        'generateRss')->name('admin.seo-suite.rss');

        // Redirects
        Route::post('/seo-suite/redirects',           'storeRedirect')->name('admin.seo-suite.redirects.store');
        Route::delete('/seo-suite/redirects/{id}',    'deleteRedirect')->name('admin.seo-suite.redirects.delete');

        // IndexNow
        Route::post('/seo-suite/indexnow',            'indexNow')->name('admin.seo-suite.indexnow');
        Route::post('/seo-suite/indexnow/generate-key','generateIndexNowKey')->name('admin.seo-suite.indexnow.generate_key');

        // LLMs.txt
        Route::post('/seo-suite/llms-txt',            'generateLlmsTxt')->name('admin.seo-suite.llms_txt');

        // AI Assistant (chat)
        Route::get('/seo-suite/ai-assistant',         'aiAssistant')->name('admin.seo-suite.ai_assistant');
        Route::post('/seo-suite/ai-assistant/chat',   'aiChat')->name('admin.seo-suite.ai_chat');
        Route::post('/seo-suite/ai-assistant/quick',  'aiQuickAction')->name('admin.seo-suite.ai_quick');

        // AI Writing Assistant
        Route::get('/seo-suite/ai-writing',           'aiWriting')->name('admin.seo-suite.ai_writing_page');
        Route::post('/seo-suite/ai-writing',          'aiWritingAssistant')->name('admin.seo-suite.ai_writing');

        // AI Image Generator
        Route::get('/seo-suite/ai-images',            'aiImageGenerator')->name('admin.seo-suite.ai_images');
        Route::post('/seo-suite/ai-images/generate',  'generateAiImage')->name('admin.seo-suite.ai_images.generate');
        Route::post('/seo-suite/ai-images/apply-og',  'applyImageAsOg')->name('admin.seo-suite.ai_images.apply_og');

        // Keyword Rank Tracker
        Route::get('/seo-suite/keyword-tracker',      'keywordTracker')->name('admin.seo-suite.keyword_tracker');
        Route::post('/seo-suite/keyword-tracker/check','checkKeywordRanks')->name('admin.seo-suite.keyword_tracker.check');

        // Search Statistics
        Route::get('/seo-suite/search-stats',         'searchStatistics')->name('admin.seo-suite.search_stats');

        // Post Index Status
        Route::get('/seo-suite/index-status',         'postIndexStatus')->name('admin.seo-suite.index_status');
        Route::post('/seo-suite/index-status/check',  'checkIndexStatus')->name('admin.seo-suite.index_status.check');

        // Webmaster Tools
        Route::get('/seo-suite/webmaster',            'webmasterTools')->name('admin.seo-suite.webmaster');
        Route::post('/seo-suite/webmaster',           'saveWebmasterTools')->name('admin.seo-suite.webmaster.save');

        // SEO Revisions
        Route::get('/seo-suite/revisions',            'seoRevisions')->name('admin.seo-suite.revisions');

        // Link Assistant
        Route::match(['get','post'], '/seo-suite/link-assistant', 'linkAssistant')->name('admin.seo-suite.link_assistant');
    });

    Route::controller(OnPageSeoController::class)->group(function () {
        Route::get('/seo/on-page', 'index')->name('admin.seo_on_page.index');
        Route::post('/seo/on-page/run', 'run')->name('admin.seo_on_page.run');
    });

    Route::controller(OffPageSeoController::class)->group(function () {
        Route::get('/seo/off-page', 'index')->name('admin.seo_off_page.index');
        Route::post('/seo/off-page/run', 'run')->name('admin.seo_off_page.run');
    });

    Route::controller(OptimizationController::class)->group(function () {
        Route::get('/seo/optimization', 'index')->name('admin.seo_optimization.index');
        Route::post('/seo/optimization/run', 'run')->name('admin.seo_optimization.run');
        Route::post('/seo/optimization/sitemap', 'generateSitemap')->name('admin.seo_optimization.sitemap');
        Route::post('/seo/optimization/robots', 'generateRobots')->name('admin.seo_optimization.robots');
        Route::post('/seo/optimization/redirects', 'storeRedirect')->name('admin.seo_optimization.redirects.store');

        // AI-spending content-generation endpoints — rate-limited.
        Route::middleware('seo.rate')->group(function () {
            Route::post('/seo/optimization/generate-meta', 'generateMetaTags')->name('admin.seo_optimization.generate_meta');
            Route::post('/seo/optimization/generate-category-content', 'generateCategoryContent')->name('admin.seo_optimization.generate_category_content');
            Route::post('/seo/optimization/generate-product-content', 'generateProductContent')->name('admin.seo_optimization.generate_product_content');
        });
    });

    // AI SEO Monitoring — telemetry dashboard
    Route::get('/seo-suite/monitoring', [SeoMonitoringController::class, 'index'])
        ->name('admin.seo.monitoring.index');

    // Google OAuth — one-click connect for Search Console
    Route::get('/seo-suite/oauth/google/connect',     [GoogleAuthController::class, 'connect'])->name('admin.seo.oauth.google.connect');
    Route::get('/seo-suite/oauth/google/callback',    [GoogleAuthController::class, 'callback'])->name('admin.seo.oauth.google.callback');
    Route::post('/seo-suite/oauth/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('admin.seo.oauth.google.disconnect');

    // AI SEO Board — flagship scan & one-click fix surface
    Route::controller(AiSeoBoardController::class)->group(function () {
        Route::get('/seo-suite/ai-board',                       'index')->name('admin.seo.ai_board.index');
        Route::get('/seo-suite/ai-board/bulk/progress/{batch}', 'bulkProgress')->whereNumber('batch')->name('admin.seo.ai_board.bulk_progress');

        // AI-spending endpoints — rate-limited.
        Route::middleware('seo.rate')->group(function () {
            Route::post('/seo-suite/ai-board/fix',                  'fix')->name('admin.seo.ai_board.fix');
            Route::post('/seo-suite/ai-board/preview',              'preview')->name('admin.seo.ai_board.preview');
            Route::post('/seo-suite/ai-board/apply-approved',       'applyApproved')->name('admin.seo.ai_board.apply_approved');
            Route::post('/seo-suite/ai-board/rescore',              'rescore')->name('admin.seo.ai_board.rescore');
            Route::post('/seo-suite/ai-board/bulk/estimate',        'bulkEstimate')->name('admin.seo.ai_board.bulk_estimate');
            Route::post('/seo-suite/ai-board/bulk/run',             'bulkRun')->name('admin.seo.ai_board.bulk_run');
            Route::post('/seo-suite/ai-board/bulk/cancel/{batch}',  'bulkCancel')->whereNumber('batch')->name('admin.seo.ai_board.bulk_cancel');
        });
    });

    // Social Media Automation (AI Agent)
    Route::controller(SocialMediaController::class)->prefix('social-media')->name('admin.social.')->group(function () {
        Route::get('/',                                 'index')->name('index');
        Route::get('/settings',                         'settings')->name('settings');
        Route::post('/settings',                        'saveSettings')->name('settings.save');
        Route::get('/logs',                             'logs')->name('logs');
        Route::get('/campaigns',                        'campaigns')->name('campaigns');
        Route::post('/campaigns',                       'createCampaign')->name('campaigns.create');
        Route::post('/campaigns/{campaign}/generate',   'generateCampaignContent')->name('campaigns.generate');
        Route::post('/campaigns/{campaign}/status',     'updateCampaignStatus')->name('campaigns.status');
        Route::delete('/campaigns/{campaign}',          'deleteCampaign')->name('campaigns.delete');
        Route::get('/compose',                          'compose')->name('compose');
        Route::post('/post',                            'sendPost')->name('post');
        Route::post('/ai/generate',                     'generateAiContent')->name('ai.generate');
        Route::post('/ai/hashtags',                     'generateHashtags')->name('ai.hashtags');
        Route::post('/test-platform',                   'testPlatform')->name('test');
        Route::delete('/queue/{post}',                  'deleteQueuedPost')->name('queue.delete');
    });

    // AI Blog Automation
    Route::controller(AiBlogController::class)->prefix('ai-blog')->name('admin.ai-blog.')->group(function () {
        Route::get('/',               'index')->name('index');
        Route::get('/settings',       'settings')->name('settings');
        Route::post('/settings',      'saveSettings')->name('settings.save');
        Route::post('/generate',      'generateNow')->name('generate');
        Route::post('/publish/{blog}','publishBlog')->name('publish');
    });

    // Description Enhancer
    Route::controller(\App\Http\Controllers\Admin\DescriptionEnhancerController::class)->group(function () {
        Route::get('/tools/enhance-descriptions', 'index')->name('admin.tools.enhance_descriptions');
        Route::post('/tools/enhance-descriptions/batch', 'processBatch')->name('admin.tools.enhance_descriptions.batch');
    });

    // AI Product Import (PDF/URL)
    Route::controller(ProductImportController::class)->group(function () {
        Route::get('/import/products', 'index')->name('admin.import.products.index');
        Route::post('/import/products/pdf', 'importFromPdf')->name('admin.import.products.pdf');
        Route::post('/import/products/urls', 'importFromUrls')->name('admin.import.products.urls');
        Route::post('/import/products/preview', 'preview')->name('admin.import.products.preview');
        Route::post('/import/products/save', 'saveProduct')->name('admin.import.products.save');
        Route::post('/import/products/bulk-save', 'bulkSave')->name('admin.import.products.bulk_save');

        // Advanced single-URL workflow used by AI Add/Edit Products page
        Route::post('/import/products/scrape-single', 'scrapeSingle')->name('admin.import.products.scrape_single');
        Route::post('/import/products/find-images', 'findImages')->name('admin.import.products.find_images');
        Route::post('/import/products/save-with-images', 'saveProductWithImages')->name('admin.import.products.save_with_images');
        Route::get('/import/products/proxy-image', 'proxyImage')->name('admin.import.products.proxy_image');
    });

    // Business Settings
    Route::controller(BusinessSettingsController::class)->group(function () {
        Route::post('/business-settings/update', 'update')->name('business_settings.update');
        Route::post('/business-settings/update/activation', 'updateActivationSettings')->name('business_settings.update.activation');
        Route::post('/payment-activation', 'updatePaymentActivationSettings')->name('payment.activation');
        Route::post('/shipping-activation', 'updateShippingActivationSettings')->name('shipping.activation');

        // Friendly GET fallback — typing the URL or browser back-history GET
        // would otherwise hit a scary 405. Quietly bounce to the settings index.
        Route::get('/business-settings/update', fn() => redirect()->route('business_settings.index'));
        Route::get('/business-settings/update/activation', fn() => redirect()->route('activation.index'));
        Route::get('/general-setting', 'general_setting')->name('general_setting.index');
        Route::get('/activation', 'activation')->name('activation.index');
        Route::get('/payment-method', 'payment_method')->name('payment_method.index');
        Route::get('/file_system', 'file_system')->name('file_system.index');
        Route::get('/social-login', 'social_login')->name('social_login.index');
        Route::get('/smtp-settings', 'smtp_settings')->name('smtp_settings.index');
        Route::get('/google-recaptcha', 'google_recaptcha')->name('google_recaptcha.index');
        Route::get('/google-map', 'google_map')->name('google-map.index');
        Route::get('/google-firebase', 'google_firebase')->name('google-firebase.index');

        Route::get('/whatsapp-chat', 'whatsappChat')->name('whatsapp_chat.index');
        Route::post('/whatsapp_chat/update', 'whatsappChatUpdate')->name('whatsapp_chat.update');

        //Facebook Settings
        Route::get('/facebook-chat', 'facebookChat')->name('facebook_chat.index');
        Route::post('/facebook-chat', 'facebookChatUpdate')->name('facebook_chat.update');
        Route::get('/facebook-comment', 'facebook_comment')->name('facebook-comment');
        Route::post('/facebook-comment', 'facebook_comment_update')->name('facebook-comment.update');
        Route::post('/facebook_pixel', 'facebook_pixel_update')->name('facebook_pixel.update');

        Route::post('/env_key_update', 'env_key_update')->name('env_key_update.update');
        Route::post('/payment_method_update', 'payment_method_update')->name('payment_method.update');
        Route::post('/google_analytics', 'google_analytics_update')->name('google_analytics.update');
        Route::post('/google_recaptcha', 'google_recaptcha_update')->name('google_recaptcha.update');
        Route::post('/google-map', 'google_map_update')->name('google-map.update');
        Route::post('/google-firebase', 'google_firebase_update')->name('google-firebase.update');
        Route::post('/google-reviews', 'google_reviews_update')->name('google_reviews.update');
        Route::post('/pixels-capi-hub', 'pixels_capi_update')->name('pixels_capi.update');

        Route::get('/verification/form', 'seller_verification_form')->name('seller_verification_form.index');
        Route::post('/verification/form', 'seller_verification_form_update')->name('seller_verification_form.update');
        Route::get('/vendor_commission', 'vendor_commission')->name('business_settings.vendor_commission');

        //Shipping Configuration
        Route::get('/shipping_method', 'shipping_method')->name('shipping_configuration.shipping_method');
        Route::get('/shipping_configuration', 'shipping_configuration')->name('shipping_configuration.index');
        Route::post('/shipping_configuration/update', 'shipping_configuration_update')->name('shipping_configuration.update');
        Route::post('/shipping_configuration/has_state', 'stateBasedShippingSettings')->name('shipping_configuration.state');

        // Order Configuration
        Route::get('/order-configuration', 'order_configuration')->name('order_configuration.index');

        // Header Selection
        Route::post('/select-header', 'select_header')->name('settings.select-header');
        //custom product visitors
        Route::post('/custom-product-visitors', 'customProductVisitorsUpdate')->name('custom_product_visitors.update');
        //font-family selection
        Route::get('/select-font-family', 'select_font_family')->name('website.select-font-family');
        Route::get('/business-settings', 'business_settings')->name('business_settings.index');
        Route::post('/business-info/update', 'business_info_update')->name('business_info.update');

        //ai
         Route::post('/ai-configuration', 'ai_config_update')->name('ai_config.update');

    });


    //Currency
    Route::controller(CurrencyController::class)->group(function () {
        Route::get('/currency', 'currency')->name('currency.index');
        Route::post('/currency/update', 'updateCurrency')->name('currency.update');
        Route::post('/your-currency/update', 'updateYourCurrency')->name('your_currency.update');
        Route::get('/currency/create', 'create')->name('currency.create');
        Route::post('/currency/store', 'store')->name('currency.store');
        Route::post('/currency/currency_edit', 'edit')->name('currency.edit');
        Route::post('/currency/update_status', 'update_status')->name('currency.update_status');
    });

    //Tax
    Route::resource('tax', TaxController::class)->except(['edit', 'destroy']);
    Route::controller(TaxController::class)->group(function () {
        Route::get('/tax/edit/{id}', 'edit')->name('tax.edit');
        Route::get('/tax/destroy/{id}', 'destroy')->name('tax.destroy');
        Route::post('tax-status', 'change_tax_status')->name('taxes.tax-status');
    });

    // Language
    Route::resource('/languages', LanguageController::class)->except(['update', 'destroy']);
    Route::controller(LanguageController::class)->group(function () {
        Route::post('/languages/{id}/update', 'update')->name('languages.update');
        Route::get('/languages/destroy/{id}', 'destroy')->name('languages.destroy');
        Route::post('/languages/update_rtl_status', 'update_rtl_status')->name('languages.update_rtl_status');
        Route::post('/languages/update-status', 'update_status')->name('languages.update-status');
        Route::post('/languages/key_value_store', 'key_value_store')->name('languages.key_value_store');
        Route::get('/languages/translations/google/{id}', 'googleTranslations')->name('translations.google');
        //App Trasnlation
        Route::post('/languages/app-translations/import', 'importEnglishFile')->name('app-translations.import');
        Route::get('/languages/app-translations/show/{id}', 'showAppTranlsationView')->name('app-translations.show');
        Route::post('/languages/app-translations/key_value_store', 'storeAppTranlsation')->name('app-translations.store');
        Route::get('/languages/app-translations/export/{id}', 'exportARBFile')->name('app-translations.export');
        Route::get('/languages/app-translations/sync/{id}', 'sycnTranslations')->name('app-translations.sync');
    });


     // website setting
    Route::group(['prefix' => 'website'], function () {
        Route::controller(WebsiteController::class)->group(function () {
            Route::post('/get-upload-file-name', 'getFileName');
            Route::post('/get-element-types', 'getElementTypesByElement')->name('get.element.types');
            Route::get('/header', 'header')->name('website.header');
            Route::get('/footer', 'footer')->name('website.footer');
            Route::get('/appearance', 'appearance')->name('website.appearance');
            Route::get('/select-homepage', 'select_homepage')->name('website.select-homepage');
            Route::get('/select-header', 'select_header')->name('website.select-header');
            Route::get('/authentication-layout-settings', 'authentication_layout_settings')->name('website.authentication-layout-settings');
            Route::get('/pages', 'pages')->name('website.pages');
            Route::get('/portfolio-header', 'portfolio_header')->name('website.portfolioheader');

        });

        // Custom Page
        Route::resource('custom-pages', PageController::class)->except(['edit', 'destroy']);
        Route::controller(PageController::class)->group(function () {
            Route::get('/custom-pages/edit/{id}', 'edit')->name('custom-pages.edit');
            Route::get('/custom-pages/destroy/{id}', 'destroy')->name('custom-pages.destroy');
        });

        // topbar
        Route::controller(TopBannerController::class)->group(function () {
            Route::get('/top-bar-list', 'index')->name('top_banner.index');
            Route::get('/top-bar-setting', 'setting')->name('top_banner.setting');
            Route::get('/top-bar-create', 'create')->name('top_banner.create');
            Route::post('/top-bar-store', 'store')->name('top_banner.store');
            Route::get('/top-bar-edit/{id}', 'edit')->name('top_banner.edit');
            Route::post('/top-bar-update/{id}', 'update')->name('top_banner.update');
            Route::get('/top-bar-delete/{id}', 'destroy')->name('top_banner.delete');
            Route::post('/top-bars-update-status', 'update_status')->name('top-banner.update-status');
        });
    });

    // element
    Route::resource('elements', ElementController::class)->except(['edit', 'destroy']);
    Route::controller(ElementController::class)->group(function () {
        Route::get('/elements/edit/{id}', 'edit')->name('elements.edit');
        Route::get('/elements/destroy/{id}', 'destroy')->name('elements.destroy');
        Route::post('/elements/type-store', 'store_element_type')->name('store-element-type');
        Route::get('/edit/elements/type/{id}', 'edit_element_type')->name('edit-element-type');
        Route::post('/update/elements/type/{id}', 'update_element_type')->name('update-element-type');
        Route::get('/delete/elements/type/{id}', 'destroy_element_type')->name('destroy-element-type');
        Route::get('/show/elements/style/{id}', 'show_element_style')->name('show-element-style');
        Route::post('/elements/style-store', 'store_element_style')->name('store-element-style');
        Route::get('/delete/elements/style/{id}', 'destroy_element_style')->name('destroy-element-style');
    });

    // Staff Roles
    Route::resource('roles', RoleController::class)->except(['edit', 'destroy']);
    Route::controller(RoleController::class)->group(function () {
        Route::get('/roles/edit/{id}', 'edit')->name('roles.edit');
        Route::get('/roles/destroy/{id}', 'destroy')->name('roles.destroy');

        // Add Permissiom
        Route::post('/roles/add_permission', 'add_permission')->name('roles.permission');
    });

    // Staff
    Route::resource('staffs', StaffController::class)->except(['destroy']);
    Route::get('/staffs/destroy/{id}', [StaffController::class, 'destroy'])->name('staffs.destroy');

    // Flash Deal
    Route::resource('flash_deals', FlashDealController::class)->except(['edit', 'destroy']);
    Route::controller(FlashDealController::class)->group(function () {
        Route::get('/flash_deals/edit/{id}', 'edit')->name('flash_deals.edit');
        Route::get('/flash_deals/destroy/{id}', 'destroy')->name('flash_deals.destroy');
        Route::post('/flash_deals/update_status', 'update_status')->name('flash_deals.update_status');
        Route::post('/flash_deals/update_featured', 'update_featured')->name('flash_deals.update_featured');
        Route::post('/flash_deals/product_discount', 'product_discount')->name('flash_deals.product_discount');
        Route::post('/flash_deals/product_discount_edit', 'product_discount_edit')->name('flash_deals.product_discount_edit');
    });

    // Store Promotions (admin-managed promo/deal tiles for the public store page)
    Route::controller(StorePromotionController::class)->group(function () {
        Route::get('/store-promotions', 'index')->name('store_promotions.index');
        Route::get('/store-promotions/create', 'create')->name('store_promotions.create');
        Route::post('/store-promotions', 'store')->name('store_promotions.store');
        Route::get('/store-promotions/edit/{id}', 'edit')->name('store_promotions.edit');
        Route::post('/store-promotions/update/{id}', 'update')->name('store_promotions.update');
        Route::get('/store-promotions/duplicate/{id}', 'duplicate')->name('store_promotions.duplicate');
        Route::get('/store-promotions/destroy/{id}', 'destroy')->name('store_promotions.destroy');
        Route::post('/store-promotions/update-status', 'updateStatus')->name('store_promotions.update_status');
        Route::post('/store-promotions/reorder', 'reorder')->name('store_promotions.reorder');
        Route::get('/store-page/settings', 'settings')->name('store_promotions.settings');
        Route::post('/store-page/settings', 'saveSettings')->name('store_promotions.settings.save');
    });

    //Subscribers
    Route::controller(SubscriberController::class)->group(function () {
        Route::get('/subscribers', 'index')->name('subscribers.index');
        Route::get('/subscribers/destroy/{id}', 'destroy')->name('subscriber.destroy');
    });

    // Order
    Route::resource('orders', OrderController::class)->except(['destroy']);
    Route::controller(OrderController::class)->group(function () {
        // All Orders
        Route::get('/all_orders', 'all_orders')->name('all_orders.index');
        Route::get('/inhouse-orders', 'all_orders')->name('inhouse_orders.index');
        Route::get('/seller_orders', 'all_orders')->name('seller_orders.index');
        Route::get('/orders_by_pickup_point', 'all_orders')->name('pick_up_point.index');
        Route::get('/unpaid_orders', 'all_orders')->name('unpaid_orders.index');

        Route::get('/orders/{id}/show', 'show')->name('all_orders.show');
        Route::get('/inhouse-orders/{id}/show', 'show')->name('inhouse_orders.show');
        Route::get('/seller_orders/{id}/show', 'show')->name('seller_orders.show');
        Route::get('/orders_by_pickup_point/{id}/show', 'show')->name('pick_up_point.order_show');

        Route::post('/bulk-order-status', 'bulk_order_status')->name('bulk-order-status');

        Route::get('/orders/destroy/{id}', 'destroy')->name('orders.destroy');
        Route::post('/bulk-order-delete', 'bulk_order_delete')->name('bulk-order-delete');

        Route::post('/orders/details', 'order_details')->name('orders.details');
        Route::post('/orders/update_delivery_status', 'update_delivery_status')->name('orders.update_delivery_status');
        Route::post('/orders/update_payment_status', 'update_payment_status')->name('orders.update_payment_status');
        Route::post('/orders/update_tracking_code', 'update_tracking_code')->name('orders.update_tracking_code');

        //Delivery Boy Assign
        Route::post('/orders/delivery-boy-assign', 'assign_delivery_boy')->name('orders.delivery-boy-assign');

        // Order bulk export
        Route::get('/order-bulk-export', 'orderBulkExport')->name('order-bulk-export');

        // 
        Route::post('order-payment-notification', 'unpaid_order_payment_notification_send')->name('unpaid_order_payment_notification');
        Route::get('/filtered-orders', 'get_filter_orders')->name('orders.filter');
    });

    Route::post('/pay_to_seller', [CommissionController::class, 'pay_to_seller'])->name('commissions.pay_to_seller');

    //Reports
    Route::controller(ReportController::class)->group(function () {
        Route::get('/in_house_sale_report', 'in_house_sale_report')->name('in_house_sale_report.index');
        Route::get('/seller_sale_report', 'seller_sale_report')->name('seller_sale_report.index');
        Route::get('/stock_report', 'stock_report')->name('stock_report.index');
        Route::get('/wish_report', 'wish_report')->name('wish_report.index');
        Route::get('/user_search_report', 'user_search_report')->name('user_search_report.index');
        Route::get('/commission-log', 'commission_history')->name('commission-log.index');
        Route::get('/wallet-history', 'wallet_transaction_history')->name('wallet-history.index');
    });

    // Earning Report
    Route::group(['prefix' => 'reports'], function () {
        Route::get('/earning-payout-report', [EarningReportController::class, 'index'])->name('earning_payout_report.index');
        Route::post('/earning-payout-report/net-sales', [EarningReportController::class, 'net_sales']);
        Route::post('/earning-payout-report/payouts', [EarningReportController::class, 'payouts']);
        Route::post('/earning-payout-report/sale-analytic', [EarningReportController::class, 'sale_analytic']);
        Route::post('/earning-payout-report/payout-analytic', [EarningReportController::class, 'payout_analytic']);
    });

    //Blog Section
    //Blog cateory
    Route::resource('blog-category', BlogCategoryController::class)->except(['destroy']);
    Route::get('/blog-category/destroy/{id}', [BlogCategoryController::class, 'destroy'])->name('blog-category.destroy');

    // Blog
    Route::resource('blog', BlogController::class)->except(['destroy']);
    Route::controller(BlogController::class)->group(function () {
        Route::get('/blog/destroy/{id}', 'destroy')->name('blog.destroy');
        Route::post('/blog/change-status', 'change_status')->name('blog.change-status');
    });

    //Coupons
    Route::resource('coupon', CouponController::class)->except(['destroy']);
    Route::controller(CouponController::class)->group(function () {
        Route::post('/coupon/update-status', 'updateStatus')->name('coupon.update_status');
        Route::get('/coupon/destroy/{id}', 'destroy')->name('coupon.destroy');

        //Coupon Form
        Route::post('/coupon/get_form', 'get_coupon_form')->name('coupon.get_coupon_form');
        Route::post('/coupon/get_form_edit', 'get_coupon_form_edit')->name('coupon.get_coupon_form_edit');
    });

    //Reviews
    Route::controller(ReviewController::class)->group(function () {
        Route::get('/reviews', 'index')->name('reviews.index');
        Route::post('/reviews/published', 'updatePublished')->name('reviews.published');
        Route::get('/reviews/detail-reviews/{id}', 'detailReviews')->name('detail-reviews');
        Route::get('/reviews/destroy', 'destroy')->name('reviews.destroy');

        Route::get('/custom-review/create/{productId?}', 'customReviewCreate')->name('custom-review.create');
        Route::get('/custom-review/edit/{id}', 'customReviewEdit')->name('custom-review.edit');
        Route::post('/custom-review/update', 'customReviewUpdate')->name('custom-review.update');
        Route::post('/custom-review/get-products', 'getProductByCategory')->name('get-custom-review-product-by-category');
    });

    //Support_Ticket
    Route::controller(SupportTicketController::class)->group(function () {
        Route::get('support_ticket/', 'admin_index')->name('support_ticket.admin_index');
        Route::get('support_ticket/{id}/show', 'admin_show')->name('support_ticket.admin_show');
        Route::post('support_ticket/reply', 'admin_store')->name('support_ticket.admin_store');
    });

    // Email Template
    Route::resource('email-templates', EmailTemplateController::class)->except(['index']);
    Route::controller(EmailTemplateController::class)->group(function () {
        Route::get('/email-template/{id}', 'index')->name('email-templates.index');
        Route::post('/email-template/update-status', 'updateStatus')->name('email-template.update-status');
    });

    //Pickup_Points
    Route::resource('pick_up_points', PickupPointController::class)->except(['edit', 'destroy']);
    Route::controller(PickupPointController::class)->group(function () {
        Route::get('/pick_up_points/edit/{id}', 'edit')->name('pick_up_points.edit');
        Route::get('/pick_up_points/destroy/{id}', 'destroy')->name('pick_up_points.destroy');
    });

    //conversation of seller customer
    Route::controller(ConversationController::class)->group(function () {
        Route::get('conversations', 'admin_index')->name('conversations.admin_index');
        Route::get('conversations/{id}/show', 'admin_show')->name('conversations.admin_show');
    });

    // product Queries show on Admin panel
    Route::controller(ProductQueryController::class)->group(function () {
        Route::get('/product-queries', 'index')->name('product_query.index');
        Route::get('/product-queries/{id}', 'show')->name('product_query.show');
        Route::put('/product-queries/{id}', 'reply')->name('product_query.reply');
    });

    // Product Attribute
    Route::resource('attributes', AttributeController::class)->except(['edit', 'destroy']);
    Route::controller(AttributeController::class)->group(function () {
        Route::get('/attributes/edit/{id}', 'edit')->name('attributes.edit');
        Route::get('/attributes/destroy/{id}', 'destroy')->name('attributes.destroy');

        //Colors
        Route::get('/colors', 'colors')->name('colors');
        Route::get('/colors/create', 'colors_create')->name('colors.create');
        Route::post('/colors/store', 'store_color')->name('colors.store');
        Route::get('/colors/edit/{id}', 'edit_color')->name('colors.edit');
        Route::post('/colors/update/{id}', 'update_color')->name('colors.update');
        Route::get('/colors/destroy/{id}', 'destroy_color')->name('colors.destroy');
    });

    // Size Chart
    Route::resource('size-charts', SizeChartController::class)->except(['destroy']);
    Route::get('/size-charts/destroy/{id}',  [SizeChartController::class, 'destroy'])->name('size-charts.destroy');
    Route::post('size-charts/get-combination',   [SizeChartController::class, 'get_combination'])->name('size-charts.get-combination');

    // Measurement Points
    Route::resource('measurement-points', MeasurementPointsController::class)->except(['destroy']);
    Route::get('/measurement-points/destroy/{id}',  [MeasurementPointsController::class, 'destroy'])->name('measurement-points.destroy');

    // Addon
    Route::resource('addons', AddonController::class);
    Route::post('/addons/activation', [AddonController::class, 'activation'])->name('addons.activation');

    //Customer Package
    Route::resource('customer_packages', CustomerPackageController::class)->except(['edit', 'destroy']);
    Route::controller(CustomerPackageController::class)->group(function () {
        Route::get('/customer_packages/edit/{id}', 'edit')->name('customer_packages.edit');
        Route::get('/customer_packages/destroy/{id}', 'destroy')->name('customer_packages.destroy');
    });

    //Classified Products
    Route::controller(CustomerProductController::class)->group(function () {
        Route::get('/classified_products', 'customer_product_index')->name('classified_products');
        Route::post('/classified_products/published', 'updatePublished')->name('classified_products.published');
        Route::get('/classified_products/destroy/{id}', 'destroy_by_admin')->name('classified_products.destroy');
    });

    // Countries
    Route::resource('countries', CountryController::class);
    Route::post('/countries/status', [CountryController::class, 'updateStatus'])->name('countries.status');

    // States
    Route::resource('states', StateController::class);
    Route::post('/states/status', [StateController::class, 'updateStatus'])->name('states.status');

    // Carriers
    Route::resource('carriers', CarrierController::class)->except(['destroy']);
    Route::controller(CarrierController::class)->group(function () {
        Route::get('/carriers/destroy/{id}', 'destroy')->name('carriers.destroy');
        Route::post('/carriers/update_status', 'updateStatus')->name('carriers.update_status');
    });


    // Zones
    Route::resource('zones', ZoneController::class)->except(['destroy']);
    Route::get('/zones/destroy/{id}', [ZoneController::class, 'destroy'])->name('zones.destroy');

    Route::resource('cities', CityController::class)->except(['edit', 'destroy']);
    Route::controller(CityController::class)->group(function () {
        Route::get('/cities/edit/{id}', 'edit')->name('cities.edit');
        Route::get('/cities/destroy/{id}', 'destroy')->name('cities.destroy');
        Route::post('/cities/status', 'updateStatus')->name('cities.status');
        Route::get('/get-cities-by-state', 'getCities')->name('get-cities-by-state');
        Route::get('/get-cities-by-country', 'getCitiesByCountry')->name('get-cities-by-country');
    });

    //Areas
    Route::resource('areas', AreaController::class)->except(['edit', 'destroy']);
    Route::controller(AreaController::class)->group(function () {
        Route::get('/areas/edit/{id}', 'edit')->name('areas.edit');
        Route::get('/areas/destroy/{id}', 'destroy')->name('areas.destroy');
        Route::post('/areas/status', 'updateStatus')->name('areas.status');
    });

     Route::controller(AddressController::class)->group(function () {
        Route::post('/get-states', 'getStates')->name('admin.get-state');
     });

    Route::view('/system/update', 'backend.system.update')->name('system_update');
    Route::view('/system/server-status', 'backend.system.server_status')->name('system_server');
    Route::view('/system/import-demo-data', 'backend.system.import_demo_data')->name('import_demo_data');

    Route::post('/import-data', [BusinessSettingsController::class, 'import_data'])->name('import_data');

    // uploaded files
    Route::resource('/uploaded-files', AizUploadController::class)->except(['destroy']);
    Route::controller(AizUploadController::class)->group(function () {
        Route::any('/uploaded-files/file-info', 'file_info')->name('uploaded-files.info');
        Route::get('/uploaded-files/destroy/{id}', 'destroy')->name('uploaded-files.destroy');
        Route::post('/bulk-uploaded-files-delete', 'bulk_uploaded_files_delete')->name('bulk-uploaded-files-delete');
        Route::get('/all-file', 'all_file');
    });

    Route::controller(NotificationController::class)->group(function () {
        Route::get('/all-notifications', 'adminIndex')->name('admin.all-notifications');
        Route::get('/notification-settings', 'notificationSettings')->name('notification.settings');

        Route::post('/notifications/bulk-delete', 'bulkDeleteAdmin')->name('admin.notifications.bulk_delete');
        Route::get('/notification/read-and-redirect/{id}', 'readAndRedirect')->name('admin.notification.read-and-redirect');

        Route::get('/custom-notification', 'customNotification')->name('custom_notification');
        Route::post('/custom-notification/send', 'sendCustomNotification')->name('custom_notification.send');

        Route::get('/custom-notification/history', 'customNotificationHistory')->name('custom_notification.history');
        Route::get('/custom-notifications.delete/{identifier}', 'customNotificationSingleDelete')->name('custom-notifications.delete');
        Route::post('/custom-notifications.bulk_delete', 'customNotificationBulkDelete')->name('custom-notifications.bulk_delete');
        Route::post('/custom-notified-customers-list', 'customNotifiedCustomersList')->name('custom_notified_customers_list');
    });

    Route::resource('notification-type', NotificationTypeController::class)->except(['edit', 'destroy']);
    Route::controller(NotificationTypeController::class)->group(function () {
        Route::get('/notification-type/edit/{id}', 'edit')->name('notification-type.edit');
        Route::post('/notification-type/update-status', 'updateStatus')->name('notification-type.update-status');
        Route::get('/notification-type/destroy/{id}', 'destroy')->name('notification-type.destroy');
        Route::post('/notification-type/bulk_delete', 'bulkDelete')->name('notifications-type.bulk_delete');
        Route::post('/notification-type.get-default-text', 'getDefaulText')->name('notification_type.get_default_text');
    });

    Route::get('/clear-cache', [AdminController::class, 'clearCache'])->name('cache.clear');

    Route::get('/admin-permissions', [RoleController::class, 'create_admin_permissions']);

    //Sitemap Generator
    Route::get('/system/sitemap-generator', [AdminController::class, 'SitemapGenerator'])->name('sitemap_generator');
    Route::post('/system/generate-sitemap', [AdminController::class, 'DoSitemapGenerate'])->name('generate_sitemap');
    Route::post('/system/delete-sitemap', [AdminController::class, 'DeleteSitemapFile'])->name('delete_sitemap');
    Route::post('/system/download-old-sitemap', [AdminController::class, 'DownloadSingleSitemapFile'])->name('download_old_sitemap');

    //Custom Visitors Setup
    Route::view('/custom-product-visitors', 'backend.marketing.custom_product_visitors')->name('custom_product_visitors');

    //Update Process
    Route::controller(NewUpdateController::class)->group(function () {
        Route::post('/update', 'step0')->name('new_update');
    });

    Route::controller(PickupController::class)->group(function () {
        Route::get('/pickup-address-list', 'index')->name('pickup_address.index');
        Route::get('/pickup-address-create', 'create')->name('pickup_address.create');
        Route::post('/pickup-address-store', 'store')->name('pickup_address.store');
        Route::get('/pickup-address-edit/{id}', 'edit')->name('pickup_address.edit');
        Route::post('/pickup-address-update/{id}', 'update')->name('pickup_address.update');
        Route::get('/pickup-address-delete/{id}', 'destroy')->name('pickup_address.delete');
        Route::post('/pickup-addresses', 'getPickupAddresses')->name('pickup.addresses.list');
        Route::post('/bulk-pickup-addresses-delete', 'bulk_delete')->name('bulk-pickup-addresses-delete');
        Route::get('/pickup-addresses/filter', 'filter')->name('pickup_addresses.filter');
        Route::post('/pickup-addresses/featured', 'updateStatus')->name('pickup_addresses.status');
    });

    Route::controller(ShippingBoxSizeController::class)->group(function () {
        Route::get('/shipping-box-size-list', 'index')->name('shipping_box_size.index');
        Route::get('/shipping-box-size-create', 'create')->name('shipping_box_size.create');
        Route::post('/shipping-box-size-store', 'store')->name('shipping_box_size.store');
        Route::get('/shipping-box-size-edit/{id}', 'edit')->name('shipping_box_size.edit');
        Route::post('/shipping-box-size-update/{id}', 'update')->name('shipping_box_size.update');
        Route::get('/shipping-box-size-delete/{id}', 'destroy')->name('shipping_box_size.delete');
        Route::post('/shipping-box-sizes', 'getBoxSizes')->name('box.sizes.list');
        Route::post('/bulk-shipping-box-sizes-delete', 'bulk_delete')->name('bulk-shipping-box-sizes-delete');
        Route::get('/shipping-box-sizes/filter', 'filter')->name('shipping_box_sizes.filter');
    });

    Route::controller(ShippingSystemController::class)->group(function () {
        Route::get('/shiprocket-configuration', 'shiprocket_configuration')->name('shiprocket_configuration');
        Route::get('/steadfast-configuration', 'steadfast_configuration')->name('steadfast_configuration');
        Route::get('/pathao-configuration', 'pathao_configuration')->name('pathao_configuration');
        Route::get('/redx-configuration', 'redx_configuration')->name('redx_configuration');    
    });

    Route::controller(FinalUpdateController::class)->group(function () {
        Route::post('/update', 'step0')->name('final_update');
    });

    Route::controller(AnalyticsController::class)->group(function () {
        Route::get('/google-analytics-report', 'google_analytics_report')->name('google-analytics-test.result');
        Route::get('/google-analytics', 'google_analytics_config')->name('google-analytics-config');
        Route::get('/google-analytics-index', 'google_analytics_config')->name('google_analytics.index');
        Route::get('/google-tag-manager', 'google_tag_manager')->name('google-tag-manager-config');
        Route::get('/pixel-analytics/configuration', 'pixel_analytics')->name('pixel_analytics.index');
        Route::get('/pixel-capi/configuration', 'pixel_conversation_api')->name('pixel_conversation_api.index');

        // Google Reviews — direct Places API integration (no 3rd-party widget)
        Route::get('/google-reviews/configuration', 'google_reviews_config')->name('google-reviews-config');
        Route::get('/google-reviews', 'google_reviews_dashboard')->name('google-reviews-dashboard');
        Route::post('/google-reviews/sync', 'google_reviews_sync_now')->name('google-reviews.sync-now');

        // Multi-channel Pixels & CAPI hub (TikTok, Pinterest, Snapchat, LinkedIn, X, Google Ads, Clarity)
        Route::get('/pixels-capi-hub', 'pixels_capi_hub')->name('pixels-capi-hub');

        // AI Marketing Insights (first-party warehouse + AI summary + NLP query)
        Route::get('/marketing-insights',              'insights_dashboard')->name('analytics.insights.dashboard');
        Route::post('/marketing-insights/ask',         'insights_ask')->name('analytics.insights.ask');
        Route::post('/marketing-insights/regenerate',  'insights_regenerate')->name('analytics.insights.regenerate');
        Route::get('/marketing-insights/forecast',     'insights_forecast')->name('analytics.insights.forecast');

        // Attribution + Funnels + Cohort Retention (Group D)
        Route::get('/attribution-funnels',             'attribution_dashboard')->name('analytics.attribution.dashboard');

        // UTM Campaign Manager (Group E)
        Route::get('/campaign-manager',                'campaigns_index')->name('analytics.campaigns.index');
        Route::post('/campaign-manager',               'campaigns_save')->name('analytics.campaigns.save');
        Route::delete('/campaign-manager/{id}',        'campaigns_delete')->name('analytics.campaigns.delete');

        // A/B Testing Lab (Group G)
        Route::get('/experiments',                     'experiments_index')->name('analytics.experiments.index');
        Route::post('/experiments',                    'experiments_save')->name('analytics.experiments.save');
        Route::delete('/experiments/{id}',             'experiments_delete')->name('analytics.experiments.delete');
    });

    Route::controller(AIController::class)->group(function () {
        Route::get('/token-usage', 'ai_token_usage')->name('ai-token-usege');
        Route::get('/ai-configuration', 'ai_configuration')->name('ai-config');
        Route::get('/ai-templates', 'ai_templates')->name('ai-prompt-templates-config');
        Route::patch('/ai-templates/{id}','update')->name('ai-prompt-templates.update');
        Route::get('/ai-add-edit-products','add_edit_products')->name('ai-add_edit_products');
    });

    // Amazon SP-API Integration
    Route::prefix('amazon')->name('amazon.')->controller(AmazonController::class)->group(function () {
        Route::get('/',                    'index')->name('index');
        Route::post('/settings',           'saveSettings')->name('settings.save');
        Route::get('/category-mapping',    'categoryMapping')->name('category-mapping');
        Route::post('/category-mapping',   'saveCategoryMapping')->name('category-mapping.save');
        Route::get('/products',            'products')->name('products');
        Route::post('/upload/{id}',        'upload')->name('upload');
        Route::post('/bulk-upload',        'bulkUpload')->name('bulk-upload');
        Route::post('/deactivate/{id}',    'deactivate')->name('deactivate');
        Route::post('/sync/inventory',     'syncInventory')->name('sync.inventory');
        Route::post('/sync/price',         'syncPrice')->name('sync.price');
        Route::get('/orders',              'orders')->name('orders');
        Route::post('/orders/import',      'importOrders')->name('orders.import');
        Route::get('/logs',                'logs')->name('logs');
    });

});

Route::get('/system/sitemap-item-add/{item}', [AdminController::class, 'SitemapItems'])->name('sitemap_item_add');
