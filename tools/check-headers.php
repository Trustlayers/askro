<?php
/**
 * Header Compliance Checker - validates file headers against templates and enforces compliance
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
 * Header compliance checker class
 * 
 * This class validates file headers against predefined templates and enforces
 * compliance by failing with non-zero exit codes when violations are found.
 */
class AskroHeaderChecker
{
    /**
     * Base directory for the plugin
     * @var string
     */
    private $baseDir;
    
    /**
     * Header templates for different file types
     * @var array
     */
    private $headerTemplates = [];
    
    /**
     * Validation results
     * @var array
     */
    private $validationResults = [];
    
    /**
     * Exit codes
     */
    const EXIT_SUCCESS = 0;
    const EXIT_VALIDATION_FAILED = 1;
    const EXIT_TEMPLATE_ERROR = 2;
    const EXIT_FILE_ERROR = 3;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseDir = dirname(__DIR__);
        $this->initializeHeaderTemplates();
    }
    
    /**
     * Initialize header templates for different file types
     */
    private function initializeHeaderTemplates()
    {
        // PHP file template (general PHP files)
        $this->headerTemplates['php'] = [
            'required_elements' => [
                'description' => '/\*\*\s*\n\s*\*\s*(.+?)\s*\n/',
                'package' => '/@package\s+(.+)/i',
                'author' => '/@author\s+(.+)/i',
                'since' => '/@since\s+(.+)/i',
                'license' => '/@license\s+(.+)/i'
            ],
            'required_patterns' => [
                'php_opening_tag' => '/^<\?php\s*\n/',
                'doc_block_start' => '/\/\*\*/',
                'doc_block_end' => '/\*\//',
                'gpl_license_text' => '/This program is free software.*GNU General Public License/s',
                'abspath_check' => '/defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)/'
            ],
            'optional_elements' => [
                'version' => '/@version\s+(.+)/i',
                'copyright' => '/@copyright\s+(.+)/i'
            ]
        ];
        
        // Main plugin file template (askro.php)
        $this->headerTemplates['plugin_main'] = [
            'required_elements' => [
                'plugin_name' => '/Plugin Name:\s*(.+)/i',
                'plugin_uri' => '/Plugin URI:\s*(.+)/i',
                'description' => '/Description:\s*(.+)/i',
                'version' => '/Version:\s*(.+)/i',
                'author' => '/Author:\s*(.+)/i',
                'license' => '/License:\s*(.+)/i',
                'package' => '/@package\s+(.+)/i',
                'doc_author' => '/@author\s+(.+)/i',
                'since' => '/@since\s+(.+)/i',
                'doc_license' => '/@license\s+(.+)/i'
            ],
            'required_patterns' => [
                'php_opening_tag' => '/^<\?php\s*\n/',
                'doc_block_start' => '/\/\*\*/',
                'doc_block_end' => '/\*\//',
                'gpl_license_text' => '/This program is free software.*GNU General Public License/s',
                'abspath_check' => '/defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)/'
            ],
            'optional_elements' => [
                'author_uri' => '/Author URI:\s*(.+)/i',
                'text_domain' => '/Text Domain:\s*(.+)/i',
                'domain_path' => '/Domain Path:\s*(.+)/i'
            ]
        ];
        
        // CSS file template
        $this->headerTemplates['css'] = [
            'required_elements' => [
                'description' => '/\/\*\*\s*\n\s*\*\s*(.+?)\s*\n/',
                'package' => '/@package\s+(.+)/i',
                'since' => '/@since\s+(.+)/i'
            ],
            'required_patterns' => [
                'doc_block_start' => '/\/\*\*/',
                'doc_block_end' => '/\*\//'
            ],
            'optional_elements' => [
                'author' => '/@author\s+(.+)/i',
                'version' => '/@version\s+(.+)/i'
            ]
        ];
        
        // JavaScript file template
        $this->headerTemplates['js'] = [
            'required_elements' => [
                'description' => '/\/\*\*\s*\n\s*\*\s*(.+?)\s*\n/',
                'package' => '/@package\s+(.+)/i',
                'since' => '/@since\s+(.+)/i'
            ],
            'required_patterns' => [
                'doc_block_start' => '/\/\*\*/',
                'doc_block_end' => '/\*\//'
            ],
            'optional_elements' => [
                'author' => '/@author\s+(.+)/i',
                'version' => '/@version\s+(.+)/i'
            ]
        ];
    }
    
    /**
     * Run header validation
     * 
     * @param array $options Validation options
     * @return int Exit code
     */
    public function validateHeaders($options = [])
    {
        $defaultOptions = [
            'fail_fast' => false,
            'verbose' => false,
            'exclude_patterns' => [
                'vendor/*',
                'node_modules/*',
                '*.min.js',
                '*.min.css',
                'tests/*'
            ]
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        echo "🔍 Starting header compliance validation...\n";
        
        try {
            // Find all relevant files
            $files = $this->findFiles($options['exclude_patterns']);
            
            if (empty($files)) {
                echo "⚠️  No files found to validate.\n";
                return self::EXIT_SUCCESS;
            }
            
            echo "📁 Found " . count($files) . " files to validate.\n\n";
            
            $validationPassed = true;
            $errorCount = 0;
            
            foreach ($files as $file) {
                $result = $this->validateFile($file, $options);
                $this->validationResults[] = $result;
                
                if (!$result['compliant']) {
                    $validationPassed = false;
                    $errorCount++;
                    
                    $this->displayFileErrors($result, $options);
                    
                    if ($options['fail_fast']) {
                        break;
                    }
                }
            }
            
            // Display summary
            $this->displaySummary($errorCount, count($files));
            
            return $validationPassed ? self::EXIT_SUCCESS : self::EXIT_VALIDATION_FAILED;
            
        } catch (Exception $e) {
            echo "❌ Error during validation: " . $e->getMessage() . "\n";
            return self::EXIT_FILE_ERROR;
        }
    }
    
    /**
     * Find all files to validate
     * 
     * @param array $excludePatterns Patterns to exclude
     * @return array Array of file paths
     */
    private function findFiles($excludePatterns = [])
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['php', 'css', 'js'])) {
                    $relativePath = str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    
                    // Check exclude patterns
                    $excluded = false;
                    foreach ($excludePatterns as $pattern) {
                        if (fnmatch($pattern, $relativePath)) {
                            $excluded = true;
                            break;
                        }
                    }
                    
                    if (!$excluded) {
                        $files[] = $relativePath;
                    }
                }
            }
        }
        
        sort($files);
        return $files;
    }
    
    /**
     * Validate a single file
     * 
     * @param string $relativePath Relative path to the file
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validateFile($relativePath, $options = [])
    {
        $fullPath = $this->baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $basename = basename($relativePath);
        
        $result = [
            'file_path' => $relativePath,
            'file_type' => $extension,
            'compliant' => false,
            'errors' => [],
            'warnings' => []
        ];
        
        if (!file_exists($fullPath)) {
            $result['errors'][] = 'File not found';
            return $result;
        }
        
        $content = file_get_contents($fullPath);
        
        // Determine template type
        $templateType = $this->getTemplateType($relativePath, $extension, $content);
        
        if (!isset($this->headerTemplates[$templateType])) {
            $result['errors'][] = "No template defined for type: {$templateType}";
            return $result;
        }
        
        $template = $this->headerTemplates[$templateType];
        
        // Validate required patterns
        foreach ($template['required_patterns'] as $patternName => $pattern) {
            // Special handling for uninstall.php
            if ($patternName === 'abspath_check' && basename($relativePath) === 'uninstall.php') {
                // Skip ABSPATH check for uninstall.php - it will be checked separately
                continue;
            }
            if (!preg_match($pattern, $content)) {
                $result['errors'][] = "Missing required pattern: {$patternName}";
            }
        }
        
        // Validate required elements
        foreach ($template['required_elements'] as $elementName => $pattern) {
            if (!preg_match($pattern, $content, $matches)) {
                $result['errors'][] = "Missing required element: {$elementName}";
            } else {
                // Additional validation for specific elements
                $this->validateElementContent($elementName, trim($matches[1] ?? ''), $result);
            }
        }
        
        // Check for common issues
        $this->performAdditionalChecks($content, $extension, $result);
        
        // Set compliance status
        $result['compliant'] = empty($result['errors']);
        
        return $result;
    }
    
    /**
     * Determine the appropriate template type for a file
     * 
     * @param string $relativePath Relative file path
     * @param string $extension File extension
     * @param string $content File content
     * @return string Template type
     */
    private function getTemplateType($relativePath, $extension, $content)
    {
        // Main plugin file
        if (basename($relativePath) === 'askro.php' && strpos($content, 'Plugin Name:') !== false) {
            return 'plugin_main';
        }
        
        // Regular extension-based templates
        return $extension;
    }
    
    /**
     * Validate specific element content
     * 
     * @param string $elementName Element name
     * @param string $value Element value
     * @param array &$result Result array to modify
     */
    private function validateElementContent($elementName, $value, &$result)
    {
        switch ($elementName) {
            case 'package':
                if (strtoupper($value) !== 'ASKRO') {
                    $result['warnings'][] = "Package should be 'ASKRO', found: '{$value}'";
                }
                break;
                
            case 'license':
            case 'doc_license':
                $validLicenses = ['GPL-3.0-or-later', 'GPL v3 or later', 'GPL-3.0+'];
                if (!in_array($value, $validLicenses)) {
                    $result['warnings'][] = "License should be one of: " . implode(', ', $validLicenses) . ", found: '{$value}'";
                }
                break;
                
            case 'since':
                if (!preg_match('/^\d+\.\d+\.\d+$/', $value)) {
                    $result['warnings'][] = "Since version should follow semantic versioning (x.y.z), found: '{$value}'";
                }
                break;
                
            case 'version':
                if (!preg_match('/^\d+\.\d+\.\d+$/', $value)) {
                    $result['warnings'][] = "Version should follow semantic versioning (x.y.z), found: '{$value}'";
                }
                break;
        }
    }
    
    /**
     * Perform additional validation checks
     * 
     * @param string $content File content
     * @param string $extension File extension
     * @param array &$result Result array to modify
     */
    private function performAdditionalChecks($content, $extension, &$result)
    {
        if ($extension === 'php') {
            // Check for proper security measures
            // Special case for uninstall.php - should check WP_UNINSTALL_PLUGIN instead of ABSPATH
            if (basename($result['file_path']) === 'uninstall.php') {
                if (!preg_match('/defined\s*\(\s*[\'"]WP_UNINSTALL_PLUGIN[\'"]\s*\)/', $content)) {
                    $result['errors'][] = 'Missing WP_UNINSTALL_PLUGIN security check (required for uninstall.php)';
                }
            } else {
                if (!preg_match('/defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)/', $content)) {
                    $result['errors'][] = 'Missing ABSPATH security check';
                }
            }
            
            // Check for proper exit on direct access
            if (strpos($content, 'exit') === false && strpos($content, 'die') === false) {
                $result['warnings'][] = 'Consider adding exit/die statement for direct access prevention';
            }
            
            // Check for consistent indentation in header
            $lines = explode("\n", $content);
            $inHeader = false;
            $headerLines = [];
            
            foreach ($lines as $line) {
                if (strpos($line, '/**') !== false) {
                    $inHeader = true;
                    continue;
                }
                if (strpos($line, '*/') !== false) {
                    break;
                }
                if ($inHeader) {
                    $headerLines[] = $line;
                }
            }
            
            // Check header line formatting
            foreach ($headerLines as $line) {
                if (!empty(trim($line)) && !preg_match('/^\s*\*(\s|$)/', $line)) {
                    $result['warnings'][] = 'Inconsistent header comment formatting';
                    break;
                }
            }
        }
    }
    
    /**
     * Display file validation errors
     * 
     * @param array $result Validation result
     * @param array $options Display options
     */
    private function displayFileErrors($result, $options = [])
    {
        echo "❌ {$result['file_path']}\n";
        
        foreach ($result['errors'] as $error) {
            echo "   🔴 ERROR: {$error}\n";
        }
        
        if ($options['verbose'] && !empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                echo "   🟡 WARNING: {$warning}\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Display validation summary
     * 
     * @param int $errorCount Number of files with errors
     * @param int $totalFiles Total number of files validated
     */
    private function displaySummary($errorCount, $totalFiles)
    {
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        echo "📊 VALIDATION SUMMARY\n";
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
        
        $passedFiles = $totalFiles - $errorCount;
        $passRate = $totalFiles > 0 ? round(($passedFiles / $totalFiles) * 100, 1) : 0;
        
        if ($errorCount === 0) {
            echo "✅ ALL VALIDATIONS PASSED!\n";
            echo "📈 Compliance Rate: 100% ({$totalFiles}/{$totalFiles} files)\n";
        } else {
            echo "❌ VALIDATION FAILED!\n";
            echo "📈 Compliance Rate: {$passRate}% ({$passedFiles}/{$totalFiles} files)\n";
            echo "🔴 Files with errors: {$errorCount}\n";
        }
        
        echo "═══════════════════════════════════════════════════════════════════════════════\n";
    }
    
    /**
     * Generate a compliance report for CI/CD integration
     * 
     * @param string $format Output format (json, xml, junit)
     * @param string $outputFile Output file path
     */
    public function generateReport($format = 'json', $outputFile = null)
    {
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_files' => count($this->validationResults),
            'compliant_files' => count(array_filter($this->validationResults, function($r) { return $r['compliant']; })),
            'non_compliant_files' => count(array_filter($this->validationResults, function($r) { return !$r['compliant']; })),
            'files' => $this->validationResults
        ];
        
        switch ($format) {
            case 'json':
                $output = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                break;
                
            case 'junit':
                $output = $this->generateJUnitXML($reportData);
                break;
                
            default:
                throw new InvalidArgumentException("Unsupported format: {$format}");
        }
        
        if ($outputFile) {
            file_put_contents($outputFile, $output);
            echo "📄 Report generated: {$outputFile}\n";
        } else {
            echo $output;
        }
    }
    
    /**
     * Generate JUnit XML format for CI/CD integration
     * 
     * @param array $reportData Report data
     * @return string JUnit XML
     */
    private function generateJUnitXML($reportData)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $testsuites = $xml->createElement('testsuites');
        $testsuites->setAttribute('name', 'Header Compliance Tests');
        $testsuites->setAttribute('tests', $reportData['total_files']);
        $testsuites->setAttribute('failures', $reportData['non_compliant_files']);
        $testsuites->setAttribute('time', '0');
        
        $testsuite = $xml->createElement('testsuite');
        $testsuite->setAttribute('name', 'Header Validation');
        $testsuite->setAttribute('tests', $reportData['total_files']);
        $testsuite->setAttribute('failures', $reportData['non_compliant_files']);
        $testsuite->setAttribute('time', '0');
        
        foreach ($reportData['files'] as $file) {
            $testcase = $xml->createElement('testcase');
            $testcase->setAttribute('classname', 'HeaderCompliance');
            $testcase->setAttribute('name', $file['file_path']);
            $testcase->setAttribute('time', '0');
            
            if (!$file['compliant']) {
                $failure = $xml->createElement('failure');
                $failure->setAttribute('message', 'Header compliance validation failed');
                $failure->setAttribute('type', 'ComplianceError');
                $failure->nodeValue = implode("\n", $file['errors']);
                $testcase->appendChild($failure);
            }
            
            $testsuite->appendChild($testcase);
        }
        
        $testsuites->appendChild($testsuite);
        $xml->appendChild($testsuites);
        
        return $xml->saveXML();
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $options = [];
    $generateReport = false;
    $reportFormat = 'json';
    $reportFile = null;
    
    // Parse command line arguments
    $args = array_slice($argv, 1);
    for ($i = 0; $i < count($args); $i++) {
        switch ($args[$i]) {
            case '--fail-fast':
                $options['fail_fast'] = true;
                break;
                
            case '--verbose':
            case '-v':
                $options['verbose'] = true;
                break;
                
            case '--report':
                $generateReport = true;
                if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '--')) {
                    $reportFormat = $args[++$i];
                }
                break;
                
            case '--output':
            case '-o':
                if (isset($args[$i + 1])) {
                    $reportFile = $args[++$i];
                }
                break;
                
            case '--exclude':
                if (isset($args[$i + 1])) {
                    $options['exclude_patterns'] = array_merge(
                        $options['exclude_patterns'] ?? [],
                        explode(',', $args[++$i])
                    );
                }
                break;
                
            case '--help':
            case '-h':
                echo "Header Compliance Checker\n\n";
                echo "Usage: php check-headers.php [OPTIONS]\n\n";
                echo "Options:\n";
                echo "  --fail-fast        Stop on first validation failure\n";
                echo "  --verbose, -v      Show warnings in addition to errors\n";
                echo "  --report [FORMAT]  Generate report (json, junit)\n";
                echo "  --output, -o FILE  Output report to file\n";
                echo "  --exclude PATTERNS Comma-separated exclude patterns\n";
                echo "  --help, -h         Show this help message\n\n";
                echo "Exit codes:\n";
                echo "  0 - All validations passed\n";
                echo "  1 - Validation failures found\n";
                echo "  2 - Template or configuration error\n";
                echo "  3 - File system error\n";
                exit(0);
        }
    }
    
    $checker = new AskroHeaderChecker();
    $exitCode = $checker->validateHeaders($options);
    
    if ($generateReport) {
        try {
            $checker->generateReport($reportFormat, $reportFile);
        } catch (Exception $e) {
            echo "❌ Error generating report: " . $e->getMessage() . "\n";
            $exitCode = max($exitCode, AskroHeaderChecker::EXIT_TEMPLATE_ERROR);
        }
    }
    
    exit($exitCode);
}
