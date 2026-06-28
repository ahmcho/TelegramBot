.PHONY: help install update test test-coverage analyze lint lint-fix quality clean docker-build docker-up docker-down docs

# Default target
help:
	@echo "Available commands:"
	@echo "  make install          - Install dependencies"
	@echo "  make update           - Update dependencies"
	@echo "  make test             - Run tests"
	@echo "  make test-coverage    - Run tests with coverage"
	@echo "  make analyze          - Run static analysis"
	@echo "  make lint             - Check code style"
	@echo "  make lint-fix         - Fix code style issues"
	@echo "  make quality          - Run all quality checks"
	@echo "  make clean            - Clean cache and generated files"
	@echo "  make docker-build     - Build Docker containers"
	@echo "  make docker-up        - Start Docker containers"
	@echo "  make docker-down      - Stop Docker containers"
	@echo "  make docs             - Generate documentation"

# Composer commands
install:
	@echo "Installing dependencies..."
	@php composer.phar install

update:
	@echo "Updating dependencies..."
	@php composer.phar update

# Testing
test:
	@echo "Running tests..."
	@php composer.phar test

test-coverage:
	@echo "Running tests with coverage..."
	@php composer.phar test-coverage

# Code quality
analyze:
	@echo "Running static analysis..."
	@php composer.phar analyze

lint:
	@echo "Checking code style..."
	@php composer.phar lint

lint-fix:
	@echo "Fixing code style..."
	@php composer.phar lint-fix

quality:
	@echo "Running all quality checks..."
	@php composer.phar quality

# Cleanup
clean:
	@echo "Cleaning cache and generated files..."
	@rm -rf vendor/ composer.lock
	@rm -rf coverage/
	@rm -rf .phpunit.result.cache
	@rm -rf logs/*.log

# Docker
docker-build:
	@echo "Building Docker containers..."
	@docker-compose build

docker-up:
	@echo "Starting Docker containers..."
	@docker-compose up -d

docker-down:
	@echo "Stopping Docker containers..."
	@docker-compose down

# Documentation
docs:
	@echo "Generating documentation..."
	@php composer.phar require --dev phpdocumentor/phpdocumentor
	@vendor/bin/phpdoc -d src/ -t docs/api/

# CI (for local testing)
ci: install quality test

# Development helpers
dev-install: install
	@echo "Setting up development environment..."
	@cp .env.example .env || true
	@mkdir -p logs

# Quick check before commit
pre-commit: lint test
