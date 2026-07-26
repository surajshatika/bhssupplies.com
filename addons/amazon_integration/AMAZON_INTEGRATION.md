# Amazon Integration (Amazon Selling Partner API)

Native integration that lists products on Amazon, keeps price/inventory in sync, and imports Amazon orders back into the store — built directly on Amazon's Selling Partner API (SP-API).

## Overview

- **Marketplace:** Amazon Canada by default (`A2EUQ1WTGCTBG2`), configurable via `.env`
- **API:** Amazon Selling Partner API (SP-API), Listings Items API 2021-08-01 + Orders API v0
- **Auth:** Login with Amazon (LWA) OAuth2 refresh-token flow
- **Sandbox support:** toggle between sandbox and production SP-API endpoints

## Features

### 1. Account & Settings Management
- Single-screen setup for Amazon Seller account credentials: Seller ID, Marketplace ID, LWA Client ID/Secret, AWS Access/Secret Key, Refresh Token
- Credentials stored per account in `amazon_accounts`; refresh token stored separately in `amazon_tokens`
- Supports multiple accounts, with one marked `is_active` as the default used by jobs/commands
- Admin UI: **Settings** page (`/admin/amazon`)

### 2. OAuth2 Authentication (LWA)
- `AmazonAuthService` exchanges the stored refresh token for a short-lived access token via `POST https://api.amazon.com/auth/o2/token`
- Access tokens are cached (`Cache::remember`, ~58 minutes TTL) and auto-refreshed on expiry
- Token metadata (`access_token`, `expires_at`) persisted to `amazon_tokens`

### 3. Category Mapping
- Maps store categories (level-0 tree with children) to Amazon category ID / category name / Amazon product type
- Stored in `amazon_category_mappings`, keyed uniquely by `website_category_id`
- Used by the product mapper to pick the correct `productType` when building listing payloads
- Admin UI: **Category Mapping** page with a mapping form per category row

### 4. Product Listing Upload (Single & Bulk)
- `AmazonProductMapper::toListingPayload()` builds an SP-API Listings Items payload from a store `Product`:
  - `item_name`, `brand` (falls back to `amazon.default_brand`), `list_price` (CAD), `fulfillment_availability` (stock qty), `item_package_weight`, `product_description` (HTML stripped), `bullet_point`s (unit/weight/tags), `main_product_image_locator`
- SKU generation: uses product barcode if present, otherwise `{sku_prefix}{product_id}` (default prefix `BHS-`)
- `AmazonListingService::uploadProduct()` issues a `PUT /listings/2021-08-01/items/{sellerId}/{sku}` call, then upserts an `amazon_products` record (`pending` status) and writes an `amazon_sync_logs` entry
- **Single upload:** `POST /admin/amazon/upload/{productId}` → dispatches `UploadProductToAmazonJob`
- **Bulk upload:** `POST /admin/amazon/bulk-upload` with a `product_ids[]` array → dispatches `BulkUploadAmazonJob` (per-item try/catch, tracks success/failure counts, 10-minute job timeout)
- Admin UI: **Product List** page shows every listing with status filter (`pending` / `active` / `inactive` / `error`)

### 5. Listing Deactivation
- `AmazonListingService::deactivateListing()` issues `DELETE /listings/2021-08-01/items/{sellerId}/{sku}`, marks the local `amazon_products` row `inactive`
- Triggered via `POST /admin/amazon/deactivate/{id}`, run asynchronously after the HTTP response (`dispatch(...)->afterResponse()`)

### 6. Inventory (Stock) Sync
- `AmazonProductMapper::toInventoryPayload()` builds a JSON-patch replacing `/attributes/fulfillment_availability` with current stock (floored at 0)
- `AmazonInventoryService::syncStock()` sends the patch via `PATCH /listings/.../items/...`, updates `last_synced_at`
- `syncAllActive()` chunks through all `active` Amazon listings (50 at a time) and syncs each
- Manual trigger: `POST /admin/amazon/sync/inventory` (single product) → `SyncAmazonInventoryJob`
- **Scheduled:** `amazon:sync-inventory` Artisan command runs **hourly**, `withoutOverlapping(10)`, logs to `storage/logs/amazon-sync.log`

### 7. Price Sync
- `AmazonProductMapper::toPricePayload()` builds a JSON-patch replacing `/attributes/list_price` with the current `unit_price` (CAD)
- `AmazonPriceService::syncPrice()` applies the patch and logs the result
- Manual trigger: `POST /admin/amazon/sync/price` → `SyncAmazonPriceJob`

### 8. Order Import
- `AmazonOrderService::fetchNewOrders()` calls `GET /orders/v0/orders` filtered by marketplace + `CreatedAfter` + order statuses (`Unshipped, PartiallyShipped, Shipped, Canceled`)
- `importOrder()` upserts into `amazon_orders` keyed by `amazon_order_id` — captures buyer name/email, total amount, currency, line items, and the full raw Amazon payload (`raw_data` JSON)
- `importAllNew()` pulls orders created in the last 24 hours
- Manual trigger: `POST /admin/amazon/orders/import` → `ImportAmazonOrdersJob` (3 retries, exponential backoff)
- **Scheduled:** `amazon:import-orders` Artisan command runs **every 30 minutes**, `withoutOverlapping(5)`, logs to `storage/logs/amazon-orders.log`
- Admin UI: **Orders** page (`/admin/amazon/orders`) with status filter and pagination

### 9. Image Handling
- `AmazonImageService` resolves a product's thumbnail and up to 8 gallery photos into public URLs (`uploaded_asset()`) for use as Amazon's main/gallery image locators
- Listing payloads currently attach the main thumbnail as `main_product_image_locator`

### 10. Sync Logging & Audit Trail
- Every action (`upload`, `price_sync`, `inventory_sync`, `order_import`, `deactivate`) writes an `amazon_sync_logs` row with request/response payloads, status (`pending` / `success` / `failed`), and error message on failure
- Admin UI: **Logs** page (`/admin/amazon/logs`) filterable by action and status, paginated

### 11. Background Jobs (Queue-based)
| Job | Purpose | Retries / Backoff |
|---|---|---|
| `UploadProductToAmazonJob` | Upload a single product listing | 3 tries, 60/120/300s |
| `BulkUploadAmazonJob` | Upload many products in one dispatch | 2 tries, 120/300s, 600s timeout |
| `SyncAmazonInventoryJob` | Push stock qty for one listing | 3 tries, 60/120/300s |
| `SyncAmazonPriceJob` | Push price for one listing | 3 tries, 60/120/300s |
| `ImportAmazonOrdersJob` | Pull new orders for an account | 3 tries, 120/300/600s |

### 12. Scheduled Commands
| Command | Schedule | Purpose |
|---|---|---|
| `amazon:sync-inventory` | Hourly | Syncs stock for all active Amazon listings |
| `amazon:import-orders` | Every 30 minutes | Imports new orders from Amazon |

Both commands no-op with an informational message if `AMAZON_ENABLED=false` or no active account is configured.

## Admin UI Pages
| Page | Route | Purpose |
|---|---|---|
| Dashboard | `GET /admin/amazon` | Account summary + listing stats (total/active/pending/error/orders) |
| Category Mapping | `GET/POST /admin/amazon/category-mapping` | Map store categories → Amazon categories/product types |
| Product List | `GET /admin/amazon/products` | View/filter all Amazon listings |
| Orders | `GET /admin/amazon/orders` | View/filter imported Amazon orders |
| Logs | `GET /admin/amazon/logs` | View/filter sync activity and errors |

## Database Schema

- **`amazon_accounts`** — seller credentials (name, seller_id, marketplace_id, LWA client id/secret, AWS keys, is_active)
- **`amazon_tokens`** — OAuth refresh/access token + expiry, per account (cascade delete with account)
- **`amazon_category_mappings`** — website_category_id (unique, FK → categories) ↔ amazon_category_id/name/product_type
- **`amazon_products`** — link between a store `Product` and its Amazon listing: amazon_sku (unique), asin, status enum, last_synced_at, error_message
- **`amazon_sync_logs`** — audit trail: action enum (upload/price_sync/inventory_sync/order_import/deactivate), status enum, request/response JSON, error_message
- **`amazon_orders`** — imported Amazon orders: amazon_order_id (unique), status, buyer info, total_amount, currency, order_items JSON, shipped_at, raw_data JSON

## Configuration (`config/amazon.php` / `.env`)

| Key | Env Var | Default |
|---|---|---|
| `enabled` | `AMAZON_ENABLED` | `false` |
| `sandbox` | `AMAZON_SANDBOX` | `true` |
| `marketplace_id` | `AMAZON_MARKETPLACE_ID` | `A2EUQ1WTGCTBG2` (Canada) |
| `lwa_client_id` | `AMAZON_LWA_CLIENT_ID` | — |
| `lwa_client_secret` | `AMAZON_LWA_CLIENT_SECRET` | — |
| `aws_access_key` | `AMAZON_AWS_ACCESS_KEY` | — |
| `aws_secret_key` | `AMAZON_AWS_SECRET_KEY` | — |
| `refresh_token` | `AMAZON_REFRESH_TOKEN` | — |
| `seller_id` | `AMAZON_SELLER_ID` | — |
| `region` | `AMAZON_REGION` | `us-east-1` |
| `default_brand` | `AMAZON_DEFAULT_BRAND` | `BHS Supplies` |
| `sku_prefix` | `AMAZON_SKU_PREFIX` | `BHS-` |

## API Endpoints Used

| SP-API Operation | Method / Path |
|---|---|
| Create/replace listing | `PUT /listings/2021-08-01/items/{sellerId}/{sku}` |
| Patch listing (price/inventory) | `PATCH /listings/2021-08-01/items/{sellerId}/{sku}` |
| Delete/deactivate listing | `DELETE /listings/2021-08-01/items/{sellerId}/{sku}` |
| Get orders | `GET /orders/v0/orders` |
| LWA token refresh | `POST https://api.amazon.com/auth/o2/token` |

Base URLs: `https://sellingpartnerapi-na.amazon.com` (production) or `https://sandbox.sellingpartnerapi-na.amazon.com` (sandbox, controlled by `AMAZON_SANDBOX`).

## Known Limitations

- No OAuth redirect/consent screen — the admin manually obtains and pastes the LWA refresh token into Settings.
- Effectively single-account: jobs/commands always resolve `AmazonAccount::where('is_active', 1)->first()` rather than a selected account, so although the schema supports multiple seller accounts, only one is operationally active at a time.
- Price sync has no scheduled automation — it only runs on manual per-product trigger.
- `AmazonImageService` duplicates image-resolution logic that also lives privately inside `AmazonProductMapper::buildImages()`; only the mapper's version is used in listing payloads today.
- `amazon_orders.shipped_at` is defined in the schema but never populated by current import code.
- Currency is hardcoded to CAD, matching the default Amazon Canada marketplace.

## Key Source Files

```
app/Http/Controllers/AmazonController.php
app/Services/Amazon/
├── AmazonAuthService.php        # LWA OAuth token management
├── AmazonListingService.php     # Put/Patch/Delete listing calls
├── AmazonProductMapper.php      # Product → SP-API payload mapping
├── AmazonInventoryService.php   # Stock sync
├── AmazonPriceService.php       # Price sync
├── AmazonOrderService.php       # Order fetch/import
└── AmazonImageService.php       # Product image resolution
app/Jobs/Amazon/
├── UploadProductToAmazonJob.php
├── BulkUploadAmazonJob.php
├── SyncAmazonInventoryJob.php
├── SyncAmazonPriceJob.php
└── ImportAmazonOrdersJob.php
app/Console/Commands/Amazon/
├── SyncAmazonInventoryCommand.php
└── ImportAmazonOrdersCommand.php
app/Models/
├── AmazonAccount.php
├── AmazonToken.php
├── AmazonCategoryMapping.php
├── AmazonProduct.php
├── AmazonSyncLog.php
└── AmazonOrder.php
resources/views/backend/amazon/
├── index.blade.php
├── category_mapping.blade.php
├── product_list.blade.php
├── orders.blade.php
├── logs.blade.php
└── partials/
config/amazon.php
database/migrations/2026_05_09_00000{1-6}_create_amazon_*.php
```
