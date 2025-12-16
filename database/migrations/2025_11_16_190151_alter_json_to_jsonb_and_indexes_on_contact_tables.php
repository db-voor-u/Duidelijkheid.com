<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // json -> jsonb
        DB::statement("ALTER TABLE contact_messages
            ALTER COLUMN utm TYPE jsonb USING utm::jsonb");

        DB::statement("ALTER TABLE contact_pages
            ALTER COLUMN additional_recipients TYPE jsonb USING additional_recipients::jsonb");

        // GIN indexen voor snelle queries
        DB::statement("CREATE INDEX IF NOT EXISTS contact_messages_utm_gin ON contact_messages USING GIN (utm)");
        DB::statement("CREATE INDEX IF NOT EXISTS contact_pages_addrec_gin ON contact_pages USING GIN (additional_recipients)");

        // Handige extra indexen
        DB::statement("CREATE INDEX IF NOT EXISTS cm_new_nospam_idx
            ON contact_messages (created_at DESC)
            WHERE is_spam = false AND status = 'new'");

        DB::statement("CREATE INDEX IF NOT EXISTS cm_created_on_idx
            ON contact_messages ((created_at::date))");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS contact_messages_utm_gin");
        DB::statement("DROP INDEX IF EXISTS contact_pages_addrec_gin");
        DB::statement("DROP INDEX IF EXISTS cm_new_nospam_idx");
        DB::statement("DROP INDEX IF EXISTS cm_created_on_idx");

        DB::statement("ALTER TABLE contact_messages
            ALTER COLUMN utm TYPE json USING utm::json");

        DB::statement("ALTER TABLE contact_pages
            ALTER COLUMN additional_recipients TYPE json USING additional_recipients::json");
    }
};
