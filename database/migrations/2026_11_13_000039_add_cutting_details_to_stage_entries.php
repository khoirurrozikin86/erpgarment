<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_order_stage_entries', function(Blueprint $table){
            if(!Schema::hasColumn('production_order_stage_entries','cutting_number')) $table->string('cutting_number',100)->nullable();
            if(!Schema::hasColumn('production_order_stage_entries','material_product_id')) $table->unsignedBigInteger('material_product_id')->nullable();
            if(!Schema::hasColumn('production_order_stage_entries','material_waste_quantity')) $table->decimal('material_waste_quantity',18,4)->default(0);
        });
        if(!collect(Schema::getForeignKeys('production_order_stage_entries'))->contains(fn($fk)=>in_array('material_product_id',$fk['columns'],true))) {
            Schema::table('production_order_stage_entries',fn(Blueprint $table)=>$table->foreign('material_product_id','cut_stage_material_product_fk')->references('id')->on('products')->restrictOnDelete());
        }
        if(!collect(Schema::getIndexes('production_order_stage_entries'))->contains(fn($index)=>in_array('cutting_number',$index['columns'],true))) {
            Schema::table('production_order_stage_entries',fn(Blueprint $table)=>$table->index('cutting_number','cut_stage_number_idx'));
        }
    }
    public function down(): void
    {
        if(Schema::hasTable('production_order_stage_entries')) Schema::table('production_order_stage_entries',function(Blueprint $table){
            if(collect(Schema::getForeignKeys('production_order_stage_entries'))->contains(fn($fk)=>in_array('material_product_id',$fk['columns'],true))) $table->dropForeign('cut_stage_material_product_fk');
            if(collect(Schema::getIndexes('production_order_stage_entries'))->contains(fn($index)=>$index['name']==='cut_stage_number_idx')) $table->dropIndex('cut_stage_number_idx');
            foreach(['cutting_number','material_product_id','material_waste_quantity'] as $column) if(Schema::hasColumn('production_order_stage_entries',$column)) $table->dropColumn($column);
        });
    }
};
