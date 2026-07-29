# Contributing to Astra OS

We love your input! We want to make contributing to Astra OS as easy and transparent as possible.

## Development Process

1. Fork the repo and create your branch from `main`.
2. If you've added code, add tests.
3. Ensure the test suite passes.
4. Make sure your code lints.
5. Issue a pull request.

## Code of Conduct

### Our Pledge

In the interest of fostering an open and welcoming environment, we as contributors and maintainers pledge to make participation in our project and our community a harassment-free experience for everyone.

### Our Standards

Examples of behavior that contributes to creating a positive environment:

- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

## Pull Request Process

1. Update the README.md with details of changes if needed.
2. Update the docs with any new configuration or API endpoints.
3. The PR will be merged once you have the sign-off from at least one maintainer.
4. All PRs must pass CI checks (lint, PHPStan, tests).

## Coding Standards

- **PSR-12** coding style (enforced by Laravel Pint)
- **Type declarations** on all methods and properties
- **DocBlocks** on all public methods
- **Return types** on all methods
- **Test coverage** for new code
- **No `dd()` or `dump()`** in committed code

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
XDEBUG_MODE=coverage php artisan test --coverage

# Run specific test
php artisan test --filter=CampaignApiTest

# Run tests in parallel
php artisan test --parallel
```

## Commit Messages

We follow conventional commits:

```
feat: add campaign budget optimization
fix: resolve N+1 query in campaign listing
docs: update API reference for campaigns
test: add unit tests for agent task assignment
chore: update composer dependencies
```

## Reporting Issues

- Use the issue tracker on GitHub
- Check if the issue has already been reported
- Include as many details as possible
- Add steps to reproduce

## Feature Requests

- Use the feature request template
- Explain why the feature would be useful
- Consider if it fits the project scope

## Architecture Decisions

- See `/docs/architecture/` for current architecture
- Discuss major changes in a GitHub issue before implementation
- Document architectural decisions in ADRs (Architecture Decision Records)

## Questions?

Open a GitHub discussion or reach out to the team at dev@astraos.io.

---

Thank you for contributing! 🚀
