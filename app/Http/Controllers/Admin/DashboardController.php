<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PriceList;
use App\Models\Supplier;
use App\Models\Unit;

class DashboardController extends Controller
{
    public function index()
    {
        $totals = [
            'products' => Product::count(),
            'pricelists' => PriceList::count(),
            'categories' => Category::count(),
            'units' => Unit::count(),
            'suppliers' => Supplier::count(),
            'customers' => Customer::count(),
        ];

        return view('super.dashboard', compact('totals'));
    }
}
