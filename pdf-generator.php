<?php
/**
 * PDF Report Generator for Atomic Analyzer
 * Creates beautiful PDF reports from analysis data
 */

if (!defined('ABSPATH')) exit;

class AA_PDF_Generator {
    
    private $upload_dir;
    private $tcpdf_loaded = false;
    
    public function __construct() {
        $upload = wp_upload_dir();
        $this->upload_dir = $upload['basedir'] . '/atomic-reports/';
        
        // Create directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            
            // Add .htaccess for security
            $htaccess = $this->upload_dir . '.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Options -Indexes\n<FilesMatch '\.(pdf|html)$'>\n    Order allow,deny\n    Allow from all\n</FilesMatch>");
            }
        }
    }
    
    /**
     * Generate comprehensive PDF report
     */
    public function generate_report($analysis_data, $options = array()) {
        $defaults = array(
            'include_cover' => true,
            'include_executive_summary' => true,
            'include_departments' => true,
            'include_recommendations' => true,
            'include_pmba_guide' => true,
            'style' => 'professional' // professional, minimal, colorful
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Generate HTML content
        $html = $this->build_html_report($analysis_data, $options);
        
        // For now, save as HTML (can be converted to PDF with external library)
        $filename = 'atomic-report-' . date('Y-m-d-His') . '.html';
        $filepath = $this->upload_dir . $filename;
        
        // Add print-friendly wrapper
        $full_html = $this->get_html_wrapper($options['style']) . $html . '</body></html>';
        
        if (!file_put_contents($filepath, $full_html)) {
            return new WP_Error('save_failed', 'Could not save report file');
        }
        
        $upload = wp_upload_dir();
        $file_url = str_replace($upload['basedir'], $upload['baseurl'], $filepath);
        
        return $file_url;
    }
    
    /**
     * Get HTML wrapper with styles
     */
    private function get_html_wrapper($style = 'professional') {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Atomic Business Analysis Report</title>
    <style>
        ' . $this->get_pdf_styles($style) . '
        @media print {
            .page-break { page-break-after: always; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>';
    }
    
    /**
     * Build HTML for PDF report
     */
    private function build_html_report($data, $options) {
        $business_type = $data['business_type'];
        $overall_score = $data['overall_score'];
        $pmba_alignment = $data['pmba_alignment'];
        $date = date('F j, Y');
        
        $html = '';
        
        // Cover Page
        if ($options['include_cover']) {
            $html .= $this->build_cover_page($data, $date);
        }
        
        // Executive Summary
        if ($options['include_executive_summary']) {
            $html .= $this->build_executive_summary($data);
        }
        
        // Score Overview
        $html .= $this->build_score_overview($data);
        
        // Department Details
        if ($options['include_departments']) {
            $html .= $this->build_department_details($data);
        }
        
        // Recommendations
        if ($options['include_recommendations']) {
            $html .= $this->build_recommendations($data);
        }
        
        // PMBA Guide
        if ($options['include_pmba_guide']) {
            $html .= $this->build_pmba_guide();
        }
        
        // Footer
        $html .= $this->build_footer($date);
        
        return $html;
    }
    
    /**
     * Build cover page
     */
    private function build_cover_page($data, $date) {
        $company_name = get_option('aa_company_name', get_bloginfo('name'));
        
        $html = '<div class="cover-page">
            <div class="cover-logo">⚛️</div>
            <h1 class="cover-title">Atomic Business Analysis Report</h1>
            <p class="cover-subtitle">Powered by The Personal MBA Framework</p>
            
            <div class="cover-company">
                <h2>' . esc_html($company_name) . '</h2>
            </div>
            
            <div class="cover-score">
                <div class="score-circle">
                    <span class="score-number">' . $data['overall_score'] . '</span>
                    <span class="score-label">/100</span>
                </div>
                <p class="score-status">' . $this->get_score_status($data['overall_score']) . '</p>
            </div>
            
            <div class="cover-meta">
                <p><strong>Business Type:</strong> ' . esc_html($data['business_type']) . '</p>
                <p><strong>Analysis Date:</strong> ' . $date . '</p>
                <p><strong>PMBA Alignment:</strong> ' . $data['pmba_alignment'] . '/100</p>
            </div>
            
            <div class="cover-footer">
                <p>by Commerce Copilot</p>
                <p class="confidential">CONFIDENTIAL - Internal Use Only</p>
            </div>
        </div>
        <div class="page-break"></div>';
        
        return $html;
    }
    
    /**
     * Build executive summary
     */
    private function build_executive_summary($data) {
        $html = '<div class="section">
            <h2 class="section-title">📊 Executive Summary</h2>
            
            <div class="summary-box">
                <h3>Business Health Overview</h3>
                <p class="lead">' . $this->generate_executive_summary($data) . '</p>
            </div>
            
            <div class="key-findings">
                <h3>Key Findings</h3>
                <ul>';
        
        // Find critical issues
        $critical_count = 0;
        $high_count = 0;
        $weakest_dept = '';
        $weakest_score = 100;
        $strongest_dept = '';
        $strongest_score = 0;
        
        foreach ($data['departments'] as $dept_key => $dept_data) {
            if ($dept_data['score'] < $weakest_score) {
                $weakest_score = $dept_data['score'];
                $weakest_dept = ucfirst($dept_key);
            }
            
            if ($dept_data['score'] > $strongest_score) {
                $strongest_score = $dept_data['score'];
                $strongest_dept = ucfirst($dept_key);
            }
            
            foreach ($dept_data['issues'] as $issue) {
                if ($issue['severity'] === 'critical') {
                    $critical_count++;
                } elseif ($issue['severity'] === 'high') {
                    $high_count++;
                }
            }
        }
        
        $html .= '<li><strong>' . $critical_count . '</strong> critical issues requiring immediate attention</li>';
        $html .= '<li><strong>' . $high_count . '</strong> high-priority issues to address</li>';
        $html .= '<li>Weakest department: <strong>' . $weakest_dept . '</strong> (' . $weakest_score . '/100)</li>';
        $html .= '<li>Strongest department: <strong>' . $strongest_dept . '</strong> (' . $strongest_score . '/100)</li>';
        $html .= '<li>PMBA Alignment: <strong>' . $data['pmba_alignment'] . '/100</strong></li>';
        
        $html .= '</ul>
            </div>
            
            <div class="quick-stats">
                <h3>Department Performance</h3>
                <div class="stats-grid">';
        
        foreach ($data['departments'] as $dept_key => $dept_data) {
            $html .= '<div class="stat-box">
                <h4>' . ucfirst($dept_key) . '</h4>
                <div class="stat-score ' . $this->get_score_class($dept_data['score']) . '">
                    ' . $dept_data['score'] . '/100
                </div>
            </div>';
        }
        
        $html .= '</div>
            </div>
        </div>
        <div class="page-break"></div>';
        
        return $html;
    }
    
    /**
     * Build score overview
     */
    private function build_score_overview($data) {
        $html = '<div class="section">
            <h2 class="section-title">⚛️ The 5 Primary Departments</h2>
            <p class="section-intro">Every business must master these 5 core areas according to The Personal MBA framework by Josh Kaufman:</p>
            
            <div class="departments-grid">';
        
        $icons = array(
            'development' => '🔬',
            'marketing' => '📢',
            'sales' => '💰',
            'delivery' => '🚀',
            'accounting' => '💵'
        );
        
        $descriptions = array(
            'development' => 'Creating value that people want or need',
            'marketing' => 'Attracting attention and building demand',
            'sales' => 'Converting prospects into customers',
            'delivery' => 'Fulfilling promises and satisfying customers',
            'accounting' => 'Tracking money flow and profitability'
        );
        
        foreach ($data['departments'] as $dept_key => $dept_data) {
            $score_class = $this->get_score_class($dept_data['score']);
            
            $html .= '<div class="dept-card">
                <div class="dept-icon">' . $icons[$dept_key] . '</div>
                <h3>' . ucfirst($dept_key) . '</h3>
                <p class="dept-mini-desc">' . $descriptions[$dept_key] . '</p>
                <div class="dept-score ' . $score_class . '">' . $dept_data['score'] . '/100</div>
                <div class="dept-issues">';
            
            $critical = count(array_filter($dept_data['issues'], function($i) {
                return $i['severity'] === 'critical';
            }));
            
            if ($critical > 0) {
                $html .= '<span class="badge badge-critical">' . $critical . ' Critical</span>';
            } else {
                $html .= '<span class="badge badge-success">✔ Healthy</span>';
            }
            
            $html .= '</div>
            </div>';
        }
        
        $html .= '</div>
            
            <div class="pmba-alignment-box">
                <h3>Personal MBA Alignment Score</h3>
                <div class="alignment-score">
                    <span class="align-number">' . $data['pmba_alignment'] . '</span>
                    <span class="align-label">/100</span>
                </div>
                <p>' . $this->get_pmba_message($data['pmba_alignment']) . '</p>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Build department details
     */
    private function build_department_details($data) {
        $html = '<div class="page-break"></div>
        <div class="section">
            <h2 class="section-title">📖 Department Analysis</h2>';
        
        $icons = array(
            'development' => '🔬',
            'marketing' => '📢',
            'sales' => '💰',
            'delivery' => '🚀',
            'accounting' => '💵'
        );
        
        foreach ($data['departments'] as $dept_key => $dept_data) {
            $html .= '<div class="dept-section">
                <div class="dept-header">
                    <span class="dept-icon-inline">' . $icons[$dept_key] . '</span>
                    <h3 class="dept-name">' . ucfirst($dept_key) . ' Department</h3>
                    <span class="dept-score-badge ' . $this->get_score_class($dept_data['score']) . '">
                        Score: ' . $dept_data['score'] . '/100
                    </span>
                </div>';
            
            if (!empty($dept_data['issues'])) {
                $html .= '<div class="issues-list">';
                
                // Sort issues by severity
                usort($dept_data['issues'], function($a, $b) {
                    $severity_order = array('critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3);
                    return $severity_order[$a['severity']] - $severity_order[$b['severity']];
                });
                
                foreach ($dept_data['issues'] as $issue) {
                    $html .= '<div class="issue-card severity-' . $issue['severity'] . '">
                        <div class="issue-header">
                            <span class="severity-badge">' . strtoupper($issue['severity']) . '</span>
                            <span class="principle-label">Principle: ' . $issue['principle'] . '</span>
                        </div>
                        <h5>' . esc_html($issue['title']) . '</h5>
                        <p>' . esc_html($issue['description']) . '</p>
                        <div class="pmba-guidance">
                            <strong>📖 Personal MBA Guidance:</strong>
                            <p>' . esc_html($issue['pmba_guidance']) . '</p>
                        </div>
                        <div class="action-box">
                            <strong>✅ Recommended Action:</strong>
                            <p>' . esc_html($issue['action']) . '</p>
                        </div>
                    </div>';
                }
                
                $html .= '</div>';
            } else {
                $html .= '<div class="success-box">✅ No critical issues found in this department. Great work!</div>';
            }
            
            if (!empty($dept_data['principle_scores'])) {
                $html .= '<div class="principle-scores">
                    <h4>Principle Scores</h4>
                    <div class="principle-grid">';
                
                foreach ($dept_data['principle_scores'] as $principle => $score) {
                    $html .= '<div class="principle-item">
                        <span class="principle-name">' . esc_html($principle) . '</span>
                        <span class="principle-score ' . $this->get_score_class($score) . '">' . $score . '</span>
                    </div>';
                }
                
                $html .= '</div>
                </div>';
            }
            
            $html .= '</div>';
            
            // Add page break after each department except the last
            end($data['departments']);
            if ($dept_key !== key($data['departments'])) {
                $html .= '<div class="page-break"></div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Build recommendations section
     */
    private function build_recommendations($data) {
        $html = '<div class="page-break"></div>
        <div class="section">
            <h2 class="section-title">🎯 Priority Recommendations</h2>
            
            <div class="recommendations-section">
                <h3>🚨 Immediate Actions (This Week)</h3>
                <p class="rec-intro">Focus on these critical issues first for maximum impact:</p>
                <ol class="action-list">';
        
        // Get critical issues
        $critical_actions = array();
        foreach ($data['departments'] as $dept_name => $dept_data) {
            foreach ($dept_data['issues'] as $issue) {
                if ($issue['severity'] === 'critical') {
                    $critical_actions[] = array(
                        'action' => $issue['action'],
                        'department' => ucfirst($dept_name),
                        'principle' => $issue['principle']
                    );
                }
            }
        }
        
        // Limit to top 5
        $critical_actions = array_slice($critical_actions, 0, 5);
        
        foreach ($critical_actions as $action) {
            $html .= '<li>
                <strong>' . esc_html($action['action']) . '</strong>
                <div class="action-meta">
                    <span class="dept-tag">' . $action['department'] . '</span>
                    <span class="principle-tag">' . $action['principle'] . '</span>
                </div>
            </li>';
        }
        
        $html .= '</ol>
            </div>
            
            <div class="recommendations-section">
                <h3>📈 30-Day Improvement Plan</h3>
                <div class="timeline">';
        
        // Week 1-2
        $html .= '<div class="timeline-item">
                <div class="timeline-marker">Week 1-2</div>
                <div class="timeline-content">
                    <h4>Foundation & Critical Fixes</h4>
                    <ul>
                        <li>Address all critical issues identified above</li>
                        <li>Set up basic tracking and monitoring</li>
                        <li>Document current processes</li>
                    </ul>
                </div>
            </div>';
        
        // Week 3-4
        $html .= '<div class="timeline-item">
                <div class="timeline-marker">Week 3-4</div>
                <div class="timeline-content">
                    <h4>Optimization & Growth</h4>
                    <ul>
                        <li>Focus on weakest department: ' . $this->get_weakest_department($data) . '</li>
                        <li>Implement high-priority improvements</li>
                        <li>Start testing and iterating</li>
                    </ul>
                </div>
            </div>';
        
        $html .= '</div>
            </div>
            
            <div class="recommendations-section">
                <h3>🚀 Quick Wins</h3>
                <p>These can be implemented in under 1 hour each:</p>
                <ul class="quick-wins-list">';
        
        // Add some universal quick wins based on common issues
        $quick_wins = $this->generate_quick_wins($data);
        
        foreach ($quick_wins as $win) {
            $html .= '<li>' . esc_html($win) . '</li>';
        }
        
        $html .= '</ul>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Build PMBA guide
     */
    private function build_pmba_guide() {
        $html = '<div class="page-break"></div>
        <div class="section">
            <h2 class="section-title">📚 The Personal MBA Framework</h2>
            
            <p class="section-intro">This analysis is based on Josh Kaufman\'s Personal MBA framework, which identifies the 5 core areas every successful business must master.</p>
            
            <div class="pmba-principles">
                <div class="principle-box">
                    <h3>🔬 Development</h3>
                    <p><strong>Core Purpose:</strong> Creating something of value that people want or need.</p>
                    <p><strong>Key Concepts:</strong> Value Creation, Iteration, Prototyping, Economic Values</p>
                    <p><strong>The 9 Economic Values:</strong></p>
                    <ol>
                        <li>Efficacy - How well does it work?</li>
                        <li>Speed - How quickly does it work?</li>
                        <li>Reliability - Can I depend on it?</li>
                        <li>Ease of Use - How easy is it to use?</li>
                        <li>Flexibility - How many things does it do?</li>
                        <li>Status - What does this say about me?</li>
                        <li>Aesthetic Appeal - How attractive is it?</li>
                        <li>Emotion - How does it make me feel?</li>
                        <li>Cost - How much do I have to give up?</li>
                    </ol>
                </div>
                
                <div class="principle-box">
                    <h3>📢 Marketing</h3>
                    <p><strong>Core Purpose:</strong> Attracting attention and building demand for what you create.</p>
                    <p><strong>Key Concepts:</strong> Attention, Remarkability, Permission Asset, Social Proof, End Result</p>
                    <p><strong>Essential Elements:</strong></p>
                    <ul>
                        <li>Capturing and holding attention</li>
                        <li>Building a permission asset (email list)</li>
                        <li>Creating remarkable content worth sharing</li>
                        <li>Demonstrating social proof</li>
                        <li>Focusing on the end result for customers</li>
                    </ul>
                </div>
                
                <div class="principle-box">
                    <h3>💰 Sales</h3>
                    <p><strong>Core Purpose:</strong> Converting prospects into paying customers.</p>
                    <p><strong>Key Concepts:</strong> Trust, Barriers to Purchase, Risk Reversal, Common Ground, Education</p>
                    <p><strong>Critical Factors:</strong></p>
                    <ul>
                        <li>Building trust and credibility</li>
                        <li>Removing barriers to purchase</li>
                        <li>Offering risk reversal (guarantees)</li>
                        <li>Clear calls to action</li>
                        <li>Educating prospects on value</li>
                    </ul>
                </div>
                
                <div class="principle-box">
                    <h3>🚀 Delivery</h3>
                    <p><strong>Core Purpose:</strong> Delivering promised value and ensuring customer satisfaction.</p>
                    <p><strong>Key Concepts:</strong> Value Stream, Systems, Scalability, Expectation Effect, Throughput</p>
                    <p><strong>Success Factors:</strong></p>
                    <ul>
                        <li>Clear value delivery process</li>
                        <li>Documented systems and procedures</li>
                        <li>Meeting or exceeding expectations</li>
                        <li>Consistent quality and reliability</li>
                        <li>Ability to scale without breaking</li>
                    </ul>
                </div>
                
                <div class="principle-box">
                    <h3>💵 Accounting</h3>
                    <p><strong>Core Purpose:</strong> Tracking money flow and ensuring sustainable profitability.</p>
                    <p><strong>Key Concepts:</strong> Profit Margin, Value Capture, Sufficiency, Cash Flow, Leverage</p>
                    <p><strong>Financial Health Indicators:</strong></p>
                    <ul>
                        <li>Healthy profit margins</li>
                        <li>Multiple revenue streams</li>
                        <li>Positive cash flow</li>
                        <li>Financial leverage opportunities</li>
                        <li>Sustainable business model</li>
                    </ul>
                </div>
            </div>
            
            <div class="pmba-resources">
                <h3>📖 Learn More</h3>
                <p>To deepen your understanding of The Personal MBA framework:</p>
                <ul>
                    <li><strong>Book:</strong> "The Personal MBA" by Josh Kaufman</li>
                    <li><strong>Website:</strong> personalmba.com</li>
                    <li><strong>Key Takeaway:</strong> Master all 5 departments for a successful business</li>
                </ul>
            </div>
        </div>';
        
        return $html;
    }
    
    /**
     * Build footer
     */
    private function build_footer($date) {
        $html = '<div class="report-footer">
            <p>Generated by <strong>Atomic Analyzer</strong> by Commerce Copilot | ' . $date . '</p>
            <p class="footer-note">This report is based on The Personal MBA framework by Josh Kaufman</p>
            <p class="footer-url">' . get_site_url() . '</p>
        </div>';
        
        return $html;
    }
    
    /**
     * Get PDF styles
     */
    private function get_pdf_styles($style = 'professional') {
        $base_styles = '
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            padding: 0;
            margin: 0;
        }
        
        .cover-page {
            text-align: center;
            padding: 60px 40px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .cover-logo {
            font-size: 120px;
            margin-bottom: 30px;
        }
        
        .cover-title {
            font-size: 48px;
            color: #6366f1;
            margin-bottom: 10px;
        }
        
        .cover-subtitle {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 40px;
        }
        
        .cover-company {
            margin: 30px 0;
        }
        
        .cover-company h2 {
            font-size: 32px;
            color: #1e293b;
        }
        
        .score-circle {
            display: inline-block;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            margin: 40px auto;
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
        }
        
        .score-number {
            font-size: 72px;
            font-weight: bold;
            line-height: 1;
        }
        
        .score-label {
            font-size: 24px;
            opacity: 0.9;
        }
        
        .score-status {
            font-size: 20px;
            color: #64748b;
            margin-top: 20px;
        }
        
        .cover-meta {
            margin: 40px 0;
            font-size: 16px;
        }
        
        .cover-meta p {
            margin: 8px 0;
        }
        
        .confidential {
            color: #ef4444;
            font-weight: bold;
            margin-top: 40px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .section {
            padding: 40px;
        }
        
        .section-title {
            font-size: 36px;
            color: #6366f1;
            margin-bottom: 20px;
            border-bottom: 3px solid #e2e8f0;
            padding-bottom: 15px;
        }
        
        .section-intro {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 30px;
        }
        
        .summary-box {
            background: #f8fafc;
            border-left: 4px solid #6366f1;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 0 8px 8px 0;
        }
        
        .lead {
            font-size: 18px;
            line-height: 1.8;
            color: #334155;
        }
        
        .key-findings {
            margin: 30px 0;
        }
        
        .key-findings h3 {
            color: #1e293b;
            margin-bottom: 15px;
        }
        
        .key-findings ul {
            list-style-position: inside;
            margin: 20px 0;
        }
        
        .key-findings li {
            margin: 10px 0;
            font-size: 16px;
            color: #475569;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
        }
        
        .stat-box h4 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-score {
            font-size: 28px;
            font-weight: bold;
        }
        
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .dept-card {
            text-align: center;
            padding: 25px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .dept-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .dept-mini-desc {
            font-size: 13px;
            color: #64748b;
            margin: 8px 0;
        }
        
        .dept-score {
            font-size: 36px;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .dept-score.high { color: #10b981; }
        .dept-score.medium { color: #f59e0b; }
        .dept-score.low { color: #ef4444; }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-critical {
            background: #ef4444;
            color: white;
        }
        
        .badge-success {
            background: #10b981;
            color: white;
        }
        
        .pmba-alignment-box {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
        }
        
        .alignment-score {
            display: inline-block;
            margin: 20px 0;
        }
        
        .align-number {
            font-size: 48px;
            font-weight: bold;
            color: #8b5cf6;
        }
        
        .align-label {
            font-size: 24px;
            color: #8b5cf6;
        }
        
        .dept-section {
            margin: 40px 0;
            background: #fcfcfc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 25px;
        }
        
        .dept-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .dept-icon-inline {
            font-size: 36px;
        }
        
        .dept-name {
            font-size: 28px;
            color: #1e293b;
            margin: 0;
            flex-grow: 1;
        }
        
        .dept-score-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            color: white;
        }
        
        .dept-score-badge.high { background: #10b981; }
        .dept-score-badge.medium { background: #f59e0b; }
        .dept-score-badge.low { background: #ef4444; }
        
        .issues-list {
            margin-top: 20px;
        }
        
        .issue-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-left-width: 6px;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        
        .issue-card.severity-critical {
            border-left-color: #ef4444;
            background: linear-gradient(to right, rgba(239, 68, 68, 0.05) 0%, white 100%);
        }
        
        .issue-card.severity-high {
            border-left-color: #f59e0b;
            background: linear-gradient(to right, rgba(245, 158, 11, 0.05) 0%, white 100%);
        }
        
        .issue-card.severity-medium {
            border-left-color: #fbbf24;
        }
        
        .issue-card.severity-low {
            border-left-color: #3b82f6;
        }
        
        .issue-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .severity-badge {
            background: #ef4444;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .principle-label {
            color: #64748b;
            font-size: 13px;
            font-style: italic;
        }
        
        .issue-card h5 {
            font-size: 20px;
            color: #1e293b;
            margin: 10px 0;
        }
        
        .issue-card > p {
            color: #475569;
            line-height: 1.6;
        }
        
        .pmba-guidance {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border-left: 4px solid #8b5cf6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .pmba-guidance strong {
            color: #7c3aed;
            display: block;
            margin-bottom: 8px;
        }
        
        .pmba-guidance p {
            margin: 0;
            color: #4c1d95;
        }
        
        .action-box {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 0 8px 8px 0;
        }
        
        .action-box strong {
            color: #047857;
            display: block;
            margin-bottom: 8px;
        }
        
        .action-box p {
            margin: 0;
            color: #065f46;
            font-weight: 500;
        }
        
        .success-box {
            background: #d1fae5;
            color: #065f46;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
        }
        
        .principle-scores {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        
        .principle-scores h4 {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .principle-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .principle-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        
        .principle-name {
            color: #475569;
            font-size: 14px;
        }
        
        .principle-score {
            font-weight: bold;
            font-size: 16px;
        }
        
        .principle-score.high { color: #10b981; }
        .principle-score.medium { color: #f59e0b; }
        .principle-score.low { color: #ef4444; }
        
        .recommendations-section {
            margin: 40px 0;
        }
        
        .recommendations-section h3 {
            color: #1e293b;
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .rec-intro {
            color: #64748b;
            margin-bottom: 20px;
        }
        
        .action-list {
            list-style-position: inside;
            margin: 20px 0;
        }
        
        .action-list li {
            margin: 20px 0;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .action-meta {
            margin-top: 8px;
            margin-left: 20px;
        }
        
        .dept-tag,
        .principle-tag {
            display: inline-block;
            padding: 4px 10px;
            margin-right: 8px;
            font-size: 12px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .dept-tag {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .principle-tag {
            background: #fef3c7;
            color: #d97706;
        }
        
        .timeline {
            margin: 20px 0;
        }
        
        .timeline-item {
            display: flex;
            margin: 20px 0;
        }
        
        .timeline-marker {
            background: #6366f1;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            white-space: nowrap;
            margin-right: 20px;
        }
        
        .timeline-content {
            flex-grow: 1;
            padding: 15px;
            background: #f8fafc;
            border-left: 4px solid #6366f1;
            border-radius: 0 8px 8px 0;
        }
        
        .timeline-content h4 {
            margin: 0 0 10px 0;
            color: #1e293b;
        }
        
        .timeline-content ul {
            list-style-position: inside;
            margin: 10px 0;
        }
        
        .quick-wins-list {
            list-style-type: none;
            padding: 0;
        }
        
        .quick-wins-list li {
            padding: 12px 20px;
            margin: 10px 0;
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 0 8px 8px 0;
            font-weight: 500;
            color: #92400e;
        }
        
        .principle-box {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .principle-box h3 {
            color: #6366f1;
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .principle-box p {
            margin: 10px 0;
            line-height: 1.6;
        }
        
        .principle-box ol,
        .principle-box ul {
            margin: 15px 0;
            padding-left: 30px;
        }
        
        .principle-box li {
            margin: 8px 0;
        }
        
        .pmba-resources {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .pmba-resources h3 {
            color: #7c3aed;
            margin-bottom: 15px;
        }
        
        .pmba-resources ul {
            list-style-position: inside;
            margin: 15px 0;
        }
        
        .pmba-resources li {
            margin: 8px 0;
        }
        
        .report-footer {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-size: 14px;
            border-top: 2px solid #e2e8f0;
            margin-top: 60px;
        }
        
        .footer-note {
            margin-top: 10px;
            font-style: italic;
        }
        
        .footer-url {
            margin-top: 5px;
            color: #6366f1;
        }
        ';
        
        // Add style variations
        if ($style === 'colorful') {
            $base_styles .= '
            .dept-card { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
            .issue-card { border-radius: 12px; }
            ';
        } elseif ($style === 'minimal') {
            $base_styles .= '
            .dept-icon { display: none; }
            .cover-logo { display: none; }
            ';
        }
        
        return $base_styles;
    }
    
    /**
     * Helper methods
     */
    private function get_score_status($score) {
        if ($score >= 90) return 'Excellent - World-Class Business Operations';
        if ($score >= 75) return 'Good - Solid Foundation with Growth Potential';
        if ($score >= 60) return 'Fair - Several Areas Need Attention';
        return 'Critical - Immediate Improvements Required';
    }
    
    private function get_score_class($score) {
        if ($score >= 75) return 'high';
        if ($score >= 60) return 'medium';
        return 'low';
    }
    
    private function generate_executive_summary($data) {
        $score = $data['overall_score'];
        
        if ($score >= 80) {
            return "This business demonstrates strong fundamentals across The Personal MBA's 5 core departments. The foundation is solid with well-executed value creation, marketing, sales, delivery, and financial management. Focus should be on optimization and scaling existing systems while maintaining current strengths.";
        } elseif ($score >= 60) {
            return "This business has established core operations but shows significant room for improvement in key areas. Several Personal MBA principles are not being fully leveraged, creating opportunities for substantial growth. With focused attention on the identified critical issues, this business can achieve meaningful improvement in the next 90 days.";
        } else {
            return "This business requires immediate attention to fundamental areas. Critical gaps exist in one or more of the 5 primary departments that are limiting growth potential. Before attempting to scale, it's essential to address these foundational issues according to Personal MBA principles. The good news: implementing even basic improvements will yield significant results.";
        }
    }
    
    private function get_pmba_message($score) {
        if ($score >= 90) {
            return 'Your business exemplifies Personal MBA principles across all departments.';
        } elseif ($score >= 75) {
            return 'Good alignment with room for improvement in specific principles.';
        } elseif ($score >= 60) {
            return 'Moderate alignment - focus on implementing core principles.';
        } else {
            return 'Low alignment - prioritize understanding and applying fundamental principles.';
        }
    }
    
    private function get_weakest_department($data) {
        $weakest = '';
        $lowest_score = 100;
        
        foreach ($data['departments'] as $dept_key => $dept_data) {
            if ($dept_data['score'] < $lowest_score) {
                $lowest_score = $dept_data['score'];
                $weakest = ucfirst($dept_key);
            }
        }
        
        return $weakest;
    }
    
    private function generate_quick_wins($data) {
        $quick_wins = array();
        
        // Check for common quick wins based on analysis
        if ($data['overall_score'] < 70) {
            $quick_wins[] = 'Add clear contact information to every page footer';
            $quick_wins[] = 'Create a simple FAQ page addressing top 5 customer questions';
            $quick_wins[] = 'Add customer testimonials to homepage above the fold';
            $quick_wins[] = 'Install Google Analytics to start tracking visitor behavior';
            $quick_wins[] = 'Write and publish one blog post solving a customer problem';
        }
        
        // Department-specific quick wins
        foreach ($data['departments'] as $dept_key => $dept_data) {
            if ($dept_data['score'] < 60) {
                switch ($dept_key) {
                    case 'development':
                        $quick_wins[] = 'List 5 specific benefits of your product/service on homepage';
                        break;
                    case 'marketing':
                        $quick_wins[] = 'Add email signup form with valuable lead magnet';
                        break;
                    case 'sales':
                        $quick_wins[] = 'Add satisfaction guarantee near all buy buttons';
                        break;
                    case 'delivery':
                        $quick_wins[] = 'Create automated order confirmation emails';
                        break;
                    case 'accounting':
                        $quick_wins[] = 'Add one additional payment method option';
                        break;
                }
            }
        }
        
        return array_slice($quick_wins, 0, 5);
    }
}