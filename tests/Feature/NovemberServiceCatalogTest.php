<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\ServiceTestCase;

class NovemberServiceCatalogTest extends ServiceTestCase
{
    public function test_requested_items_are_available_and_carpet_surface_is_charged_per_square_metre(): void
    {
        $categories = DB::table('service_categories')->pluck('id', 'slug');
        $this->assertDatabaseHas('service_categories', [
            'id' => $categories['spalatorie-covoare'], 'parent_id' => $categories['spalatorie'], 'is_active' => true,
        ]);
        $this->assertDatabaseHas('service_categories', [
            'id' => $categories['vulcanizare-autoturisme'], 'parent_id' => $categories['vulcanizare'],
        ]);
        foreach ([
            ['Demontat + montat + echilibrat SUV-4x4', 50], ['Curățat jantă', 10],
            ['ANVELOPE SH 50', 50], ['ANVELOPE SH 70', 70], ['ANVELOPE SH 100', 100],
        ] as [$name, $price]) {
            $this->assertDatabaseHas('service_items', [
                'service_category_id' => $categories['vulcanizare-autoturisme'],
                'name' => $name, 'price' => $price, 'unit' => 'buc.', 'is_active' => true,
            ]);
        }
        foreach ([5, 10, 50, 100] as $price) {
            $this->assertDatabaseHas('service_items', [
                'service_category_id' => $categories['spalatorie-covoare'],
                'name' => 'Covoare MP '.$price, 'price' => $price, 'unit' => 'm²', 'is_active' => true,
            ]);
        }

        $itemId = DB::table('service_items')->where('name', 'Covoare MP 5')->value('id');
        $response = $this->asServiceOperator()->postJson('/service/orders', [
            'items' => [['service_item_id' => $itemId, 'quantity' => '2.50']],
        ])->assertOk()->assertJsonPath('order.total', 12.5);
        $this->assertDatabaseHas('service_order_items', [
            'service_order_id' => $response->json('order.id'), 'service_item_id' => $itemId,
            'quantity' => 2.5, 'unit' => 'm²', 'unit_price' => 5, 'line_total' => 12.5,
        ]);
    }

    public function test_upgrade_appends_missing_items_once_and_keeps_custom_catalog_and_order_history(): void
    {
        $rugCategory = DB::table('service_categories')->where('slug', 'spalatorie-covoare')->first();
        DB::table('service_categories')->where('id', $rugCategory->id)->update([
            'slug' => 'categorie-covoare-locala', 'sort_order' => 87,
        ]);
        $tire = DB::table('service_items')->where('name', 'ANVELOPE SH 70')->first();
        $order = $this->asServiceOperator()->postJson('/service/orders', [
            'items' => [['service_item_id' => $tire->id, 'quantity' => 2]],
        ])->assertOk()->json('order.id');
        DB::table('service_items')->where('id', $tire->id)->update([
            'price' => 73, 'description' => 'Tarif local', 'is_active' => false, 'sort_order' => 590,
        ]);
        DB::table('service_items')->where('name', 'ANVELOPE SH 50')->delete();

        $migration = require database_path('migrations/2026_11_11_100000_extend_vulcanizare_and_carpet_catalog.php');
        $migration->up();
        $afterUpgrade = DB::table('service_items')->orderBy('id')->get();
        $migration->down();
        $migration->up();

        $this->assertEquals($afterUpgrade, DB::table('service_items')->orderBy('id')->get());
        $this->assertDatabaseHas('service_categories', [
            'id' => $rugCategory->id, 'slug' => 'categorie-covoare-locala', 'sort_order' => 87,
        ]);
        $this->assertSame(1, DB::table('service_categories')->where('parent_id', $rugCategory->parent_id)->where('name', 'Covoare')->count());
        $this->assertDatabaseHas('service_items', [
            'id' => $tire->id, 'price' => 73, 'description' => 'Tarif local', 'is_active' => false, 'sort_order' => 590,
        ]);
        $this->assertDatabaseHas('service_items', [
            'name' => 'ANVELOPE SH 50', 'service_category_id' => $tire->service_category_id, 'price' => 50, 'sort_order' => 600,
        ]);
        $this->assertDatabaseHas('service_order_items', [
            'service_order_id' => $order, 'service_item_id' => $tire->id, 'unit_price' => 70, 'line_total' => 140,
        ]);
    }
}
