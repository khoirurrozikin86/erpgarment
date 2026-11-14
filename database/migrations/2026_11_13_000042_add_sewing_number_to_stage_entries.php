<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  if(!Schema::hasTable('production_order_stage_entries'))return;
  if(!Schema::hasColumn('production_order_stage_entries','sewing_number'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->string('sewing_number',100)->nullable());
  if(!collect(Schema::getIndexes('production_order_stage_entries'))->contains(fn($i)=>$i['name']==='sew_stage_number_idx'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->index('sewing_number','sew_stage_number_idx'));
 }
 public function down():void{if(Schema::hasTable('production_order_stage_entries')){if(collect(Schema::getIndexes('production_order_stage_entries'))->contains(fn($i)=>$i['name']==='sew_stage_number_idx'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->dropIndex('sew_stage_number_idx'));if(Schema::hasColumn('production_order_stage_entries','sewing_number'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->dropColumn('sewing_number'));}}
};
