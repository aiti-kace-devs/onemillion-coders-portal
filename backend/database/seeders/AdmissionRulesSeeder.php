<?php

namespace Database\Seeders;

use App\Models\Rule;
use Illuminate\Database\Seeder;

class AdmissionRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Pass Mark',
                'rule_class_path' => 'App\\Services\\AdmissionRules\\PassMark',
                'description' => 'Filter students by minimum exam score (hard filter). This is typically the first rule in the pipeline.',
                'default_parameters' => json_encode(['pass_mark' => 22]),
                'is_active' => true,
            ],
            [
                'name' => 'Completed Exam',
                'rule_class_path' => 'App\\Services\\AdmissionRules\\CompletedExam',
                'description' => 'Ensure students have completed and submitted their exam (hard filter).',
                'default_parameters' => json_encode([
                    'require_completion' => true,
                    'require_submission' => true
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'Applied Before',
                'rule_class_path' => 'App\\Services\\AdmissionRules\\AppliedBefore',
                'description' => 'Filter or prioritize students based on application date. Can include only those before a date, or prioritize early applicants.',
                'default_parameters' => json_encode([
                    'before_date' => null,
                    'priority' => 'include_only' // Options: include_only, prioritize, exclude
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'Sort By Date',
                'rule_class_path' => 'App\\Services\\AdmissionRules\\SortByDate',
                'description' => 'Sort applicants by registration date (first-come, first-served or reverse).',
                'default_parameters' => json_encode(['direction' => 'asc']), // asc or desc
                'is_active' => true,
            ],
            [
                'name' => 'Gender Quota',
                'rule_class_path' => 'App\\Services\\AdmissionRules\\GenderQuota',
                'description' => 'Ensure minimum representation for a specific gender by prioritizing them in the results.',
                'default_parameters' => json_encode([
                    'gender' => 'female',
                    'min_count' => 0
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'Age Range',
                'rule_class_path' => 'App\\Services\\AdmissionRules\\AgeRange',
                'description' => 'Filter students by age range.',
                'default_parameters' => json_encode([
                    'min_age' => null,
                    'max_age' => null
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'Educational Level',
                'rule_class_path' => 'App\\Services\\AdmissionRules\\EducationalLevel',
                'description' => 'Sort and optionally filter students by educational hierarchy (highest education first).',
                'default_parameters' => json_encode([
                    'hierarchy' => ['PhD', 'Masters', 'Bachelors', 'HND', 'Diploma', 'SHS', 'JHS'],
                    'min_level' => null // Optional minimum level
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            Rule::updateOrCreate(
                ['rule_class_path' => $rule['rule_class_path']],
                $rule
            );
        }

        $this->command->info('✅ Admission rules seeded successfully!');
    }
}
