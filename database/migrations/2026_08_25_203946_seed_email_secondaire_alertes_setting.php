<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Setting::get('email_secondaire_alertes') === null) {
            Setting::set('email_secondaire_alertes', 'nathalie.marksman@uae-eps.com');
        }
    }

    public function down(): void
    {
        Setting::where('key', 'email_secondaire_alertes')->delete();
    }
};
