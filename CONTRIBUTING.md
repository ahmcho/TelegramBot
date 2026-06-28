# Contributing to AhmCho\Telegram

Thank you for considering contributing to the AhmCho\Telegram framework! This document provides guidelines and instructions for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Reporting Issues](#reporting-issues)

---

## Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on what is best for the community
- Show empathy towards other community members

---

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Git
- Composer (for development dependencies)

### Setup Development Environment

```bash
# Clone the repository
git clone https://github.com/yourusername/tg-bots.git
cd tg-bots

# Install dependencies
composer install

# Copy environment file
cp .env.example .env
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
make test-coverage
```

### Code Quality Checks

```bash
# Run all quality checks
make quality

# Individual checks
make lint      # Code style
make analyze   # Static analysis
```

---

## Development Workflow

### 1. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-bug-fix
```

### 2. Make Changes

- Follow coding standards
- Add tests for new features
- Update documentation as needed

### 3. Test Your Changes

```bash
# Run tests
make test

# Check code style
make lint

# Run static analysis
make analyze
```

### 4. Commit Changes

Use clear commit messages:

```
[FEATURE] Add new feature description
[FIX] Fix bug description
[REFACTOR] Refactor description
[DOCS] Documentation update
[TEST] Add tests for...
[CI] CI/CD configuration
```

Example:
```
[FEATURE] Add conversation flow manager

- Add ConversationFlow class for state management
- Add state persistence between updates
- Add context switching between conversations
- Add tests for conversation flows
- Update documentation with examples
```

### 5. Push and Create PR

```bash
git push origin feature/your-feature-name
```

Then create a Pull Request on GitHub.

---

## Coding Standards

### PHP Standards

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Use type hints for all parameters and return types
- Use readonly properties where appropriate
- Use constructor property promotion

### Naming Conventions

- **Classes:** `PascalCase` (e.g., `MessageService`)
- **Methods:** `camelCase` (e.g., `sendMessage`)
- **Properties:** `camelCase` (e.g., `$apiService`)
- **Constants:** `SCREAMING_SNAKE_CASE` (e.g., `MAX_RETRIES`)
- **Functions:** `camelCase` (e.g., `formatMessage`)

### Documentation

- Add PHPDoc to all public methods
- Include `@param`, `@return`, `@throws` tags
- Add usage examples for complex features

```php
/**
 * Send a text message with automatic retry on failure
 *
 * @param array<string, mixed> $params Message parameters
 * @param array<string, mixed> $options Retry options:
 *   - max_retries: int (default: 3)
 *   - initial_delay_ms: int (default: 1000)
 *   - max_delay_ms: int (default: 10000)
 *   - on_retry: callable Called on each retry
 * @return array<string, mixed> The API response
 * @throws ApiException If all retry attempts fail
 */
public function sendMessageWithRetry(array $params, array $options = []): array
{
    // Implementation
}
```

### Code Organization

- One class per file
- Namespace matches directory structure
- Separate concerns (services, clients, config, etc.)

### Best Practices

- **Dependency Injection:** Use constructor injection
- **Immutability:** Use readonly properties where possible
- **Type Safety:** Use strict types and type declarations
- **Error Handling:** Use specific exception types
- **Logging:** Use PSR-3 logger for important events

---

## Testing Guidelines

### Write Tests For

- New features
- Bug fixes (regression tests)
- Edge cases
- Error conditions

### Test Structure

- Unit tests for individual classes
- Integration tests for service interactions
- Use descriptive test names

```php
public function testSendMessageWithRetrySucceedsOnFirstAttempt(): void
{
    // Arrange
    $bot = new TelegramBot();
    $params = ['chat_id' => 123, 'text' => 'Test'];
    
    // Act
    $result = $bot->sendMessageWithRetry($params);
    
    // Assert
    $this->assertIsArray($result);
    $this->assertArrayHasKey('message_id', $result);
}
```

### Running Tests

```bash
# All tests
make test

# Specific test suite
phpunit tests/Unit/Command/

# With coverage
make test-coverage
```

---

## Pull Request Process

### PR Checklist

- [ ] Tests pass locally
- [ ] Code follows project standards
- [ ] Documentation updated
- [ ] Commit messages follow guidelines
- [ ] No merge conflicts with target branch

### PR Description Template

```markdown
## Summary
Brief description of changes

## Type
- [ ] Feature
- [ ] Bug fix
- [ ] Refactor
- [ ] Documentation
- [ ] Tests
- [ ] Other

## Changes
- List of main changes

## Testing
- How to test these changes

## Screenshots (if applicable)
Add screenshots for UI changes

## Checklist
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
```

### Review Process

1. Automated checks must pass
2. At least one approval required
3. Address all review comments
4. Squash commits if needed
5. Merge when approved

---

## Reporting Issues

### Bug Reports

Include:
- PHP version
- Framework version
- Error message
- Steps to reproduce
- Expected vs actual behavior
- Code snippet (if applicable)

### Feature Requests

Include:
- Use case description
- Proposed solution
- Alternative approaches considered
- Impact on existing functionality

### Security Issues

**Do not** report security issues publicly. Email them to the maintainers.

---

## Additional Resources

- [README.md](README.md) - Framework documentation
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [Examples](examples/) - Usage examples
- [Telegram Bot API](https://core.telegram.org/bots/api) - Official API docs

---

## Questions?

Feel free to open an issue with the `question` label.

Thank you for contributing! 🎉
