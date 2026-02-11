---
applyTo: '**/**/Pages/**/*.html, **/**/js/**/*.js'
---

# Educa Performance & Optimization Guidelines

When generating or modifying HTML and JavaScript code in this project, always follow these performance and UX rules:

## 1. API Request Optimization

- Never create sequential (waterfall) fetch calls unless required.
- Use `Promise.all()` to run independent API calls in parallel.
- Avoid unnecessary repeated fetches.
- Do not use `window.location.reload()` after CRUD operations.
- Update the DOM dynamically instead of reloading the page.

Example pattern:

```js
const [classes, subjects] = await Promise.all([
  fetch('/classes'),
  fetch('/subjects')
]);

```

## 2. DOM Efficiency

- Prefer event delegation over multiple individual event listeners.
- Avoid repeated document.querySelectorAll() when not necessary.
- Cache frequently used DOM elements in variables.
- Use innerHTML batching (with .join('')) instead of multiple append calls where appropriate.

## 3. User Experience Performance

- Always show loading indicators for async operations.
- Use optimistic UI updates where safe.
- Prevent layout shifts when modals open.
- Avoid blocking UI while waiting for API responses.  

## 4. Table Rendering Rules
- For tables with more than 20 rows, implement pagination or virtual scrolling.
- Tables must include placeholder rows during loading.
- Never render empty containers that later expand in size.
- Avoid full table re-renders when updating a single row.

## 5. Code Structure
- Keep JavaScript modular and organized by feature.
- Separate rendering logic from API logic.