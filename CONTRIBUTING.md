# Contributing to ASKRO

Thank you for your interest in contributing to the **ASKRO - Ask and Answer Plugin**! This document provides guidelines and information about contributing to this project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Code Standards](#code-standards)
- [Header Compliance](#header-compliance)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)

## Code of Conduct

This project adheres to a code of conduct that we expect all contributors to follow. Please be respectful, inclusive, and considerate in all interactions.

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- WordPress 5.0 or higher
- Composer for dependency management
- Git for version control

### Development Environment Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/williamalowe/askro-plugin.git
   cd askro-plugin
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Set up the development environment:**
   - Install WordPress locally (using Local, XAMPP, or similar)
   - Symlink or copy the plugin to your WordPress plugins directory
   - Activate the plugin in your WordPress admin

## Development Workflow

### Branching Strategy

- `main` - Production-ready code
- `develop` - Integration branch for features
- `feature/*` - New feature development
- `bugfix/*` - Bug fixes
- `hotfix/*` - Critical production fixes

### Making Changes

1. Create a new branch from `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/your-feature-name
   ```

2. Make your changes following our [code standards](#code-standards)

3. Test your changes thoroughly

4. Commit your changes with descriptive messages

5. Push to your branch and create a pull request

## Code Standards

### General Guidelines

- Follow WordPress Coding Standards
- Use meaningful variable and function names
- Write self-documenting code
- Include inline comments for complex logic
- Keep functions small and focused on a single responsibility

### PHP Standards

- Use PHP 7.4+ features appropriately
- Follow PSR-4 autoloading standards
- Use strict typing where possible
- Handle errors gracefully

### File Organization

```
askro/
├── askro.php              # Main plugin file
├── uninstall.php          # Uninstall cleanup
├── includes/              # Core plugin files
├── assets/               # CSS, JS, images
├── languages/            # Translation files
├── templates/            # Template files
├── tools/                # Development tools
└── docs/                 # Documentation
```

## Header Compliance

**All PHP, CSS, and JavaScript files must include proper documentation headers.** We have automated tools to enforce this requirement.

### Header Requirements

#### PHP Files

All PHP files must include a header with the following elements:

```php
<?php
/**
 * Brief description of the file's purpose
 * 
 * @package ASKRO
 * @author William Lowe
 * @since 1.0.0
 * @license GPL-3.0-or-later
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
```

#### CSS Files

```css
/**
 * Brief description of the stylesheet's purpose
 * 
 * @package ASKRO
 * @since 1.0.0
 */
```

#### JavaScript Files

```javascript
/**
 * Brief description of the script's purpose
 * 
 * @package ASKRO
 * @since 1.0.0
 */
```

### Required Elements

- **Description**: Brief explanation of the file's purpose
- **@package**: Must be "ASKRO"
- **@author**: Author name (for PHP files)
- **@since**: Version when the file was introduced
- **@license**: License identifier (for PHP files)
- **Security Check**: ABSPATH check (for PHP files)
- **GPL License Text**: Full license text (for PHP files)

### Header Compliance Tools

We provide several tools to help maintain header compliance:

#### 1. Header Compliance Checker

**Location**: `tools/check-headers.php`

**Usage**:
```bash
# Basic validation
php tools/check-headers.php

# Verbose output with warnings
php tools/check-headers.php --verbose

# Stop on first failure
php tools/check-headers.php --fail-fast

# Generate JSON report
php tools/check-headers.php --report json --output compliance-report.json

# Generate JUnit XML for CI
php tools/check-headers.php --report junit --output test-results.xml

# Exclude specific patterns
php tools/check-headers.php --exclude "vendor/*,*.min.js"
```

**Exit Codes**:
- `0` - All validations passed
- `1` - Validation failures found
- `2` - Template or configuration error
- `3` - File system error

#### 2. Pre-commit Hook

A Git pre-commit hook automatically runs header validation before each commit:

**Location**: `.git/hooks/pre-commit`

The hook will prevent commits if header compliance issues are found. To bypass (not recommended):
```bash
git commit --no-verify
```

#### 3. Continuous Integration

Our GitHub Actions workflow (`.github/workflows/header-lint.yml`) automatically:

- ✅ Validates header compliance on all pull requests
- 📊 Generates compliance reports and badges
- 🔒 Performs security header validation
- 💬 Comments on PRs with compliance status
- 📄 Uploads detailed reports as artifacts

#### 4. Header Audit Tool

**Location**: `tools/header-audit.php`

Generates comprehensive reports for existing files:
```bash
php tools/header-audit.php
```

Outputs:
- `tools/header-audit-inventory.json` - Detailed JSON report
- `tools/header-audit-report.md` - Human-readable markdown report

### Fixing Compliance Issues

1. **Run the compliance checker**:
   ```bash
   php tools/check-headers.php --verbose
   ```

2. **Review the output** and identify missing elements

3. **Add or update headers** according to the templates above

4. **Re-run the checker** to verify fixes:
   ```bash
   php tools/check-headers.php
   ```

5. **Commit your changes** once all issues are resolved

### Common Issues and Solutions

| Issue | Solution |
|-------|----------|
| Missing @package declaration | Add `@package ASKRO` to the header |
| Wrong package name | Ensure package is exactly "ASKRO" |
| Missing ABSPATH check | Add security check after header |
| Missing license text | Include full GPL license text |
| Inconsistent formatting | Follow exact header template format |
| Missing @since tag | Add version when file was introduced |

## Testing

### Running Tests

```bash
# Header compliance
php tools/check-headers.php

# Full audit
php tools/header-audit.php

# Unit tests (when available)
composer test
```

### Before Submitting

Always run these checks before submitting a pull request:

1. **Header compliance**: `php tools/check-headers.php --verbose`
2. **Code linting**: Check for syntax errors
3. **Manual testing**: Test your changes in a WordPress environment
4. **Documentation**: Update relevant documentation

## Pull Request Process

### Pull Request Template

When creating a pull request, include:

- **Description**: Clear description of changes
- **Testing**: How you tested the changes
- **Screenshots**: For UI changes
- **Breaking Changes**: Any backward compatibility issues
- **Checklist**: Confirm you've followed all guidelines

### Review Criteria

Pull requests must:

- ✅ Pass all automated checks (including header compliance)
- ✅ Include appropriate tests
- ✅ Follow coding standards
- ✅ Have clear, descriptive commit messages
- ✅ Update documentation if needed
- ✅ Not break existing functionality

### Automated Checks

Our CI pipeline automatically checks:

- Header compliance
- Security patterns
- Code quality
- Test coverage
- Documentation completeness

## Issue Reporting

### Before Reporting

1. Search existing issues
2. Check the documentation
3. Test with the latest version

### Issue Template

Include:

- **Environment**: WordPress version, PHP version, plugin version
- **Expected Behavior**: What should happen
- **Actual Behavior**: What actually happens
- **Steps to Reproduce**: Detailed reproduction steps
- **Screenshots**: If applicable
- **Error Messages**: Any error messages or logs

## Development Tools

### Available Scripts

| Script | Purpose |
|--------|---------|
| `tools/check-headers.php` | Validate header compliance |
| `tools/header-audit.php` | Generate comprehensive header reports |

### IDE Configuration

We recommend:
- **VS Code** with PHP extensions
- **PhpStorm** with WordPress plugin
- **Vim/Neovim** with appropriate plugins

### Code Quality Tools

- **PHP_CodeSniffer**: For coding standards
- **PHPStan**: For static analysis
- **Header Checker**: For documentation compliance

## Getting Help

- **Issues**: [GitHub Issues](https://github.com/williamalowe/askro-plugin/issues)
- **Discussions**: [GitHub Discussions](https://github.com/williamalowe/askro-plugin/discussions)
- **Documentation**: Check the `docs/` directory
- **Code Review**: Feel free to ask questions in pull requests

## License

By contributing to ASKRO, you agree that your contributions will be licensed under the same GPL-3.0-or-later license that covers the project.

---

**Thank you for contributing to ASKRO!** 🚀

*This document is living and may be updated as the project evolves. Please check for updates regularly.*
