<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Entry;
use Statamic\Entries\Entry as EntryModel;

class CustomApiController extends Controller
{
    /**
     * Fetch a single entry and augment its replicator fields
     * to include full data for related entries.
     *
     * @param string $collection The handle of the collection.
     * @param string $slug The slug of the entry.
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($collection, $slug)
    {
        // 1. Find the entry by its collection and slug.
        // We use `findBySlug` which will return null if not found.
        $entry = Entry::findBySlug($slug, $collection);

        // 2. Handle the case where the entry doesn't exist.
        if (!$entry) {
            return response()->json(['error' => 'Entry not found.'], 404);
        }

        // 3. Get the raw data for the entry.
        // Using `data()` gives us the unprocessed values, including the IDs in relationship fields.
        $data = $entry->data()->all();

        // 4. Manually "eager load" related entries in the Replicator field.
        // Replace 'your_replicator_field_handle' with the actual handle of your Replicator field.
        $replicatorFieldHandle = 'blocks'; // <-- IMPORTANT: CHANGE THIS

        if (isset($data[$replicatorFieldHandle]) && is_array($data[$replicatorFieldHandle])) {
            // Loop through each set in the Replicator field.
            $data[$replicatorFieldHandle] = collect($data[$replicatorFieldHandle])->map(function ($set) {

                // Check if the set has the relationship field you're interested in.
                // Replace 'related_articles' with the handle of your Entries field.
                $relationshipFieldHandle = 'related_articles'; // <-- IMPORTANT: CHANGE THIS

                if (isset($set[$relationshipFieldHandle]) && is_array($set[$relationshipFieldHandle])) {

                    // Get the array of related entry IDs.
                    $relatedEntryIds = $set[$relationshipFieldHandle];

                    // Fetch the full entry data for each ID.
                    $relatedEntries = collect($relatedEntryIds)->map(function ($id) {

                        // Find the entry by its ID.
                        $relatedEntry = Entry::find($id);

                        // If found, return its augmented data. Otherwise, return null.
                        // `toAugmentedArray` provides all the computed values you'd expect in a template.
                        return $relatedEntry ? $relatedEntry->toAugmentedArray() : null;
                    })->filter(); // Use filter() to remove any nulls if an entry wasn't found.

                    // Replace the array of IDs with the collection of full entry objects.
                    $set[$relationshipFieldHandle] = $relatedEntries->values()->all();
                }

                return $set;
            })->all();
        }

        // 5. Return the modified data as a JSON response.
        // We wrap the final data in a 'data' key to follow common API conventions.
        return response()->json(['data' => $data]);
    }
}

/**
 * ------------------------------------------------------------------
 * HOW TO USE THIS
 * ------------------------------------------------------------------
 *
 * 1. SAVE THE CONTROLLER:
 * Save this file as `app/Http/Controllers/CustomApiController.php`.
 *
 * 2. REGISTER THE ROUTE:
 * Open your `routes/api.php` file and add the following route definition:
 *
 * use App\Http\Controllers\CustomApiController;
 *
 * Route::get('/custom/{collection}/{slug}', [CustomApiController::class, 'show']);
 *
 *
 * 3. CONFIGURE THE CONTROLLER:
 * - In the `CustomApiController.php` file, change `$replicatorFieldHandle` to the handle
 * of your Replicator field (e.g., 'page_builder', 'content_sections').
 * - Change `$relationshipFieldHandle` to the handle of your Entries field inside the
 * Replicator set (e.g., 'featured_posts', 'related_products').
 *
 * 4. ACCESS THE ENDPOINT:
 * You can now access your data by visiting a URL like:
 * https://your-domain.com/api/custom/pages/about-us
 *
 * This will return a JSON object for the 'about-us' entry from the 'pages' collection,
 * with the related entries in the 'content_blocks' Replicator field fully expanded.
 *
 */
