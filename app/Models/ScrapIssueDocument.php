<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScrapIssueDocument extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'number', 'issue_date', 'customer_id', 'scrap_product_id', 'quantity', 'description', 'created_by'];
    protected function casts(): array { return ['issue_date' => 'date', 'quantity' => 'decimal:4']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function scrapProduct(): BelongsTo { return $this->belongsTo(Product::class, 'scrap_product_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
