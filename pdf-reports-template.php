<?php
/**
 * PDF Reports Page Template
 */

if (!defined('ABSPATH')) exit;

$last_analysis = $this->get_last_full_analysis();
$upload = wp_upload_dir();
$reports_dir = $upload['basedir'] . '/atomic-reports/';

// Get existing reports
$reports = array();
if (file_exists($reports_dir)) {
    $files = glob($reports_dir . '*.html');
    foreach ($files as $file) {
        $reports[] = array(
            'filename' => basename($file),
            'date' => date('F j, Y g:i A', filemtime($file)),
            'size' => size_format(filesize($file)),
            'url' => str_replace($upload['basedir'], $upload['baseurl'], $file)
        );
    }
    // Sort by date (newest first)
    usort($reports, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<div class="wrap aa-pdf-page">
    <h1>📄 PDF Reports</h1>
    <p class="description">Generate beautiful PDF reports of your business analysis to share with stakeholders or keep for your records.</p>
    
    <?php if (!$last_analysis): ?>
    <div class="notice notice-warning">
        <p><strong>No Analysis Data Available</strong></p>
        <p>Run a full atomic analysis first to generate reports.</p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=atomic-analyzer'); ?>" class="button button-primary">
                Run Analysis →
            </a>
        </p>
    </div>
    <?php else: ?>
    
    <div class="aa-pdf-generator">
        <div class="aa-generator-card">
            <h2>Generate New Report</h2>
            <p>Create a comprehensive business intelligence report based on your latest analysis.</p>
            
            <div class="aa-report-preview">
                <h3>Report Preview</h3>
                <div class="aa-preview-info">
                    <div class="aa-preview-stat">
                        <span class="label">Analysis Date:</span>
                        <span class="value"><?php echo date('F j, Y', strtotime($last_analysis['date'])); ?></span>
                    </div>
                    <div class="aa-preview-stat">
                        <span class="label">Overall Score:</span>
                        <span class="value <?php echo $last_analysis['overall_score'] >= 75 ? 'good' : ($last_analysis['overall_score'] >= 60 ? 'fair' : 'poor'); ?>">
                            <?php echo $last_analysis['overall_score']; ?>/100
                        </span>
                    </div>
                    <div class="aa-preview-stat">
                        <span class="label">PMBA Alignment:</span>
                        <span class="value"><?php echo $last_analysis['pmba_alignment']; ?>/100</span>
                    </div>
                    <div class="aa-preview-stat">
                        <span class="label">Critical Issues:</span>
                        <span class="value">
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
                </div>
            </div>
            
            <div class="aa-report-options">
                <h3>📝 Report Sections</h3>
                <div class="aa-options-grid">
                    <label class="aa-option-item">
                        <input type="checkbox" name="include_cover" checked>
                        <span class="option-label">Cover Page</span>
                        <span class="option-desc">Professional cover with score summary</span>
                    </label>
                    <label class="aa-option-item">
                        <input type="checkbox" name="include_executive_summary" checked>
                        <span class="option-label">Executive Summary</span>
                        <span class="option-desc">High-level overview and key findings</span>
                    </label>
                    <label class="aa-option-item">
                        <input type="checkbox" name="include_departments" checked>
                        <span class="option-label">Department Details</span>
                        <span class="option-desc">In-depth analysis of all 5 departments</span>
                    </label>
                    <label class="aa-option-item">
                        <input type="checkbox" name="include_recommendations" checked>
                        <span class="option-label">Recommendations</span>
                        <span class="option-desc">Priority actions and improvement plan</span>
                    </label>
                    <label class="aa-option-item">
                        <input type="checkbox" name="include_pmba_guide" checked>
                        <span class="option-label">Personal MBA Guide</span>
                        <span class="option-desc">Framework explanation and principles</span>
                    </label>
                </div>
            </div>
            
            <div class="aa-report-style">
                <h3>🎨 Report Style</h3>
                <div class="aa-style-options">
                    <label class="aa-style-option">
                        <input type="radio" name="report_style" value="professional" checked>
                        <span class="style-card">
                            <span class="style-name">Professional</span>
                            <span class="style-desc">Clean, corporate design</span>
                        </span>
                    </label>
                    <label class="aa-style-option">
                        <input type="radio" name="report_style" value="minimal">
                        <span class="style-card">
                            <span class="style-name">Minimal</span>
                            <span class="style-desc">Simple, text-focused</span>
                        </span>
                    </label>
                    <label class="aa-style-option">
                        <input type="radio" name="report_style" value="colorful">
                        <span class="style-card">
                            <span class="style-name">Colorful</span>
                            <span class="style-desc">Vibrant, engaging design</span>
                        </span>
                    </label>
                </div>
            </div>
            
            <div class="aa-generate-actions">
                <button id="aa-generate-pdf" class="button button-primary button-hero">
                    📄 Generate PDF Report
                </button>
                <span class="aa-format-note">Currently generates HTML format. Print to PDF or use a PDF converter.</span>
            </div>
        </div>
    </div>
    
    <div id="aa-pdf-loading" style="display:none;">
        <div class="aa-loading-box">
            <div class="aa-spinner"></div>
            <p>Generating your report...</p>
            <p class="description">Creating comprehensive business analysis document</p>
        </div>
    </div>
    
    <div id="aa-pdf-result" style="display:none;">
        <div class="aa-success-box">
            <h3>✅ Report Generated Successfully!</h3>
            <p id="aa-pdf-message"></p>
            <div class="aa-pdf-actions">
                <a id="aa-pdf-download" href="#" class="button button-primary" target="_blank">
                    View Report
                </a>
                <button id="aa-pdf-email" class="button">
                    📧 Email Report
                </button>
                <button id="aa-pdf-print" class="button">
                    🖨️ Print Report
                </button>
            </div>
            <div class="aa-pdf-instructions">
                <h4>📌 How to Save as PDF:</h4>
                <ol>
                    <li>Click "View Report" to open in new tab</li>
                    <li>Press <strong>Ctrl/Cmd + P</strong> to open print dialog</li>
                    <li>Select "Save as PDF" as destination</li>
                    <li>Click Save</li>
                </ol>
            </div>
        </div>
    </div>
    
    <?php if (!empty($reports)): ?>
    <div class="aa-reports-history">
        <h2>📚 Previous Reports</h2>
        <p class="description">Your report generation history</p>
        
        <div class="aa-reports-table-wrapper">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Generated</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    foreach ($reports as $report): 
                        $count++;
                        if ($count > 10) break; // Show only last 10 reports
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($report['filename']); ?></strong>
                        </td>
                        <td><?php echo esc_html($report['date']); ?></td>
                        <td><?php echo esc_html($report['size']); ?></td>
                        <td>
                            <a href="<?php echo esc_url($report['url']); ?>" target="_blank" class="button button-small">
                                View
                            </a>
                            <a href="<?php echo esc_url($report['url']); ?>" download class="button button-small">
                                Download
                            </a>
                            <button class="button button-small delete-report" data-file="<?php echo esc_attr($report['filename']); ?>">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (count($reports) > 10): ?>
            <p class="description" style="margin-top: 10px;">
                Showing 10 most recent reports out of <?php echo count($reports); ?> total.
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<style>
.aa-generator-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 30px;
    margin: 20px 0;
}

.aa-report-preview {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 25px;
    margin: 25px 0;
}

.aa-preview-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.aa-preview-stat {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.aa-preview-stat .label {
    color: #64748b;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.aa-preview-stat .value {
    font-size: 20px;
    font-weight: bold;
    color: #1e293b;
}

.aa-preview-stat .value.good { color: #10b981; }
.aa-preview-stat .value.fair { color: #f59e0b; }
.aa-preview-stat .value.poor { color: #ef4444; }

.aa-report-options {
    margin: 30px 0;
}

.aa-report-options h3,
.aa-report-style h3 {
    margin-bottom: 15px;
    color: #1e293b;
}

.aa-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
}

.aa-option-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.aa-option-item:hover {
    border-color: #6366f1;
    background: #f8f9ff;
}

.aa-option-item input[type="checkbox"] {
    margin-top: 2px;
}

.aa-option-item input[type="checkbox"]:checked ~ .option-label {
    color: #6366f1;
    font-weight: 600;
}

.option-label {
    display: block;
    color: #1e293b;
    font-weight: 500;
}

.option-desc {
    display: block;
    color: #64748b;
    font-size: 13px;
    margin-top: 4px;
}

.aa-report-style {
    margin: 30px 0;
}

.aa-style-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.aa-style-option {
    position: relative;
    cursor: pointer;
}

.aa-style-option input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.style-card {
    display: block;
    padding: 20px;
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    text-align: center;
    transition: all 0.2s;
}

.aa-style-option input[type="radio"]:checked + .style-card {
    border-color: #6366f1;
    background: #f8f9ff;
}

.style-name {
    display: block;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 5px;
}

.style-desc {
    display: block;
    font-size: 13px;
    color: #64748b;
}

.aa-generate-actions {
    text-align: center;
    margin-top: 30px;
}

.aa-format-note {
    display: block;
    margin-top: 10px;
    color: #64748b;
    font-size: 14px;
    font-style: italic;
}

.aa-success-box {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border: 2px solid #10b981;
    border-radius: 12px;
    padding: 30px;
    margin: 20px 0;
    text-align: center;
}

.aa-success-box h3 {
    color: #047857;
    margin-top: 0;
}

.aa-pdf-actions {
    margin: 20px 0;
}

.aa-pdf-actions .button {
    margin: 0 5px;
}

.aa-pdf-instructions {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    text-align: left;
}

.aa-pdf-instructions h4 {
    margin-top: 0;
    color: #1e293b;
}

.aa-pdf-instructions ol {
    margin: 10px 0 0 20px;
    color: #64748b;
}

.aa-reports-history {
    margin-top: 50px;
}

.aa-reports-history h2 {
    color: #1e293b;
}

.aa-reports-table-wrapper {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0;
    overflow: hidden;
}

.aa-reports-table-wrapper table {
    border: none;
    margin: 0;
}

.aa-reports-table-wrapper table td,
.aa-reports-table-wrapper table th {
    border-bottom: 1px solid #e2e8f0;
}

.aa-reports-table-wrapper table tr:last-child td {
    border-bottom: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#aa-generate-pdf').on('click', function(e) {
        e.preventDefault();
        
        const options = {
            include_cover: $('input[name="include_cover"]').is(':checked'),
            include_executive_summary: $('input[name="include_executive_summary"]').is(':checked'),
            include_departments: $('input[name="include_departments"]').is(':checked'),
            include_recommendations: $('input[name="include_recommendations"]').is(':checked'),
            include_pmba_guide: $('input[name="include_pmba_guide"]').is(':checked'),
            style: $('input[name="report_style"]:checked').val()
        };
        
        // Validate at least one section is selected
        if (!options.include_cover && !options.include_executive_summary && 
            !options.include_departments && !options.include_recommendations && 
            !options.include_pmba_guide) {
            alert('Please select at least one section to include in the report.');
            return;
        }
        
        $('#aa-pdf-loading').show();
        $('#aa-pdf-result').hide();
        
        $.ajax({
            url: atomicData.ajaxurl,
            type: 'POST',
            data: {
                action: 'aa_generate_pdf_report',
                nonce: atomicData.nonce,
                options: options
            },
            success: function(response) {
                if (response.success) {
                    $('#aa-pdf-message').text('Your report is ready! It includes all selected sections in ' + options.style + ' style.');
                    $('#aa-pdf-download').attr('href', response.data.pdf_url);
                    $('#aa-pdf-result').show();
                    
                    // Smooth scroll to result
                    $('html, body').animate({
                        scrollTop: $('#aa-pdf-result').offset().top - 50
                    }, 500);
                } else {
                    alert('Error generating report: ' + response.data);
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
            },
            complete: function() {
                $('#aa-pdf-loading').hide();
            }
        });
    });
    
    $('#aa-pdf-email').on('click', function(e) {
        e.preventDefault();
        
        const email = prompt('Enter email address to send report:');
        if (!email) return;
        
        // Basic email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address.');
            return;
        }
        
        const pdfUrl = $('#aa-pdf-download').attr('href');
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Sending...');
        
        // Note: Email functionality would need to be implemented server-side
        setTimeout(function() {
            alert('Email functionality coming soon! For now, please download the report and attach it manually.');
            $btn.prop('disabled', false).text(originalText);
        }, 1000);
    });
    
    $('#aa-pdf-print').on('click', function(e) {
        e.preventDefault();
        const reportUrl = $('#aa-pdf-download').attr('href');
        window.open(reportUrl, '_blank');
        setTimeout(function() {
            alert('Report opened in new tab. Press Ctrl/Cmd + P to print.');
        }, 1000);
    });
    
    $('.delete-report').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Delete this report? This cannot be undone.')) {
            return;
        }
        
        const filename = $(this).data('file');
        const $row = $(this).closest('tr');
        
        // Note: Delete functionality would need server-side implementation
        $row.fadeOut(function() {
            $(this).remove();
        });
    });
});
</script>