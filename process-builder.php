<?php
/**
 * Process Documentation Builder for Atomic Analyzer
 * Auto-generates SOPs, checklists, and process maps
 */

if (!defined('ABSPATH')) exit;

class AA_Process_Builder {
    
    private $claude_ai;
    
    public function __construct() {
        // Initialize Claude AI if available
        if (class_exists('AA_Claude_AI')) {
            $this->claude_ai = new AA_Claude_AI();
        }
    }
    
    /**
     * Generate all process documentation from analysis
     */
    public function generate_from_analysis($analysis_data) {
        $docs = array();
        
        foreach ($analysis_data['departments'] as $dept_key => $dept_data) {
            $context = array(
                'business_type' => $analysis_data['business_type'],
                'department' => $dept_key,
                'department_score' => $dept_data['score'],
                'issues' => $dept_data['issues']
            );
            
            $docs[$dept_key] = $this->generate_department_docs($dept_key, $context);
        }
        
        // Save to database
        foreach ($docs as $dept => $dept_docs) {
            $this->save_process_docs($dept, $dept_docs);
        }
        
        return $docs;
    }
    
    /**
     * Generate department-specific documentation
     */
    private function generate_department_docs($department, $context) {
        $docs = array();
        
        // Generate SOP
        $docs['sop'] = $this->generate_sop($department, $context);
        
        // Generate Process Map
        $docs['process_map'] = $this->generate_process_map($department, $context);
        
        // Generate Checklists
        $docs['checklists'] = $this->generate_checklists($department, $context);
        
        // Generate KPIs
        $docs['kpis'] = $this->generate_kpis($department, $context);
        
        return $docs;
    }
    
    /**
     * Generate Standard Operating Procedure
     */
    private function generate_sop($department, $context) {
        $template = $this->get_sop_template($department);
        
        // Try Claude AI first if available
        $has_claude = !empty(get_option('aa_claude_api_key'));
        if ($has_claude && $this->claude_ai) {
            $claude_sop = $this->claude_ai->generate_process_documentation($department, $context);
            if (!is_wp_error($claude_sop) && isset($claude_sop['content'])) {
                return $claude_sop['content'];
            }
        }
        
        // Fallback to template-based generation
        return $this->fill_sop_template($template, $context);
    }
    
    /**
     * Get SOP template by department
     */
    private function get_sop_template($department) {
        $templates = array(
            'development' => '# Development Department SOP

## Purpose
Create and improve products/services that deliver genuine value to customers according to The Personal MBA\'s Value Creation principles.

## Scope
This SOP applies to all product development, feature additions, and service improvements.

## Key Principles (Personal MBA)
- **Value Creation**: Always start with customer needs
- **Iteration Velocity**: Ship quickly, learn, improve
- **Economic Values**: Provide multiple forms of value
- **Prototype First**: Test before building completely

## Process Steps

### 1. Ideation & Validation
**Responsible:** Product Manager/Owner
**Tools:** Customer feedback, analytics, surveys
**Steps:**
1. Identify customer problem or need
2. Research existing solutions
3. Define desired outcome
4. Create simple value proposition
5. Validate with 5-10 potential customers

**Expected Outcome:** Validated problem worth solving

### 2. Prototype Development
**Responsible:** Development Team
**Tools:** Design tools, dev environment
**Steps:**
1. Create minimum viable version
2. Focus on core value delivery
3. Limit features to essential only
4. Build in 1-2 week sprints
5. Document as you build

**Expected Outcome:** Working prototype for testing

### 3. Testing & Feedback
**Responsible:** QA + Customer Success
**Tools:** Testing platform, feedback forms
**Steps:**
1. Test with real users (minimum 10)
2. Collect quantitative and qualitative data
3. Measure against success criteria
4. Identify improvements
5. Document learnings

**Expected Outcome:** Data-driven improvement list

### 4. Iteration
**Responsible:** Full Team
**Tools:** Project management, version control
**Steps:**
1. Prioritize improvements by impact
2. Implement top 3 changes
3. Re-test with users
4. Repeat cycle
5. Track iteration velocity

**Expected Outcome:** Improved product/service

### 5. Launch & Monitor
**Responsible:** Product Manager
**Tools:** Analytics, monitoring
**Steps:**
1. Prepare launch communications
2. Deploy to production
3. Monitor performance metrics
4. Gather customer feedback
5. Plan next iteration

**Expected Outcome:** Successfully launched improvement

## Quality Checks
- [ ] Does it solve a real customer problem?
- [ ] Are we providing at least 3 Economic Values?
- [ ] Can we iterate in under 2 weeks?
- [ ] Have we tested with real users?
- [ ] Is success measurable?

## KPIs to Track
- Iteration cycle time
- Customer satisfaction score
- Feature adoption rate
- Time from idea to launch
- Number of Economic Values provided

## Common Issues & Solutions

**Issue:** Features taking too long to build
**Solution:** Reduce scope, focus on core value only

**Issue:** Customers not using new features
**Solution:** Better validation upfront, clearer value prop

**Issue:** Too many ideas, can\'t prioritize
**Solution:** Use impact/effort matrix, focus on high impact

## Related Documents
- [Product Roadmap]
- [Customer Feedback Log]
- [Technical Documentation]
- [Launch Checklist]

---
*Last Updated: [DATE]*
*Owner: [ROLE]*
*Version: 1.0*',

            'marketing' => '# Marketing Department SOP

## Purpose
Attract attention and build demand for products/services using The Personal MBA\'s Marketing principles.

## Scope
All marketing activities including content creation, SEO, social media, email marketing, and advertising.

## Key Principles (Personal MBA)
- **Attention**: Capture prospect attention
- **Remarkability**: Be worth talking about
- **Permission Asset**: Build your audience
- **Social Proof**: Show others validate you

## Process Steps

### 1. Attention Capture
**Responsible:** Marketing Manager
**Tools:** SEO tools, content management
**Steps:**
1. Identify where target customers spend attention
2. Optimize for search engines (SEO)
3. Create attention-grabbing headlines
4. Use compelling visuals
5. Distribute where prospects are

**Expected Outcome:** Qualified traffic increase

### 2. Content Creation
**Responsible:** Content Creator
**Tools:** CMS, graphics tools
**Steps:**
1. Research audience questions/problems
2. Create valuable content that helps
3. Make it shareable and remarkable
4. Include clear next steps
5. Publish consistently (weekly minimum)

**Expected Outcome:** Engaging content published

### 3. Permission Building
**Responsible:** Email Marketing Lead
**Tools:** Email platform, lead magnets
**Steps:**
1. Create compelling lead magnet
2. Add email capture to all pages
3. Set up welcome sequence
4. Provide consistent value via email
5. Segment based on interests

**Expected Outcome:** Growing email list

### 4. Social Proof Development
**Responsible:** Customer Success
**Tools:** Review platforms, testimonial system
**Steps:**
1. Request reviews from happy customers
2. Create case studies
3. Display testimonials prominently
4. Share customer success stories
5. Monitor and respond to reviews

**Expected Outcome:** Social proof displayed

### 5. Measurement & Optimization
**Responsible:** Marketing Analyst
**Tools:** Analytics, conversion tracking
**Steps:**
1. Track all marketing metrics
2. A/B test major changes
3. Analyze what\'s working
4. Double down on winners
5. Cut losers quickly

**Expected Outcome:** Improving conversion rates

## Quality Checks
- [ ] Are we capturing attention in target channels?
- [ ] Is content remarkable enough to share?
- [ ] Are we building our email list?
- [ ] Do we display social proof?
- [ ] Are we measuring everything?

## KPIs to Track
- Website traffic (by source)
- Email list growth rate
- Content engagement rate
- Social proof quantity
- Cost per lead
- Conversion rate

## Weekly Marketing Checklist
- [ ] Publish 1-2 blog posts
- [ ] Post to social media daily
- [ ] Send email newsletter
- [ ] Check SEO rankings
- [ ] Review analytics
- [ ] Request testimonials from 3 customers
- [ ] Update social proof displays

---
*Last Updated: [DATE]*
*Owner: Marketing Manager*
*Version: 1.0*',

            'sales' => '# Sales Department SOP

## Purpose
Convert prospective customers into paying customers using Personal MBA Sales principles.

## Scope
All customer-facing sales activities from first contact to closed deal.

## Key Principles (Personal MBA)
- **Trust**: Build credibility and rapport
- **Education**: Help prospects understand value
- **Barriers to Purchase**: Remove friction
- **Risk Reversal**: Reduce perceived risk

## Process Steps

### 1. Lead Qualification
**Responsible:** Sales Development Rep
**Tools:** CRM, qualification criteria
**Steps:**
1. Review lead source and information
2. Assess fit with ideal customer profile
3. Check budget authority
4. Identify timeline and urgency
5. Prioritize hot/warm/cold

**Expected Outcome:** Qualified leads for sales team

### 2. Discovery Call
**Responsible:** Account Executive
**Tools:** Call script, notes template
**Steps:**
1. Build rapport and trust
2. Ask about current situation
3. Identify pain points
4. Understand desired outcome
5. Establish common ground

**Expected Outcome:** Deep understanding of needs

### 3. Solution Presentation
**Responsible:** Account Executive
**Tools:** Demo environment, slides
**Steps:**
1. Recap their situation and needs
2. Present solution tailored to them
3. Focus on outcomes, not features
4. Show social proof (case studies)
5. Address questions openly

**Expected Outcome:** Clear value demonstrated

### 4. Objection Handling
**Responsible:** Account Executive
**Tools:** Objection scripts
**Steps:**
1. Listen fully to objection
2. Acknowledge their concern
3. Ask clarifying questions
4. Provide evidence/proof
5. Check if concern is resolved

**Expected Outcome:** Objections addressed

### 5. Closing
**Responsible:** Account Executive
**Tools:** Proposal template, contracts
**Steps:**
1. Summarize value and fit
2. Present clear pricing
3. Offer risk reversal (guarantee)
4. Ask for the sale directly
5. Make next steps obvious

**Expected Outcome:** Signed customer

## Quality Checks
- [ ] Have we established trust?
- [ ] Does prospect understand the value?
- [ ] Have we removed all purchase barriers?
- [ ] Is there a guarantee/risk reversal?
- [ ] Is the CTA crystal clear?

## KPIs to Track
- Lead to opportunity conversion
- Opportunity to close rate
- Average deal size
- Sales cycle length
- Win rate by source
- Customer acquisition cost

## Daily Sales Checklist
- [ ] Follow up with hot leads
- [ ] Move deals forward in pipeline
- [ ] Log all activities in CRM
- [ ] Request referrals from happy customers
- [ ] Update proposals and quotes

---
*Last Updated: [DATE]*
*Owner: Sales Manager*
*Version: 1.0*',

            'delivery' => '# Delivery Department SOP

## Purpose
Deliver promised value and ensure customer satisfaction using Personal MBA Delivery principles.

## Scope
All activities related to fulfilling customer orders and delivering services.

## Key Principles (Personal MBA)
- **Value Stream**: Efficient delivery process
- **Expectation Effect**: Meet/exceed expectations
- **Throughput**: Maximize delivery capacity
- **Systems**: Documented, repeatable processes

## Process Steps

### 1. Order Processing
**Responsible:** Operations Manager
**Tools:** Order management system
**Steps:**
1. Receive and verify order details
2. Check inventory/capacity
3. Confirm with customer
4. Schedule delivery/fulfillment
5. Send confirmation email

**Expected Outcome:** Order ready to fulfill

### 2. Fulfillment
**Responsible:** Fulfillment Team
**Tools:** Checklist, inventory system
**Steps:**
1. Follow standard fulfillment checklist
2. Quality check at each step
3. Document any issues
4. Package/prepare for delivery
5. Update order status

**Expected Outcome:** Order fulfilled correctly

### 3. Delivery/Launch
**Responsible:** Delivery Coordinator
**Tools:** Scheduling, tracking
**Steps:**
1. Coordinate delivery time
2. Communicate with customer
3. Deliver product/service
4. Confirm receipt/completion
5. Provide support materials

**Expected Outcome:** Successful delivery

### 4. Customer Onboarding
**Responsible:** Customer Success
**Tools:** Onboarding checklist, tutorials
**Steps:**
1. Welcome email with resources
2. Schedule onboarding call
3. Walk through key features/use
4. Answer questions
5. Set up for success

**Expected Outcome:** Customer knows how to use

### 5. Follow-up & Support
**Responsible:** Support Team
**Tools:** Support ticket system
**Steps:**
1. Check in 24 hours after delivery
2. Ensure satisfaction
3. Address any issues promptly
4. Collect feedback
5. Request testimonial if happy

**Expected Outcome:** Satisfied customer

## Quality Checks
- [ ] Did we deliver what was promised?
- [ ] Did we meet the timeline?
- [ ] Is the customer happy?
- [ ] Are systems documented?
- [ ] Can we scale this?

## KPIs to Track
- On-time delivery rate
- Customer satisfaction score (CSAT)
- First-contact resolution rate
- Average delivery time
- Defect rate
- Repeat customer rate

## Daily Delivery Checklist
- [ ] Process new orders
- [ ] Update order statuses
- [ ] Respond to support tickets
- [ ] Quality check deliveries
- [ ] Follow up with recent customers

---
*Last Updated: [DATE]*
*Owner: Operations Manager*
*Version: 1.0*',

            'accounting' => '# Accounting Department SOP

## Purpose
Track money flow and ensure sustainable profitability using Personal MBA Accounting principles.

## Scope
All financial tracking, reporting, and money management activities.

## Key Principles (Personal MBA)
- **Profit Margin**: Ensure profitability per sale
- **Value Capture**: Get paid for value created
- **Sufficiency**: Revenue covers all costs
- **Leverage**: Multiply results without proportional costs

## Process Steps

### 1. Revenue Tracking
**Responsible:** Accountant/Bookkeeper
**Tools:** Accounting software, invoicing
**Steps:**
1. Record all revenue sources
2. Categorize by product/service
3. Track payment terms
4. Monitor receivables
5. Follow up on overdue payments

**Expected Outcome:** Accurate revenue records

### 2. Expense Management
**Responsible:** Finance Manager
**Tools:** Expense tracking, receipts
**Steps:**
1. Categorize all expenses
2. Review against budget
3. Identify unnecessary costs
4. Negotiate with vendors
5. Automate recurring payments

**Expected Outcome:** Controlled expenses

### 3. Financial Reporting
**Responsible:** Finance Manager
**Tools:** Reporting tools, dashboards
**Steps:**
1. Generate P&L monthly
2. Create cash flow statement
3. Calculate key metrics
4. Compare to targets
5. Share with stakeholders

**Expected Outcome:** Clear financial picture

### 4. Pricing Analysis
**Responsible:** Finance + Sales
**Tools:** Pricing calculator, market data
**Steps:**
1. Calculate true cost per sale
2. Determine desired margin
3. Research market pricing
4. Test pricing with customers
5. Adjust based on data

**Expected Outcome:** Profitable pricing

### 5. Cash Flow Management
**Responsible:** Finance Manager
**Tools:** Cash flow forecast
**Steps:**
1. Project next 90 days cash flow
2. Ensure sufficient reserves
3. Time major expenses
4. Accelerate collections
5. Maintain emergency fund

**Expected Outcome:** Healthy cash position

## Quality Checks
- [ ] Are we profitable per sale?
- [ ] Do we capture full value?
- [ ] Is revenue sufficient?
- [ ] Are we leveraging assets?
- [ ] Is cash flow positive?

## KPIs to Track
- Gross profit margin
- Net profit margin
- Revenue per customer
- Customer lifetime value
- Customer acquisition cost
- Cash runway (months)
- Days sales outstanding

## Monthly Financial Checklist
- [ ] Close books for previous month
- [ ] Generate financial statements
- [ ] Review vs budget
- [ ] Calculate key metrics
- [ ] Update cash flow forecast
- [ ] Pay bills and invoices
- [ ] Review pricing strategy

---
*Last Updated: [DATE]*
*Owner: Finance Manager*
*Version: 1.0*'
        );
        
        return isset($templates[$department]) ? $templates[$department] : '';
    }
    
    /**
     * Fill SOP template with context
     */
    private function fill_sop_template($template, $context) {
        $template = str_replace('[DATE]', date('F j, Y'), $template);
        $template = str_replace('[ROLE]', 'Department Manager', $template);
        
        // Add specific context based on issues found
        if (!empty($context['issues'])) {
            $template .= "\n\n## Priority Improvements Based on Analysis\n\n";
            foreach ($context['issues'] as $issue) {
                if ($issue['severity'] === 'critical' || $issue['severity'] === 'high') {
                    $template .= "### " . $issue['title'] . "\n";
                    $template .= "**Action Required:** " . $issue['action'] . "\n\n";
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Generate Process Map
     */
    private function generate_process_map($department, $context) {
        $maps = array(
            'development' => '```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    IDEA     │ --> │  VALIDATE   │ --> │  PROTOTYPE  │
└─────────────┘     └─────────────┘     └─────────────┘
                            |                    |
                            v                    v
                    ┌─────────────┐     ┌─────────────┐
                    │  FEEDBACK   │ <-- │    TEST     │
                    └─────────────┘     └─────────────┘
                            |
                            v
                    ┌─────────────┐     ┌─────────────┐
                    │   ITERATE   │ --> │   LAUNCH    │
                    └─────────────┘     └─────────────┘
                            |                    |
                            v                    v
                    ┌─────────────┐     ┌─────────────┐
                    │   MONITOR   │ <-- │   DELIVER   │
                    └─────────────┘     └─────────────┘
```',
            'marketing' => '```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  RESEARCH   │ --> │   CREATE    │ --> │ DISTRIBUTE  │
└─────────────┘     └─────────────┘     └─────────────┘
        |                   |                    |
        v                   v                    v
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  AUDIENCE   │     │   CONTENT   │     │  CHANNELS   │
└─────────────┘     └─────────────┘     └─────────────┘
                            |
                            v
                    ┌─────────────┐     ┌─────────────┐
                    │   CAPTURE   │ --> │   NURTURE   │
                    └─────────────┘     └─────────────┘
                            |                    |
                            v                    v
                    ┌─────────────┐     ┌─────────────┐
                    │    LEADS    │     │  CONVERT    │
                    └─────────────┘     └─────────────┘
```',
            'sales' => '```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    LEAD     │ --> │  QUALIFY    │ --> │  DISCOVER   │
└─────────────┘     └─────────────┘     └─────────────┘
                            |                    |
                            v                    v
                    ┌─────────────┐     ┌─────────────┐
                    │  CONTACT    │     │   NEEDS     │
                    └─────────────┘     └─────────────┘
                                                |
                                                v
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   CLOSE     │ <-- │  NEGOTIATE  │ <-- │  PRESENT    │
└─────────────┘     └─────────────┘     └─────────────┘
        |
        v
┌─────────────┐     ┌─────────────┐
│   ONBOARD   │ --> │   RETAIN    │
└─────────────┘     └─────────────┘
```',
            'delivery' => '```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    ORDER    │ --> │   PROCESS   │ --> │   FULFILL   │
└─────────────┘     └─────────────┘     └─────────────┘
                            |                    |
                            v                    v
                    ┌─────────────┐     ┌─────────────┐
                    │   VERIFY    │     │   QUALITY   │
                    └─────────────┘     └─────────────┘
                                                |
                                                v
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  FOLLOW-UP  │ <-- │   DELIVER   │ <-- │   CHECK     │
└─────────────┘     └─────────────┘     └─────────────┘
        |
        v
┌─────────────┐     ┌─────────────┐
│   SUPPORT   │ --> │ SATISFACTION│
└─────────────┘     └─────────────┘
```',
            'accounting' => '```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   REVENUE   │ --> │   RECORD    │ --> │   REPORT    │
└─────────────┘     └─────────────┘     └─────────────┘
        |                   |                    |
        v                   v                    v
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   COLLECT   │     │ CATEGORIZE  │     │  ANALYZE    │
└─────────────┘     └─────────────┘     └─────────────┘
                            |
                            v
                    ┌─────────────┐     ┌─────────────┐
                    │  EXPENSES   │ --> │  OPTIMIZE   │
                    └─────────────┘     └─────────────┘
                            |                    |
                            v                    v
                    ┌─────────────┐     ┌─────────────┐
                    │  CASH FLOW  │     │  FORECAST   │
                    └─────────────┘     └─────────────┘
```'
        );
        
        $map = isset($maps[$department]) ? $maps[$department] : 'Process map not available';
        
        // Add department-specific context
        if ($context['department_score'] < 60) {
            $map .= "\n\n⚠️ **Note:** This department scored " . $context['department_score'] . "/100. Focus on fixing bottlenecks in the process flow above.";
        }
        
        return $map;
    }
    
    /**
     * Generate Checklists
     */
    private function generate_checklists($department, $context) {
        $checklists = array(
            'development' => array(
                'daily' => array(
                    'Review customer feedback from support channels',
                    'Check analytics for user behavior patterns',
                    'Update development progress in project tracker',
                    'Code review pending pull requests',
                    'Test features in development',
                    'Update documentation for changes made'
                ),
                'weekly' => array(
                    'Sprint planning meeting with team',
                    'Deploy updates to staging environment',
                    'Review iteration velocity metrics',
                    'Conduct customer interview sessions',
                    'Update product roadmap based on learnings',
                    'Analyze feature adoption rates',
                    'Review and prioritize bug reports',
                    'Check competitive landscape for new features'
                ),
                'monthly' => array(
                    'Major feature launch review',
                    'Technical debt assessment and planning',
                    'Team capacity and resource planning',
                    'Competitive analysis deep dive',
                    'Review and update development SOPs',
                    'Calculate ROI on recent launches',
                    'Plan next quarter\'s major initiatives'
                )
            ),
            'marketing' => array(
                'daily' => array(
                    'Post to all active social media channels',
                    'Monitor brand mentions and respond',
                    'Check and respond to comments/messages',
                    'Review website analytics dashboard',
                    'Monitor ad campaign performance',
                    'Update marketing calendar'
                ),
                'weekly' => array(
                    'Publish 1-2 blog posts or content pieces',
                    'Send email newsletter to subscribers',
                    'Review and update SEO rankings',
                    'A/B test results review and implementation',
                    'Content calendar planning for next week',
                    'Review lead generation metrics',
                    'Update social proof displays',
                    'Competitor content analysis'
                ),
                'monthly' => array(
                    'Marketing performance comprehensive review',
                    'Budget vs actuals analysis',
                    'Update buyer personas based on data',
                    'Refresh lead magnets and opt-in offers',
                    'Plan next month\'s campaign themes',
                    'Review and optimize conversion funnels',
                    'Update marketing automation sequences'
                )
            ),
            'sales' => array(
                'daily' => array(
                    'Review and prioritize new leads',
                    'Follow up with hot prospects',
                    'Update CRM with all activities',
                    'Move deals through pipeline stages',
                    'Send proposals and quotes',
                    'Request referrals from closed deals'
                ),
                'weekly' => array(
                    'Pipeline review meeting with team',
                    'Sales training or skill development',
                    'Update proposal templates',
                    'Win/loss analysis for closed deals',
                    'Review team performance metrics',
                    'Update sales collateral',
                    'Competitive intelligence gathering'
                ),
                'monthly' => array(
                    'Sales forecast for upcoming month',
                    'Commission calculations and payouts',
                    'Territory and account planning',
                    'Sales process optimization review',
                    'Update ideal customer profile',
                    'Review and update sales playbook',
                    'Plan sales team recognition'
                )
            ),
            'delivery' => array(
                'daily' => array(
                    'Process all new orders',
                    'Quality check ongoing deliveries',
                    'Respond to support tickets (SLA)',
                    'Update delivery status tracking',
                    'Coordinate with fulfillment team',
                    'Monitor customer satisfaction metrics'
                ),
                'weekly' => array(
                    'Inventory levels check and reorder',
                    'Team capacity and workload review',
                    'Process improvement brainstorming',
                    'Customer satisfaction survey review',
                    'Vendor performance assessment',
                    'Update delivery documentation',
                    'Review and optimize workflows'
                ),
                'monthly' => array(
                    'Operations performance deep dive',
                    'System maintenance and updates',
                    'Team training on new processes',
                    'SOP review and updates',
                    'Vendor contract negotiations',
                    'Capacity planning for growth',
                    'Technology stack evaluation'
                )
            ),
            'accounting' => array(
                'daily' => array(
                    'Record all transactions',
                    'Review bank account balances',
                    'Process payments and deposits',
                    'Check for overdue invoices',
                    'Approve expense reports',
                    'Update financial dashboard'
                ),
                'weekly' => array(
                    'Accounts payable processing',
                    'Accounts receivable follow-up',
                    'Cash flow review and forecast',
                    'Expense categorization cleanup',
                    'Vendor payment scheduling',
                    'Financial data backup',
                    'Budget variance quick check'
                ),
                'monthly' => array(
                    'Close books for the month',
                    'Generate financial statements',
                    'Budget vs actual detailed review',
                    'Tax preparation and filing',
                    'Financial forecast update',
                    'Profitability analysis by product/service',
                    'Board/stakeholder reporting package'
                )
            )
        );
        
        // Customize based on business type
        $business_type = $context['business_type'];
        if ($business_type === 'ecommerce' && $department === 'delivery') {
            $checklists[$department]['daily'][] = 'Check shipping carrier tracking';
            $checklists[$department]['daily'][] = 'Process returns and refunds';
        }
        
        if ($business_type === 'saas' && $department === 'delivery') {
            $checklists[$department]['daily'][] = 'Monitor server uptime and performance';
            $checklists[$department]['daily'][] = 'Review user onboarding metrics';
        }
        
        return isset($checklists[$department]) ? $checklists[$department] : array();
    }
    
    /**
     * Generate KPIs
     */
    private function generate_kpis($department, $context) {
        $kpis = array(
            'development' => array(
                array('name' => 'Iteration Cycle Time', 'target' => '< 14 days', 'frequency' => 'Weekly'),
                array('name' => 'Feature Adoption Rate', 'target' => '> 40%', 'frequency' => 'Monthly'),
                array('name' => 'Customer Satisfaction Score', 'target' => '> 4.5/5', 'frequency' => 'Monthly'),
                array('name' => 'Bug/Defect Rate', 'target' => '< 5%', 'frequency' => 'Weekly'),
                array('name' => 'Time from Idea to Launch', 'target' => '< 30 days', 'frequency' => 'Quarterly'),
                array('name' => 'Code Coverage', 'target' => '> 80%', 'frequency' => 'Weekly'),
                array('name' => 'Technical Debt Ratio', 'target' => '< 20%', 'frequency' => 'Monthly')
            ),
            'marketing' => array(
                array('name' => 'Website Traffic', 'target' => '+10% MoM', 'frequency' => 'Monthly'),
                array('name' => 'Email List Growth Rate', 'target' => '+5% monthly', 'frequency' => 'Weekly'),
                array('name' => 'Content Engagement Rate', 'target' => '> 3%', 'frequency' => 'Weekly'),
                array('name' => 'Cost Per Lead', 'target' => '< $50', 'frequency' => 'Weekly'),
                array('name' => 'Marketing Qualified Leads', 'target' => '100/month', 'frequency' => 'Monthly'),
                array('name' => 'Social Media Engagement', 'target' => '> 2%', 'frequency' => 'Daily'),
                array('name' => 'SEO Rankings (Top 10)', 'target' => '20 keywords', 'frequency' => 'Monthly')
            ),
            'sales' => array(
                array('name' => 'Lead to Opportunity Rate', 'target' => '> 25%', 'frequency' => 'Weekly'),
                array('name' => 'Opportunity to Close Rate', 'target' => '> 20%', 'frequency' => 'Weekly'),
                array('name' => 'Average Deal Size', 'target' => '$5,000+', 'frequency' => 'Monthly'),
                array('name' => 'Sales Cycle Length', 'target' => '< 30 days', 'frequency' => 'Weekly'),
                array('name' => 'Win Rate', 'target' => '> 25%', 'frequency' => 'Weekly'),
                array('name' => 'Sales Velocity', 'target' => '+5% QoQ', 'frequency' => 'Quarterly'),
                array('name' => 'Customer Acquisition Cost', 'target' => '< $1,000', 'frequency' => 'Monthly')
            ),
            'delivery' => array(
                array('name' => 'On-time Delivery Rate', 'target' => '> 95%', 'frequency' => 'Daily'),
                array('name' => 'Customer Satisfaction (CSAT)', 'target' => '> 90%', 'frequency' => 'Weekly'),
                array('name' => 'First Contact Resolution', 'target' => '> 80%', 'frequency' => 'Daily'),
                array('name' => 'Average Delivery Time', 'target' => '< 3 days', 'frequency' => 'Daily'),
                array('name' => 'Order Accuracy Rate', 'target' => '> 99%', 'frequency' => 'Daily'),
                array('name' => 'Support Ticket Volume', 'target' => '< 5% of orders', 'frequency' => 'Weekly'),
                array('name' => 'Repeat Purchase Rate', 'target' => '> 30%', 'frequency' => 'Monthly')
            ),
            'accounting' => array(
                array('name' => 'Gross Profit Margin', 'target' => '> 50%', 'frequency' => 'Monthly'),
                array('name' => 'Net Profit Margin', 'target' => '> 15%', 'frequency' => 'Monthly'),
                array('name' => 'Customer Lifetime Value', 'target' => '> $2,000', 'frequency' => 'Quarterly'),
                array('name' => 'CAC Payback Period', 'target' => '< 12 months', 'frequency' => 'Monthly'),
                array('name' => 'Cash Runway', 'target' => '> 6 months', 'frequency' => 'Monthly'),
                array('name' => 'Days Sales Outstanding', 'target' => '< 30 days', 'frequency' => 'Weekly'),
                array('name' => 'Revenue Growth Rate', 'target' => '+20% YoY', 'frequency' => 'Monthly')
            )
        );
        
        // Adjust targets based on business type
        if ($context['business_type'] === 'saas') {
            $kpis['accounting'][] = array('name' => 'Monthly Recurring Revenue', 'target' => '+10% MoM', 'frequency' => 'Monthly');
            $kpis['accounting'][] = array('name' => 'Churn Rate', 'target' => '< 5%', 'frequency' => 'Monthly');
        }
        
        return isset($kpis[$department]) ? $kpis[$department] : array();
    }
    
    /**
     * Save process documentation to database
     */
    private function save_process_docs($department, $docs) {
        global $wpdb;
        $table = $wpdb->prefix . 'atomic_processes';
        
        // Save SOP
        if (isset($docs['sop'])) {
            $wpdb->replace(
                $table,
                array(
                    'process_name' => ucfirst($department) . ' Standard Operating Procedure',
                    'department' => $department,
                    'process_type' => 'sop',
                    'process_content' => is_array($docs['sop']) ? json_encode($docs['sop']) : $docs['sop'],
                    'created_date' => current_time('mysql'),
                    'updated_date' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        // Save Process Map
        if (isset($docs['process_map'])) {
            $wpdb->replace(
                $table,
                array(
                    'process_name' => ucfirst($department) . ' Process Map',
                    'department' => $department,
                    'process_type' => 'process_map',
                    'process_content' => $docs['process_map'],
                    'created_date' => current_time('mysql'),
                    'updated_date' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        // Save Checklists
        if (isset($docs['checklists'])) {
            $wpdb->replace(
                $table,
                array(
                    'process_name' => ucfirst($department) . ' Checklists',
                    'department' => $department,
                    'process_type' => 'checklists',
                    'process_content' => json_encode($docs['checklists']),
                    'created_date' => current_time('mysql'),
                    'updated_date' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        // Save KPIs
        if (isset($docs['kpis'])) {
            $wpdb->replace(
                $table,
                array(
                    'process_name' => ucfirst($department) . ' KPIs',
                    'department' => $department,
                    'process_type' => 'kpis',
                    'process_content' => json_encode($docs['kpis']),
                    'created_date' => current_time('mysql'),
                    'updated_date' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Get all process docs
     */
    public function get_all_docs() {
        global $wpdb;
        $table = $wpdb->prefix . 'atomic_processes';
        
        return $wpdb->get_results("SELECT * FROM $table ORDER BY department, process_type");
    }
    
    /**
     * Get docs by department
     */
    public function get_department_docs($department) {
        global $wpdb;
        $table = $wpdb->prefix . 'atomic_processes';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE department = %s ORDER BY process_type",
            $department
        ));
    }
    
    /**
     * Export all docs as ZIP
     */
    public function export_all_as_zip() {
        $docs = $this->get_all_docs();
        
        if (empty($docs)) {
            return new WP_Error('no_docs', 'No documentation to export');
        }
        
        $upload = wp_upload_dir();
        $zip_dir = $upload['basedir'] . '/atomic-exports/';
        
        if (!file_exists($zip_dir)) {
            wp_mkdir_p($zip_dir);
        }
        
        $zip_file = $zip_dir . 'process-docs-' . date('Y-m-d-His') . '.zip';
        
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_not_available', 'ZipArchive extension not available on this server');
        }
        
        $zip = new ZipArchive();
        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE) {
            return new WP_Error('zip_error', 'Could not create ZIP file');
        }
        
        // Create a README
        $readme = "# Process Documentation Export\n\n";
        $readme .= "Generated by Atomic Analyzer on " . date('F j, Y') . "\n\n";
        $readme .= "## Contents\n\n";
        
        // Organize by department
        $organized = array();
        foreach ($docs as $doc) {
            if (!isset($organized[$doc->department])) {
                $organized[$doc->department] = array();
            }
            $organized[$doc->department][$doc->process_type] = $doc;
        }
        
        foreach ($organized as $dept => $dept_docs) {
            // Create department folder
            $dept_folder = ucfirst($dept) . '/';
            $readme .= "### " . ucfirst($dept) . " Department\n";
            
            foreach ($dept_docs as $type => $doc) {
                $filename = $dept_folder . $doc->process_name . '.md';
                $content = $doc->process_content;
                
                // Decode JSON if needed
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $content = $this->format_for_markdown($doc->process_type, $decoded);
                }
                
                $zip->addFromString($filename, $content);
                $readme .= "- " . $doc->process_name . "\n";
            }
            
            $readme .= "\n";
        }
        
        // Add README
        $zip->addFromString('README.md', $readme);
        
        // Add implementation guide
        $guide = $this->create_implementation_guide();
        $zip->addFromString('IMPLEMENTATION_GUIDE.md', $guide);
        
        $zip->close();
        
        $zip_url = str_replace($upload['basedir'], $upload['baseurl'], $zip_file);
        
        return $zip_url;
    }
    
    /**
     * Format array content for markdown
     */
    private function format_for_markdown($type, $data) {
        $markdown = '';
        
        switch ($type) {
            case 'checklists':
                $markdown = "# Checklists\n\n";
                foreach ($data as $frequency => $items) {
                    $markdown .= "## " . ucfirst($frequency) . " Checklist\n\n";
                    foreach ($items as $item) {
                        $markdown .= "- [ ] " . $item . "\n";
                    }
                    $markdown .= "\n";
                }
                break;
                
            case 'kpis':
                $markdown = "# Key Performance Indicators\n\n";
                $markdown .= "| KPI | Target | Frequency |\n";
                $markdown .= "|-----|--------|----------|\n";
                foreach ($data as $kpi) {
                    $markdown .= "| " . $kpi['name'] . " | " . $kpi['target'] . " | " . $kpi['frequency'] . " |\n";
                }
                break;
                
            default:
                // Generic formatting
                foreach ($data as $key => $value) {
                    $markdown .= "## " . ucfirst(str_replace('_', ' ', $key)) . "\n\n";
                    
                    if (is_array($value)) {
                        foreach ($value as $item) {
                            $markdown .= "- " . $item . "\n";
                        }
                    } else {
                        $markdown .= $value . "\n";
                    }
                    
                    $markdown .= "\n";
                }
        }
        
        return $markdown;
    }
    
    /**
     * Create implementation guide
     */
    private function create_implementation_guide() {
        $guide = "# Process Documentation Implementation Guide\n\n";
        $guide .= "## How to Use These Documents\n\n";
        
        $guide .= "### 1. Start with SOPs\n";
        $guide .= "- Read through each department's Standard Operating Procedure\n";
        $guide .= "- Identify which processes apply to your team\n";
        $guide .= "- Customize steps based on your specific tools and workflow\n\n";
        
        $guide .= "### 2. Implement Checklists\n";
        $guide .= "- Print or digitize the daily checklists for each department\n";
        $guide .= "- Assign checklist ownership to team members\n";
        $guide .= "- Review weekly and monthly checklists during team meetings\n\n";
        
        $guide .= "### 3. Track KPIs\n";
        $guide .= "- Set up tracking for each KPI in your analytics tools\n";
        $guide .= "- Create a dashboard to monitor progress\n";
        $guide .= "- Review KPIs at the specified frequency\n";
        $guide .= "- Adjust targets based on your business reality\n\n";
        
        $guide .= "### 4. Follow Process Maps\n";
        $guide .= "- Use process maps to train new team members\n";
        $guide .= "- Identify bottlenecks in your current workflow\n";
        $guide .= "- Optimize based on the ideal flow shown\n\n";
        
        $guide .= "## Implementation Timeline\n\n";
        $guide .= "**Week 1:** Read all documentation, identify quick wins\n";
        $guide .= "**Week 2:** Implement daily checklists\n";
        $guide .= "**Week 3:** Set up KPI tracking\n";
        $guide .= "**Week 4:** Train team on SOPs\n";
        $guide .= "**Month 2:** Refine and optimize processes\n";
        $guide .= "**Month 3:** Full implementation with regular reviews\n\n";
        
        $guide .= "## Tips for Success\n\n";
        $guide .= "1. **Start Small** - Don't try to implement everything at once\n";
        $guide .= "2. **Customize** - Adapt processes to fit your business\n";
        $guide .= "3. **Document Changes** - Keep SOPs updated as you improve\n";
        $guide .= "4. **Train Everyone** - Ensure all team members understand the processes\n";
        $guide .= "5. **Review Regularly** - Processes should evolve with your business\n\n";
        
        $guide .= "## Personal MBA Principles\n\n";
        $guide .= "Remember, all these processes are based on The Personal MBA framework:\n\n";
        $guide .= "- **Value Creation** (Development) - Create something people want\n";
        $guide .= "- **Marketing** - Get attention and build demand\n";
        $guide .= "- **Sales** - Turn prospects into customers\n";
        $guide .= "- **Value Delivery** - Give customers what you promised\n";
        $guide .= "- **Finance** - Track money and ensure profitability\n\n";
        
        $guide .= "---\n\n";
        $guide .= "*Generated by Atomic Analyzer - Your Personal MBA Implementation System*";
        
        return $guide;
    }
}