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
        Schema::create('member_game_setting', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->unsignedInteger('member_id')->comment('會員Id (rel:members > id)');
            $table->json('enter_game')->comment('已進入過遊戲清單');
            $table->json('switch')->comment('遊戲狀態');
            $table->json('model')->comment('遊戲範本');
            $table->json('win_limit')->comment('限贏金額');
            $table->json('bet_limit')->comment('最低投注額');
            // 建立時間
            $table->datetime('created_at')
                ->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            $table->unique('member_id');
        });

        DB::statement("ALTER TABLE member_game_setting COMMENT '會員遊戲設定'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('member_game_setting');
    }
};
