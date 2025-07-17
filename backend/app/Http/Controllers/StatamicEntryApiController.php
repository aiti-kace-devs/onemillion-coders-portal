<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Statamic\Facades\Entry;
use Statamic\Facades\Collection;
use Statamic\Facades\Asset;
use Statamic\Facades\Term;

class StatamicEntryApiController extends Controller
{
    /**
     * Get entries with eager loaded related entries
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'collection' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'fields' => 'nullable|string', // comma-separated field names
            'include_related' => 'nullable|string', // comma-separated relationship field names with dot notation for nesting
        ]);

        $collection = $request->input('collection', 'pages');
        $limit = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $fields = $request->input('fields') ? explode(',', $request->input('fields')) : null;
        $includeRelated = $request->input('include_related') ? explode(',', $request->input('include_related')) : null;

        // Build the query
        $query = Entry::query()->where('collection', $collection);

        // Apply pagination
        $entries = $query->paginate($limit, ['*'], 'page', $page);

        // Transform entries with related data
        $data = $entries->getCollection()->map(function ($entry) use ($fields, $includeRelated) {
            $entryData = $this->transformEntry($entry, $fields);

            // Include related entries if requested
            if ($includeRelated) {
                $related = $this->getRelatedEntries($entry, $includeRelated);
                if ($related instanceof \Illuminate\Support\Collection) {
                    $related = $related->toArray();
                }
                $entryData = array_merge($entryData, $related);
            }

            return $entryData;
        });

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
                'from' => $entries->firstItem(),
                'to' => $entries->lastItem(),
            ],
            'meta' => [
                'collection' => $collection,
                'fields' => $fields,
                'include_related' => $includeRelated,
            ]
        ]);
    }

    /**
     * Get a single entry with related entries
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'fields' => 'nullable|string',
            'include_related' => 'nullable|string',
        ]);

        $entry = Entry::find($id);

        if (!$entry) {
            return response()->json(['error' => 'Entry not found'], 404);
        }

        $fields = $request->input('fields') ? explode(',', $request->input('fields')) : null;
        $includeRelated = $request->input('include_related') ? explode(',', $request->input('include_related')) : null;

        $entryData = $this->transformEntry($entry, $fields);

        if ($includeRelated) {
            $related = $this->getRelatedEntries($entry, $includeRelated);
            if ($related instanceof \Illuminate\Support\Collection) {
                $related = $related->toArray();
            }
            $entryData = array_merge($entryData, $related);
        }

        return response()->json(['data' => $entryData]);
    }

    /**
     * Get a single entry from the Pages collection by slug
     */
    public function showPageBySlug(Request $request, $slug)
    {
        $request->validate([
            'fields' => 'nullable|string',
            'include_related' => 'nullable|string',
        ]);

        $fields = $request->input('fields');
        $includeRelated = $request->input('include_related') ?? 'sections.section_items';
        $relationshipFields = $includeRelated ? array_map('trim', explode(',', $includeRelated)) : [];

        $entry = \Statamic\Facades\Entry::query()
            ->where('collection', 'pages')
            ->where('slug', $slug)
            ->first();

        if (!$entry) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $data = $entry->data();
        $data['id'] = $entry->id();
        $data['slug'] = $entry->slug();
        $data['url'] = $entry->url();
        $data['collection'] = $entry->collection()->handle();

        if (!empty($relationshipFields)) {
            $related = $this->getRelatedEntries($entry, $relationshipFields);
            if ($related instanceof \Illuminate\Support\Collection) {
                $related = $related->toArray();
            }
            if ($data instanceof \Illuminate\Support\Collection) {
                $data = $data->toArray();
            }
            $data = array_merge($data, $related);
        }

        // Optionally filter fields
        if ($fields) {
            $fieldsArr = array_map('trim', explode(',', $fields));
            $data = array_intersect_key($data, array_flip($fieldsArr));
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Get entries by collection
     */
    public function byCollection(Request $request, string $collection): JsonResponse
    {
        $request->merge(['collection' => $collection]);
        return $this->index($request);
    }

    /**
     * Transform entry to array with specified fields
     */
    private function transformEntry($entry, ?array $fields = null): array
    {
        $data = [
            'id' => $entry->id(),
            // 'slug' => $entry->slug(),
            'title' => $entry->get('title'),
            // 'collection' => $entry->collection()->handle(),
            // 'url' => $entry->url(),
            // 'published' => $entry->published(),
            // 'date' => $entry->date(),
            // 'updated_at' => $entry->lastModified(),
        ];

        // Add custom fields if specified
        if ($fields) {
            foreach ($fields as $field) {
                $field = trim($field);
                if (
                    $field && $field !== 'id' && $field !== 'slug' && $field !== 'title' &&
                    $field !== 'collection' && $field !== 'url' && $field !== 'published' &&
                    $field !== 'date' && $field !== 'updated_at'
                ) {
                    $data[$field] = $entry->get($field);
                }
            }
        } else {
            // Include all data fields if no specific fields requested
            $entryData = $entry->data();
            foreach ($entryData as $key => $value) {
                if (!in_array($key, [
                    'id',
                    // 'slug', 'collection', 'url', 'published', 'date', 'updated_at'
                    'title',
                ])) {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * Get related entries for specified relationship fields with nested relationships
     */
    private function getRelatedEntries($entry, array $relationshipFields): array
    {
        $related = [];

        foreach ($relationshipFields as $field) {
            $field = trim($field);
            $fieldParts = explode('.', $field);
            $mainField = $fieldParts[0];

            if ($entry->has($mainField)) {
                $fieldValue = $entry->get($mainField);

                if (is_array($fieldValue)) {
                    // Handle array of associative arrays (e.g., Bard/Grid/blocks)
                    if (!empty($fieldValue) && is_array($fieldValue[0]) && array_keys($fieldValue[0]) !== range(0, count($fieldValue[0]) - 1)) {
                        // If next part exists (e.g., block_items)
                        if (isset($fieldParts[1])) {
                            $nestedField = $fieldParts[1];
                            $remainingFields = array_slice($fieldParts, 1);
                            $related[$mainField] = array_map(function ($block) use ($nestedField, $remainingFields) {
                                $blockWithRelated = $block;
                                if (isset($block[$nestedField]) && is_array($block[$nestedField])) {
                                    // block_items is an array of entry IDs
                                    $blockWithRelated[$nestedField] = collect($block[$nestedField])
                                        ->map(function ($id) use ($remainingFields) {
                                            $relatedEntry = \Statamic\Facades\Entry::find($id);
                                            if ($relatedEntry) {
                                                // If there are more nested fields, recurse
                                                if (count($remainingFields) > 1) {
                                                    return $this->transformRelatedEntry($relatedEntry, array_slice($remainingFields, 1));
                                                }
                                                return $this->transformRelatedEntry($relatedEntry, []);
                                            }
                                            return null;
                                        })
                                        ->filter()
                                        ->values()
                                        ->all();
                                }
                                return $blockWithRelated;
                            }, $fieldValue);
                        } else {
                            $related[$mainField] = $fieldValue;
                        }
                    } else {
                        // Flat array of IDs (e.g., related entry IDs)
                        $related[$mainField] = collect($fieldValue)
                            ->map(function ($id) {
                                $relatedEntry = \Statamic\Facades\Entry::find($id);
                                return $relatedEntry ? $this->transformRelatedEntry($relatedEntry, []) : null;
                            })
                            ->filter()
                            ->values()
                            ->all();
                    }
                } else {
                    // Single ID (string)
                    $relatedEntry = \Statamic\Facades\Entry::find($fieldValue);
                    $related[$mainField] = $relatedEntry ? $this->transformRelatedEntry($relatedEntry, []) : null;
                }
            }
        }
        return $related;
    }

    /**
     * Transform a related entry with nested relationships
     */
    private function transformRelatedEntry($entry, array $fieldParts): array | \Illuminate\Support\Collection
    {
        // Get all entry data fields
        $data = $entry->data();
        // Add top-level meta fields for clarity
        $data['id'] = $entry->id();
        $data['slug'] = $entry->slug();
        // $data['url'] = $entry->url();
        $data['collection'] = $entry->collection()->handle();

        // Augment asset fields at the top level of this entry
        foreach ($data as $key => $value) {
            if ($this->isAssetField($key)) {
                $data[$key] = $this->fetchAssetData($value, $entry, $key);
            }
        }

        // If there are nested relationships (more than 1 part), load them
        if (count($fieldParts) > 1) {
            $nestedRelationships = array_slice($fieldParts, 1);
            $nestedData = $this->getNestedRelationships($entry, $nestedRelationships);
            // Instead of putting in 'related', merge into the original property
            foreach ($nestedData as $nestedKey => $nestedValue) {
                $data[$nestedKey] = $nestedValue;
            }
        }

        return $data;
    }

    /**
     * Helper to detect and fetch asset(s) for a given value
     */
    private function fetchAssetData($value, $entry = null, $fieldName = null)
    {
        // Default to 'main' if we can't determine the container
        $container = config('filesystems.default', 'main');

        // Try to get the container from the blueprint
        if ($entry && $fieldName) {
            $blueprint = $entry->blueprint();
            if ($blueprint && $field = $blueprint->field($fieldName)) {
                $config = $field->config();
                if (isset($config['container'])) {
                    $container = $config['container'];
                }
            }
        }

        if (is_array($value)) {
            return collect($value)
                ->map(function ($assetId) use ($entry, $fieldName, $container) {
                    return $this->fetchAssetData($assetId, $entry, $fieldName);
                })
                ->filter()
                ->values()
                ->all();
        } elseif (is_string($value)) {
            $assetId = str_contains($value, '::') ? $value : ($container . '::' . ltrim($value, '/'));
            $asset = Asset::find($assetId);
            if ($asset) {
                return [
                    'id' => $asset->id(),
                    'url' => $asset->url(),
                    'path' => $asset->path(),
                    'basename' => $asset->basename(),
                    'extension' => $asset->extension(),
                    'mime_type' => $asset->mimeType(),
                    // 'size' => $asset->size(),
                    // 'last_modified' => $asset->lastModified(),
                    'alt' => $asset->get('alt'),
                    'meta' => $asset->meta(),
                ];
            }
        }
        return null;
    }

    /**
     * Helper to determine if a field is likely an asset field
     */
    private function isAssetField($fieldName)
    {
        // You can customize this logic based on your blueprints/field naming
        $assetFieldNames = ['media', 'image', 'images', 'asset', 'assets', 'file', 'files', 'gallery'];
        return in_array($fieldName, $assetFieldNames);
    }

    /**
     * Get nested relationships for an entry
     */
    private function getNestedRelationships($entry, array $nestedFields): array
    {
        $nested = [];

        foreach ($nestedFields as $fieldIndex => $field) {
            $field = trim($field);

            if ($entry->has($field)) {
                $fieldValue = $entry->get($field);

                // Asset field handling
                if ($this->isAssetField($field)) {
                    $nested[$field] = $this->fetchAssetData($fieldValue, $entry, $field);
                    continue;
                }

                if (is_array($fieldValue)) {
                    // Handle array of arrays (associative arrays, e.g., Bard/Grid/custom blocks)
                    if (!empty($fieldValue) && is_array($fieldValue[0]) && array_keys($fieldValue[0]) !== range(0, count($fieldValue[0]) - 1)) {
                        // If there is a deeper nested relationship (e.g., blocks.block_items or blocks.block_items.media)
                        $nextField = isset($nestedFields[$fieldIndex + 1]) ? $nestedFields[$fieldIndex + 1] : null;
                        $remainingFields = array_slice($nestedFields, $fieldIndex + 1);
                        if ($nextField) {
                            $blocksWithRelated = array_map(function ($block) use ($nextField, $remainingFields, $entry) {
                                $block = (array) $block;
                                if (isset($block[$nextField]) && is_array($block[$nextField])) {
                                    // Asset field inside block
                                    if ($this->isAssetField($nextField)) {
                                        $block[$nextField] = $this->fetchAssetData($block[$nextField], $entry, $nextField);
                                    } elseif (!empty($block[$nextField]) && is_string($block[$nextField][0])) {
                                        $relatedEntries = Entry::query()
                                            ->whereIn('id', $block[$nextField])
                                            ->get()
                                            ->map(function ($relatedEntry) use ($remainingFields) {
                                                // Recursively process deeper relationships
                                                return $this->transformRelatedEntry($relatedEntry, $remainingFields);
                                            });
                                        $block[$nextField] = $relatedEntries;
                                    }
                                }
                                return $block;
                            }, $fieldValue);
                            $nested[$field] = $blocksWithRelated;
                        } else {
                            $nested[$field] = $fieldValue;
                        }
                    } else {
                        // Flat array of IDs
                        $nestedEntries = Entry::query()
                            ->whereIn('id', $fieldValue)
                            ->get()
                            ->map(function ($nestedEntry) use ($nestedFields, $fieldIndex) {
                                $remainingFields = array_slice($nestedFields, $fieldIndex + 1);
                                return $this->transformRelatedEntry($nestedEntry, $remainingFields);
                            });
                        $nested[$field] = $nestedEntries;
                    }
                } elseif (is_string($fieldValue)) {
                    $nestedEntry = Entry::find($fieldValue);
                    if ($nestedEntry) {
                        $remainingFields = array_slice($nestedFields, $fieldIndex + 1);
                        $nested[$field] = $this->transformRelatedEntry($nestedEntry, $remainingFields);
                    }
                } else {
                    $nested[$field] = null;
                }
            }
        }

        return $nested;
    }

    /**
     * Get available collections
     */
    public function collections(): JsonResponse
    {
        $collections = Collection::all()->map(function ($collection) {
            return [
                'handle' => $collection->handle(),
                'title' => $collection->title(),
                'entries_count' => $collection->queryEntries()->count(),
            ];
        });

        return response()->json(['data' => $collections]);
    }

    /**
     * Search entries
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'collection' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = $request->input('q');
        $collection = $request->input('collection');
        $limit = $request->input('limit', 25);

        $entriesQuery = Entry::query();

        if ($collection) {
            $entriesQuery->where('collection', $collection);
        }

        // Search in title and content fields
        $entries = $entriesQuery->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhere('content', 'like', "%{$query}%");
        })->limit($limit)->get();

        $data = $entries->map(function ($entry) {
            return [
                'id' => $entry->id(),
                'title' => $entry->get('title'),
                'slug' => $entry->slug(),
                'url' => $entry->url(),
                'collection' => $entry->collection()->handle(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'query' => $query,
                'total' => $entries->count(),
            ]
        ]);
    }
}
