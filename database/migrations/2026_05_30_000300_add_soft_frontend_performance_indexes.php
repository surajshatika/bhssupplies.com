<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addPrefixIndex('products', 'slug', 191, 'products_slug_perf_idx');
        $this->addIndex('products', ['approved', 'published', 'auction_product', 'category_id', 'id'], 'products_public_category_perf_idx');
        $this->addIndex('products', ['brand_id', 'approved', 'published', 'auction_product', 'id'], 'products_public_brand_perf_idx');
        $this->addIndex('products', ['featured', 'approved', 'published', 'auction_product', 'id'], 'products_featured_perf_idx');
        $this->addIndex('products', ['todays_deal', 'approved', 'published', 'auction_product', 'id'], 'products_todays_deal_perf_idx');
        $this->addIndex('products', ['num_of_sale', 'approved', 'published', 'auction_product'], 'products_sales_perf_idx');
        $this->addIndex('products', ['unit_price'], 'products_price_perf_idx');

        $this->addIndex('product_categories', ['category_id', 'product_id'], 'product_categories_category_product_perf_idx');
        $this->addIndex('product_categories', ['product_id', 'category_id'], 'product_categories_product_category_perf_idx');

        $this->addPrefixIndex('categories', 'slug', 191, 'categories_slug_perf_idx');
        $this->addIndex('categories', ['parent_id', 'order_level'], 'categories_parent_order_perf_idx');
        $this->addIndex('categories', ['level', 'order_level'], 'categories_level_order_perf_idx');
        $this->addIndex('categories', ['featured'], 'categories_featured_perf_idx');
        $this->addIndex('categories', ['hot_category'], 'categories_hot_perf_idx');

        $this->addIndex('product_stocks', ['product_id'], 'product_stocks_product_perf_idx');
        $this->addIndex('product_taxes', ['product_id'], 'product_taxes_product_perf_idx');
        $this->addIndex('product_translations', ['product_id', 'lang'], 'product_translations_product_lang_perf_idx');
        $this->addIndex('reviews', ['product_id', 'status', 'created_at'], 'reviews_product_status_perf_idx');
        $this->addIndex('product_queries', ['product_id', 'customer_id', 'id'], 'product_queries_product_customer_perf_idx');
    }

    public function down(): void
    {
        foreach ([
            ['products', 'products_slug_perf_idx'],
            ['products', 'products_public_category_perf_idx'],
            ['products', 'products_public_brand_perf_idx'],
            ['products', 'products_featured_perf_idx'],
            ['products', 'products_todays_deal_perf_idx'],
            ['products', 'products_sales_perf_idx'],
            ['products', 'products_price_perf_idx'],
            ['product_categories', 'product_categories_category_product_perf_idx'],
            ['product_categories', 'product_categories_product_category_perf_idx'],
            ['categories', 'categories_slug_perf_idx'],
            ['categories', 'categories_parent_order_perf_idx'],
            ['categories', 'categories_level_order_perf_idx'],
            ['categories', 'categories_featured_perf_idx'],
            ['categories', 'categories_hot_perf_idx'],
            ['product_stocks', 'product_stocks_product_perf_idx'],
            ['product_taxes', 'product_taxes_product_perf_idx'],
            ['product_translations', 'product_translations_product_lang_perf_idx'],
            ['reviews', 'reviews_product_status_perf_idx'],
            ['product_queries', 'product_queries_product_customer_perf_idx'],
        ] as [$table, $index]) {
            $this->dropIndex($table, $index);
        }
    }

    private function addIndex(string $table, array $columns, string $index): void
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $index)) {
            return;
        }
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
            $blueprint->index($columns, $index);
        });
    }

    private function addPrefixIndex(string $table, string $column, int $length, string $index): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || $this->indexExists($table, $index)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$index}` (`{$column}`({$length}))");
            return;
        }

        $this->addIndex($table, [$column], $index);
    }

    private function dropIndex(string $table, string $index): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropIndex($index);
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
                return count($rows) > 0;
            }
        } catch (Throwable $e) {
            return false;
        }

        return false;
    }
};
