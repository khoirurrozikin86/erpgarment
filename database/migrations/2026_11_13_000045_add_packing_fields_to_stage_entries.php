<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{if(!Schema::hasTable('production_order_stage_entries'))return;if(!Schema::hasColumn('production_order_stage_entries','packing_number'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->string('packing_number',100)->nullable());if(!Schema::hasColumn('production_order_stage_entries','deleted_at'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->softDeletes());if(!collect(Schema::getIndexes('production_order_stage_entries'))->contains(fn($i)=>$i['name']==='pack_stage_number_idx'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->index('packing_number','pack_stage_number_idx'));}
 public function down():void{if(!Schema::hasTable('production_order_stage_entries'))return;if(collect(Schema::getIndexes('production_order_stage_entries'))->contains(fn($i)=>$i['name']==='pack_stage_number_idx'))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->dropIndex('pack_stage_number_idx'));foreach(['packing_number','deleted_at'] as $c)if(Schema::hasColumn('production_order_stage_entries',$c))Schema::table('production_order_stage_entries',fn(Blueprint $t)=>$t->dropColumn($c));}
};
