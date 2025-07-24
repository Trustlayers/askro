<?php
/**
 * Header Audit Tool - audits PHP and CSS files for proper header compliance
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

class AskroHeaderAudit
{
    /**
     * Base directory for the plugin
     * @var string
     */
    private $baseDir;
    
    /**
     * Audit results
     * @var array
     */
    private $auditResults = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseDir = dirname(__DIR__);
    }
    
    /**
     * Run the complete audit process
     */
    public function runAudit()
    {
        echo "Starting Askro Plugin Header Audit...\n";
        
        // Find all PHP and CSS files
        $files = $this->findFiles();
        
        // Audit each file
        foreach ($files as $file) {
            $this->auditFile($file);
        }
        
        // Generate reports
        $this->generateJsonReport();
        $this->generateMarkdownReport();
        
        echo "Audit completed successfully!\n";
        echo "Reports generated:\n";
        echo "- tools/header-audit-inventory.json\n";
        echo "- tools/header-audit-report.md\n";
    }
    
    /**
     * Find all PHP and CSS files recursively
     * 
     * @return array Array of file paths
     */
    private function findFiles()
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['php', 'css'])) {
                    $relativePath = str_replace($this->baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath); // Normalize path separators
                    $files[] = $relativePath;
                }
            }
        }
        
        sort($files);
        return $files;
    }
    
    /**
     * Audit a single file for header compliance
     * 
     * @param string $relativePath Relative path to the file
     */
    private function auditFile($relativePath)
    {
        $fullPath = $this->baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        
        if (!file_exists($fullPath)) {
            $this->auditResults[] = [
                'file_path' => $relativePath,
                'file_type' => $extension,
                'header_present' => false,
                'header_compliant' => false,
                'issues' => ['File not found'],
                'file_size' => 0,
                'last_modified' => null
            ];
            return;
        }
        
        $content = file_get_contents($fullPath);
        $fileInfo = stat($fullPath);
        
        $headerAnalysis = $this->analyzeHeader($content, $extension);
        
        $this->auditResults[] = [
            'file_path' => $relativePath,
            'file_type' => $extension,
            'header_present' => $headerAnalysis['present'],
            'header_compliant' => $headerAnalysis['compliant'],
            'issues' => $headerAnalysis['issues'],
            'file_size' => $fileInfo['size'],
            'last_modified' => date('Y-m-d H:i:s', $fileInfo['mtime']),
            'header_details' => $headerAnalysis['details']
        ];
    }
    
    /**
     * Analyze header for compliance
     * 
     * @param string $content File content
     * @param string $extension File extension
     * @return array Analysis results
     */
    private function analyzeHeader($content, $extension)
    {
        $result = [
            'present' => false,
            'compliant' => false,
            'issues' => [],
            'details' => []
        ];
        
        if ($extension === 'php') {
            return $this->analyzePhpHeader($content);
        } elseif ($extension === 'css') {
            return $this->analyzeCssHeader($content);
        }
        
        return $result;
    }
    
    /**
     * Analyze PHP file header
     * 
     * @param string $content File content
     * @return array Analysis results
     */
    private function analyzePhpHeader($content)
    {
        $result = [
            'present' => false,
            'compliant' => false,
            'issues' => [],
            'details' => []
        ];
        
        // Check for opening PHP tag
        if (!preg_match('/^<\?php/', $content)) {
            $result['issues'][] = 'Missing opening PHP tag';
            return $result;
        }
        
        // Extract the comment block after <?php
        preg_match('/^<\?php\s*\r?\n(\/\*\*.*?\*\/)/s', $content, $matches);
        
        if (empty($matches[1])) {
            $result['issues'][] = 'No documentation header found';
            return $result;
        }
        
        $result['present'] = true;
        $headerBlock = $matches[1];
        
        // Check for required elements in WordPress plugin files
        $requiredElements = [
            'package' => '/\* @package\s+(.+)/i',
            'author' => '/\* @author\s+(.+)/i',
            'since' => '/\* @since\s+(.+)/i'
        ];
        
        // Special check for main plugin file (askro.php)
        if (strpos($content, 'Plugin Name:') !== false) {
            $result['details']['plugin_header'] = true;
            $requiredElements = array_merge($requiredElements, [
                'plugin_name' => '/\* Plugin Name:\s*(.+)/i',
                'version' => '/\* Version:\s*(.+)/i',
                'description' => '/\* Description:\s*(.+)/i',
                'license' => '/\* License:\s*(.+)/i'
            ]);
        }
        
        $foundElements = 0;
        foreach ($requiredElements as $element => $pattern) {
            if (preg_match($pattern, $headerBlock, $elementMatch)) {
                $result['details'][$element] = trim($elementMatch[1]);
                $foundElements++;
            } else {
                $result['issues'][] = "Missing {$element} declaration";
            }
        }
        
        // Check compliance
        $totalRequired = count($requiredElements);
        if ($foundElements === $totalRequired) {
            $result['compliant'] = true;
        } else {
            $result['issues'][] = "Only {$foundElements}/{$totalRequired} required elements found";
        }
        
        // Additional checks
        if (!preg_match('/defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)/', $content)) {
            $result['issues'][] = 'Missing ABSPATH security check';
        }
        
        return $result;
    }
    
    /**
     * Analyze CSS file header
     * 
     * @param string $content File content
     * @return array Analysis results
     */
    private function analyzeCssHeader($content)
    {
        $result = [
            'present' => false,
            'compliant' => false,
            'issues' => [],
            'details' => []
        ];
        
        // Check for CSS comment header
        preg_match('/^\/\*\*.*?\*\//s', trim($content), $matches);
        
        if (empty($matches[0])) {
            $result['issues'][] = 'No CSS header comment found';
            return $result;
        }
        
        $result['present'] = true;
        $headerBlock = $matches[0];
        
        // Check for basic CSS file documentation
        $requiredElements = [
            'description' => '/\*\s*(.+?)\s*\*/s',
            'package' => '/@package\s+(.+)/i',
            'since' => '/@since\s+(.+)/i'
        ];
        
        $foundElements = 0;
        foreach ($requiredElements as $element => $pattern) {
            if (preg_match($pattern, $headerBlock, $elementMatch)) {
                $result['details'][$element] = trim($elementMatch[1]);
                $foundElements++;
            } else {
                $result['issues'][] = "Missing {$element} in CSS header";
            }
        }
        
        // CSS files have more relaxed requirements
        if ($foundElements >= 1) {
            $result['compliant'] = true;
        }
        
        return $result;
    }
    
    /**
     * Generate JSON inventory report
     */
    private function generateJsonReport()
    {
        $report = [
            'audit_metadata' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'plugin_name' => 'Askro',
                'base_directory' => $this->baseDir,
                'total_files' => count($this->auditResults),
                'tool_version' => '1.0.0'
            ],
            'summary' => $this->generateSummary(),
            'files' => $this->auditResults
        ];
        
        $jsonPath = $this->baseDir . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'header-audit-inventory.json';
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Generate summary statistics
     * 
     * @return array Summary data
     */
    private function generateSummary()
    {
        $summary = [
            'total_files' => 0,
            'php_files' => 0,
            'css_files' => 0,
            'files_with_headers' => 0,
            'compliant_files' => 0,
            'non_compliant_files' => 0,
            'compliance_rate' => 0
        ];
        
        foreach ($this->auditResults as $result) {
            $summary['total_files']++;
            
            if ($result['file_type'] === 'php') {
                $summary['php_files']++;
            } elseif ($result['file_type'] === 'css') {
                $summary['css_files']++;
            }
            
            if ($result['header_present']) {
                $summary['files_with_headers']++;
            }
            
            if ($result['header_compliant']) {
                $summary['compliant_files']++;
            } else {
                $summary['non_compliant_files']++;
            }
        }
        
        if ($summary['total_files'] > 0) {
            $summary['compliance_rate'] = round(($summary['compliant_files'] / $summary['total_files']) * 100, 2);
        }
        
        return $summary;
    }
    
    /**
     * Generate markdown report
     */
    private function generateMarkdownReport()
    {
        $summary = $this->generateSummary();
        $markdownPath = $this->baseDir . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'header-audit-report.md';
        
        $content = $this->generateMarkdownContent($summary);
        file_put_contents($markdownPath, $content);
    }
    
    /**
     * Generate markdown content
     * 
     * @param array $summary Summary statistics
     * @return string Markdown content
     */
    private function generateMarkdownContent($summary)
    {
        $md = "# Askro Plugin Header Audit Report\n\n";
        $md .= "**Generated:** " . date('Y-m-d H:i:s') . "\n";
        $md .= "**Tool Version:** 1.0.0\n\n";
        
        // Executive Summary
        $md .= "## Executive Summary\n\n";
        $md .= "This report provides a comprehensive audit of file headers across the Askro WordPress plugin codebase.\n\n";
        
        // Statistics
        $md .= "### Statistics\n\n";
        $md .= "| Metric | Count | Percentage |\n";
        $md .= "|--------|--------|------------|\n";
        $md .= "| Total Files | {$summary['total_files']} | 100% |\n";
        $md .= "| PHP Files | {$summary['php_files']} | " . ($summary['total_files'] > 0 ? round(($summary['php_files'] / $summary['total_files']) * 100, 1) : 0) . "% |\n";
        $md .= "| CSS Files | {$summary['css_files']} | " . ($summary['total_files'] > 0 ? round(($summary['css_files'] / $summary['total_files']) * 100, 1) : 0) . "% |\n";
        $md .= "| Files with Headers | {$summary['files_with_headers']} | " . ($summary['total_files'] > 0 ? round(($summary['files_with_headers'] / $summary['total_files']) * 100, 1) : 0) . "% |\n";
        $md .= "| Compliant Files | {$summary['compliant_files']} | {$summary['compliance_rate']}% |\n";
        $md .= "| Non-Compliant Files | {$summary['non_compliant_files']} | " . (100 - $summary['compliance_rate']) . "% |\n\n";
        
        // Compliance Status
        if ($summary['compliance_rate'] >= 90) {
            $statusIcon = "✅";
            $statusText = "EXCELLENT";
        } elseif ($summary['compliance_rate'] >= 70) {
            $statusIcon = "⚠️";
            $statusText = "GOOD";
        } elseif ($summary['compliance_rate'] >= 50) {
            $statusIcon = "⚠️";
            $statusText = "NEEDS IMPROVEMENT";
        } else {
            $statusIcon = "❌";
            $statusText = "POOR";
        }
        
        $md .= "### Overall Compliance Status\n\n";
        $md .= "{$statusIcon} **{$statusText}** ({$summary['compliance_rate']}% compliance rate)\n\n";
        
        // Detailed Results
        $md .= "## Detailed File Analysis\n\n";
        
        foreach ($this->auditResults as $result) {
            $status = $result['header_compliant'] ? "✅ COMPLIANT" : ($result['header_present'] ? "⚠️ NON-COMPLIANT" : "❌ NO HEADER");
            
            $md .= "### `{$result['file_path']}`\n\n";
            $md .= "- **Status:** {$status}\n";
            $md .= "- **Type:** " . strtoupper($result['file_type']) . " file\n";
            $md .= "- **Size:** " . number_format($result['file_size']) . " bytes\n";
            $md .= "- **Modified:** {$result['last_modified']}\n";
            
            if (!empty($result['issues'])) {
                $md .= "- **Issues:**\n";
                foreach ($result['issues'] as $issue) {
                    $md .= "  - {$issue}\n";
                }
            }
            
            if (!empty($result['header_details'])) {
                $md .= "- **Header Details:**\n";
                foreach ($result['header_details'] as $key => $value) {
                    $md .= "  - **" . ucfirst(str_replace('_', ' ', $key)) . ":** {$value}\n";
                }
            }
            
            $md .= "\n";
        }
        
        // Recommendations
        $md .= "## Recommendations\n\n";
        
        if ($summary['non_compliant_files'] > 0) {
            $md .= "### Immediate Actions Required\n\n";
            $md .= "1. **Add proper headers** to files currently missing documentation\n";
            $md .= "2. **Standardize header format** across all files\n";
            $md .= "3. **Include required documentation elements**:\n";
            $md .= "   - @package declaration\n";
            $md .= "   - @author information\n";
            $md .= "   - @since version\n";
            $md .= "   - Security checks (ABSPATH for PHP files)\n\n";
        }
        
        $md .= "### Best Practices\n\n";
        $md .= "1. **Consistent Format:** Use the same header format across all files\n";
        $md .= "2. **Security First:** Always include security checks in PHP files\n";
        $md .= "3. **Documentation:** Provide clear descriptions of file purpose\n";
        $md .= "4. **Version Control:** Include @since tags for tracking\n";
        $md .= "5. **License Compliance:** Ensure proper license declarations\n\n";
        
        // Footer
        $md .= "---\n\n";
        $md .= "*This report was generated automatically by the Askro Header Audit Tool.*\n";
        $md .= "*For questions or issues, please contact the development team.*\n";
        
        return $md;
    }
}

// Run the audit if this script is executed directly
if (php_sapi_name() === 'cli' || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET')) {
    $audit = new AskroHeaderAudit();
    $audit->runAudit();
}
