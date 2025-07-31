# Statamic Custom API Documentation

This document describes the custom Statamic API endpoints that provide access to entries with eager loading of related entries, including nested relationships.

## Base URL
All endpoints are prefixed with `/api/statamic/`

## Endpoints

### 1. Get All Entries
**GET** `/api/statamic/entries`

**Query Parameters:**
- `collection` (optional): Collection handle (default: 'pages')
- `limit` (optional): Number of entries per page (default: 25, max: 100)
- `page` (optional): Page number (default: 1)
- `fields` (optional): Comma-separated list of fields to include
- `include_related` (optional): Comma-separated list of relationship field names with dot notation for nesting

**Dot Notation for Nested Relationships:**
Use dot notation to load nested relationships. For example:
- `author` - loads the author entry
- `author.posts` - loads the author and their posts
- `author.posts.comments` - loads the author, their posts, and comments on those posts

**Example:**
```
GET /api/statamic/entries?collection=blog&limit=10&fields=title,content&include_related=author.posts,related_posts.comments
```

**Response:**
```json
{
  "data": [
    {
      "id": "entry-id",
      "slug": "my-entry",
      "title": "My Entry Title",
      "collection": "blog",
      "url": "/blog/my-entry",
      "published": true,
      "date": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z",
      "content": "Entry content...",
      "related": {
        "author": {
          "id": "author-id",
          "title": "Author Name",
          "slug": "author-name",
          "url": "/authors/author-name",
          "related": {
            "posts": [
              {
                "id": "post-1",
                "title": "Author's Post 1",
                "slug": "authors-post-1",
                "url": "/blog/authors-post-1"
              },
              {
                "id": "post-2",
                "title": "Author's Post 2",
                "slug": "authors-post-2",
                "url": "/blog/authors-post-2"
              }
            ]
          }
        },
        "related_posts": [
          {
            "id": "related-entry-id",
            "title": "Related Entry",
            "slug": "related-entry",
            "url": "/blog/related-entry",
            "related": {
              "comments": [
                {
                  "id": "comment-1",
                  "title": "Comment 1",
                  "slug": "comment-1",
                  "url": "/comments/comment-1"
                },
                {
                  "id": "comment-2",
                  "title": "Comment 2",
                  "slug": "comment-2",
                  "url": "/comments/comment-2"
                }
              ]
            }
          }
        ]
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50,
    "from": 1,
    "to": 10
  },
  "meta": {
    "collection": "blog",
    "fields": ["title", "content"],
    "include_related": ["author.posts", "related_posts.comments"]
  }
}
```

### 2. Get Single Entry
**GET** `/api/statamic/entries/{id}`

**Query Parameters:**
- `fields` (optional): Comma-separated list of fields to include
- `include_related` (optional): Comma-separated list of relationship field names with dot notation for nesting

**Example:**
```
GET /api/statamic/entries/entry-id?include_related=author.posts.comments,related_posts
```

### 3. Get Entries by Collection
**GET** `/api/statamic/collections/{collection}/entries`

**Path Parameters:**
- `collection`: Collection handle

**Query Parameters:** Same as "Get All Entries"

**Example:**
```
GET /api/statamic/collections/blog/entries?limit=5&include_related=author.posts
```

### 4. Get Available Collections
**GET** `/api/statamic/collections`

**Response:**
```json
{
  "data": [
    {
      "handle": "blog",
      "title": "Blog Posts",
      "entries_count": 25
    },
    {
      "handle": "pages",
      "title": "Pages",
      "entries_count": 10
    }
  ]
}
```

### 5. Search Entries
**GET** `/api/statamic/search`

**Query Parameters:**
- `q` (required): Search query (minimum 2 characters)
- `collection` (optional): Limit search to specific collection
- `limit` (optional): Number of results (default: 25, max: 100)

**Example:**
```
GET /api/statamic/search?q=statamic&collection=blog&limit=10
```

**Response:**
```json
{
  "data": [
    {
      "id": "entry-id",
      "title": "Getting Started with Statamic",
      "slug": "getting-started-statamic",
      "url": "/blog/getting-started-statamic",
      "collection": "blog"
    }
  ],
  "meta": {
    "query": "statamic",
    "total": 1
  }
}
```

## Usage Examples

### Get blog entries with simple related posts
```
GET /api/statamic/entries?collection=blog&include_related=related_posts,author
```

### Get blog entries with nested relationships
```
GET /api/statamic/entries?collection=blog&include_related=author.posts,related_posts.comments
```

### Get entries with deeply nested relationships
```
GET /api/statamic/entries?collection=blog&include_related=author.posts.comments,related_posts.author.posts
```

### Get specific fields only
```
GET /api/statamic/entries?fields=title,content,featured_image
```

### Get paginated results with nested relationships
```
GET /api/statamic/entries?collection=blog&limit=10&page=2&include_related=author.posts
```

### Search for entries
```
GET /api/statamic/search?q=laravel&collection=blog
```

## Nested Relationships Examples

### Simple Relationship
```
include_related=author
```
Loads the author entry for each blog post.

### One Level Deep
```
include_related=author.posts
```
Loads the author and all posts by that author.

### Two Levels Deep
```
include_related=author.posts.comments
```
Loads the author, their posts, and comments on those posts.

### Multiple Nested Paths
```
include_related=author.posts,related_posts.comments
```
Loads:
- Author and their posts
- Related posts and comments on those posts

### Complex Nested Structure
```
include_related=author.posts.comments,related_posts.author.posts
```
Loads:
- Author, their posts, and comments on those posts
- Related posts, their authors, and posts by those authors

## Notes

- All endpoints return JSON responses
- Relationship fields are automatically eager loaded when specified in `include_related`
- The API supports both single relationship fields and multiple relationship fields
- **Nested relationships use dot notation** (e.g., `author.posts.comments`)
- You can specify multiple nested paths by separating them with commas
- Pagination is included for endpoints that return multiple entries
- Error responses include appropriate HTTP status codes and error messages
- All dates are returned in ISO 8601 format
- Nested relationships are limited to prevent infinite recursion
- Each level of nesting adds the related data under a `related` key

## Error Responses

**404 Not Found:**
```json
{
  "error": "Entry not found"
}
```

**422 Validation Error:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "limit": ["The limit must be at least 1."]
  }
}
``` 
