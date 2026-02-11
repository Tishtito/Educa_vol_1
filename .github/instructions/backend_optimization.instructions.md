---
applyTo: '**/backend/app/src/Controllers/**/*.php'
---


# Educa Backend Controller Standards (Performance + Scalability)

All controllers must follow these architectural and performance rules.

---

## 1. Response Standardization

Controllers must NEVER manually echo JSON repeatedly.

Always use a helper method:

```php
private function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
}

```

All responses must follow:

``` php
  Success:

  [
      'success' => true,
      'data' => $data
  ]


  Error:

  [
      'success' => false,
      'message' => 'Readable error message'
  ]

```

## 2. Query Optimization Rules

- NEVER use `SELECT *`
- Always select only required columns
- Fields used in `WHERE` / `JOIN` / `ORDER` must be indexed
- Avoid N+1 queries
- Use `JOIN` or `WHERE IN`  instead of per-row queries
- Avoid unnecessary pre-check queries when constraints exist
