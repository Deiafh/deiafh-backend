<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The original migration had a typo: an Arabic ي character instead of 'd'
        // Column name: visa_fixeي_fees → rename to visa_fixed_fees
        $allCols = array_column(DB::select('SHOW COLUMNS FROM settings'), 'Field');
        if (!in_array('visa_fixed_fees', $allCols)) {
            // The bad column name contains Arabic ya (U+064A) between 'fixe' and '_fees'
            $badName = "visa_fixe\u{064A}_fees";
            DB::statement("ALTER TABLE settings CHANGE `{$badName}` `visa_fixed_fees` FLOAT NOT NULL DEFAULT 0");
        }
    }

    public function down(): void
    {
        // irreversible — the original name had a unicode typo
    }
};
