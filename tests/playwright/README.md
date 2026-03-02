# Playwright tests (Adam module)

E2E tests for the Adam module run in the context of the brand plugin (e.g. wp-plugin-bluehost). The plugin provides the Playwright config, wp-env, and project discovery. **Run tests from the brand plugin repo only.**

## Run tests locally

With the Adam module in `vendor` or as a path repo, from the plugin root:

```bash
cd /path/to/wp-plugin-bluehost
npm run test:playwright:update-projects
npx playwright test --project="newfold-labs/wp-module-adam"
```

To run all plugin and module projects:

```bash
npm run test:playwright
```

The plugin must have the Adam module as a dependency and wp-env available (e.g. `npx wp-env start`).

## Structure

- **specs/** – Test files (e.g. `adam-aside.spec.mjs`). All Adam API data is mocked; no real Adam API or WordPress REST call for Adam items.
- **helpers/index.mjs** – Re-exports plugin helpers and adds Adam SELECTORS, FIXTURES, `mockAdamApi`, `setupAndNavigateToHome`.
- **fixtures/** – JSON fixtures matching the WordPress REST response shape `{ "response": [ ... ] }` for `newfold-adam/v1/items`.
