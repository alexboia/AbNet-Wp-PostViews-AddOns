# Spam Referals Handling

For a spam referrer list with a few thousand entries, the best approach is not a custom binary format. The real cost is the lookup strategy, not the file format.

## Recommendation

- Keep the source list in a simple format such as plain text or JSON.
- Load it once, normalize it, and compile it into an associative array for fast exact lookup.
- Cache the processed structure in memory for the request, and optionally persist a compiled version.

## Why this works

If the list is around 2343 entries, that is small for PHP. What matters is whether the code scans the list linearly on every request or uses constant-time lookups.

- A linear scan over JSON or binary data is still $O(n)$.
- An associative array lookup is effectively constant time.
- A custom binary parser in PHP userland can easily be slower than `json_decode()` because the parser itself runs in PHP.

## Suggested flow

1. Read the source file once.
2. Normalize each entry into a host-like token.
3. Split entries into:
   - exact domains
   - suffix or wildcard-like rules
4. Extract the host from `HTTP_REFERER`.
5. Check exact matches first.
6. Check parent-domain suffixes only if needed.

## Preferred data shape

Compile the source into something like:

```php
return [
    'domains' => [
        'spamdomain1.com' => true,
        'spamdomain2.com' => true,
    ],
    'suffixes' => [
        'example-spam.net',
    ],
];
```

Then the request-time logic is:

- parse the referer host
- normalize it
- check `isset($domains[$host])`
- if not found, walk parent suffixes

## What to avoid

Do not do this on every request:

- read JSON
- decode JSON
- loop through thousands of entries
- run substring checks against the whole referer URL

That works, but it is the slow path.

## Best option for the plugin

- Use Matomo's list as the source of truth.
- Compile it into a PHP array cache or another hash-based structure.
- Keep fuzzy rules separate from exact domain rules.

For this plugin, the best balance is maintainability plus fast exact matching, not a custom binary file.
