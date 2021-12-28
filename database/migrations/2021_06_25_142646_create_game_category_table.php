<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_category', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('name', 20)->comment('名稱');
            $table->unsignedTinyInteger('active')->default(1)->comment('狀態(1:啟用, 2:停用)');
            $table->unsignedTinyInteger('sort')->default(0)->comment('排序(由數字小到大由上往下排列顯示)');
            // 建立時間
            $table->datetime('created_at')
                ->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            $table->unique('name');
        });

        DB::statement("ALTER TABLE game_category COMMENT '遊戲分類'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_category');
    }
};
