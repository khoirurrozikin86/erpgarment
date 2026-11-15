<?php
namespace App\Models;
use Illuminate\Database\Eloquent\{Model,SoftDeletes};
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class CustomsDocument extends Model {
 use SoftDeletes;
 protected $fillable=['customer_id','exporter_id','company_id','document_type','submission_number','document_number','document_date','bl_awb_number','bl_awb_date','invoice_number','invoice_date','subcontractor_id','supplier_id','importer_id','price_type','currency','created_by'];
 protected function casts():array{return ['document_date'=>'date','bl_awb_date'=>'date','invoice_date'=>'date'];}
 public function subcontractor():BelongsTo{return $this->belongsTo(Subcontractor::class);}
 public function customer():BelongsTo{return $this->belongsTo(Customer::class);}
 public function exporter():BelongsTo{return $this->belongsTo(Exporter::class);}
 public function supplier():BelongsTo{return $this->belongsTo(Supplier::class);}
 public function importer():BelongsTo{return $this->belongsTo(Importer::class);}
 public function items():HasMany{return $this->hasMany(CustomsDocumentItem::class,'document_id');}
}
