<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CustomsDocumentItem extends Model {
 protected $fillable=['item_per_unit','tax_invoice_number','tax_invoice_date','goods_sent_note_item_id','shipment_number','shipment_date','document_id','subcontract_receipt_item_id','product_id','goods_receipt_item_id','equipment_receipt_item_id','receipt_number','receipt_date','item_code','item_name','unit','quantity','unit_price','total_price','cif','country_code','hs_code'];
 protected function casts():array{return ['item_per_unit'=>'integer','shipment_date'=>'date','tax_invoice_date'=>'date','receipt_date'=>'date','quantity'=>'decimal:4','unit_price'=>'decimal:4','total_price'=>'decimal:4','cif'=>'decimal:4'];}
 public function document():BelongsTo{return $this->belongsTo(CustomsDocument::class,'document_id');}
 public function subcontractReceiptItem():BelongsTo{return $this->belongsTo(SubcontractMaterialIssueItem::class,'subcontract_receipt_item_id');}
 public function goodsSentNoteItem():BelongsTo{return $this->belongsTo(GoodsSentNoteItem::class);}
 public function product():BelongsTo{return $this->belongsTo(Product::class);}
}
