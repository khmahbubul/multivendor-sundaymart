<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id()->from(1500);
            $table->morphs('payable');
            $table->index('payable_id');
            $table->index('payable_type');
            $table->double('price', 22);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('payment_sys_id')->nullable();
            $table->string('payment_trx_id', 255)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('perform_time')->nullable();
            $table->timestamp('refund_time')->nullable();
            $table->enum('status', ['progress', 'paid', 'canceled', 'rejected'])
                ->default('progress')->index();
            $table->string('status_description');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
