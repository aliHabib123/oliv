<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time setup fee per country: AED 1,500 converted at
     * 1 USD = 3.6725 AED = 3.75 SAR = 3.64 QAR ≈ 0.745 GBP ≈ 0.855 EUR.
     */
    private const SETUP_FEES = [
        'AE' => 1500,
        'US' => 408,
        'SA' => 1532,
        'QA' => 1487,
        'GB' => 304,
        'EU' => 349,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('package_pricing', function (Blueprint $table) {
            $table->decimal('setup_fee', 10, 2)->default(0)->after('yearly_price');
        });

        foreach (self::SETUP_FEES as $countryCode => $fee) {
            DB::table('package_pricing')
                ->where('country_code', $countryCode)
                ->update(['setup_fee' => $fee]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_pricing', function (Blueprint $table) {
            $table->dropColumn('setup_fee');
        });
    }
};
