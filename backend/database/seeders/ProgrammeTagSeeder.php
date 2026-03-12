<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Programme;
use App\Models\Tag;
use App\Models\TagType;

class ProgrammeTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tagType = TagType::firstOrCreate(
            ['name' => 'Programme'],
            ['target_models' => ['App\Models\Course', 'App\Models\Programme', 'App\Models\OexQuestionMaster']]
        );

        $programmes = Programme::whereNotNull('title')->get();

        foreach ($programmes as $programme) {
            Tag::updateOrCreate(
                [
                    'name' => $programme->title,
                    'tag_type_id' => $tagType->id
                ]
            );
        }

        $this->command->info('Configured ' . $programmes->count() . ' tags matched to existing Programmes!');

        $difficultyTagType = TagType::firstOrCreate(
            ['name' => 'Difficulty Level'],
            ['target_models' => ['App\Models\Course', 'App\Models\Programme', 'App\Models\OexQuestionMaster']]
        );

        $difficulties = ['Beginner', 'Intermediate', 'Advanced'];
        foreach ($difficulties as $difficulty) {
            Tag::updateOrCreate(
                [
                    'name' => $difficulty,
                    'tag_type_id' => $difficultyTagType->id
                ]
            );
        }

        $this->command->info('Configured Difficulty Level tags!');
    }
}
