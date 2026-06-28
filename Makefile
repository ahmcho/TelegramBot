.PHONY: help test lint lint-fix analyze quality clean install setup

help:
	@echo "Available commands:"
	@echo "  make test          - Run PHPUnit tests"
	@echo "  make test-coverage  - Run tests with coverage report"
	@echo "  make lint          - Check code style with PHPCS"
	@echo "  make lint-fix      - Fix code style issues automatically"
	@echo "  make analyze       - Run static analysis with PHPStan"
	@echo "  make quality        - Run all quality checks (analyze + lint)"
	@echo "  make clean         - Clean generated files"
	@echo "  make install       - Install composer dependencies"
	@echo "  make setup         - Setup development environment"
	@echo "  make examples      - Check syntax of all example files"

test:
	@echo "Running tests..."
	phpunit

test-coverage:
	@echo "Running tests with coverage..."
	phpunit --coverage-html coverage

lint:
	@echo "Checking code style..."
	phpcs --standard=PSR12 src/

lint-fix:
	@echo "Fixing code style..."
	phpcbf --standard=PSR12 src/

analyze:
	@echo "Running static analysis..."
	phpstan analyse

quality: analyze lint

clean:
	@echo "Cleaning generated files..."
	@rm -rf coverage/
	@rm -rf vendor/
	@composer clean-cache

install:
	@echo "Installing dependencies..."
	composer install --prefer-dist

setup:
	@echo "Setting up development environment..."
	@cp .env.example .env 2>/dev/null || echo ".env.example not found, skipping..."
	@composer install --prefer-dist
	@echo "Development environment ready!"

examples:
	@echo "Checking syntax of example files..."
	@for file in examples/*.php; do \
		php -l "$file" || exit 1; \
	done
	@echo "All example files are syntactically correct."

validate: examples
	@echo "All validation checks passed!"

# Development helpers
run-example:
	@if [ -z "$(FILE)" ]; then \
		echo "Usage: make run-example FILE=example.php"; \
		exit 1; \
	fi
	@php examples/$(FILE)

# Git helpers
pre-commit: lint
	@echo "Pre-commit checks passed!"

# Documentation
docs-check:
	@echo "Checking documentation links..."
	@echo "TODO: Add documentation link checker"
