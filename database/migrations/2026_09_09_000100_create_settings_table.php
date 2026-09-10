<?php

use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::create('settings',function(Blueprint $t){$t->string('key')->primary();$t->longText('value')->nullable();});}public function down():void{Schema::dropIfExists('settings');}};
