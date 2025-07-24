<?php
/**
 * Git Hooks Setup Script - installs and configures Git hooks for header compliance
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
if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    exit('Direct access denied.');
}

/**
 * Git hooks setup utility
 * 
 * This script helps set up Git hooks for automated header compliance checking.
 */
class AskroHooksSetup
{
    /**
     * Base directory for the plugin
     * @var string
     */
    private $baseDir;
    
    /**
     * Git hooks directory
     * @var string
     */
    private $hooksDir;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseDir = dirname(__DIR__);
        $this->hooksDir = $this->baseDir . DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR . 'hooks';
    }
    
    /**
     * Set up all Git hooks
     * 
     * @param array $options Setup options
     * @return bool Success status
     */
    public function setupHooks($options = [])
    {
        $defaultOptions = [
            'force' => false,
            'verbose' => false
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        echo "🔧 Setting up Git hooks for ASKRO plugin...\n\n";
        
        // Check if we're in a Git repository
        if (!$this->isGitRepository()) {
            echo "❌ Error: Not in a Git repository.\n";
            echo "   Please run this script from the plugin root directory.\n";
            return false;
        }
        
        // Create hooks directory if it doesn't exist
        if (!is_dir($this->hooksDir)) {
            if (!mkdir($this->hooksDir, 0755, true)) {
                echo "❌ Error: Could not create hooks directory.\n";
                return false;
            }
            echo "📁 Created hooks directory: {$this->hooksDir}\n";
        }
        
        // Set up pre-commit hook
        $success = $this->setupPreCommitHook($options);
        
        if ($success) {
            echo "\n✅ Git hooks setup completed successfully!\n";
            echo "🔍 Header compliance will now be checked automatically before each commit.\n\n";
            echo "💡 To test the setup, try running:\n";
            echo "   php tools/check-headers.php\n\n";
            echo "🚫 To bypass the hook (not recommended), use:\n";
            echo "   git commit --no-verify\n";
        } else {
            echo "\n❌ Git hooks setup failed!\n";
            echo "   Please check the error messages above and try again.\n";
        }
        
        return $success;
    }
    
    /**
     * Set up the pre-commit hook
     * 
     * @param array $options Setup options
     * @return bool Success status
     */
    private function setupPreCommitHook($options = [])
    {
        $hookPath = $this->hooksDir . DIRECTORY_SEPARATOR . 'pre-commit';
        
        // Check if hook already exists
        if (file_exists($hookPath) && !$options['force']) {
            echo "⚠️  Pre-commit hook already exists.\n";
            echo "   Use --force to overwrite the existing hook.\n";
            return false;
        }
        
        // Generate hook content
        $hookContent = $this->generatePreCommitHook();
        
        // Write the hook file
        if (!file_put_contents($hookPath, $hookContent)) {
            echo "❌ Error: Could not write pre-commit hook file.\n";
            return false;
        }
        
        // Make the hook executable (Unix-like systems)
        if (!$this->isWindows()) {
            chmod($hookPath, 0755);
        }
        
        echo "✅ Pre-commit hook installed: {$hookPath}\n";
        
        if ($options['verbose']) {
            echo "\n📄 Hook content:\n";
            echo "---\n";
            echo $hookContent;
            echo "---\n";
        }
        
        return true;
    }
    
    /**
     * Generate the pre-commit hook content
     * 
     * @return string Hook script content
     */
    private function generatePreCommitHook()
    {
        $shebang = $this->isWindows() ? '@echo off' : '#!/bin/sh';
        $checkCommand = $this->isWindows() ? 'php tools/check-headers.php' : 'php tools/check-headers.php';
        $exitCheck = $this->isWindows() ? 
            'if %errorlevel% neq 0 (' . "\n" .
            '    echo Header compliance check failed. Please fix the errors and try committing again.' . "\n" .
            '    exit /b 1' . "\n" .
            ')' :
            'if [ $? -ne 0 ]; then' . "\n" .
            '    echo "Header compliance check failed. Please fix the errors and try committing again."' . "\n" .
            '    exit 1' . "\n" .
            'fi';
        
        return $shebang . "\n" .
               '# ASKRO Plugin - Pre-commit Hook' . "\n" .
               '# Automatically checks header compliance before commits' . "\n" .
               '# Generated by tools/setup-hooks.php' . "\n\n" .
               'echo "🔍 Checking header compliance..."' . "\n" .
               $checkCommand . "\n" .
               $exitCheck . "\n\n" .
               'echo "✅ Header compliance check passed!"' . "\n";
    }
    
    /**
     * Check if we're in a Git repository
     * 
     * @return bool True if in a Git repository
     */
    private function isGitRepository()
    {
        return is_dir($this->baseDir . DIRECTORY_SEPARATOR . '.git');
    }
    
    /**
     * Check if we're running on Windows
     * 
     * @return bool True if on Windows
     */
    private function isWindows()
    {
        return PHP_OS_FAMILY === 'Windows';
    }
    
    /**
     * Remove Git hooks
     * 
     * @param array $options Removal options
     * @return bool Success status
     */
    public function removeHooks($options = [])
    {
        echo "🗑️  Removing Git hooks...\n\n";
        
        $hookPath = $this->hooksDir . DIRECTORY_SEPARATOR . 'pre-commit';
        
        if (file_exists($hookPath)) {
            if (unlink($hookPath)) {
                echo "✅ Pre-commit hook removed: {$hookPath}\n";
                return true;
            } else {
                echo "❌ Error: Could not remove pre-commit hook.\n";
                return false;
            }
        } else {
            echo "⚠️  Pre-commit hook not found.\n";
            return true;
        }
    }
    
    /**
     * Display hook status
     */
    public function showStatus()
    {
        echo "📊 Git Hooks Status for ASKRO Plugin\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
        
        // Check repository status
        if ($this->isGitRepository()) {
            echo "✅ Git repository detected\n";
        } else {
            echo "❌ Not in a Git repository\n";
            return;
        }
        
        // Check hooks directory
        if (is_dir($this->hooksDir)) {
            echo "✅ Hooks directory exists: {$this->hooksDir}\n";
        } else {
            echo "❌ Hooks directory not found\n";
            return;
        }
        
        // Check pre-commit hook
        $preCommitPath = $this->hooksDir . DIRECTORY_SEPARATOR . 'pre-commit';
        if (file_exists($preCommitPath)) {
            echo "✅ Pre-commit hook installed: {$preCommitPath}\n";
            
            // Check if executable (Unix-like systems)
            if (!$this->isWindows()) {
                $perms = fileperms($preCommitPath);
                if ($perms & 0x0040) { // Owner execute bit
                    echo "✅ Pre-commit hook is executable\n";
                } else {
                    echo "⚠️  Pre-commit hook is not executable\n";
                }
            }
        } else {
            echo "❌ Pre-commit hook not installed\n";
        }
        
        echo "\n💡 To install hooks: php tools/setup-hooks.php --install\n";
        echo "💡 To remove hooks: php tools/setup-hooks.php --remove\n";
        echo "💡 To test compliance: php tools/check-headers.php\n";
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $action = 'status';
    $options = [];
    
    // Parse command line arguments
    $args = array_slice($argv, 1);
    for ($i = 0; $i < count($args); $i++) {
        switch ($args[$i]) {
            case '--install':
                $action = 'install';
                break;
                
            case '--remove':
                $action = 'remove';
                break;
                
            case '--status':
                $action = 'status';
                break;
                
            case '--force':
                $options['force'] = true;
                break;
                
            case '--verbose':
            case '-v':
                $options['verbose'] = true;
                break;
                
            case '--help':
            case '-h':
                echo "Git Hooks Setup for ASKRO Plugin\n\n";
                echo "Usage: php setup-hooks.php [ACTION] [OPTIONS]\n\n";
                echo "Actions:\n";
                echo "  --install    Install Git hooks (default action if no args)\n";
                echo "  --remove     Remove installed Git hooks\n";
                echo "  --status     Show current hooks status\n\n";
                echo "Options:\n";
                echo "  --force      Force overwrite existing hooks\n";
                echo "  --verbose    Show detailed output\n";
                echo "  --help, -h   Show this help message\n\n";
                echo "Examples:\n";
                echo "  php tools/setup-hooks.php --install\n";
                echo "  php tools/setup-hooks.php --remove\n";
                echo "  php tools/setup-hooks.php --status\n";
                exit(0);
        }
    }
    
    // Default to install if no action specified and not just showing help
    if (empty($args) || (!in_array($action, ['install', 'remove', 'status']) && !in_array('--help', $args))) {
        $action = 'install';
    }
    
    $setup = new AskroHooksSetup();
    
    switch ($action) {
        case 'install':
            $success = $setup->setupHooks($options);
            exit($success ? 0 : 1);
            
        case 'remove':
            $success = $setup->removeHooks($options);
            exit($success ? 0 : 1);
            
        case 'status':
        default:
            $setup->showStatus();
            exit(0);
    }
}
