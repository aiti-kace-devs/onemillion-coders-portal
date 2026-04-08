<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partner_integrations')) {
            return;
        }

        Schema::table('partner_integrations', function (Blueprint $table) {
            if (! Schema::hasColumn('partner_integrations', 'response_mapping_json')) {
                $table->json('response_mapping_json')->nullable();
            }
            if (! Schema::hasColumn('partner_integrations', 'pagination_mapping_json')) {
                $table->json('pagination_mapping_json')->nullable();
            }
            if (! Schema::hasColumn('partner_integrations', 'metrics_mapping_json')) {
                $table->json('metrics_mapping_json')->nullable();
            }
            if (! Schema::hasColumn('partner_integrations', 'validation_contract_json')) {
                $table->json('validation_contract_json')->nullable();
            }
        });

        $this->normalizePartnerCodes();
    }

    public function down(): void
    {
        if (! Schema::hasTable('partner_integrations')) {
            return;
        }

        Schema::table('partner_integrations', function (Blueprint $table) {
            foreach (['response_mapping_json', 'pagination_mapping_json', 'metrics_mapping_json', 'validation_contract_json'] as $col) {
                if (Schema::hasColumn('partner_integrations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function normalizePartnerCodes(): void
    {
        if (Schema::hasTable('partner_integrations')) {
            DB::table('partner_integrations')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $normalized = $this->slug((string) ($row->partner_code ?? ''));
                    if ($normalized === '' || $normalized === $row->partner_code) {
                        continue;
                    }
                    $exists = DB::table('partner_integrations')
                        ->where('partner_code', $normalized)
                        ->where('id', '!=', $row->id)
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                    DB::table('partner_integrations')->where('id', $row->id)->update(['partner_code' => $normalized]);
                }
            });
        }

        if (Schema::hasTable('partner_course_mappings')) {
            DB::table('partner_course_mappings')->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $normalized = $this->slug((string) ($row->partner_code ?? ''));
                    if ($normalized === '' || $normalized === $row->partner_code) {
                        continue;
                    }
                    $dup = DB::table('partner_course_mappings')
                        ->where('partner_code', $normalized)
                        ->where('course_id', $row->course_id)
                        ->exists();
                    if ($dup) {
                        continue;
                    }
                    DB::table('partner_course_mappings')->where('id', $row->id)->update(['partner_code' => $normalized]);
                }
            });
        }
    }

    private function slug(string $code): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_-]+/', '-', $code) ?? $code;
        $code = trim((string) $code, '-');

        return $code;
    }
};
