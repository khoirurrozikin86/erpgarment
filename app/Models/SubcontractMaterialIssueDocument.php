<?php
namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class SubcontractMaterialIssueDocument extends Model
{
    use SoftDeletes;
    protected $fillable=['issue_kind','company_id','number','issue_date','contract_id','subcontractor_id','vehicle_number','invoice_number','invoice_date','contract_number','created_by'];
    protected function casts(): array { return ['invoice_date'=>'date','issue_date'=>'date','confirmed_at'=>'datetime']; }
    public function contract(): BelongsTo { return $this->belongsTo(SubcontractContract::class,'contract_id')->withTrashed(); }
    public function receiver(): BelongsTo { return $this->belongsTo(Subcontractor::class,'subcontractor_id')->withTrashed(); }
    public function confirmer(): BelongsTo { return $this->belongsTo(User::class,'confirmed_by'); }
    public function items(): HasMany { return $this->hasMany(SubcontractMaterialIssueItem::class,'document_id'); }
}
