<?php
/**
 * Webhook Manager for Atomic Analyzer
 * Connects to external services like Zapier, Make, Slack, Discord, etc.
 */

if (!defined('ABSPATH')) exit;

class AA_Webhook_Manager {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'atomic_webhooks';
    }
    
    /**
     * Trigger webhook for specific event
     */
    public function trigger($event, $data = array()) {
        $webhooks = $this->get_active_webhooks_by_event($event);
        
        if (empty($webhooks)) {
            return array();
        }
        
        $results = array();
        
        foreach ($webhooks as $webhook) {
            $result = $this->send_webhook($webhook, $data);
            $results[] = $result;
            
            // Update last triggered time
            $this->update_last_triggered($webhook->id);
            
            // Log result if failed
            if (!$result['success']) {
                error_log('Atomic Analyzer Webhook Failed: ' . $webhook->webhook_name . ' - ' . json_encode($result));
            }
        }
        
        return $results;
    }
    
    /**
     * Send webhook request
     */
    private function send_webhook($webhook, $data) {
        $url = $webhook->webhook_url;
        $method = $webhook->webhook_method;
        
        // Prepare payload
        $payload = $this->prepare_payload($webhook->trigger_event, $data);
        
        // Prepare headers
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'Atomic-Analyzer/' . AA_VERSION,
            'X-Atomic-Event' => $webhook->trigger_event,
            'X-Atomic-Webhook-ID' => $webhook->id
        );
        
        // Add custom headers if any
        if (!empty($webhook->custom_headers)) {
            $custom = json_decode($webhook->custom_headers, true);
            if (is_array($custom)) {
                $headers = array_merge($headers, $custom);
            }
        }
        
        // Add webhook secret for verification
        $secret = get_option('aa_webhook_secret');
        if ($secret) {
            $signature = hash_hmac('sha256', json_encode($payload), $secret);
            $headers['X-Atomic-Signature'] = $signature;
        }
        
        // Special formatting for specific services
        $final_payload = $this->format_payload_for_service($webhook, $payload);
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'body' => json_encode($final_payload),
            'timeout' => 30,
            'sslverify' => true,
            'data_format' => 'body'
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message(),
                'webhook_id' => $webhook->id,
                'webhook_name' => $webhook->webhook_name
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        return array(
            'success' => ($status_code >= 200 && $status_code < 300),
            'status_code' => $status_code,
            'response' => $response_body,
            'webhook_id' => $webhook->id,
            'webhook_name' => $webhook->webhook_name
        );
    }
    
    /**
     * Format payload for specific services
     */
    private function format_payload_for_service($webhook, $payload) {
        $url = $webhook->webhook_url;
        
        // Slack webhook formatting
        if (strpos($url, 'hooks.slack.com') !== false) {
            return array(
                'text' => $this->format_slack_message($payload),
                'attachments' => $this->get_slack_attachments($payload)
            );
        }
        
        // Discord webhook formatting
        if (strpos($url, 'discord.com/api/webhooks') !== false) {
            return array(
                'content' => $this->format_discord_message($payload),
                'embeds' => $this->get_discord_embeds($payload)
            );
        }
        
        // Default format for Zapier, Make, etc.
        return $payload;
    }
    
    /**
     * Format message for Slack
     */
    private function format_slack_message($payload) {
        $event = $payload['event'];
        $score = isset($payload['score']) ? $payload['score'] : 'N/A';
        
        $messages = array(
            'analysis_complete' => "🎉 Atomic Analysis Complete! Overall Score: *{$score}/100*",
            'critical_issue_found' => "🚨 Critical Issue Found in {$payload['data']['department']} department!",
            'score_improved' => "📈 Score Improved! New score: *{$payload['new_score']}/100* (+" . abs($payload['change']) . " points)",
            'score_declined' => "📉 Score Declined! New score: *{$payload['new_score']}/100* (" . $payload['change'] . " points)",
            'pdf_generated' => "📄 New PDF Report Generated!",
            'process_docs_created' => "📋 Process Documentation Created!"
        );
        
        return isset($messages[$event]) ? $messages[$event] : "Atomic Analyzer Event: {$event}";
    }
    
    /**
     * Get Slack attachments
     */
    private function get_slack_attachments($payload) {
        $attachments = array();
        
        if ($payload['event'] === 'analysis_complete' && isset($payload['data']['departments'])) {
            $fields = array();
            
            foreach ($payload['data']['departments'] as $dept => $data) {
                $fields[] = array(
                    'title' => ucfirst($dept),
                    'value' => $data['score'] . '/100',
                    'short' => true
                );
            }
            
            $attachments[] = array(
                'color' => $this->get_score_color($payload['score']),
                'fields' => $fields,
                'footer' => 'Atomic Analyzer',
                'footer_icon' => 'https://commercecopilot.com/icon.png',
                'ts' => time()
            );
        }
        
        return $attachments;
    }
    
    /**
     * Format message for Discord
     */
    private function format_discord_message($payload) {
        $event = $payload['event'];
        
        $messages = array(
            'analysis_complete' => "⚛️ **Atomic Analysis Complete!**",
            'critical_issue_found' => "🚨 **Critical Issue Found!**",
            'score_improved' => "📈 **Business Score Improved!**",
            'score_declined' => "📉 **Business Score Declined!**",
            'pdf_generated' => "📄 **PDF Report Generated!**",
            'process_docs_created' => "📋 **Process Documentation Created!**"
        );
        
        return isset($messages[$event]) ? $messages[$event] : "**Atomic Analyzer Event**";
    }
    
    /**
     * Get Discord embeds
     */
    private function get_discord_embeds($payload) {
        $embeds = array();
        
        $embed = array(
            'title' => $this->get_event_title($payload['event']),
            'color' => hexdec($this->get_score_color($payload['score'] ?? 0)),
            'timestamp' => gmdate('c'),
            'footer' => array(
                'text' => 'Atomic Analyzer by Commerce Copilot'
            ),
            'fields' => array()
        );
        
        if ($payload['event'] === 'analysis_complete') {
            $embed['description'] = "Overall Score: **{$payload['score']}/100**\nPMBA Alignment: **{$payload['pmba_alignment']}/100**";
            
            if (isset($payload['data']['departments'])) {
                foreach ($payload['data']['departments'] as $dept => $data) {
                    $embed['fields'][] = array(
                        'name' => ucfirst($dept),
                        'value' => $data['score'] . '/100',
                        'inline' => true
                    );
                }
            }
        }
        
        if (!empty($embed['fields']) || !empty($embed['description'])) {
            $embeds[] = $embed;
        }
        
        return $embeds;
    }
    
    /**
     * Prepare payload for webhook
     */
    private function prepare_payload($event, $data) {
        $payload = array(
            'event' => $event,
            'timestamp' => current_time('mysql'),
            'timestamp_unix' => time(),
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'business_type' => get_option('aa_business_type'),
            'data' => $data
        );
        
        // Add event-specific data
        switch ($event) {
            case 'analysis_complete':
                $payload['score'] = isset($data['overall_score']) ? $data['overall_score'] : 0;
                $payload['pmba_alignment'] = isset($data['pmba_alignment']) ? $data['pmba_alignment'] : 0;
                $payload['critical_issues'] = $this->count_critical_issues($data);
                $payload['departments_summary'] = $this->get_departments_summary($data);
                break;
                
            case 'critical_issue_found':
                $payload['severity'] = 'critical';
                $payload['department'] = isset($data['department']) ? $data['department'] : 'unknown';
                $payload['issue_title'] = isset($data['issue']['title']) ? $data['issue']['title'] : '';
                $payload['issue_description'] = isset($data['issue']['description']) ? $data['issue']['description'] : '';
                break;
                
            case 'score_improved':
            case 'score_declined':
                $payload['old_score'] = isset($data['old_score']) ? $data['old_score'] : 0;
                $payload['new_score'] = isset($data['new_score']) ? $data['new_score'] : 0;
                $payload['change'] = isset($data['new_score']) && isset($data['old_score']) 
                    ? $data['new_score'] - $data['old_score'] 
                    : 0;
                break;
                
            case 'pdf_generated':
                $payload['pdf_url'] = isset($data['pdf_url']) ? $data['pdf_url'] : '';
                $payload['report_type'] = 'Full Analysis Report';
                break;
                
            case 'process_docs_created':
                $payload['departments'] = isset($data['departments']) ? $data['departments'] : array();
                $payload['doc_types'] = array('sop', 'process_map', 'checklists', 'kpis');
                break;
        }
        
        return apply_filters('aa_webhook_payload', $payload, $event, $data);
    }
    
    /**
     * Count critical issues
     */
    private function count_critical_issues($data) {
        $count = 0;
        
        if (isset($data['departments'])) {
            foreach ($data['departments'] as $dept) {
                if (isset($dept['issues'])) {
                    foreach ($dept['issues'] as $issue) {
                        if (isset($issue['severity']) && $issue['severity'] === 'critical') {
                            $count++;
                        }
                    }
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Get departments summary
     */
    private function get_departments_summary($data) {
        $summary = array();
        
        if (isset($data['departments'])) {
            foreach ($data['departments'] as $dept_key => $dept) {
                $summary[$dept_key] = array(
                    'score' => isset($dept['score']) ? $dept['score'] : 0,
                    'issues_count' => isset($dept['issues']) ? count($dept['issues']) : 0,
                    'critical_issues' => 0
                );
                
                if (isset($dept['issues'])) {
                    foreach ($dept['issues'] as $issue) {
                        if ($issue['severity'] === 'critical') {
                            $summary[$dept_key]['critical_issues']++;
                        }
                    }
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Save or update webhook
     */
    public function save_webhook($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['name']) || empty($data['url']) || empty($data['trigger'])) {
            return new WP_Error('missing_fields', 'Name, URL, and trigger event are required');
        }
        
        // Validate URL
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', 'Invalid webhook URL');
        }
        
        // Check if webhook URL is reachable (optional)
        $test_response = wp_remote_head($data['url'], array('timeout' => 5));
        if (is_wp_error($test_response) && !strpos($data['url'], 'webhook')) {
            // Only warn if it doesn't look like a webhook URL
            $warning = 'Note: Could not reach the webhook URL. Make sure it\'s correct.';
        }
        
        $webhook_data = array(
            'webhook_name' => sanitize_text_field($data['name']),
            'webhook_url' => esc_url_raw($data['url']),
            'trigger_event' => sanitize_text_field($data['trigger']),
            'webhook_method' => isset($data['method']) ? sanitize_text_field($data['method']) : 'POST',
            'custom_headers' => isset($data['headers']) ? json_encode($data['headers']) : null,
            'is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1
        );
        
        $format = array('%s', '%s', '%s', '%s', '%s', '%d');
        
        if (isset($data['id']) && !empty($data['id'])) {
            // Update existing webhook
            $result = $wpdb->update(
                $this->table_name,
                $webhook_data,
                array('id' => intval($data['id'])),
                $format,
                array('%d')
            );
            
            if ($result === false) {
                return new WP_Error('update_failed', 'Failed to update webhook');
            }
            
            $webhook_id = intval($data['id']);
        } else {
            // Insert new webhook
            $webhook_data['created_date'] = current_time('mysql');
            $format[] = '%s';
            
            $result = $wpdb->insert(
                $this->table_name,
                $webhook_data,
                $format
            );
            
            if ($result === false) {
                return new WP_Error('insert_failed', 'Failed to create webhook');
            }
            
            $webhook_id = $wpdb->insert_id;
        }
        
        return array(
            'webhook_id' => $webhook_id,
            'message' => 'Webhook saved successfully',
            'warning' => isset($warning) ? $warning : null
        );
    }
    
    /**
     * Delete webhook
     */
    public function delete_webhook($webhook_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => intval($webhook_id)),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get all webhooks
     */
    public function get_all_webhooks() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT * FROM {$this->table_name} 
            ORDER BY created_date DESC
        ");
        
        // Decode custom headers for display
        foreach ($results as &$webhook) {
            if ($webhook->custom_headers) {
                $webhook->custom_headers_decoded = json_decode($webhook->custom_headers, true);
            }
        }
        
        return $results;
    }
    
    /**
     * Get active webhooks by event
     */
    private function get_active_webhooks_by_event($event) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$this->table_name} 
            WHERE trigger_event = %s 
            AND is_active = 1
        ", $event));
    }
    
    /**
     * Get webhook by ID
     */
    public function get_webhook($webhook_id) {
        global $wpdb;
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            intval($webhook_id)
        ));
        
        if ($webhook && $webhook->custom_headers) {
            $webhook->custom_headers_decoded = json_decode($webhook->custom_headers, true);
        }
        
        return $webhook;
    }
    
    /**
     * Update last triggered time
     */
    private function update_last_triggered($webhook_id) {
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            array('last_triggered' => current_time('mysql')),
            array('id' => intval($webhook_id)),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Test webhook
     */
    public function test_webhook($webhook_id) {
        global $wpdb;
        
        $webhook = $this->get_webhook($webhook_id);
        
        if (!$webhook) {
            return new WP_Error('not_found', 'Webhook not found');
        }
        
        // Create test data based on the event type
        $test_data = $this->generate_test_data($webhook->trigger_event);
        
        $result = $this->send_webhook($webhook, $test_data);
        
        if (!$result['success']) {
            $error_message = isset($result['error']) ? $result['error'] : 'Webhook request failed';
            if (isset($result['status_code'])) {
                $error_message .= ' (HTTP ' . $result['status_code'] . ')';
            }
            return new WP_Error('webhook_failed', $error_message);
        }
        
        return array(
            'success' => true,
            'message' => 'Test webhook sent successfully',
            'status_code' => $result['status_code'],
            'response' => substr($result['response'], 0, 500) // First 500 chars of response
        );
    }
    
    /**
     * Generate test data for webhook testing
     */
    private function generate_test_data($event) {
        $test_data = array(
            'test' => true,
            'message' => 'This is a test webhook from Atomic Analyzer',
            'test_timestamp' => current_time('mysql')
        );
        
        // Event-specific test data
        switch ($event) {
            case 'analysis_complete':
                $test_data = array_merge($test_data, array(
                    'overall_score' => 85,
                    'pmba_alignment' => 78,
                    'departments' => array(
                        'development' => array('score' => 80, 'issues' => array()),
                        'marketing' => array('score' => 85, 'issues' => array()),
                        'sales' => array('score' => 90, 'issues' => array()),
                        'delivery' => array('score' => 82, 'issues' => array()),
                        'accounting' => array('score' => 88, 'issues' => array())
                    )
                ));
                break;
                
            case 'critical_issue_found':
                $test_data = array_merge($test_data, array(
                    'department' => 'marketing',
                    'issue' => array(
                        'severity' => 'critical',
                        'title' => 'No Email Capture Forms',
                        'description' => 'Missing email list building capability',
                        'principle' => 'Permission Asset',
                        'action' => 'Add email capture form with lead magnet'
                    )
                ));
                break;
                
            case 'score_improved':
                $test_data = array_merge($test_data, array(
                    'old_score' => 75,
                    'new_score' => 82
                ));
                break;
                
            case 'score_declined':
                $test_data = array_merge($test_data, array(
                    'old_score' => 82,
                    'new_score' => 75
                ));
                break;
                
            case 'pdf_generated':
                $test_data = array_merge($test_data, array(
                    'pdf_url' => get_site_url() . '/wp-content/uploads/atomic-reports/test-report.pdf',
                    'analysis_summary' => array(
                        'overall_score' => 85,
                        'pmba_alignment' => 78
                    )
                ));
                break;
                
            case 'process_docs_created':
                $test_data = array_merge($test_data, array(
                    'departments' => array('development', 'marketing', 'sales', 'delivery', 'accounting')
                ));
                break;
        }
        
        return $test_data;
    }
    
    /**
     * Toggle webhook active status
     */
    public function toggle_active($webhook_id) {
        global $wpdb;
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT is_active FROM {$this->table_name} WHERE id = %d",
            intval($webhook_id)
        ));
        
        if (!$webhook) {
            return new WP_Error('not_found', 'Webhook not found');
        }
        
        $new_status = $webhook->is_active ? 0 : 1;
        
        $result = $wpdb->update(
            $this->table_name,
            array('is_active' => $new_status),
            array('id' => intval($webhook_id)),
            array('%d'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update webhook status');
        }
        
        return array(
            'success' => true,
            'is_active' => $new_status
        );
    }
    
    /**
     * Get available trigger events
     */
    public function get_available_triggers() {
        return array(
            'analysis_complete' => array(
                'name' => 'Analysis Complete',
                'description' => 'Triggered when a full atomic analysis is completed',
                'data' => 'Full analysis results including all department scores'
            ),
            'critical_issue_found' => array(
                'name' => 'Critical Issue Found',
                'description' => 'Triggered when a critical severity issue is detected',
                'data' => 'Issue details including department and recommendation'
            ),
            'score_improved' => array(
                'name' => 'Score Improved',
                'description' => 'Triggered when overall score increases by 5+ points',
                'data' => 'Old score, new score, and change amount'
            ),
            'score_declined' => array(
                'name' => 'Score Declined',
                'description' => 'Triggered when overall score decreases by 5+ points',
                'data' => 'Old score, new score, and change amount'
            ),
            'pdf_generated' => array(
                'name' => 'PDF Report Generated',
                'description' => 'Triggered when a PDF report is created',
                'data' => 'PDF URL and analysis summary'
            ),
            'process_docs_created' => array(
                'name' => 'Process Documentation Created',
                'description' => 'Triggered when process documentation is auto-generated',
                'data' => 'List of created documents'
            )
        );
    }
    
    /**
     * Get webhook templates for popular services
     */
    public function get_webhook_templates() {
        return array(
            'zapier' => array(
                'name' => 'Zapier',
                'description' => 'Connect to 5,000+ apps through Zapier',
                'url_format' => 'https://hooks.zapier.com/hooks/catch/{YOUR_ZAPIER_ID}/',
                'method' => 'POST',
                'headers' => array(),
                'instructions' => '1. Create a Zap in Zapier
2. Choose "Webhooks by Zapier" as trigger
3. Select "Catch Hook" as trigger event
4. Copy the webhook URL provided
5. Paste it here and save'
            ),
            'make' => array(
                'name' => 'Make (Integromat)',
                'description' => 'Create powerful automations with Make',
                'url_format' => 'https://hook.{region}.make.com/{YOUR_HOOK_ID}',
                'method' => 'POST',
                'headers' => array(),
                'instructions' => '1. Create scenario in Make
2. Add "Webhooks" module as trigger
3. Select "Custom webhook"
4. Copy the webhook URL
5. Paste it here and save'
            ),
            'slack' => array(
                'name' => 'Slack',
                'description' => 'Send notifications to Slack channels',
                'url_format' => 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK',
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json'),
                'instructions' => '1. Go to Slack App Directory
2. Search for "Incoming Webhooks"
3. Add to Slack and choose channel
4. Copy webhook URL
5. Paste it here and save'
            ),
            'discord' => array(
                'name' => 'Discord',
                'description' => 'Post updates to Discord channels',
                'url_format' => 'https://discord.com/api/webhooks/{id}/{token}',
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json'),
                'instructions' => '1. Edit Discord channel settings
2. Go to Integrations → Webhooks
3. Create New Webhook
4. Copy webhook URL
5. Paste it here and save'
            ),
            'ifttt' => array(
                'name' => 'IFTTT',
                'description' => 'If This Then That automation',
                'url_format' => 'https://maker.ifttt.com/trigger/{event}/with/key/{YOUR_KEY}',
                'method' => 'POST',
                'headers' => array('Content-Type' => 'application/json'),
                'instructions' => '1. Create IFTTT account
2. Create new Applet
3. Choose "Webhooks" for IF
4. Get your Maker key
5. Build URL and paste here'
            ),
            'custom' => array(
                'name' => 'Custom Webhook',
                'description' => 'Connect to any custom endpoint',
                'url_format' => 'https://your-domain.com/webhook/endpoint',
                'method' => 'POST',
                'headers' => array(),
                'instructions' => '1. Enter your webhook URL
2. Choose HTTP method
3. Add any custom headers needed
4. Test the webhook
5. Save when working'
            )
        );
    }
    
    /**
     * Generate webhook documentation
     */
    public function generate_documentation() {
        $secret = get_option('aa_webhook_secret');
        
        $docs = "# Atomic Analyzer Webhook Documentation\n\n";
        $docs .= "## Overview\n\n";
        $docs .= "Atomic Analyzer can send webhooks to external services when key events occur in your business analysis.\n\n";
        
        $docs .= "## Authentication\n\n";
        $docs .= "All webhooks include an `X-Atomic-Signature` header for verification:\n\n";
        $docs .= "```\n";
        $docs .= "signature = HMAC-SHA256(payload, secret_key)\n";
        $docs .= "```\n\n";
        $docs .= "Your webhook secret: `" . $secret . "`\n\n";
        $docs .= "⚠️ **Important:** Keep this secret secure! Regenerate if compromised.\n\n";
        
        $docs .= "## Webhook Headers\n\n";
        $docs .= "All webhooks include these headers:\n";
        $docs .= "- `Content-Type: application/json`\n";
        $docs .= "- `User-Agent: Atomic-Analyzer/" . AA_VERSION . "`\n";
        $docs .= "- `X-Atomic-Event: {event_name}`\n";
        $docs .= "- `X-Atomic-Webhook-ID: {webhook_id}`\n";
        $docs .= "- `X-Atomic-Signature: {hmac_signature}`\n\n";
        
        $docs .= "## Webhook Payload Structure\n\n";
        $docs .= "```json\n";
        $docs .= json_encode(array(
            'event' => 'analysis_complete',
            'timestamp' => '2024-01-15 10:30:00',
            'timestamp_unix' => 1705318200,
            'site_url' => get_site_url(),
            'site_name' => 'Your Business Name',
            'business_type' => 'E-commerce',
            'data' => array(
                'overall_score' => 85,
                'pmba_alignment' => 78,
                'departments' => array(
                    'development' => array('score' => 80),
                    'marketing' => array('score' => 85),
                    'sales' => array('score' => 90),
                    'delivery' => array('score' => 82),
                    'accounting' => array('score' => 88)
                )
            )
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        $docs .= "```\n\n";
        
        $docs .= "## Available Events\n\n";
        foreach ($this->get_available_triggers() as $key => $trigger) {
            $docs .= "### " . $trigger['name'] . "\n";
            $docs .= "**Event:** `" . $key . "`\n\n";
            $docs .= $trigger['description'] . "\n\n";
            $docs .= "**Data included:** " . $trigger['data'] . "\n\n";
        }
        
        $docs .= "## Integration Examples\n\n";
        
        $docs .= "### Zapier Integration\n";
        $docs .= "1. Create a new Zap\n";
        $docs .= "2. Choose \"Webhooks by Zapier\" as trigger\n";
        $docs .= "3. Select \"Catch Hook\"\n";
        $docs .= "4. Copy the webhook URL\n";
        $docs .= "5. Create webhook in Atomic Analyzer\n";
        $docs .= "6. Test and map fields in Zapier\n\n";
        
        $docs .= "### Slack Notification Example\n";
        $docs .= "```javascript\n";
        $docs .= "// Zapier Code Step\n";
        $docs .= "const score = inputData.score;\n";
        $docs .= "const message = `Analysis Complete! Score: \${score}/100`;\n\n";
        $docs .= "const emoji = score >= 80 ? '🎉' : score >= 60 ? '📊' : '⚠️';\n\n";
        $docs .= "return {\n";
        $docs .= "  text: `\${emoji} \${message}`,\n";
        $docs .= "  channel: '#business-metrics'\n";
        $docs .= "};\n";
        $docs .= "```\n\n";
        
        $docs .= "## Webhook Verification Example\n\n";
        $docs .= "### Node.js/Express\n";
        $docs .= "```javascript\n";
        $docs .= "const crypto = require('crypto');\n";
        $docs .= "const express = require('express');\n";
        $docs .= "const app = express();\n\n";
        $docs .= "app.use(express.raw({ type: 'application/json' }));\n\n";
        $docs .= "app.post('/webhook', (req, res) => {\n";
        $docs .= "  const signature = req.headers['x-atomic-signature'];\n";
        $docs .= "  const payload = req.body;\n";
        $docs .= "  const secret = '" . $secret . "';\n\n";
        $docs .= "  const expectedSignature = crypto\n";
        $docs .= "    .createHmac('sha256', secret)\n";
        $docs .= "    .update(payload)\n";
        $docs .= "    .digest('hex');\n\n";
        $docs .= "  if (signature === expectedSignature) {\n";
        $docs .= "    const data = JSON.parse(payload);\n";
        $docs .= "    console.log('Webhook verified!', data);\n";
        $docs .= "    \n";
        $docs .= "    // Process webhook data\n";
        $docs .= "    if (data.event === 'analysis_complete') {\n";
        $docs .= "      console.log('Score:', data.score);\n";
        $docs .= "    }\n";
        $docs .= "    \n";
        $docs .= "    res.sendStatus(200);\n";
        $docs .= "  } else {\n";
        $docs .= "    console.error('Invalid signature');\n";
        $docs .= "    res.sendStatus(401);\n";
        $docs .= "  }\n";
        $docs .= "});\n";
        $docs .= "```\n\n";
        
        $docs .= "### PHP Example\n";
        $docs .= "```php\n";
        $docs .= "<?php\n";
        $docs .= "\$payload = file_get_contents('php://input');\n";
        $docs .= "\$signature = \$_SERVER['HTTP_X_ATOMIC_SIGNATURE'];\n";
        $docs .= "\$secret = '" . $secret . "';\n\n";
        $docs .= "\$expected = hash_hmac('sha256', \$payload, \$secret);\n\n";
        $docs .= "if (hash_equals(\$expected, \$signature)) {\n";
        $docs .= "    \$data = json_decode(\$payload, true);\n";
        $docs .= "    // Process webhook\n";
        $docs .= "    http_response_code(200);\n";
        $docs .= "} else {\n";
        $docs .= "    http_response_code(401);\n";
        $docs .= "}\n";
        $docs .= "```\n\n";
        
        $docs .= "## Troubleshooting\n\n";
        $docs .= "### Common Issues\n\n";
        $docs .= "1. **Webhook not firing**\n";
        $docs .= "   - Check if webhook is active\n";
        $docs .= "   - Verify trigger event is occurring\n";
        $docs .= "   - Check WordPress error logs\n\n";
        $docs .= "2. **401 Unauthorized**\n";
        $docs .= "   - Verify signature calculation\n";
        $docs .= "   - Check secret key matches\n";
        $docs .= "   - Ensure raw payload is used\n\n";
        $docs .= "3. **Timeout errors**\n";
        $docs .= "   - Webhook endpoint must respond within 30 seconds\n";
        $docs .= "   - Process asynchronously if needed\n";
        $docs .= "   - Return 200 immediately, process later\n\n";
        
        $docs .= "---\n\n";
        $docs .= "*Generated by Atomic Analyzer v" . AA_VERSION . "*";
        
        return $docs;
    }
    
    /**
     * Get color based on score
     */
    private function get_score_color($score) {
        if ($score >= 80) return '2ecc71'; // Green
        if ($score >= 60) return 'f39c12'; // Orange
        return 'e74c3c'; // Red
    }
    
    /**
     * Get event title for display
     */
    private function get_event_title($event) {
        $triggers = $this->get_available_triggers();
        return isset($triggers[$event]) ? $triggers[$event]['name'] : 'Webhook Event';
    }
}