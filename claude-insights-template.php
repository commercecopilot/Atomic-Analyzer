<?php
/**
 * Claude AI Insights Page Template
 */

if (!defined('ABSPATH')) exit;

$has_api_key = !empty(get_option('aa_claude_api_key'));
$last_analysis = $this->get_last_full_analysis();
?>

<div class="wrap aa-claude-page">
    <h1>🤖 Claude AI Insights</h1>
    <p class="description">Get intelligent, personalized business recommendations powered by Anthropic's Claude AI and The Personal MBA framework.</p>
    
    <?php if (!$has_api_key): ?>
    <div class="notice notice-error">
        <p><strong>Claude AI Not Configured</strong></p>
        <p>To use AI-powered insights, you need to add your Anthropic API key.</p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=aa-settings'); ?>" class="button button-primary">
                Go to Settings →
            </a>
            <a href="https://console.anthropic.com/" target="_blank" class="button">
                Get API Key from Anthropic
            </a>
        </p>
    </div>
    <?php elseif (!$last_analysis): ?>
    <div class="notice notice-warning">
        <p><strong>No Analysis Data Available</strong></p>
        <p>Run a full atomic analysis first to generate AI insights.</p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=atomic-analyzer'); ?>" class="button button-primary">
                Run Analysis →
            </a>
        </p>
    </div>
    <?php else: ?>
    
    <div class="aa-insights-controls">
        <button id="aa-get-claude-insights" class="button button-primary button-hero">
            🤖 Generate Claude AI Insights
        </button>
        <button id="aa-get-quick-wins" class="button">
            ⚡ Get 5 Quick Wins
        </button>
        <button id="aa-refresh-insights" class="button" style="display:none;">
            🔄 Refresh Insights
        </button>
    </div>
    
    <div id="aa-insights-loading" style="display:none;">
        <div class="aa-loading-box">
            <div class="aa-spinner"></div>
            <p>Claude is analyzing your business...</p>
            <p class="description">This may take 30-60 seconds for comprehensive insights</p>
        </div>
    </div>
    
    <div id="aa-insights-content">
        <div class="aa-empty-state">
            <h2>Ready to Unlock AI-Powered Insights</h2>
            <p>Click "Generate Claude AI Insights" to receive personalized recommendations based on your business analysis.</p>
            
            <div class="aa-insights-preview">
                <h3>What You'll Get:</h3>
                <div class="aa-preview-grid">
                    <div class="aa-preview-item">
                        <h4>📊 Executive Summary</h4>
                        <p>Big picture analysis of your business health using Personal MBA principles</p>
                    </div>
                    <div class="aa-preview-item">
                        <h4>🚨 Critical Priorities</h4>
                        <p>Top 3 issues that need immediate attention with specific actions</p>
                    </div>
                    <div class="aa-preview-item">
                        <h4>⚡ Quick Wins</h4>
                        <p>5 high-impact actions you can complete in under an hour each</p>
                    </div>
                    <div class="aa-preview-item">
                        <h4>🎯 Strategic Moves</h4>
                        <p>Long-term initiatives for sustainable growth based on PMBA framework</p>
                    </div>
                    <div class="aa-preview-item">
                        <h4>📚 PMBA Wisdom</h4>
                        <p>How to apply specific Personal MBA principles to your business</p>
                    </div>
                    <div class="aa-preview-item">
                        <h4>🗓️ 90-Day Roadmap</h4>
                        <p>Phased implementation plan with weekly milestones</p>
                    </div>
                </div>
            </div>
            
            <div class="aa-insights-stats">
                <h3>Your Current Status</h3>
                <div class="aa-stats-grid">
                    <div class="aa-stat-box">
                        <span class="aa-stat-label">Overall Score</span>
                        <span class="aa-stat-value"><?php echo $last_analysis['overall_score']; ?>/100</span>
                    </div>
                    <div class="aa-stat-box">
                        <span class="aa-stat-label">PMBA Alignment</span>
                        <span class="aa-stat-value"><?php echo $last_analysis['pmba_alignment']; ?>/100</span>
                    </div>
                    <div class="aa-stat-box">
                        <span class="aa-stat-label">Critical Issues</span>
                        <span class="aa-stat-value">
                            <?php
                            $critical_count = 0;
                            foreach ($last_analysis['departments'] as $dept) {
                                foreach ($dept['issues'] as $issue) {
                                    if ($issue['severity'] === 'critical') {
                                        $critical_count++;
                                    }
                                }
                            }
                            echo $critical_count;
                            ?>
                        </span>
                    </div>
                    <div class="aa-stat-box">
                        <span class="aa-stat-label">Last Analysis</span>
                        <span class="aa-stat-value"><?php echo human_time_diff(strtotime($last_analysis['date'])); ?> ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<style>
.aa-loading-box {
    text-align: center;
    padding: 60px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    margin: 30px 0;
}

.aa-insights-preview {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 30px;
    margin: 30px 0;
}

.aa-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.aa-preview-item {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.aa-preview-item h4 {
    margin: 0 0 10px 0;
    color: #6366f1;
    font-size: 16px;
}

.aa-preview-item p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.aa-insights-stats {
    margin: 30px 0;
}

.aa-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.aa-stat-box {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.aa-stat-label {
    display: block;
    color: #64748b;
    font-size: 12px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.aa-stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #1e293b;
}

.aa-insight-section {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 30px;
    margin: 20px 0;
}

.aa-insight-section h2 {
    color: #6366f1;
    margin-top: 0;
    font-size: 24px;
}

.aa-quick-wins-list,
.aa-strategic-list,
.aa-priority-list {
    margin: 20px 0;
}

.aa-quick-wins-list li,
.aa-strategic-list li {
    padding: 15px;
    margin: 10px 0;
    background: #f8fafc;
    border-left: 4px solid #10b981;
    border-radius: 0 6px 6px 0;
    list-style: none;
}

.aa-priority-list li {
    padding: 20px;
    margin: 15px 0;
    background: linear-gradient(to right, rgba(239, 68, 68, 0.05), transparent);
    border-left: 4px solid #ef4444;
    border-radius: 0 8px 8px 0;
}

.aa-insights-controls {
    margin: 30px 0;
}

.aa-insights-controls .button {
    margin-right: 10px;
}

.aa-pmba-box {
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.aa-roadmap {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    white-space: pre-wrap;
    font-family: monospace;
}

.aa-quick-wins-box {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.aa-quick-wins-box h3 {
    color: #78350f;
    margin-top: 0;
}

.aa-quick-wins-box ul {
    list-style: none;
    padding: 0;
}

.aa-quick-wins-box li {
    background: white;
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>

<script>
jQuery(document).ready(function($) {
    let insightsGenerated = false;
    
    $('#aa-get-claude-insights').on('click', function(e) {
        e.preventDefault();
        generateInsights('full');
    });
    
    $('#aa-get-quick-wins').on('click', function(e) {
        e.preventDefault();
        generateInsights('quick_wins');
    });
    
    $('#aa-refresh-insights').on('click', function(e) {
        e.preventDefault();
        generateInsights('full');
    });
    
    function generateInsights(type) {
        $('#aa-insights-loading').show();
        $('#aa-insights-content').html('');
        $('.aa-insights-controls .button').prop('disabled', true);
        
        const action = type === 'quick_wins' ? 'aa_get_quick_wins' : 'aa_get_claude_insights';
        
        $.ajax({
            url: atomicData.ajaxurl,
            type: 'POST',
            data: {
                action: action,
                nonce: atomicData.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (type === 'quick_wins') {
                        displayQuickWins(response.data);
                    } else {
                        displayInsights(response.data);
                        $('#aa-refresh-insights').show();
                        insightsGenerated = true;
                    }
                } else {
                    showError(response.data || 'Failed to generate insights');
                }
            },
            error: function() {
                showError('Connection error. Please try again.');
            },
            complete: function() {
                $('#aa-insights-loading').hide();
                $('.aa-insights-controls .button').prop('disabled', false);
            }
        });
    }
    
    function displayInsights(insights) {
        let html = '<div class="aa-insights-display">';
        
        // Executive Summary
        if (insights.executive_summary) {
            html += '<div class="aa-insight-section">';
            html += '<h2>📊 Executive Summary</h2>';
            html += '<p class="aa-insight-summary">' + escapeHtml(insights.executive_summary) + '</p>';
            html += '</div>';
        }
        
        // Critical Priorities
        if (insights.critical_priorities && insights.critical_priorities.length > 0) {
            html += '<div class="aa-insight-section">';
            html += '<h2>🚨 Critical Priorities</h2>';
            html += '<p class="description">Address these issues immediately for maximum impact:</p>';
            html += '<ol class="aa-priority-list">';
            insights.critical_priorities.forEach(function(priority) {
                html += '<li>' + escapeHtml(priority) + '</li>';
            });
            html += '</ol>';
            html += '</div>';
        }
        
        // Quick Wins
        if (insights.quick_wins && insights.quick_wins.length > 0) {
            html += '<div class="aa-insight-section">';
            html += '<h2>⚡ Quick Wins (Under 1 Hour Each)</h2>';
            html += '<p class="description">High-impact actions you can implement immediately:</p>';
            html += '<ul class="aa-quick-wins-list">';
            insights.quick_wins.forEach(function(win, index) {
                html += '<li><strong>' + (index + 1) + '.</strong> ' + escapeHtml(win) + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        // Strategic Moves
        if (insights.strategic_moves && insights.strategic_moves.length > 0) {
            html += '<div class="aa-insight-section">';
            html += '<h2>🎯 Strategic Moves</h2>';
            html += '<p class="description">Long-term initiatives for sustainable growth:</p>';
            html += '<ul class="aa-strategic-list">';
            insights.strategic_moves.forEach(function(move) {
                html += '<li>' + escapeHtml(move) + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        // PMBA Wisdom
        if (insights.pmba_wisdom) {
            html += '<div class="aa-insight-section">';
            html += '<h2>📚 Personal MBA Wisdom</h2>';
            html += '<div class="aa-pmba-box">';
            html += '<p>' + escapeHtml(insights.pmba_wisdom).replace(/\n/g, '<br>') + '</p>';
            html += '</div>';
            html += '</div>';
        }
        
        // 90-Day Roadmap
        if (insights.roadmap) {
            html += '<div class="aa-insight-section">';
            html += '<h2>🗓️ 90-Day Implementation Roadmap</h2>';
            html += '<div class="aa-roadmap">' + escapeHtml(insights.roadmap) + '</div>';
            html += '</div>';
        }
        
        // Raw Response (optional, for debugging)
        if (insights.raw_response && window.location.href.indexOf('debug=1') !== -1) {
            html += '<div class="aa-insight-section">';
            html += '<h2>Raw Claude Response</h2>';
            html += '<pre>' + escapeHtml(insights.raw_response) + '</pre>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $('#aa-insights-content').html(html);
    }
    
    function displayQuickWins(wins) {
        let html = '<div class="aa-quick-wins-box">';
        html += '<h3>⚡ Your 5 Quick Wins</h3>';
        html += '<p>Complete these actions in under 1 hour each for immediate impact:</p>';
        html += '<ul>';
        
        if (Array.isArray(wins)) {
            wins.forEach(function(win, index) {
                html += '<li><strong>#' + (index + 1) + '</strong> - ' + escapeHtml(win) + '</li>';
            });
        } else {
            html += '<li>No quick wins generated. Try running full insights instead.</li>';
        }
        
        html += '</ul>';
        html += '</div>';
        
        $('#aa-insights-content').html(html);
    }
    
    function showError(message) {
        $('#aa-insights-content').html(
            '<div class="notice notice-error"><p>' + escapeHtml(message) + '</p></div>'
        );
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
});
</script>