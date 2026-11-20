<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubcontractContract extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'subcontractor_id', 'customer_id', 'contract_number', 'bc_decree_number', 'description', 'starts_on', 'ends_on', 'quota', 'created_by'];
    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date', 'quota' => 'decimal:4']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function subcontractor(): BelongsTo { return $this->belongsTo(Subcontractor::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
