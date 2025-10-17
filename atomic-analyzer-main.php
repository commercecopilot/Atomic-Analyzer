<?php
/**
 * Plugin Name: Atomic Analyzer by Commerce Copilot
 * Plugin URI: https://commercecopilot.com
 * Description: Complete business intelligence system using The Personal MBA framework. Includes Claude AI integration, PDF reports, process documentation, and webhook automation.
 * Version: 2.0.0
 * Author: Commerce Copilot
 * License: GPL v2 or later
 * Text Domain: atomic-analyzer
 */

if (!defined('ABSPATH')) exit;

define('AA_VERSION', '2.0.0');
define('AA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load modules
require_once AA_PLUGIN_DIR . 'includes/class-claude-ai.php';
require_once AA_PLUGIN_DIR . 'includes/class-pdf-generator.php';
require_once AA_PLUGIN_DIR . 'includes/class-process-builder.php';
require_once AA_PLUGIN_DIR . 'includes/class-webhook-manager.php';

class Atomic_Analyzer {
    
    private static $instance = null;
    private $pmba_framework;
    private $claude_ai;
    private $pdf_generator;
    private $process_builder;
    private $webhook_manager;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->pmba_framework = $this->initialize_pmba_framework();
        
        // Initialize modules
        $this->claude_ai = new AA_Claude_AI();
        $this->pdf_generator = new AA_PDF_Generator();
        $this->process_builder = new AA_Process_Builder();
        $this->webhook_manager = new AA_Webhook_Manager();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Core AJAX handlers
        add_action('wp_ajax_aa_run_atomic_analysis', array($this, 'ajax_run_atomic_analysis'));
        add_action('wp_ajax_aa_analyze_department', array($this, 'ajax_analyze_department'));
        add_action('wp_ajax_aa_detect_business_type', array($this, 'ajax_detect_business_type'));
        add_action('wp_ajax_aa_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_aa_save_business_type', array($this, 'ajax_save_business_type'));
        
        // Module AJAX handlers
        add_action('wp_ajax_aa_get_claude_insights', array($this, 'ajax_get_claude_insights'));
        add_action('wp_ajax_aa_generate_pdf_report', array($this, 'ajax_generate_pdf_report'));
        add_action('wp_ajax_aa_generate_process_docs', array($this, 'ajax_generate_process_docs'));
        add_action('wp_ajax_aa_test_webhook', array($this, 'ajax_test_webhook'));
        add_action('wp_ajax_aa_save_webhook', array($this, 'ajax_save_webhook'));
        add_action('wp_ajax_aa_delete_webhook', array($this, 'ajax_delete_webhook'));
        add_action('wp_ajax_aa_toggle_webhook', array($this, 'ajax_toggle_webhook'));
        add_action('wp_ajax_aa_test_claude_connection', array($this, 'ajax_test_claude_connection'));
        add_action('wp_ajax_aa_get_webhook_docs', array($this, 'ajax_get_webhook_docs'));
        add_action('wp_ajax_aa_export_docs', array($this, 'ajax_export_docs'));
        add_action('wp_ajax_aa_get_doc', array($this, 'ajax_get_doc'));
        add_action('wp_ajax_aa_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_aa_import_settings', array($this, 'ajax_import_settings'));
        add_action('wp_ajax_aa_regenerate_webhook_secret', array($this, 'ajax_regenerate_webhook_secret'));
        add_action('wp_ajax_aa_clear_all_data', array($this, 'ajax_clear_all_data'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    private function initialize_pmba_framework() {
        return array(
            'development' => array(
                'name' => 'Development',
                'icon' => '🔬',
                'description' => 'Creating something of value that people want or need',
                'key_principles' => array(
                    'Value Creation' => 'Are you creating genuine value?',
                    'Iteration Velocity' => 'How fast can you improve?',
                    'Economic Values' => 'Which of the 9 economic values do you provide?',
                    'Prototype' => 'Are you testing before investing heavily?',
                    'Iteration Cycle' => 'How quickly can you learn and adapt?'
                ),
                'economic_values' => array(
                    'Efficacy' => 'How well does it work?',
                    'Speed' => 'How quickly does it work?',
                    'Reliability' => 'Can I depend on it?',
                    'Ease of Use' => 'How easy is it to use?',
                    'Flexibility' => 'How many things does it do?',
                    'Status' => 'What does this say about me?',
                    'Aesthetic Appeal' => 'How attractive/appealing is it?',
                    'Emotion' => 'How does this make me feel?',
                    'Cost' => 'How much do I have to give up?'
                )
            ),
            'marketing' => array(
                'name' => 'Marketing',
                'icon' => '📢',
                'description' => 'Attracting attention and building demand for what you create',
                'key_principles' => array(
                    'Attention' => 'Are you capturing attention of prospects?',
                    'Receptivity' => 'Are prospects open to your message?',
                    'Remarkability' => 'Is your offer worth talking about?',
                    'Probable Purchaser' => 'Are you reaching the right people?',
                    'Preoccupation' => 'What are prospects thinking about?',
                    'End Result' => 'What transformation do you promise?',
                    'Qualification' => 'Are you filtering for ideal customers?'
                )
            ),
            'sales' => array(
                'name' => 'Sales',
                'icon' => '💰',
                'description' => 'Turning prospective customers into paying customers',
                'key_principles' => array(
                    'Trust' => 'Do prospects trust you?',
                    'Common Ground' => 'Do you understand their needs?',
                    'Education' => 'Are prospects informed about value?',
                    'Pricing Uncertainty' => 'Is pricing clear and fair?',
                    'Barriers to Purchase' => 'What prevents people from buying?',
                    'Risk Reversal' => 'Are you reducing perceived risk?',
                    'Call to Action' => 'Is next step obvious?'
                )
            ),
            'delivery' => array(
                'name' => 'Delivery',
                'icon' => '🚀',
                'description' => 'Delivering the value promised and ensuring customer satisfaction',
                'key_principles' => array(
                    'Value Stream' => 'How is value actually delivered?',
                    'Expectation Effect' => 'Are you meeting/exceeding expectations?',
                    'Predictability' => 'Is delivery consistent?',
                    'Throughput' => 'How much can you deliver?',
                    'Duplication' => 'Can processes be replicated?',
                    'Scalability' => 'Can you grow without breaking?',
                    'Systems' => 'Are processes documented and automated?'
                )
            ),
            'accounting' => array(
                'name' => 'Accounting',
                'icon' => '💵',
                'description' => 'Tracking money flow and ensuring sustainable profitability',
                'key_principles' => array(
                    'Profit Margin' => 'How much profit per sale?',
                    'Value Capture' => 'Are you capturing fair value?',
                    'Sufficiency' => 'Is revenue enough to sustain?',
                    'Valuation' => 'What is the business worth?',
                    'Cash Flow Cycle' => 'How quickly does money flow?',
                    'Breakeven' => 'What volume is needed to survive?',
                    'Amortization' => 'Are you spreading costs intelligently?',
                    'Leverage' => 'Are you multiplying results?'
                )
            )
        );
    }
    
    public function activate() {
        global $wpdb;
        
        // Analysis table
        $table_name = $wpdb->prefix . 'atomic_analysis';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            analysis_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            business_type varchar(100) NOT NULL,
            department varchar(50) NOT NULL,
            analysis_data longtext NOT NULL,
            atomic_score int NOT NULL,
            pmba_alignment int NOT NULL,
            PRIMARY KEY  (id),
            KEY department (department),
            KEY analysis_date (analysis_date)
        ) $charset_collate;";
        
        // Webhooks table
        $webhooks_table = $wpdb->prefix . 'atomic_webhooks';
        $sql2 = "CREATE TABLE IF NOT EXISTS $webhooks_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            webhook_name varchar(100) NOT NULL,
            webhook_url varchar(500) NOT NULL,
            trigger_event varchar(100) NOT NULL,
            webhook_method varchar(10) DEFAULT 'POST',
            custom_headers longtext,
            is_active tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_triggered datetime,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Process documentation table
        $process_table = $wpdb->prefix . 'atomic_processes';
        $sql3 = "CREATE TABLE IF NOT EXISTS $process_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            process_name varchar(200) NOT NULL,
            department varchar(50) NOT NULL,
            process_type varchar(50) NOT NULL,
            process_content longtext NOT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY department (department),
            KEY process_type (process_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);
        
        // Set default options
        if (!get_option('aa_business_type')) {
            update_option('aa_business_type', $this->detect_business_type());
        }
        
        if (!get_option('aa_claude_api_key')) {
            update_option('aa_claude_api_key', '');
        }
        
        if (!get_option('aa_webhook_secret')) {
            update_option('aa_webhook_secret', wp_generate_password(32, false));
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Atomic Analyzer',
            'Atomic Analyzer',
            'manage_options',
            'atomic-analyzer',
            array($this, 'render_dashboard'),
            'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIxMCIgY3k9IjEwIiByPSI4IiBzdHJva2U9IiNhN2FhYWQiIHN0cm9rZS13aWR0aD0iMiIvPjxjaXJjbGUgY3g9IjEwIiBjeT0iMTAiIHI9IjMiIGZpbGw9IiNhN2FhYWQiLz48L3N2Zz4=',
            3
        );
        
        // 5 Department Pages
        foreach ($this->pmba_framework as $dept_key => $dept_data) {
            add_submenu_page(
                'atomic-analyzer',
                $dept_data['name'],
                $dept_data['icon'] . ' ' . $dept_data['name'],
                'manage_options',
                'aa-' . $dept_key,
                array($this, 'render_department_page')
            );
        }
        
        // Claude AI Insights
        add_submenu_page(
            'atomic-analyzer',
            'Claude AI Insights',
            '🤖 Claude AI',
            'manage_options',
            'aa-claude-insights',
            array($this, 'render_claude_insights')
        );
        
        // PDF Reports
        add_submenu_page(
            'atomic-analyzer',
            'PDF Reports',
            '📄 PDF Reports',
            'manage_options',
            'aa-pdf-reports',
            array($this, 'render_pdf_reports')
        );
        
        // Process Documentation
        add_submenu_page(
            'atomic-analyzer',
            'Process Documentation',
            '📋 Process Docs',
            'manage_options',
            'aa-process-docs',
            array($this, 'render_process_docs')
        );
        
        // Webhooks
        add_submenu_page(
            'atomic-analyzer',
            'Webhooks & Automation',
            '🔗 Webhooks',
            'manage_options',
            'aa-webhooks',
            array($this, 'render_webhooks')
        );
        
        // Settings
        add_submenu_page(
            'atomic-analyzer',
            'Settings',
            '⚙️ Settings',
            'manage_options',
            'aa-settings',
            array($this, 'render_settings')
        );
        
        // Business Profile (hidden)
        add_submenu_page(
            null,
            'Business Profile',
            'Business Profile',
            'manage_options',
            'aa-business-profile',
            array($this, 'render_business_profile')
        );
    }
    
    public function enqueue_assets($hook) {
        if (strpos($hook, 'atomic-analyzer') === false && strpos($hook, 'aa-') === false) {
            return;
        }
        
        wp_enqueue_style('aa-admin', AA_PLUGIN_URL . 'assets/css/atomic.css', array(), AA_VERSION);
        wp_enqueue_script('aa-admin', AA_PLUGIN_URL . 'assets/js/atomic.js', array('jquery'), AA_VERSION, true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
        
        wp_localize_script('aa-admin', 'atomicData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aa_nonce'),
            'siteUrl' => get_site_url(),
            'businessType' => get_option('aa_business_type', 'unknown'),
            'pmbaFramework' => $this->pmba_framework,
            'hasClaudeKey' => !empty(get_option('aa_claude_api_key')),
            'version' => AA_VERSION
        ));
    }
    
    public function render_dashboard() {
        $business_type = get_option('aa_business_type', 'Not Set');
        $last_analysis = $this->get_last_full_analysis();
        $has_claude = !empty(get_option('aa_claude_api_key'));
        ?>
        <div class="wrap aa-dashboard">
            <div class="aa-header">
                <h1>⚛️ Atomic Analyzer</h1>
                <p class="aa-tagline">by Commerce Copilot | Powered by The Personal MBA Framework</p>
            </div>
            
            <?php if (!$has_claude): ?>
            <div class="notice notice-warning">
                <p><strong>🤖 Claude AI Not Connected</strong> - Add your API key in Settings to unlock AI-powered insights!</p>
            </div>
            <?php endif; ?>
            
            <div class="aa-business-banner">
                <div class="aa-business-info">
                    <span class="aa-business-label">Business Type:</span>
                    <strong><?php echo esc_html($business_type); ?></strong>
                    <button class="button button-small" id="aa-change-business-type">Change</button>
                </div>
                <div class="aa-last-analysis">
                    <?php if ($last_analysis): ?>
                        Last Analysis: <?php echo human_time_diff(strtotime($last_analysis['date'])); ?> ago
                    <?php else: ?>
                        No analysis run yet
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="aa-atomic-score-card">
                <div class="aa-score-header">
                    <h2>Atomic Business Health Score</h2>
                    <p class="description">Based on The Personal MBA's 5 Primary Departments</p>
                </div>
                <div class="aa-score-display">
                    <div class="aa-score-circle">
                        <span class="aa-score-number" id="aa-overall-score">
                            <?php echo $last_analysis ? $last_analysis['overall_score'] : '--'; ?>
                        </span>
                        <span class="aa-score-label">/100</span>
                    </div>
                    <div class="aa-score-status" id="aa-score-status">
                        <?php echo $last_analysis ? $this->get_pmba_alignment_message($last_analysis['pmba_alignment']) : 'Run analysis to see score'; ?>
                    </div>
                </div>
                <button id="aa-run-full-analysis" class="button button-primary button-hero">
                    ⚛️ Run Full Atomic Analysis
                </button>
                
                <?php if ($last_analysis): ?>
                <div class="aa-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=aa-claude-insights'); ?>" class="button">
                        🤖 Get Claude AI Insights
                    </a>
                    <button id="aa-generate-pdf" class="button">
                        📄 Generate PDF Report
                    </button>
                    <button id="aa-generate-processes" class="button">
                        📋 Generate Process Docs
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="aa-departments-grid">
                <?php foreach ($this->pmba_framework as $dept_key => $dept_data): ?>
                    <div class="aa-dept-card" data-department="<?php echo esc_attr($dept_key); ?>">
                        <div class="aa-dept-icon"><?php echo $dept_data['icon']; ?></div>
                        <h3><?php echo esc_html($dept_data['name']); ?></h3>
                        <p class="aa-dept-description"><?php echo esc_html($dept_data['description']); ?></p>
                        <div class="aa-dept-score">
                            <span class="aa-dept-score-number" id="score-<?php echo esc_attr($dept_key); ?>">
                                <?php echo $last_analysis && isset($last_analysis['departments'][$dept_key]) 
                                    ? $last_analysis['departments'][$dept_key]['score'] 
                                    : '--'; ?>
                            </span>
                            <span class="aa-dept-score-label">/100</span>
                        </div>
                        <div class="aa-dept-issues" id="issues-<?php echo esc_attr($dept_key); ?>">
                            <?php 
                            if ($last_analysis && isset($last_analysis['departments'][$dept_key])) {
                                $critical = count(array_filter($last_analysis['departments'][$dept_key]['issues'], function($i) {
                                    return $i['severity'] === 'critical';
                                }));
                                if ($critical > 0) {
                                    echo '<span class="aa-critical-badge">' . $critical . ' Critical</span>';
                                } else {
                                    echo '<span class="aa-healthy-badge">✔ Healthy</span>';
                                }
                            }
                            ?>
                        </div>
                        <a href="<?php echo admin_url('admin.php?page=aa-' . $dept_key); ?>" class="button">
                            Analyze in Detail →
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    public function render_department_page() {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $dept_key = str_replace('aa-', '', $page);
        
        if (!isset($this->pmba_framework[$dept_key])) {
            wp_die('Invalid department');
        }
        
        $dept = $this->pmba_framework[$dept_key];
        $analysis = $this->get_department_analysis($dept_key);
        ?>
        <div class="wrap aa-department-page">
            <div class="aa-dept-header">
                <div class="aa-dept-icon-large"><?php echo $dept['icon']; ?></div>
                <div>
                    <h1><?php echo esc_html($dept['name']); ?> Analysis</h1>
                    <p class="aa-dept-description-full"><?php echo esc_html($dept['description']); ?></p>
                </div>
            </div>
            
            <div class="aa-dept-score-banner">
                <div class="aa-dept-score-large">
                    <span id="dept-score-display"><?php echo $analysis ? $analysis['score'] : '--'; ?></span>/100
                </div>
                <button class="button button-primary" id="aa-analyze-department" data-department="<?php echo esc_attr($dept_key); ?>">
                    🔬 Analyze <?php echo esc_html($dept['name']); ?>
                </button>
            </div>
            
            <div class="aa-dept-principles">
                <h2>📖 Key Personal MBA Principles</h2>
                <div class="aa-principles-list">
                    <?php foreach ($dept['key_principles'] as $principle => $question): ?>
                        <div class="aa-principle-item">
                            <h4><?php echo esc_html($principle); ?></h4>
                            <p><?php echo esc_html($question); ?></p>
                            <?php if ($analysis && isset($analysis['principle_scores'][$principle])): ?>
                                <div class="aa-principle-score">
                                    Score: <strong><?php echo $analysis['principle_scores'][$principle]; ?>/100</strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (isset($dept['economic_values']) && !empty($dept['economic_values'])): ?>
            <div class="aa-economic-values">
                <h2>💎 The 9 Economic Values</h2>
                <div class="aa-values-grid">
                    <?php foreach ($dept['economic_values'] as $value => $description): ?>
                        <div class="aa-value-item">
                            <h4><?php echo esc_html($value); ?></h4>
                            <p><?php echo esc_html($description); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="aa-dept-analysis-results" id="aa-dept-results">
                <?php if ($analysis): ?>
                    <?php $this->render_department_results($analysis, $dept_key); ?>
                <?php else: ?>
                    <div class="aa-empty-state">
                        <h3>No Analysis Yet</h3>
                        <p>Click "Analyze <?php echo esc_html($dept['name']); ?>" to get detailed insights.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function render_claude_insights() {
        include AA_PLUGIN_DIR . 'templates/claude-insights.php';
    }
    
    public function render_pdf_reports() {
        include AA_PLUGIN_DIR . 'templates/pdf-reports.php';
    }
    
    public function render_process_docs() {
        include AA_PLUGIN_DIR . 'templates/process-docs.php';
    }
    
    public function render_webhooks() {
        include AA_PLUGIN_DIR . 'templates/webhooks.php';
    }
    
    public function render_settings() {
        include AA_PLUGIN_DIR . 'templates/settings.php';
    }
    
    public function render_business_profile() {
        ?>
        <div class="wrap aa-business-profile">
            <h1>Business Profile Setup</h1>
            
            <div class="aa-profile-section">
                <h2>What type of business do you run?</h2>
                
                <div class="aa-business-types">
                    <?php
                    $business_types = array(
                        'ecommerce' => 'E-commerce Store',
                        'service' => 'Service Business',
                        'saas' => 'Software as a Service (SaaS)',
                        'content' => 'Content/Media Site',
                        'agency' => 'Agency/Consultancy',
                        'membership' => 'Membership Site',
                        'marketplace' => 'Marketplace/Platform',
                        'local' => 'Local Business',
                        'affiliate' => 'Affiliate Business',
                        'education' => 'Education/Training',
                        'nonprofit' => 'Non-Profit Organization',
                        'other' => 'Other'
                    );
                    
                    foreach ($business_types as $key => $label): ?>
                        <label class="aa-business-type-option">
                            <input type="radio" name="business_type" value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <button id="aa-save-business-type" class="button button-primary">
                    Save Business Type
                </button>
            </div>
        </div>
        <?php
    }
    
    // AJAX Handlers
    
    public function ajax_run_atomic_analysis() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $business_type = get_option('aa_business_type', 'unknown');
        $analysis_results = $this->perform_full_atomic_analysis($business_type);
        
        // Store results
        global $wpdb;
        $table_name = $wpdb->prefix . 'atomic_analysis';
        
        $wpdb->insert(
            $table_name,
            array(
                'analysis_date' => current_time('mysql'),
                'business_type' => $business_type,
                'department' => 'full',
                'analysis_data' => json_encode($analysis_results),
                'atomic_score' => $analysis_results['overall_score'],
                'pmba_alignment' => $analysis_results['pmba_alignment']
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        // Trigger webhooks
        $this->webhook_manager->trigger('analysis_complete', $analysis_results);
        
        wp_send_json_success($analysis_results);
    }
    
    public function ajax_get_claude_insights() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $last_analysis = $this->get_last_full_analysis();
        
        if (!$last_analysis) {
            wp_send_json_error('Run a full analysis first');
        }
        
        $insights = $this->claude_ai->generate_insights($last_analysis);
        
        if (is_wp_error($insights)) {
            wp_send_json_error($insights->get_error_message());
        }
        
        wp_send_json_success($insights);
    }
    
    public function ajax_generate_pdf_report() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $last_analysis = $this->get_last_full_analysis();
        
        if (!$last_analysis) {
            wp_send_json_error('No analysis data available');
        }
        
        $options = isset($_POST['options']) ? $_POST['options'] : array();
        $pdf_url = $this->pdf_generator->generate_report($last_analysis, $options);
        
        if (is_wp_error($pdf_url)) {
            wp_send_json_error($pdf_url->get_error_message());
        }
        
        // Trigger webhook
        $this->webhook_manager->trigger('pdf_generated', array(
            'pdf_url' => $pdf_url,
            'analysis_summary' => array(
                'overall_score' => $last_analysis['overall_score'],
                'pmba_alignment' => $last_analysis['pmba_alignment']
            )
        ));
        
        wp_send_json_success(array('pdf_url' => $pdf_url));
    }
    
    public function ajax_generate_process_docs() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $last_analysis = $this->get_last_full_analysis();
        
        if (!$last_analysis) {
            wp_send_json_error('No analysis data available');
        }
        
        $docs = $this->process_builder->generate_from_analysis($last_analysis);
        
        if (is_wp_error($docs)) {
            wp_send_json_error($docs->get_error_message());
        }
        
        // Trigger webhook
        $this->webhook_manager->trigger('process_docs_created', array(
            'departments' => array_keys($docs)
        ));
        
        wp_send_json_success($docs);
    }
    
    public function ajax_test_webhook() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $webhook_id = intval($_POST['webhook_id']);
        $result = $this->webhook_manager->test_webhook($webhook_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_save_webhook() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $webhook_data = array(
            'name' => sanitize_text_field($_POST['webhook_name']),
            'url' => esc_url_raw($_POST['webhook_url']),
            'trigger' => sanitize_text_field($_POST['trigger_event']),
            'method' => sanitize_text_field($_POST['webhook_method']),
            'headers' => isset($_POST['custom_headers']) ? json_encode($_POST['custom_headers']) : null,
            'is_active' => isset($_POST['is_active']) ? intval($_POST['is_active']) : 1
        );
        
        if (isset($_POST['id'])) {
            $webhook_data['id'] = intval($_POST['id']);
        }
        
        $result = $this->webhook_manager->save_webhook($webhook_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_delete_webhook() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $webhook_id = intval($_POST['webhook_id']);
        $this->webhook_manager->delete_webhook($webhook_id);
        
        wp_send_json_success();
    }
    
    public function ajax_toggle_webhook() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $webhook_id = intval($_POST['webhook_id']);
        $result = $this->webhook_manager->toggle_active($webhook_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_save_settings() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (isset($_POST['claude_api_key'])) {
            update_option('aa_claude_api_key', sanitize_text_field($_POST['claude_api_key']));
        }
        
        if (isset($_POST['business_type'])) {
            update_option('aa_business_type', sanitize_text_field($_POST['business_type']));
        }
        
        if (isset($_POST['company_name'])) {
            update_option('aa_company_name', sanitize_text_field($_POST['company_name']));
        }
        
        update_option('aa_auto_analysis', isset($_POST['auto_analysis']) ? 1 : 0);
        update_option('aa_email_on_critical', isset($_POST['email_on_critical']) ? 1 : 0);
        update_option('aa_email_on_score_change', isset($_POST['email_on_score_change']) ? 1 : 0);
        
        wp_send_json_success(array('message' => 'Settings saved successfully!'));
    }
    
    public function ajax_detect_business_type() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $detected_type = $this->detect_business_type();
        update_option('aa_business_type', $detected_type);
        
        wp_send_json_success(array('business_type' => $detected_type));
    }
    
    public function ajax_save_business_type() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $business_type = sanitize_text_field($_POST['business_type']);
        update_option('aa_business_type', $business_type);
        
        wp_send_json_success();
    }
    
    public function ajax_analyze_department() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $department = sanitize_text_field($_POST['department']);
        $business_type = get_option('aa_business_type', 'unknown');
        
        $analysis = $this->analyze_department($department, $business_type);
        
        // Store results
        global $wpdb;
        $table_name = $wpdb->prefix . 'atomic_analysis';
        
        $wpdb->insert(
            $table_name,
            array(
                'analysis_date' => current_time('mysql'),
                'business_type' => $business_type,
                'department' => $department,
                'analysis_data' => json_encode($analysis),
                'atomic_score' => $analysis['score'],
                'pmba_alignment' => 0 // Department-specific, no overall alignment
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        wp_send_json_success($analysis);
    }
    
    public function ajax_test_claude_connection() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $result = $this->claude_ai->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_get_webhook_docs() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $docs = $this->webhook_manager->generate_documentation();
        wp_send_json_success($docs);
    }
    
    public function ajax_export_docs() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $zip_url = $this->process_builder->export_all_as_zip();
        
        if (is_wp_error($zip_url)) {
            wp_send_json_error($zip_url->get_error_message());
        }
        
        header('Location: ' . $zip_url);
        exit;
    }
    
    public function ajax_get_doc() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $doc_id = intval($_POST['doc_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'atomic_processes';
        
        $doc = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $doc_id
        ));
        
        if (!$doc) {
            wp_send_json_error('Document not found');
        }
        
        wp_send_json_success(array(
            'content' => $doc->process_content
        ));
    }
    
    public function ajax_export_settings() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = array(
            'aa_business_type' => get_option('aa_business_type'),
            'aa_company_name' => get_option('aa_company_name'),
            'aa_auto_analysis' => get_option('aa_auto_analysis'),
            'aa_email_on_critical' => get_option('aa_email_on_critical'),
            'aa_email_on_score_change' => get_option('aa_email_on_score_change'),
            'aa_claude_api_key' => get_option('aa_claude_api_key'),
            'export_date' => current_time('mysql'),
            'export_version' => AA_VERSION
        );
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="atomic-analyzer-settings-' . date('Y-m-d') . '.json"');
        echo json_encode($settings, JSON_PRETTY_PRINT);
        exit;
    }
    
    public function ajax_import_settings() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = json_decode(stripslashes($_POST['settings']), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON file');
        }
        
        // Import settings
        if (isset($settings['aa_business_type'])) {
            update_option('aa_business_type', sanitize_text_field($settings['aa_business_type']));
        }
        
        if (isset($settings['aa_company_name'])) {
            update_option('aa_company_name', sanitize_text_field($settings['aa_company_name']));
        }
        
        if (isset($settings['aa_auto_analysis'])) {
            update_option('aa_auto_analysis', intval($settings['aa_auto_analysis']));
        }
        
        if (isset($settings['aa_email_on_critical'])) {
            update_option('aa_email_on_critical', intval($settings['aa_email_on_critical']));
        }
        
        if (isset($settings['aa_email_on_score_change'])) {
            update_option('aa_email_on_score_change', intval($settings['aa_email_on_score_change']));
        }
        
        if (isset($settings['aa_claude_api_key'])) {
            update_option('aa_claude_api_key', sanitize_text_field($settings['aa_claude_api_key']));
        }
        
        wp_send_json_success(array('message' => 'Settings imported successfully'));
    }
    
    public function ajax_regenerate_webhook_secret() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $new_secret = wp_generate_password(32, false);
        update_option('aa_webhook_secret', $new_secret);
        
        wp_send_json_success(array('secret' => $new_secret));
    }
    
    public function ajax_clear_all_data() {
        check_ajax_referer('aa_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Clear analysis data
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}atomic_analysis");
        
        // Clear process docs
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}atomic_processes");
        
        // Clear webhooks (optional - uncomment if desired)
        // $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}atomic_webhooks");
        
        wp_send_json_success(array('message' => 'All data cleared successfully'));
    }
    
    // Core analysis methods
    
    private function perform_full_atomic_analysis($business_type) {
        $results = array(
            'timestamp' => current_time('mysql'),
            'business_type' => $business_type,
            'departments' => array()
        );
        
        foreach ($this->pmba_framework as $dept_key => $dept_data) {
            $results['departments'][$dept_key] = $this->analyze_department($dept_key, $business_type);
        }
        
        $total = 0;
        $count = 0;
        foreach ($results['departments'] as $dept) {
            $total += $dept['score'];
            $count++;
        }
        $results['overall_score'] = $count > 0 ? round($total / $count) : 0;
        $results['pmba_alignment'] = $this->calculate_pmba_alignment($results['departments']);
        $results['top_recommendations'] = $this->generate_top_recommendations($results['departments']);
        
        return $results;
    }
    
    private function analyze_department($dept_key, $business_type) {
        $analysis = array(
            'score' => 0,
            'issues' => array(),
            'opportunities' => array(),
            'principle_scores' => array()
        );
        
        switch ($dept_key) {
            case 'development':
                $analysis = $this->analyze_development($business_type);
                break;
            case 'marketing':
                $analysis = $this->analyze_marketing($business_type);
                break;
            case 'sales':
                $analysis = $this->analyze_sales($business_type);
                break;
            case 'delivery':
                $analysis = $this->analyze_delivery($business_type);
                break;
            case 'accounting':
                $analysis = $this->analyze_accounting($business_type);
                break;
        }
        
        return $analysis;
    }
    
    private function analyze_development($business_type) {
        $score = 100;
        $issues = array();
        $principle_scores = array();
        
        // Check Value Proposition
        $has_clear_value = $this->check_value_proposition();
        $principle_scores['Value Creation'] = $has_clear_value ? 85 : 45;
        
        if (!$has_clear_value) {
            $issues[] = array(
                'severity' => 'critical',
                'principle' => 'Value Creation',
                'title' => 'Unclear Value Proposition',
                'description' => 'Your website doesn\'t clearly communicate what value you create for customers.',
                'pmba_guidance' => 'Every business must create value by moving prospects from their current state to their desired state. Without clear value communication, prospects can\'t understand why they should buy.',
                'action' => 'Add a clear headline on your homepage explaining the transformation or outcome customers get from your product/service.'
            );
            $score -= 30;
        }
        
        // Check Economic Values Coverage
        $economic_values_count = $this->count_economic_values();
        $principle_scores['Economic Values'] = min($economic_values_count * 11, 100);
        
        if ($economic_values_count < 3) {
            $issues[] = array(
                'severity' => 'high',
                'principle' => 'Economic Values',
                'title' => 'Limited Economic Values',
                'description' => 'Your offer provides fewer than 3 of the 9 possible economic values.',
                'pmba_guidance' => 'The more economic values you provide (Efficacy, Speed, Reliability, Ease of Use, Flexibility, Status, Aesthetic Appeal, Emotion, Cost), the more valuable your offer becomes.',
                'action' => 'Analyze which economic values you currently provide and add at least 2 more to strengthen your offer.'
            );
            $score -= 20;
        }
        
        // Check Iteration/Update Frequency
        $last_update = $this->get_last_content_update();
        $days_since_update = round((time() - strtotime($last_update)) / 86400);
        $principle_scores['Iteration Velocity'] = max(0, 100 - ($days_since_update * 2));
        
        if ($days_since_update > 30) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Iteration Velocity',
                'title' => 'Slow Iteration Cycle',
                'description' => 'No updates in ' . $days_since_update . ' days indicates slow improvement velocity.',
                'pmba_guidance' => 'Fast iteration cycles lead to rapid improvement. The quicker you can test and improve, the faster you\'ll find product-market fit.',
                'action' => 'Establish weekly improvement sprints. Update something meaningful every week.'
            );
            $score -= 15;
        }
        
        // Check for Testing/Prototype Evidence
        $has_testing = $this->check_for_testing_evidence();
        $principle_scores['Prototype'] = $has_testing ? 75 : 25;
        
        if (!$has_testing) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Prototype',
                'title' => 'No Testing Evidence',
                'description' => 'No evidence of A/B testing, beta features, or prototyping.',
                'pmba_guidance' => 'Testing prototypes before full investment reduces risk and accelerates learning.',
                'action' => 'Implement simple A/B testing on key pages. Start with headline variations.'
            );
            $score -= 10;
        }
        
        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'opportunities' => $this->get_development_opportunities($business_type),
            'principle_scores' => $principle_scores
        );
    }
    
    private function analyze_marketing($business_type) {
        $score = 100;
        $issues = array();
        $principle_scores = array();
        
        // Check SEO Setup
        $seo_score = $this->check_seo_setup();
        $principle_scores['Attention'] = $seo_score;
        
        if ($seo_score < 70) {
            $issues[] = array(
                'severity' => 'high',
                'principle' => 'Attention',
                'title' => 'Poor SEO Configuration',
                'description' => 'Missing critical SEO elements that help capture attention from search engines.',
                'pmba_guidance' => 'You can\'t sell if you can\'t capture attention. SEO is fundamental for attracting prospects.',
                'action' => 'Install Yoast SEO or RankMath. Optimize title tags, meta descriptions, and create XML sitemap.'
            );
            $score -= 25;
        }
        
        // Check Content Marketing
        $content_frequency = $this->check_content_frequency();
        $principle_scores['Remarkability'] = min($content_frequency * 25, 100);
        
        if ($content_frequency < 2) {
            $issues[] = array(
                'severity' => 'high',
                'principle' => 'Remarkability',
                'title' => 'Insufficient Content Creation',
                'description' => 'Publishing less than 2 pieces of content per month limits remarkability.',
                'pmba_guidance' => 'Remarkable content gets shared and talked about. It\'s your best marketing investment.',
                'action' => 'Commit to publishing valuable content weekly. Focus on solving customer problems.'
            );
            $score -= 20;
        }
        
        // Check Email List Building
        $has_email_capture = $this->check_email_capture();
        $principle_scores['Permission Asset'] = $has_email_capture ? 80 : 20;
        
        if (!$has_email_capture) {
            $issues[] = array(
                'severity' => 'critical',
                'principle' => 'Permission Asset',
                'title' => 'No Email List Building',
                'description' => 'No visible email capture forms or lead magnets found.',
                'pmba_guidance' => 'Your email list is your most valuable marketing asset - people who gave permission to contact them.',
                'action' => 'Add email capture forms and create a valuable lead magnet to incentivize signups.'
            );
            $score -= 30;
        }
        
        // Check Social Proof
        $social_proof_score = $this->check_social_proof();
        $principle_scores['Social Proof'] = $social_proof_score;
        
        if ($social_proof_score < 50) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Social Proof',
                'title' => 'Weak Social Proof',
                'description' => 'Limited testimonials, reviews, or trust signals visible.',
                'pmba_guidance' => 'People look to others for validation. Social proof reduces perceived risk.',
                'action' => 'Display customer testimonials prominently. Add review badges and case studies.'
            );
            $score -= 15;
        }
        
        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'opportunities' => $this->get_marketing_opportunities($business_type),
            'principle_scores' => $principle_scores
        );
    }
    
    private function analyze_sales($business_type) {
        $score = 100;
        $issues = array();
        $principle_scores = array();
        
        // Check Trust Signals
        $trust_score = $this->check_trust_signals();
        $principle_scores['Trust'] = $trust_score;
        
        if ($trust_score < 70) {
            $issues[] = array(
                'severity' => 'critical',
                'principle' => 'Trust',
                'title' => 'Insufficient Trust Signals',
                'description' => 'Missing key trust elements like SSL, contact info, or about page.',
                'pmba_guidance' => 'Trust is the foundation of all sales. Without trust, nothing else matters.',
                'action' => 'Ensure SSL certificate, add clear contact information, create detailed about page.'
            );
            $score -= 30;
        }
        
        // Check Pricing Clarity
        $has_clear_pricing = $this->check_pricing_clarity();
        $principle_scores['Pricing Uncertainty'] = $has_clear_pricing ? 90 : 30;
        
        if (!$has_clear_pricing) {
            $issues[] = array(
                'severity' => 'high',
                'principle' => 'Pricing Uncertainty',
                'title' => 'Unclear Pricing',
                'description' => 'Pricing is hidden or requires contact for quotes.',
                'pmba_guidance' => 'Pricing uncertainty creates friction. Clear pricing builds trust and qualifies prospects.',
                'action' => 'Display pricing clearly. If complex, show starting prices or ranges.'
            );
            $score -= 20;
        }
        
        // Check Call to Action
        $cta_score = $this->check_cta_effectiveness();
        $principle_scores['Call to Action'] = $cta_score;
        
        if ($cta_score < 60) {
            $issues[] = array(
                'severity' => 'high',
                'principle' => 'Call to Action',
                'title' => 'Weak Calls to Action',
                'description' => 'CTAs are unclear, hidden, or not compelling.',
                'pmba_guidance' => 'Every page needs a clear next step. Make it obvious what prospects should do.',
                'action' => 'Add clear, action-oriented CTAs above the fold. Use contrasting colors.'
            );
            $score -= 20;
        }
        
        // Check Risk Reversal
        $has_guarantee = $this->check_risk_reversal();
        $principle_scores['Risk Reversal'] = $has_guarantee ? 85 : 25;
        
        if (!$has_guarantee) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Risk Reversal',
                'title' => 'No Risk Reversal',
                'description' => 'No visible guarantee, warranty, or risk reversal offer.',
                'pmba_guidance' => 'Risk reversal shifts the risk from buyer to seller, making purchase decisions easier.',
                'action' => 'Add a satisfaction guarantee or warranty. Make it prominent near CTAs.'
            );
            $score -= 15;
        }
        
        // Check Purchase Barriers
        $barriers_score = $this->check_purchase_barriers();
        $principle_scores['Barriers to Purchase'] = $barriers_score;
        
        if ($barriers_score < 70) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Barriers to Purchase',
                'title' => 'High Purchase Friction',
                'description' => 'Too many steps, fields, or requirements in purchase process.',
                'pmba_guidance' => 'Every additional step or field reduces conversion. Minimize friction.',
                'action' => 'Streamline checkout. Remove unnecessary fields. Add express checkout options.'
            );
            $score -= 15;
        }
        
        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'opportunities' => $this->get_sales_opportunities($business_type),
            'principle_scores' => $principle_scores
        );
    }
    
    private function analyze_delivery($business_type) {
        $score = 100;
        $issues = array();
        $principle_scores = array();
        
        // Check Systems Documentation
        $has_systems = $this->check_systems_documentation();
        $principle_scores['Systems'] = $has_systems ? 75 : 25;
        
        if (!$has_systems) {
            $issues[] = array(
                'severity' => 'high',
                'principle' => 'Systems',
                'title' => 'No Documented Systems',
                'description' => 'No evidence of documented processes or systems.',
                'pmba_guidance' => 'Systems create consistency and scalability. Without them, quality varies.',
                'action' => 'Document your top 3 customer-facing processes. Create simple checklists.'
            );
            $score -= 25;
        }
        
        // Check Customer Communication
        $comm_score = $this->check_customer_communication();
        $principle_scores['Expectation Effect'] = $comm_score;
        
        if ($comm_score < 70) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Expectation Effect',
                'title' => 'Poor Customer Communication',
                'description' => 'Limited automated confirmations or status updates.',
                'pmba_guidance' => 'Customer satisfaction depends on meeting or exceeding expectations through clear communication.',
                'action' => 'Set up automated order confirmations, shipping notifications, and follow-ups.'
            );
            $score -= 15;
        }
        
        // Check Scalability Indicators
        $scalability_score = $this->check_scalability();
        $principle_scores['Scalability'] = $scalability_score;
        
        if ($scalability_score < 60) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Scalability',
                'title' => 'Limited Scalability',
                'description' => 'Current setup appears difficult to scale without major changes.',
                'pmba_guidance' => 'Scalable businesses can grow revenue without proportionally growing costs.',
                'action' => 'Identify manual processes that could be automated. Implement one automation this week.'
            );
            $score -= 20;
        }
        
        // Check Value Stream
        $value_stream_score = $this->check_value_stream();
        $principle_scores['Value Stream'] = $value_stream_score;
        
        if ($value_stream_score < 70) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Value Stream',
                'title' => 'Unclear Value Delivery',
                'description' => 'The process of how value is delivered to customers is unclear.',
                'pmba_guidance' => 'Understanding your value stream helps optimize delivery and find bottlenecks.',
                'action' => 'Map out your complete customer journey from purchase to value receipt.'
            );
            $score -= 15;
        }
        
        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'opportunities' => $this->get_delivery_opportunities($business_type),
            'principle_scores' => $principle_scores
        );
    }
    
    private function analyze_accounting($business_type) {
        $score = 100;
        $issues = array();
        $principle_scores = array();
        
        // Check Payment Options
        $payment_score = $this->check_payment_infrastructure();
        $principle_scores['Value Capture'] = $payment_score;
        
        if ($payment_score < 70) {
            $issues[] = array(
                'severity' => 'high',
                'principle' => 'Value Capture',
                'title' => 'Limited Payment Options',
                'description' => 'Fewer than 3 payment methods available.',
                'pmba_guidance' => 'You can only capture value if customers can easily pay you. More options = more sales.',
                'action' => 'Add multiple payment methods. Consider PayPal, Stripe, and buy-now-pay-later options.'
            );
            $score -= 20;
        }
        
        // Check Revenue Diversity
        $revenue_streams = $this->count_revenue_streams();
        $principle_scores['Sufficiency'] = min($revenue_streams * 33, 100);
        
        if ($revenue_streams < 2) {
            $issues[] = array(
                'severity' => 'critical',
                'principle' => 'Sufficiency',
                'title' => 'Single Revenue Stream',
                'description' => 'Relying on only one source of revenue is risky.',
                'pmba_guidance' => 'Multiple revenue streams provide stability and growth opportunities.',
                'action' => 'Identify 2-3 additional revenue streams you could add (upsells, subscriptions, services).'
            );
            $score -= 30;
        }
        
        // Check Pricing Strategy
        $pricing_optimization = $this->check_pricing_optimization();
        $principle_scores['Profit Margin'] = $pricing_optimization;
        
        if ($pricing_optimization < 60) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Profit Margin',
                'title' => 'Unoptimized Pricing',
                'description' => 'No evidence of pricing tiers or value-based pricing.',
                'pmba_guidance' => 'Profit margin determines sustainability. Price based on value, not just cost.',
                'action' => 'Create 3 pricing tiers. Anchor with premium option to make standard seem reasonable.'
            );
            $score -= 15;
        }
        
        // Check Financial Leverage
        $leverage_score = $this->check_financial_leverage();
        $principle_scores['Leverage'] = $leverage_score;
        
        if ($leverage_score < 50) {
            $issues[] = array(
                'severity' => 'medium',
                'principle' => 'Leverage',
                'title' => 'Low Financial Leverage',
                'description' => 'Not maximizing results from existing assets or efforts.',
                'pmba_guidance' => 'Leverage multiplies results without proportional effort increase.',
                'action' => 'Identify your highest-margin products/services and focus marketing there.'
            );
            $score -= 15;
        }
        
        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'opportunities' => $this->get_accounting_opportunities($business_type),
            'principle_scores' => $principle_scores
        );
    }
    
    // Helper Analysis Methods
    
    private function check_value_proposition() {
        // Check homepage for clear value proposition
        $homepage_content = $this->get_homepage_content();
        
        // Look for value indicators
        $value_keywords = array('transform', 'achieve', 'solve', 'improve', 'help', 'enable', 'empower');
        $has_value_keywords = false;
        
        foreach ($value_keywords as $keyword) {
            if (stripos($homepage_content, $keyword) !== false) {
                $has_value_keywords = true;
                break;
            }
        }
        
        // Check for clear headline
        $has_clear_headline = preg_match('/<h1[^>]*>(.+?)<\/h1>/i', $homepage_content, $matches);
        
        return $has_value_keywords && $has_clear_headline;
    }
    
    private function count_economic_values() {
        $count = 0;
        $content = $this->get_homepage_content();
        
        // Check for each economic value
        $value_indicators = array(
            'efficacy' => array('effective', 'works', 'proven', 'results'),
            'speed' => array('fast', 'quick', 'instant', 'rapid', 'immediate'),
            'reliability' => array('reliable', 'dependable', 'consistent', 'stable'),
            'ease' => array('easy', 'simple', 'intuitive', 'user-friendly'),
            'flexibility' => array('flexible', 'versatile', 'adaptable', 'customizable'),
            'status' => array('premium', 'exclusive', 'luxury', 'prestige'),
            'aesthetic' => array('beautiful', 'elegant', 'design', 'stunning'),
            'emotion' => array('love', 'enjoy', 'delight', 'happy', 'satisfaction'),
            'cost' => array('affordable', 'value', 'save', 'discount', 'price')
        );
        
        foreach ($value_indicators as $value => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $count++;
                    break;
                }
            }
        }
        
        return $count;
    }
    
    private function get_last_content_update() {
        global $wpdb;
        
        $last_post = $wpdb->get_var("
            SELECT MAX(post_modified) 
            FROM $wpdb->posts 
            WHERE post_status = 'publish' 
            AND post_type IN ('post', 'page')
        ");
        
        return $last_post ?: current_time('mysql');
    }
    
    private function check_for_testing_evidence() {
        // Check for A/B testing plugins or evidence
        $testing_plugins = array(
            'google-analytics-for-wordpress/googleanalytics.php',
            'google-analytics-dashboard-for-wp/gadwp.php',
            'nelio-ab-testing/nelio-ab-testing.php',
            'split-test-for-elementor/split-test-for-elementor.php'
        );
        
        foreach ($testing_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function check_seo_setup() {
        $score = 0;
        
        // Check for SEO plugin
        $seo_plugins = array(
            'wordpress-seo/wp-seo.php',
            'all-in-one-seo-pack/all_in_one_seo_pack.php',
            'seo-by-rank-math/rank-math.php'
        );
        
        foreach ($seo_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $score += 40;
                break;
            }
        }
        
        // Check for XML sitemap
        if (file_exists(ABSPATH . 'sitemap.xml') || file_exists(ABSPATH . 'sitemap_index.xml')) {
            $score += 20;
        }
        
        // Check robots.txt
        if (file_exists(ABSPATH . 'robots.txt')) {
            $score += 10;
        }
        
        // Check for meta descriptions
        $homepage_content = $this->get_homepage_content();
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]*>/i', $homepage_content)) {
            $score += 15;
        }
        
        // Check for proper heading structure
        if (preg_match_all('/<h[1-6][^>]*>/i', $homepage_content) >= 3) {
            $score += 15;
        }
        
        return min($score, 100);
    }
    
    private function check_content_frequency() {
        global $wpdb;
        
        // Count posts in last 30 days
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $wpdb->posts 
            WHERE post_status = 'publish' 
            AND post_type = 'post'
            AND post_date > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return intval($count);
    }
    
    private function check_email_capture() {
        $content = $this->get_homepage_content();
        
        // Look for email capture indicators
        $email_indicators = array(
            'type="email"',
            'type=\'email\'',
            'newsletter',
            'subscribe',
            'email list',
            'get updates'
        );
        
        foreach ($email_indicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return true;
            }
        }
        
        // Check for email marketing plugins
        $email_plugins = array(
            'mailchimp-for-wp/mailchimp-for-wp.php',
            'newsletter/plugin.php',
            'mailoptin/mailoptin.php'
        );
        
        foreach ($email_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function check_social_proof() {
        $score = 0;
        $content = $this->get_homepage_content();
        
        // Check for testimonials
        if (preg_match('/testimonial|review|feedback|what our customers say/i', $content)) {
            $score += 30;
        }
        
        // Check for trust badges
        $trust_indicators = array('guarantee', 'certified', 'accredited', 'award', 'trusted by');
        foreach ($trust_indicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                $score += 15;
                break;
            }
        }
        
        // Check for client logos
        if (preg_match('/clients|featured in|as seen on|trusted by/i', $content)) {
            $score += 20;
        }
        
        // Check for case studies
        if (preg_match('/case study|success story|results/i', $content)) {
            $score += 20;
        }
        
        // Check for review count
        if (preg_match('/\d+\s*(reviews|ratings|customers|clients)/i', $content)) {
            $score += 15;
        }
        
        return min($score, 100);
    }
    
    private function check_trust_signals() {
        $score = 0;
        
        // Check SSL
        if (is_ssl()) {
            $score += 30;
        }
        
        // Check for contact page
        $contact_page = get_page_by_path('contact');
        if ($contact_page) {
            $score += 20;
        }
        
        // Check for about page
        $about_page = get_page_by_path('about');
        if ($about_page) {
            $score += 20;
        }
        
        // Check for privacy policy
        $privacy_page = get_privacy_policy_url();
        if ($privacy_page) {
            $score += 15;
        }
        
        // Check for phone number
        $content = $this->get_homepage_content();
        if (preg_match('/\d{3}[-.]?\d{3}[-.]?\d{4}/', $content)) {
            $score += 15;
        }
        
        return min($score, 100);
    }
    
    private function check_pricing_clarity() {
        // Check for pricing page
        $pricing_page = get_page_by_path('pricing');
        if ($pricing_page) {
            return true;
        }
        
        // Check homepage for pricing info
        $content = $this->get_homepage_content();
        return preg_match('/\$\d+|price|pricing|cost/i', $content);
    }
    
    private function check_cta_effectiveness() {
        $score = 0;
        $content = $this->get_homepage_content();
        
        // Check for CTA buttons
        if (preg_match_all('/<button|<a[^>]+class=["\'][^"\']*button/i', $content, $matches)) {
            $cta_count = count($matches[0]);
            $score += min($cta_count * 20, 40);
        }
        
        // Check for action words
        $action_words = array('get started', 'try free', 'sign up', 'buy now', 'learn more', 'download', 'book now');
        $action_count = 0;
        foreach ($action_words as $word) {
            if (stripos($content, $word) !== false) {
                $action_count++;
            }
        }
        $score += min($action_count * 10, 30);
        
        // Check CTA positioning (above fold indication)
        if (strpos($content, '<button') !== false && strpos($content, '<button') < 2000) {
            $score += 30;
        }
        
        return min($score, 100);
    }
    
    private function check_risk_reversal() {
        $content = $this->get_homepage_content();
        
        $guarantee_terms = array(
            'guarantee',
            'warranty',
            'money back',
            'risk free',
            'no risk',
            'satisfaction guaranteed',
            'refund'
        );
        
        foreach ($guarantee_terms as $term) {
            if (stripos($content, $term) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function check_purchase_barriers() {
        $score = 100;
        
        // Check if WooCommerce is active
        if (class_exists('WooCommerce')) {
            // Check checkout field count
            $checkout_fields = WC()->checkout()->get_checkout_fields();
            $total_fields = 0;
            foreach ($checkout_fields as $fieldset) {
                $total_fields += count($fieldset);
            }
            
            // Penalize for too many fields (ideal is under 10)
            if ($total_fields > 15) {
                $score -= 30;
            } elseif ($total_fields > 10) {
                $score -= 15;
            }
            
            // Check for guest checkout
            if (get_option('woocommerce_enable_guest_checkout') !== 'yes') {
                $score -= 20;
            }
            
            // Check payment methods
            $payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            if (count($payment_gateways) < 2) {
                $score -= 15;
            }
        }
        
        return max($score, 0);
    }
    
    private function check_systems_documentation() {
        // Check for documentation plugins or pages
        $doc_pages = array('documentation', 'docs', 'help', 'support', 'faq');
        
        foreach ($doc_pages as $slug) {
            if (get_page_by_path($slug)) {
                return true;
            }
        }
        
        // Check for knowledge base plugins
        $kb_plugins = array(
            'echo-knowledge-base/echo-knowledge-base.php',
            'wedocs/wedocs.php',
            'documentor-lite/documentor-lite.php'
        );
        
        foreach ($kb_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function check_customer_communication() {
        $score = 0;
        
        // Check for WooCommerce email settings
        if (class_exists('WooCommerce')) {
            // Check if order confirmation emails are enabled
            $email_settings = get_option('woocommerce_new_order_settings');
            if ($email_settings && $email_settings['enabled'] === 'yes') {
                $score += 40;
            }
        }
        
        // Check for contact form
        $cf_plugins = array(
            'contact-form-7/wp-contact-form-7.php',
            'wpforms-lite/wpforms.php',
            'ninja-forms/ninja-forms.php'
        );
        
        foreach ($cf_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $score += 30;
                break;
            }
        }
        
        // Check for live chat
        $chat_plugins = array(
            'tawk-to/tawkto.php',
            'wp-live-chat-support/wp-live-chat-support.php'
        );
        
        foreach ($chat_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $score += 30;
                break;
            }
        }
        
        return min($score, 100);
    }
    
    private function check_scalability() {
        $score = 0;
        
        // Check for caching
        $cache_plugins = array(
            'wp-rocket/wp-rocket.php',
            'w3-total-cache/w3-total-cache.php',
            'wp-super-cache/wp-cache.php',
            'litespeed-cache/litespeed-cache.php'
        );
        
        foreach ($cache_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $score += 30;
                break;
            }
        }
        
        // Check for CDN
        if (defined('CLOUDFLARE_PLUGIN_DIR') || get_option('cdn_enabled')) {
            $score += 25;
        }
        
        // Check for automation plugins
        $automation_plugins = array(
            'automatewoo/automatewoo.php',
            'uncanny-automator/uncanny-automator.php'
        );
        
        foreach ($automation_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $score += 25;
                break;
            }
        }
        
        // Check database optimization
        global $wpdb;
        $autoload_size = $wpdb->get_var("
            SELECT SUM(LENGTH(option_value)) 
            FROM $wpdb->options 
            WHERE autoload = 'yes'
        ");
        
        // Good if under 1MB
        if ($autoload_size < 1000000) {
            $score += 20;
        }
        
        return min($score, 100);
    }
    
    private function check_value_stream() {
        $score = 0;
        
        // Check for clear process pages
        $process_pages = array('how-it-works', 'process', 'our-process', 'getting-started');
        foreach ($process_pages as $slug) {
            if (get_page_by_path($slug)) {
                $score += 40;
                break;
            }
        }
        
        // Check homepage for process explanation
        $content = $this->get_homepage_content();
        if (preg_match('/step\s*\d|phase\s*\d|process|how it works/i', $content)) {
            $score += 30;
        }
        
        // Check for visual indicators (numbered lists, etc.)
        if (preg_match('/<ol|class=["\'][^"\']*steps|class=["\'][^"\']*process/i', $content)) {
            $score += 30;
        }
        
        return min($score, 100);
    }
    
    private function check_payment_infrastructure() {
        $score = 0;
        
        if (class_exists('WooCommerce')) {
            $payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            $gateway_count = count($payment_gateways);
            
            // Score based on number of payment methods
            $score = min($gateway_count * 25, 100);
            
            // Bonus for specific popular gateways
            $preferred_gateways = array('stripe', 'paypal', 'square');
            foreach ($payment_gateways as $gateway) {
                if (in_array($gateway->id, $preferred_gateways)) {
                    $score = min($score + 10, 100);
                }
            }
        } else {
            // Check for payment plugin indicators
            $payment_plugins = array(
                'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
                'woocommerce-gateway-paypal-express-checkout/woocommerce-gateway-paypal-express-checkout.php'
            );
            
            foreach ($payment_plugins as $plugin) {
                if (is_plugin_active($plugin)) {
                    $score += 50;
                }
            }
        }
        
        return min($score, 100);
    }
    
    private function count_revenue_streams() {
        $streams = 0;
        
        // Check for WooCommerce products
        if (class_exists('WooCommerce')) {
            $product_count = wp_count_posts('product')->publish;
            if ($product_count > 0) {
                $streams++;
            }
            
            // Check for subscriptions
            if (class_exists('WC_Subscriptions')) {
                $streams++;
            }
        }
        
        // Check for membership plugins
        $membership_plugins = array(
            'paid-memberships-pro/paid-memberships-pro.php',
            'restrict-content-pro/restrict-content-pro.php',
            'memberpress/memberpress.php'
        );
        
        foreach ($membership_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $streams++;
                break;
            }
        }
        
        // Check for service/booking plugins
        $booking_plugins = array(
            'bookly-responsive-appointment-booking-tool/main.php',
            'appointment-hour-booking/app-booking.php'
        );
        
        foreach ($booking_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $streams++;
                break;
            }
        }
        
        // Check for advertising
        $content = $this->get_homepage_content();
        if (preg_match('/google-adsense|mediavine|adthrive/i', $content)) {
            $streams++;
        }
        
        return $streams;
    }
    
    private function check_pricing_optimization() {
        $score = 0;
        
        // Check for pricing table
        $content = $this->get_homepage_content();
        if (preg_match('/pricing-table|price-table|pricing-grid/i', $content)) {
            $score += 40;
        }
        
        // Check for multiple tiers
        if (preg_match_all('/\$\d+/i', $content, $matches) && count($matches[0]) >= 3) {
            $score += 30;
        }
        
        // Check for value-based language
        $value_terms = array('value', 'save', 'roi', 'investment', 'worth');
        foreach ($value_terms as $term) {
            if (stripos($content, $term) !== false) {
                $score += 10;
                break;
            }
        }
        
        // Check for urgency/scarcity
        $urgency_terms = array('limited', 'exclusive', 'only', 'ends', 'hurry');
        foreach ($urgency_terms as $term) {
            if (stripos($content, $term) !== false) {
                $score += 20;
                break;
            }
        }
        
        return min($score, 100);
    }
    
    private function check_financial_leverage() {
        $score = 0;
        
        // Check for upsells/cross-sells
        if (class_exists('WooCommerce')) {
            // Check if upsells are configured
            $products_with_upsells = get_posts(array(
                'post_type' => 'product',
                'meta_query' => array(
                    array(
                        'key' => '_upsell_ids',
                        'value' => '',
                        'compare' => '!='
                    )
                ),
                'posts_per_page' => 1
            ));
            
            if (!empty($products_with_upsells)) {
                $score += 30;
            }
        }
        
        // Check for email automation
        $email_automation = array(
            'mailchimp-for-woocommerce/mailchimp-woocommerce.php',
            'automatewoo/automatewoo.php'
        );
        
        foreach ($email_automation as $plugin) {
            if (is_plugin_active($plugin)) {
                $score += 35;
                break;
            }
        }
        
        // Check for affiliate program
        $affiliate_plugins = array(
            'affiliatewp/affiliate-wp.php',
            'thirstyaffiliates/thirstyaffiliates.php'
        );
        
        foreach ($affiliate_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $score += 35;
                break;
            }
        }
        
        return min($score, 100);
    }
    
    // Opportunity Generation Methods
    
    private function get_development_opportunities($business_type) {
        $opportunities = array();
        
        $opportunities[] = array(
            'title' => 'Add More Economic Values',
            'description' => 'Review the 9 economic values and identify 2-3 more you could add to your offer.',
            'impact' => 'high',
            'effort' => 'medium'
        );
        
        if ($business_type === 'saas' || $business_type === 'service') {
            $opportunities[] = array(
                'title' => 'Create a Free Trial or Demo',
                'description' => 'Let prospects experience value before purchasing.',
                'impact' => 'high',
                'effort' => 'medium'
            );
        }
        
        return $opportunities;
    }
    
    private function get_marketing_opportunities($business_type) {
        $opportunities = array();
        
        $opportunities[] = array(
            'title' => 'Build Topic Clusters',
            'description' => 'Create comprehensive content around your main topics to dominate search results.',
            'impact' => 'high',
            'effort' => 'high'
        );
        
        $opportunities[] = array(
            'title' => 'Implement Exit-Intent Popups',
            'description' => 'Capture emails from visitors about to leave your site.',
            'impact' => 'medium',
            'effort' => 'low'
        );
        
        return $opportunities;
    }
    
    private function get_sales_opportunities($business_type) {
        $opportunities = array();
        
        $opportunities[] = array(
            'title' => 'Add Live Chat',
            'description' => 'Answer questions in real-time to reduce purchase hesitation.',
            'impact' => 'high',
            'effort' => 'low'
        );
        
        if ($business_type === 'ecommerce') {
            $opportunities[] = array(
                'title' => 'Implement Abandoned Cart Recovery',
                'description' => 'Recover 10-30% of abandoned carts with automated emails.',
                'impact' => 'high',
                'effort' => 'medium'
            );
        }
        
        return $opportunities;
    }
    
    private function get_delivery_opportunities($business_type) {
        $opportunities = array();
        
        $opportunities[] = array(
            'title' => 'Create Customer Onboarding Sequence',
            'description' => 'Guide new customers to success with automated onboarding.',
            'impact' => 'high',
            'effort' => 'medium'
        );
        
        $opportunities[] = array(
            'title' => 'Implement NPS Surveys',
            'description' => 'Measure customer satisfaction and identify improvement areas.',
            'impact' => 'medium',
            'effort' => 'low'
        );
        
        return $opportunities;
    }
    
    private function get_accounting_opportunities($business_type) {
        $opportunities = array();
        
        $opportunities[] = array(
            'title' => 'Add Subscription Options',
            'description' => 'Create recurring revenue with subscription tiers.',
            'impact' => 'high',
            'effort' => 'medium'
        );
        
        $opportunities[] = array(
            'title' => 'Implement Dynamic Pricing',
            'description' => 'Test different price points to optimize revenue.',
            'impact' => 'medium',
            'effort' => 'medium'
        );
        
        return $opportunities;
    }
    
    // Helper Methods
    
    private function get_homepage_content() {
        $homepage_id = get_option('page_on_front');
        
        if ($homepage_id) {
            $post = get_post($homepage_id);
            return $post ? $post->post_content : '';
        }
        
        // Fallback to latest posts
        $latest_posts = get_posts(array(
            'numberposts' => 5,
            'post_status' => 'publish'
        ));
        
        $content = '';
        foreach ($latest_posts as $post) {
            $content .= $post->post_content . ' ';
        }
        
        return $content;
    }
    
    private function detect_business_type() {
        // Auto-detect based on installed plugins and content
        
        if (class_exists('WooCommerce')) {
            return 'ecommerce';
        }
        
        if (is_plugin_active('memberpress/memberpress.php') || 
            is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')) {
            return 'membership';
        }
        
        if (is_plugin_active('bookly-responsive-appointment-booking-tool/main.php')) {
            return 'service';
        }
        
        if (is_plugin_active('wp-courseware/wp-courseware.php') || 
            is_plugin_active('learnpress/learnpress.php')) {
            return 'education';
        }
        
        // Check content for clues
        $content = $this->get_homepage_content();
        
        if (preg_match('/software|app|saas|platform/i', $content)) {
            return 'saas';
        }
        
        if (preg_match('/agency|consulting|services/i', $content)) {
            return 'agency';
        }
        
        if (preg_match('/nonprofit|charity|donate/i', $content)) {
            return 'nonprofit';
        }
        
        return 'other';
    }
    
    private function calculate_pmba_alignment($departments) {
        $total_principles = 0;
        $total_score = 0;
        
        foreach ($departments as $dept) {
            if (isset($dept['principle_scores'])) {
                foreach ($dept['principle_scores'] as $principle => $score) {
                    $total_principles++;
                    $total_score += $score;
                }
            }
        }
        
        return $total_principles > 0 ? round($total_score / $total_principles) : 0;
    }
    
    private function generate_top_recommendations($departments) {
        $recommendations = array();
        
        // Collect all critical issues first
        foreach ($departments as $dept_name => $dept_data) {
            foreach ($dept_data['issues'] as $issue) {
                if ($issue['severity'] === 'critical') {
                    $recommendations[] = array(
                        'title' => $issue['title'],
                        'description' => $issue['action'],
                        'department' => ucfirst($dept_name),
                        'priority' => 1
                    );
                }
            }
        }
        
        // If fewer than 5 critical, add high priority issues
        if (count($recommendations) < 5) {
            foreach ($departments as $dept_name => $dept_data) {
                foreach ($dept_data['issues'] as $issue) {
                    if ($issue['severity'] === 'high' && count($recommendations) < 5) {
                        $recommendations[] = array(
                            'title' => $issue['title'],
                            'description' => $issue['action'],
                            'department' => ucfirst($dept_name),
                            'priority' => 2
                        );
                    }
                }
            }
        }
        
        // Sort by priority and return top 5
        usort($recommendations, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return array_slice($recommendations, 0, 5);
    }
    
    private function get_pmba_alignment_message($score) {
        if ($score >= 90) {
            return '🎯 Excellent PMBA alignment! Your business follows core principles exceptionally well.';
        } elseif ($score >= 75) {
            return '✅ Good PMBA alignment with room for improvement in some areas.';
        } elseif ($score >= 60) {
            return '⚠️ Moderate alignment. Several PMBA principles need attention.';
        } else {
            return '🚨 Low PMBA alignment. Focus on implementing fundamental principles.';
        }
    }
    
    private function get_last_full_analysis() {
        global $wpdb;
        $table = $wpdb->prefix . 'atomic_analysis';
        
        $analysis = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table 
            WHERE department = %s 
            ORDER BY analysis_date DESC 
            LIMIT 1
        ", 'full'));
        
        if (!$analysis) {
            return null;
        }
        
        $data = json_decode($analysis->analysis_data, true);
        
        return array(
            'date' => $analysis->analysis_date,
            'overall_score' => $analysis->atomic_score,
            'pmba_alignment' => $analysis->pmba_alignment,
            'departments' => isset($data['departments']) ? $data['departments'] : array(),
            'business_type' => $analysis->business_type,
            'top_recommendations' => isset($data['top_recommendations']) ? $data['top_recommendations'] : array()
        );
    }
    
    private function get_department_analysis($dept_key) {
        $last = $this->get_last_full_analysis();
        return $last && isset($last['departments'][$dept_key]) ? $last['departments'][$dept_key] : null;
    }
    
    private function render_department_results($analysis, $dept_key) {
        ?>
        <div class="aa-results-display">
            <?php if (!empty($analysis['issues'])): ?>
                <h3>Issues Found</h3>
                <?php foreach ($analysis['issues'] as $issue): ?>
                    <div class="aa-issue-card severity-<?php echo esc_attr($issue['severity']); ?>">
                        <div class="aa-issue-header">
                            <span class="aa-issue-severity"><?php echo esc_html(strtoupper($issue['severity'])); ?></span>
                            <span class="aa-issue-principle">Principle: <?php echo esc_html($issue['principle']); ?></span>
                        </div>
                        <h4><?php echo esc_html($issue['title']); ?></h4>
                        <p><?php echo esc_html($issue['description']); ?></p>
                        <div class="aa-pmba-guidance">
                            <strong>📖 Personal MBA Guidance:</strong>
                            <p><?php echo esc_html($issue['pmba_guidance']); ?></p>
                        </div>
                        <div class="aa-action">
                            <strong>✅ Action:</strong>
                            <p><?php echo esc_html($issue['action']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="aa-no-issues">✅ No critical issues found in this department!</div>
            <?php endif; ?>
            
            <?php if (!empty($analysis['opportunities'])): ?>
                <h3>Growth Opportunities</h3>
                <?php foreach ($analysis['opportunities'] as $opp): ?>
                    <div class="aa-opportunity-card">
                        <h4><?php echo esc_html($opp['title']); ?></h4>
                        <p><?php echo esc_html($opp['description']); ?></p>
                        <div class="aa-opp-meta">
                            <span class="aa-impact">Impact: <?php echo esc_html($opp['impact']); ?></span>
                            <span class="aa-effort">Effort: <?php echo esc_html($opp['effort']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize plugin
function aa_init() {
    return Atomic_Analyzer::get_instance();
}

add_action('plugins_loaded', 'aa_init');