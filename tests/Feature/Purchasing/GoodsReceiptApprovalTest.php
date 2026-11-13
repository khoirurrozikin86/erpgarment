<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\Actions\ApproveGoodsReceiptAction;
use App\Domain\Purchasing\Actions\CreateGoodsReceiptAction;
use App\Domain\Purchasing\DTOs\GoodsReceiptData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GoodsReceiptApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_goods_receipt_is_pending_until_approved_then_posts_stock(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::query()->create(['code' => 'SUP-1', 'name' => 'Supplier One', 'is_active' => true]);
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'PCS', 'is_active' => true]);
        $category = Category::query()->create(['code' => 'MAT', 'name' => 'Material', 'is_active' => true]);
        $product = Product::query()->create([
            'code' => 'MAT-1', 'name' => 'Fabric', 'category_id' => $category->id,
            'unit_id' => $unit->id, 'product_type' => 'stock', 'track_stock' => true,
            'is_active' => true, 'is_available' => true,
        ]);

        $materialRequest = \App\Models\MaterialRequest::query()->create([
            'number' => 'MR-1', 'requested_by' => $user->id, 'reason' => 'Production need', 'status' => 'approved',
        ]);
        $requestItem = $materialRequest->items()->create(['product_id' => $product->id, 'quantity' => 5]);
        $purchaseRequest = \App\Models\PurchaseRequest::query()->create([
            'number' => 'PR-1', 'material_request_id' => $materialRequest->id,
            'supplier_id' => $supplier->id, 'requested_by' => $user->id, 'status' => 'approved',
        ]);
        $purchaseRequestItem = $purchaseRequest->items()->create([
            'material_request_item_id' => $requestItem->id, 'product_id' => $product->id,
            'quantity' => 5, 'unit_price' => 2, 'line_total' => 10,
        ]);
        $purchaseOrder = PurchaseOrder::query()->create([
            'number' => 'PO-1', 'purchase_request_id' => $purchaseRequest->id,
            'supplier_id' => $supplier->id, 'created_by' => $user->id,
            'order_date' => now()->toDateString(), 'status' => 'issued',
        ]);
        $poItem = $purchaseOrder->items()->create([
            'purchase_request_item_id' => $purchaseRequestItem->id, 'product_id' => $product->id,
            'quantity' => 5, 'received_quantity' => 0, 'unit_price' => 2, 'line_total' => 10,
        ]);

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')->twice()->andReturn(new AuditLog());
        $numbers = Mockery::mock(DocumentNumberService::class);
        $numbers->shouldReceive('generate')->once()->with('goods_receipt')->andReturn('GRN-1');
        $journals = Mockery::mock(AutomaticJournalService::class);
        $journals->shouldReceive('recordStockMovement')->once();

        $receipt = (new CreateGoodsReceiptAction($numbers, $audit))(new GoodsReceiptData(
            purchaseOrderId: $purchaseOrder->id,
            receivedAt: now()->format('Y-m-d H:i:s'),
            notes: null,
            items: [['purchase_order_item_id' => $poItem->id, 'quantity' => 5, 'lot_number' => 'LOT-1']],
            currency: 'USD',
        ), $user->id);

        $this->assertSame('pending', $receipt->status);
        $this->assertDatabaseMissing('product_stocks', ['product_id' => $product->id]);
        $this->assertDatabaseMissing('stock_movements', ['goods_receipt_id' => $receipt->id]);
        $this->assertDatabaseHas('purchase_order_items', ['id' => $poItem->id, 'received_quantity' => '5.0000']);

        $approved = (new ApproveGoodsReceiptAction($journals, $audit))($receipt, $user->id);

        $this->assertSame('approved', $approved->status);
        $this->assertSame($user->id, $approved->approved_by);
        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'quantity' => '5.0000']);
        $this->assertDatabaseHas('inventory_lots', ['product_id' => $product->id, 'lot_number' => 'LOT-1', 'current_quantity' => '5.0000']);
        $this->assertDatabaseHas('stock_movements', ['goods_receipt_id' => $receipt->id, 'movement_type' => 'purchase_receipt']);
    }
}
