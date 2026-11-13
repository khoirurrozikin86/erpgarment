<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialIssueDocument extends Model
{
    protected $fillable = ['company_id', 'material_request_id', 'raw_material_order_document_id', 'number', 'issue_date', 'created_by', 'production_receipt_status', 'production_received_by', 'production_received_at'];
    protected function casts(): array { return ['issue_date' => 'date', 'production_received_at' => 'datetime']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function request(): BelongsTo { return $this->belongsTo(MaterialRequest::class, 'material_request_id'); }
    public function rawMaterialOrder(): BelongsTo { return $this->belongsTo(RawMaterialOrderDocument::class, 'raw_material_order_document_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function productionReceiver(): BelongsTo { return $this->belongsTo(User::class, 'production_received_by'); }
    public function items(): HasMany { return $this->hasMany(MaterialIssueItem::class); }
}
