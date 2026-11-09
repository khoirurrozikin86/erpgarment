<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\MaterialRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Company;

class DashboardController extends Controller
{
    public function index()
    {
        $totals = [
            'products' => Product::where('product_type', 'stock')->count(),
            'fabrics' => Product::where('garment_item_type', 'fabric')->count(),
            'accessories' => Product::where('garment_item_type', 'accessory')->count(),
            'work_in_process' => Product::where('garment_item_type', 'work_in_process')->count(),
            'finished_goods' => Product::where('garment_item_type', 'finished_good')->count(),
            'suppliers' => Supplier::count(),
            'material_requests' => MaterialRequest::where('status', 'pending')->count(),
            'purchase_requests' => PurchaseRequest::where('status', 'pending')->count(),
            'purchase_orders' => PurchaseOrder::whereIn('status', ['draft', 'issued', 'partially_received'])->count(),
            'goods_receipts' => GoodsReceipt::count(),
        ];

        return view('super.dashboard', [
            'totals' => $totals,
            'companyConfigured' => Company::query()->where('is_active', true)->exists(),
        ]);
    }
}
