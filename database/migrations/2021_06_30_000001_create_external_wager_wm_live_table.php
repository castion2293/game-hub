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
        Schema::create('external_wager_wm_live', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('PK');
            $table->string('no', 50)->comment('下注單號');
            $table->unsignedBigInteger('member_id')->comment('會員ID');
            $table->unsignedSmallInteger('game_type')->default(0)->comment('遊戲類別');
            $table->unsignedDecimal('bet_total', 20, 4)->default(0)->comment('下注金額');
            $table->unsignedDecimal('bet_effective', 20, 4)->default(0)->comment('有效下注總額');
            $table->unsignedDecimal('commission', 10, 4)->default(0)->comment('退水');
            $table->decimal('payoff_none', 20, 4)->default(0)->comment('輸贏結果(未含退水)');
            $table->decimal('payoff', 20, 4)->default(0)->comment('會員結果(含退水)');
            $table->unsignedTinyInteger('status')->default(1)->comment('注單狀態(1:已結, 2:未結, 3:取消單)');
            $table->dateTime('bet_at')->comment('下注時間');
            $table->dateTime('check_at')->default('9999-12-31 00:00:00')->comment('結帳時間');
            $table->json('bet_info')->comment('下注內容');
            $table->json('holding')->comment('佔成設定');

            // 建立時間
            $table->datetime('created_at')
                ->default(DB::raw('CURRENT_TIMESTAMP'))
                ->comment('建立時間');

            // 最後更新
            $table->datetime('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))
                ->comment('最後更新');

            $table->unique(['no', 'member_id']);
            $table->index(['bet_at', 'status']);
            $table->index(['check_at', 'status']);
        });

        DB::statement("ALTER TABLE external_wager_wm_live COMMENT '外接遊戲 - 完美真人注單資訊'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('external_wager_wm_live');
    }
};
