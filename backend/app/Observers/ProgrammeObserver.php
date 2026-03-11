<?php

namespace App\Observers;

use App\Models\Programme;
use App\Models\Tag;
use App\Models\TagType;

class ProgrammeObserver
{
    /**
     * Handle the Programme "created" event.
     *
     * @param  \App\Models\Programme  $programme
     * @return void
     */
    public function created(Programme $programme)
    {
        $this->syncProgrammeTag($programme);
    }

    /**
     * Handle the Programme "updated" event.
     *
     * @param  \App\Models\Programme  $programme
     * @return void
     */
    public function updated(Programme $programme)
    {
        // For 'updated', check if title changed to update the tag, or just ensure it exists
        if ($programme->isDirty('title')) {
            $originalTitle = $programme->getOriginal('title');

            // Find existing tag by old title
            $tagType = $this->getProgrammeTagType();
            $tag = Tag::where('name', $originalTitle)
                ->where('tag_type_id', $tagType->id)
                ->first();

            if ($tag) {
                // Update existing tag
                $tag->update(['name' => $programme->title]);
            } else {
                // Create it if it didn't exist for some reason
                $this->syncProgrammeTag($programme);
            }
        } else {
            // In case title didn't change but tag was deleted accidentally
            $this->syncProgrammeTag($programme);
        }
    }

    /**
     * Handle the Programme "deleted" event.
     *
     * @param  \App\Models\Programme  $programme
     * @return void
     */
    public function deleted(Programme $programme)
    {
        $tagType = $this->getProgrammeTagType();
        Tag::where('name', $programme->title)
            ->where('tag_type_id', $tagType->id)
            ->delete();
    }

    /**
     * Create a corresponding Tag for the Programme.
     */
    protected function syncProgrammeTag(Programme $programme)
    {
        if (empty($programme->title)) {
            return;
        }

        $tagType = $this->getProgrammeTagType();

        // UpdateOrCreate to ensure it exists
        Tag::updateOrCreate(
            [
                'name' => $programme->title,
                'tag_type_id' => $tagType->id
            ]
        );
    }

    /**
     * Get or create the Programme TagType
     */
    protected function getProgrammeTagType()
    {
        return TagType::firstOrCreate(
            ['name' => 'Programme'],
            ['target_models' => ['App\Models\Course']]
        );
    }
}
