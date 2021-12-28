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
        Schema::create('game_kind', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('code', 20)->comment('遊戲代碼');
            $table->string('name', 20)->comment('遊戲名稱');
            $table->unsignedTinyInteger('active')->default(1)->comment('狀態(1:啟用, 2:停用, 3:敬請期待, 4:維護, 5:下架)');
            $table->dateTime('open_at')->nullable()->comment('掛維護後開放時間');
            $table->unsignedTinyInteger('sort')->default(0)->comment('排序(由數字小到大由上往下排列顯示)');
            $table->boolean('lobby')->default(true)->comment('是否有遊戲大廳');
            // 建立時間
            $table->datetime('created_at')
                ->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            $table->unique('code');
        });

        DB::statement("ALTER TABLE game_kind COMMENT '遊戲資訊'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_kind');
    }
};
