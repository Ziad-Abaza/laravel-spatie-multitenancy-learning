<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Application Quality Rules

- Every concern must have a single source of truth.
- No hardcoded business rules, permissions, routes, settings, statuses, currencies, locales, themes, or configuration.
- Follow Laravel conventions and architecture before introducing custom patterns.
- Keep controllers, models, Vue pages, stores, and components focused on their responsibilities.
- Centralize reusable business logic instead of duplicating it.
- Centralize reusable UI behavior instead of duplicating it.
- Prefer extension of existing abstractions over parallel implementations.
- Every user action must produce clear and consistent feedback.
- State changes must propagate through the established application state flow without requiring manual page refreshes.
- Disabled or unavailable functionality must not remain reachable through UI, routes, actions, or APIs.
- Every implementation must handle loading, success, validation, empty, authorization, and error states where applicable.
- Fix root causes, not symptoms.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Performance, Architecture & UI Engineering Rules

These rules are mandatory for all application changes, especially Vue 3, Inertia.js, Pinia, TypeScript, and reusable UI components.

### 1. Performance Patterns

- **Internal Navigation:** Use Inertia.js `<Link>` or `router.visit()` for internal navigation. Do not use normal `<a href>` navigation when an Inertia navigation is appropriate, as this causes unnecessary full-page reloads.

- **Event Listeners:** Never register global listeners such as `window.addEventListener()` or `document.addEventListener()` inside components rendered repeatedly by `v-for`. Route shared/global listeners through a reusable Singleton Composable with proper lifecycle cleanup.

- **Pure Transformations:** Filtering, mapping, formatting, and transformation functions must not mutate Pinia state, props, or other source data. Return new objects/collections instead.

- **Pinia State:** When extracting reactive state from Pinia stores, prefer `storeToRefs()` over recreating equivalent `computed()` wrappers in individual components.

- **Long Lists:** Do not render unbounded or very large collections directly with `v-for`. Use windowing/virtualization so only the visible portion of the dataset is rendered when the dataset can become large.

- **Fast Membership Checks:** For repeated membership checks during rendering or iteration, prefer precomputed `Set`/`Map` structures over repeated `Array.includes()` calls when the data size or frequency makes this materially beneficial.

- **Shared Global Resources:** Resources such as scroll locking must use reusable reference-counted utilities. Do not manage shared resources with independent boolean flags that can conflict when multiple consumers are active.

- **Transformation Caching:** Cache expensive object-based transformations in `WeakMap` when the transformation is derived from an object reference and can safely reuse the result while that reference remains valid.

### 2. Component & Architecture Rules

- **Extend, Don't Duplicate:** Extend existing reusable components such as `EnterpriseDataGrid` and `EnterpriseFormEngine` instead of creating parallel alternatives that duplicate their responsibilities.

- **Safe Extensibility:** Prefer established extension mechanisms such as slots, composables, configuration, dependency injection, and typed extension points before introducing new component architectures.

- **Architectural Invariants:** Existing architectural invariants are mandatory. For example:
  - `useThemeStore` remains the single source of truth for application theme state.
  - `setupInertiaStateBridge` remains the established bridge between Inertia state and Pinia.
  - Do not introduce competing state or theme sources without explicit architectural justification.

- **No Parallel Architecture:** Do not introduce a new abstraction, framework pattern, store, composable, service, or component hierarchy when an existing project abstraction already provides the required capability.

### 3. Type Safety & Reusability

- **Generics:** Reusable Vue components should use TypeScript generics where the component operates on caller-defined data types, e.g. `<script setup lang="ts" generic="T">`.

- **Type Integrity:** Do not weaken type safety with unnecessary `any`, unsafe casts, duplicated interfaces, or type assertions merely to make an implementation compile.

- **Reusable Contracts:** Generic components must preserve type inference between their inputs, outputs, slots, and emitted events.

### 4. UI State Requirements

Reusable data-driven components must explicitly support:

- **Loading State:** Provide structural Skeleton loading states that preserve the final layout and minimize layout shift.
- **Empty State:** Provide a clear, intentional empty state rather than rendering a blank area.
- **Error State:** Provide an integrated error state with an understandable message and an appropriate recovery/action mechanism where applicable.

### 5. Semantic Data Formatting

Use standardized semantic formatter components wherever the project provides them.

Examples:
- `BadgeCell` for statuses, labels, and semantic states.
- `CurrencyCell` for monetary values.
- Other existing formatter components for dates, numbers, users, percentages, or domain-specific values.

Do not duplicate formatting logic across table columns when an established semantic formatter exists.

### 6. UI/UX & Design-System Rules

- **Design Tokens:** Use the project's CSS variables/design tokens instead of hardcoded colors, spacing, typography, borders, shadows, or other theme-dependent values.

- **Theme Compatibility:** UI implementations must remain compatible with all supported themes, including light and dark modes. Do not introduce hardcoded values that only work in one theme.

- **Icons:** Use the project's standard SVG icon system/components. Do not use emojis as UI icons.

- **Stable Hover States:** Hover, focus, and active states must not cause layout shifts. Avoid changing dimensions, borders, padding, font metrics, or other layout-affecting properties on interaction unless the layout explicitly reserves the required space.

- **Visual Consistency:** Reuse established design-system components and patterns before introducing new visual primitives.

### 7. Review Gate

Before considering a change complete, verify:

1. No unnecessary full-page navigation was introduced.
2. No repeated component creates unmanaged global listeners.
3. No props or Pinia state are mutated by transformation logic.
4. Existing Pinia state extraction patterns are preserved.
5. Large collections use appropriate rendering strategies.
6. Existing reusable components were extended before creating alternatives.
7. Architectural invariants remain intact.
8. Generic reusable components preserve TypeScript type safety.
9. Loading, empty, and error states are handled where applicable.
10. Existing semantic formatters are reused.
11. Design tokens are used instead of hardcoded theme values.
12. Icons follow the project's SVG icon system.
13. Interaction states do not introduce layout shift.

If an existing project pattern conflicts with one of these rules, inspect the actual implementation and project conventions first. Do not silently create a second pattern. Document the conflict and choose the solution that preserves the established architecture and consistency.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
