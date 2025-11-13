# Makefile для запуска проверок/фиксинга кода через composer-скрипты
SHELL := /bin/bash
COMPOSER ?= composer

# Composer-скрипты (ожидаются в composer.json)
CS_CHECK = $(COMPOSER) run cs:check
CS_CBF   = $(COMPOSER) run cs:cbf
CS_FIXER = $(COMPOSER) run cs:fix
PSALM    = $(COMPOSER) run psalm
TESTS    = $(COMPOSER) run test

# Файлы/папки, которые обычно не должны проверяться (подстраивайте под проект)
EXCLUDE_DIRS := vendor var bin

.PHONY: help setup install deps cs-check cs-cbf cs-fix cs-fix-dry cs-fix-full psalm lint check-all ci format clean

help:
	@printf "\nUsage:\n"
	@printf "  make setup         Install dependencies (composer install)\n"
	@printf "  make cs-check      Run php-code-sniffer (summary)\n"
	@printf "  make cs-cbf        Run phpcbf (auto-fix)\n"
	@printf "  make cs-fix        Run php-cs-fixer (fix)\n"
	@printf "  make cs-fix-dry    Run php-cs-fixer in dry-run (shows diffs)\n"
	@printf "  make psalm         Run psalm\n"
	@printf "  make lint          Run cs-check + psalm\n"
	@printf "  make check-all     Full sequence used locally\n"
	@printf "  make format        Apply automatic fixes (phpcbf + php-cs-fixer)\n"
	@printf "  run tests          Run test for api and services\n"
	@printf "  make clean         Clean caches (local)\n\n"

# Установка зависимостей (локально)
setup:
	$(COMPOSER) install --no-interaction --prefer-dist

# Быстрая установка для CI (без dev-зависимостей)
install:
	$(COMPOSER) install --no-interaction --prefer-dist --no-progress

# Запуск phpcs (check only)
cs-check:
	@echo "Running PHPCS..."
	$(CS_CHECK)

# auto-fix через phpcbf
cs-cbf:
	@echo "Running PHPCBF (auto-fix trivial issues)..."
	$(CS_CBF)

# Запуск php-cs-fixer для реального фикса
cs-fix:
	@echo "Running PHP-CS-Fixer (apply fixes)..."
	$(CS_FIXER)

# php-cs-fixer dry-run (показывает дифф)
cs-fix-dry:
	@echo "Running PHP-CS-Fixer (dry-run, show diffs)..."
	$(CS_FIXER) -- --dry-run --diff

# Полный автоматический фикс: phpcbf + php-cs-fixer (не dry)
cs-fix-full: cs-cbf cs-fix
	@echo "Auto-fix done (phpcbf + php-cs-fixer)."

# Запуск psalm
psalm:
	@echo "Running Psalm..."
	$(PSALM)

# Быстрая проверка: cs-check + psalm
lint: cs-check psalm

# "Полная" локальная последовательность: проверка -> автозамятки -> dry-run -> psalm
check-all:
	@echo "=== PHPCS (check) ==="
	$(CS_CHECK)
	@echo "=== PHPCBF (auto-fix trivial) ==="
	$(CS_CBF) || true
	@echo "=== PHP-CS-Fixer (dry-run) ==="
	$(CS_FIXER) -- --dry-run --diff || true
	@echo "=== PSALM ==="
	$(PSALM)

# Быстрое форматирование (применит фиксеры)
format: cs-cbf cs-fix
	@echo "Formatting applied."

# Очистка локальных кэшей (дополните при необходимости)
clean:
	@echo "Cleaning caches..."
	-rm -rf var/cache/* .php-cs-fixer.cache

# Запуск тестов (дополните командами для вашего проекта)
tests:
	@echo "Running tests..."
	$(TESTS)
