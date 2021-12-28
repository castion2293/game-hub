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
        Schema::create('game_kind_category', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->unsignedInteger('game_kind_id')->comment('遊戲資訊ID');
            $table->unsignedInteger('game_category_id')->comment('遊戲分類ID');
            // 建立時間
            $table->datetime('created_at')
                ->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            $table->unique(['game_kind_id', 'game_category_id']);
        });

        DB::statement("ALTER TABLE game_kind_category COMMENT '遊戲資訊 - 遊戲分類 樞紐表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_kind_category');
    }
};
