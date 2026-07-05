import re

with open('leads.php', 'r', encoding='utf-8') as f: html = f.read()

# Extract owner performance
owner_perf_match = re.search(r'<!-- ── BOTTOM: LEAD OWNER PERFORMANCE ─ -->\s*<section class="leads-panel-card owner-performance-card">.*?</section>', html, flags=re.DOTALL)
owner_perf = owner_perf_match.group(0) if owner_perf_match else ''
html = html.replace(owner_perf, '')

# Extract the 4 analytics cards from leads-analytics-row
analytics_row_match = re.search(r'<!-- ── CHARTS \+ FOLLOW-UP ROW ─────── -->\s*<section class="leads-analytics-row">(.*?)</section>', html, flags=re.DOTALL)
if analytics_row_match:
    inner = analytics_row_match.group(1)
    
    funnel = re.search(r'<!-- Leads Funnel -->.*?(?=<!-- Leads by Source \(Donut\) -->)', inner, flags=re.DOTALL).group(0)
    source = re.search(r'<!-- Leads by Source \(Donut\) -->.*?(?=<!-- Leads by Status -->)', inner, flags=re.DOTALL).group(0)
    status = re.search(r'<!-- Leads by Status -->.*?(?=<!-- Upcoming Follow-ups -->)', inner, flags=re.DOTALL).group(0)
    followup = re.search(r'<!-- Upcoming Follow-ups -->.*', inner, flags=re.DOTALL).group(0)
    
    owner_perf_div = owner_perf.replace('<section class="leads-panel-card owner-performance-card">', '<div class="leads-panel-card owner-performance-card">').replace('</section>', '</div>')
    owner_perf_div = re.sub(r'<!-- ── BOTTOM: LEAD OWNER PERFORMANCE ─ -->\s*', '', owner_perf_div)
    
    new_rows = f'''
    <!-- ── CHARTS ROW 1 ─────── -->
    <section class="leads-analytics-row row-3">
{funnel}
{source}
{status}
    </section>
    
    <!-- ── CHARTS ROW 2 ─────── -->
    <section class="leads-analytics-row row-2 mb-3 mt-3">
{followup}
{owner_perf_div}
    </section>
    '''
    
    html = html.replace(analytics_row_match.group(0), new_rows)

with open('leads.php', 'w', encoding='utf-8') as f: f.write(html)
print('Updated leads.php layout')
