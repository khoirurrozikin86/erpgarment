<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\Actions\DeletePurchaseReturnAction;
use App\Models\Category;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryLot;
use App\Models\MaterialRequest;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PurchaseReturnDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_purchase_return_restores_stock_lots_and_receipt_returnable_quantity(): void
    {
        $fixture = $this->returnFixture();
        $journals = Mockery::mock(AutomaticJournalService::class);
        $journals->shouldReceive('reversePurchaseReturnJournals')->once()->withArgs(fn ($return, $userId) => $return->id === $fixture['return']->id && $userId === $fixture['user']->id);
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->once();

        (new DeletePurchaseReturnAction($journals, $audit))($fixture['return'], $fixture['user']->id);

        $this->assertDatabaseMissing('purchase_returns', ['id' => $fixture['return']->id]);
        $this->assertDatabaseMissing('stock_movements', ['id' => $fixture['movement']->id]);
        $this->assertDatabaseHas('goods_receipt_items', ['id' => $fixture['receiptItem']->id, 'returned_quantity' => '0.0000']);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $fixture['product']->id, 'quantity' => '10.0000']);
        $this->assertDatabaseHas('inventory_lots', ['id' => $fixture['lot']->id, 'current_quantity' => '6.0000']);
    }

    public function test_purchase_return_cannot_be_deleted_after_a_later_stock_movement(): void
    {
        $fixture = $this->returnFixture();
        StockMovement::query()->create([
            'product_id' => $fixture['product']->id, 'created_by' => $fixture['user']->id,
            'movement_type' => 'material_issue', 'quantity' => -1, 'quantity_before' => 8,
            'quantity_after' => 7, 'cost_amount' => 2,
        ]);
        $journals = Mockery::mock(AutomaticJournalService::class);
        $journals->shouldNotReceive('reversePurchaseReturnJournals');
        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldNotReceive('log');

        try {
            (new DeletePurchaseReturnAction($journals, $audit))($fixture['return'], $fixture['user']->id);
            $this->fail('Expected stock history validation to prevent deletion.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('purchase_return', $exception->errors());
        }

        $this->assertDatabaseHas('purchase_returns', ['id' => $fixture['return']->id]);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $fixture['product']->id, 'quantity' => '8.0000']);
    }

    private function returnFixture(): array
    {
        Company::query()->create(['code' => 'TEST', 'name' => 'Test Co', 'is_active' => true]);
        $user = User::factory()->create();
        $supplier = Supplier::query()->create(['code' => 'SUP-1', 'name' => 'Supplier', 'is_active' => true]);
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'MAT', 'name' => 'Material', 'is_active' => true]);
        $product = Product::query()->create([
            'code' => 'MAT-1', 'name' => 'Material', 'category_id' => $category->id, 'unit_id' => $unit->id,
            'product_type' => 'stock', 'track_stock' => true, 'purchase_price' => 2,
            'is_active' => true, 'is_available' => true,
        ]);
        $request = MaterialRequest::query()->create(['number' => 'MR-1', 'requested_by' => $user->id, 'reason' => 'Test', 'status' => 'approved']);
        $requestItem = $request->items()->create(['product_id' => $product->id, 'quantity' => 10]);
        $purchaseRequest = PurchaseRequest::query()->create([
            'number' => 'PR-1', 'material_request_id' => $request->id, 'supplier_id' => $supplier->id,
            'requested_by' => $user->id, 'status' => 'approved',
        ]);
        $purchaseRequestItem = $purchaseRequest->items()->create([
            'material_request_item_id' => $requestItem->id,
            'product_id' => $product->id, 'quantity' => 10, 'unit_price' => 2, 'line_total' => 20,
        ]);
        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-1', 'purchase_request_id' => $purchaseRequest->id, 'supplier_id' => $supplier->id,
            'created_by' => $user->id, 'order_date' => today(), 'status' => 'received',
        ]);
        $purchaseOrderItem = $purchaseOrder->items()->create([
            'purchase_request_item_id' => $purchaseRequestItem->id,
            'product_id' => $product->id, 'quantity' => 10, 'received_quantity' => 10,
            'unit_price' => 2, 'line_total' => 20,
        ]);
        $receipt = GoodsReceipt::query()->create([
            'number' => 'GRN-1', 'purchase_order_id' => $purchaseOrder->id, 'supplier_id' => $supplier->id,
            'received_by' => $user->id, 'received_at' => now(), 'status' => 'approved',
        ]);
        $lot = InventoryLot::query()->create([
            'product_id' => $product->id, 'lot_number' => 'LOT-1', 'received_at' => now(),
            'initial_quantity' => 10, 'current_quantity' => 4, 'unit_cost' => 2, 'source_type' => 'receipt',
        ]);
        $receiptItem = $receipt->items()->create([
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'product_id' => $product->id, 'inventory_lot_id' => $lot->id, 'quantity' => 10,
            'returned_quantity' => 2, 'unit_price' => 2, 'line_total' => 20,
        ]);
        $return = PurchaseReturn::query()->create([
            'number' => 'RET-1', 'goods_receipt_id' => $receipt->id, 'supplier_id' => $supplier->id,
            'returned_by' => $user->id, 'returned_at' => now(), 'reason' => 'Damaged', 'total_amount' => 4,
        ]);
        $returnItem = $return->items()->create([
            'goods_receipt_item_id' => $receiptItem->id, 'product_id' => $product->id,
            'quantity' => 2, 'unit_price' => 2, 'line_total' => 4,
        ]);
        $movement = StockMovement::query()->create([
            'product_id' => $product->id, 'purchase_return_id' => $return->id, 'purchase_return_item_id' => $returnItem->id,
            'created_by' => $user->id, 'movement_type' => 'purchase_return', 'quantity' => -2,
            'quantity_before' => 10, 'quantity_after' => 8, 'cost_amount' => 4,
        ]);
        $movement->lotAllocations()->create(['inventory_lot_id' => $lot->id, 'quantity' => -2, 'unit_cost' => 2]);
        $stock = ProductStock::query()->create(['product_id' => $product->id, 'quantity' => 8, 'average_unit_cost' => 2]);

        return compact('user', 'product', 'lot', 'receiptItem', 'return', 'movement', 'stock');
    }
}
