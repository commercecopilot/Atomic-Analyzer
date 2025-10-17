<?php
/**
 * Claude AI Integration for Atomic Analyzer
 * Connects to Anthropic's Claude API for intelligent business analysis
 */

if (!defined('ABSPATH')) exit;

class AA_Claude_AI {
    
    private $api_key;
    private $api_url = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-sonnet-20240229'; // Updated to stable model
    
    public function __construct() {
        $this->api_key = get_option('aa_claude_api_key');
    }
    
    /**
     * Generate comprehensive insights from analysis data
     */
    public function generate_insights($analysis_data) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Claude API key not configured');
        }
        
        $prompt = $this->build_analysis_prompt($analysis_data);
        
        $response = $this->call_claude_api($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_insights($response);
    }
    
    /**
     * Get specific department recommendations
     */
    public function get_department_insights($department, $analysis_data) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Claude API key not configured');
        }
        
        $prompt = $this->build_department_prompt($department, $analysis_data);
        
        $response = $this->call_claude_api($prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->parse_insights($response);
    }
    
    /**
     * Generate process documentation using Claude
     */
    public function generate_process_documentation($department, $context) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Claude API key not configured');
        }
        
        $prompt = $this->build_process_prompt($department, $context);
        
        $response = $this->call_claude_api($prompt, 3000);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->extract_process_docs($response);
    }
    
    /**
     * Get quick action recommendations
     */
    public function get_quick_wins($analysis_data) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Claude API key not configured');
        }
        
        $prompt = "Based on this business analysis using The Personal MBA framework:\n\n";
        $prompt .= "Overall Score: " . $analysis_data['overall_score'] . "/100\n";
        $prompt .= "PMBA Alignment: " . $analysis_data['pmba_alignment'] . "/100\n\n";
        
        $prompt .= "Department Scores:\n";
        foreach ($analysis_data['departments'] as $dept => $data) {
            $prompt .= "- " . ucfirst($dept) . ": " . $data['score'] . "/100\n";
            if (!empty($data['issues'])) {
                foreach ($data['issues'] as $issue) {
                    $prompt .= "  • " . $issue['title'] . " (Severity: " . $issue['severity'] . ")\n";
                }
            }
        }
        
        $prompt .= "\nProvide exactly 5 quick-win actions that:\n";
        $prompt .= "1. Can be completed in under 1 hour each\n";
        $prompt .= "2. Have high impact on business fundamentals\n";
        $prompt .= "3. Follow Personal MBA principles\n";
        $prompt .= "4. Are specific and actionable\n";
        $prompt .= "5. Address the most critical issues first\n\n";
        $prompt .= "Format as a numbered list with just the action (no explanations).";
        
        $response = $this->call_claude_api($prompt, 500);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $this->extract_list_items($response);
    }
    
    /**
     * Build comprehensive analysis prompt
     */
    private function build_analysis_prompt($data) {
        $business_type = $data['business_type'];
        $overall_score = $data['overall_score'];
        $pmba_alignment = $data['pmba_alignment'];
        
        $prompt = "You are a business consultant expert in The Personal MBA framework by Josh Kaufman. ";
        $prompt .= "Analyze this WordPress business and provide strategic insights.\n\n";
        
        $prompt .= "BUSINESS CONTEXT:\n";
        $prompt .= "Type: " . $business_type . "\n";
        $prompt .= "Overall Health Score: " . $overall_score . "/100\n";
        $prompt .= "PMBA Alignment: " . $pmba_alignment . "/100\n\n";
        
        $prompt .= "THE 5 DEPARTMENTS ANALYSIS:\n\n";
        
        foreach ($data['departments'] as $dept_key => $dept_data) {
            $prompt .= strtoupper($dept_key) . " (" . $dept_data['score'] . "/100):\n";
            
            if (!empty($dept_data['issues'])) {
                $prompt .= "Issues:\n";
                foreach ($dept_data['issues'] as $issue) {
                    $prompt .= "- [" . $issue['severity'] . "] " . $issue['title'] . "\n";
                    $prompt .= "  PMBA Principle: " . $issue['principle'] . "\n";
                    $prompt .= "  Description: " . $issue['description'] . "\n";
                }
            }
            
            $prompt .= "\n";
        }
        
        $prompt .= "Based on The Personal MBA's 5 Primary Departments framework, provide:\n\n";
        $prompt .= "1. EXECUTIVE SUMMARY (2-3 sentences): Overall business health assessment\n\n";
        $prompt .= "2. CRITICAL PRIORITIES (Top 3): What must be fixed immediately and why\n\n";
        $prompt .= "3. QUICK WINS (5 actions): High-impact, low-effort improvements (<1 hour each)\n\n";
        $prompt .= "4. STRATEGIC MOVES (3-5): Long-term initiatives for sustainable growth\n\n";
        $prompt .= "5. PMBA WISDOM: Which Personal MBA principles are being violated and how to apply them\n\n";
        $prompt .= "6. 90-DAY ROADMAP: Phased plan with weeks 1-4, 5-8, and 9-12\n\n";
        $prompt .= "Keep all advice specific, actionable, and grounded in Personal MBA principles.";
        
        return $prompt;
    }
    
    /**
     * Build department-specific prompt
     */
    private function build_department_prompt($department, $data) {
        $dept_data = $data['departments'][$department];
        
        $prompt = "You are a business consultant specializing in The Personal MBA framework.\n\n";
        $prompt .= "Analyze the " . strtoupper($department) . " department for this " . $data['business_type'] . ".\n\n";
        
        $prompt .= "Current Score: " . $dept_data['score'] . "/100\n\n";
        
        if (!empty($dept_data['issues'])) {
            $prompt .= "Issues Found:\n";
            foreach ($dept_data['issues'] as $issue) {
                $prompt .= "- " . $issue['title'] . " (" . $issue['severity'] . ")\n";
                $prompt .= "  PMBA: " . $issue['pmba_guidance'] . "\n";
            }
        }
        
        $prompt .= "\nProvide:\n";
        $prompt .= "1. Deep dive analysis of this department's health\n";
        $prompt .= "2. Specific Personal MBA principles that apply\n";
        $prompt .= "3. Step-by-step action plan to improve\n";
        $prompt .= "4. Metrics to track progress\n";
        $prompt .= "5. Common pitfalls to avoid\n";
        
        return $prompt;
    }
    
    /**
     * Build process documentation prompt
     */
    private function build_process_prompt($department, $context) {
        $prompt = "You are a business process expert using The Personal MBA framework.\n\n";
        $prompt .= "Generate Standard Operating Procedures (SOPs) for the " . strtoupper($department) . " department.\n\n";
        $prompt .= "Business Context: " . $context['business_type'] . "\n";
        $prompt .= "Department Score: " . $context['department_score'] . "/100\n\n";
        
        $prompt .= "Create comprehensive process documentation including:\n\n";
        $prompt .= "1. PROCESS MAP: Visual flow of key activities\n";
        $prompt .= "2. SOPs: Detailed step-by-step procedures\n";
        $prompt .= "3. CHECKLISTS: Daily, weekly, and monthly tasks\n";
        $prompt .= "4. AUTOMATION OPPORTUNITIES: What can be automated\n";
        $prompt .= "5. KPIs: Key metrics to track\n";
        $prompt .= "6. PMBA ALIGNMENT: How processes follow PMBA principles\n\n";
        
        $prompt .= "Format in clean markdown for easy copy/paste.";
        
        return $prompt;
    }
    
    /**
     * Call Claude API
     */
    private function call_claude_api($prompt, $max_tokens = 2000) {
        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $this->api_key,
            'anthropic-version' => '2023-06-01'
        );
        
        $body = array(
            'model' => $this->model,
            'max_tokens' => $max_tokens,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7
        );
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => $headers,
            'body' => json_encode($body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            $error_body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($error_body['error']['message']) 
                ? $error_body['error']['message'] 
                : 'Claude API error: ' . $status_code;
            return new WP_Error('api_error', $error_message);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['content'][0]['text'])) {
            return new WP_Error('invalid_response', 'Invalid response from Claude API');
        }
        
        return $body['content'][0]['text'];
    }
    
    /**
     * Parse insights from Claude response
     */
    private function parse_insights($response) {
        // Extract sections from Claude's response
        $insights = array(
            'executive_summary' => '',
            'critical_priorities' => array(),
            'quick_wins' => array(),
            'strategic_moves' => array(),
            'pmba_wisdom' => '',
            'roadmap' => '',
            'raw_response' => $response,
            'generated_at' => current_time('mysql')
        );
        
        // Simple parsing - look for section headers
        if (preg_match('/EXECUTIVE SUMMARY[:\s]+(.*?)(?=\n\n|CRITICAL|$)/is', $response, $matches)) {
            $insights['executive_summary'] = trim($matches[1]);
        }
        
        if (preg_match('/CRITICAL PRIORITIES[:\s]+(.*?)(?=\n\nQUICK|$)/is', $response, $matches)) {
            $insights['critical_priorities'] = $this->extract_list_items($matches[1]);
        }
        
        if (preg_match('/QUICK WINS[:\s]+(.*?)(?=\n\nSTRATEGIC|$)/is', $response, $matches)) {
            $insights['quick_wins'] = $this->extract_list_items($matches[1]);
        }
        
        if (preg_match('/STRATEGIC MOVES[:\s]+(.*?)(?=\n\nPMBA|$)/is', $response, $matches)) {
            $insights['strategic_moves'] = $this->extract_list_items($matches[1]);
        }
        
        if (preg_match('/PMBA WISDOM[:\s]+(.*?)(?=\n\n90-DAY|$)/is', $response, $matches)) {
            $insights['pmba_wisdom'] = trim($matches[1]);
        }
        
        if (preg_match('/90-DAY ROADMAP[:\s]+(.*?)$/is', $response, $matches)) {
            $insights['roadmap'] = trim($matches[1]);
        }
        
        return $insights;
    }
    
    /**
     * Extract process documentation
     */
    private function extract_process_docs($response) {
        return array(
            'content' => $response,
            'sections' => $this->parse_markdown_sections($response),
            'generated_at' => current_time('mysql')
        );
    }
    
    /**
     * Extract list items from text
     */
    private function extract_list_items($text) {
        $items = array();
        
        // Match numbered or bulleted lists
        preg_match_all('/(?:^|\n)\s*(?:\d+\.|[-•*])\s*(.+?)(?=\n|$)/m', $text, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $item) {
                $item = trim($item);
                if (!empty($item)) {
                    $items[] = $item;
                }
            }
        }
        
        return $items;
    }
    
    /**
     * Parse markdown sections
     */
    private function parse_markdown_sections($markdown) {
        $sections = array();
        $current_section = '';
        $current_content = '';
        
        $lines = explode("\n", $markdown);
        
        foreach ($lines as $line) {
            if (preg_match('/^#+\s+(.+)$/', $line, $matches)) {
                if ($current_section) {
                    $sections[$current_section] = trim($current_content);
                }
                $current_section = $matches[1];
                $current_content = '';
            } else {
                $current_content .= $line . "\n";
            }
        }
        
        if ($current_section) {
            $sections[$current_section] = trim($current_content);
        }
        
        return $sections;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'API key not set');
        }
        
        $test_prompt = "Respond with exactly: 'Claude AI connection successful!'";
        $response = $this->call_claude_api($test_prompt, 100);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return array(
            'success' => true,
            'message' => 'Claude AI connected successfully!',
            'model' => $this->model
        );
    }
    
    /**
     * Generate executive report using Claude
     */
    public function generate_executive_report($analysis_data) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Claude API key not configured');
        }
        
        $prompt = "You are creating an executive business report based on The Personal MBA framework.\n\n";
        $prompt .= "Business Type: " . $analysis_data['business_type'] . "\n";
        $prompt .= "Overall Score: " . $analysis_data['overall_score'] . "/100\n\n";
        
        $prompt .= "Create a professional executive report that includes:\n";
        $prompt .= "1. Executive Summary (focus on business impact)\n";
        $prompt .= "2. Key Performance Indicators\n";
        $prompt .= "3. Critical Business Risks\n";
        $prompt .= "4. Strategic Recommendations\n";
        $prompt .= "5. Implementation Timeline\n";
        $prompt .= "6. Expected ROI\n\n";
        
        $prompt .= "Make it suitable for C-level executives and investors.";
        
        $response = $this->call_claude_api($prompt, 2500);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return $response;
    }
}