# Landing Page Design Plan
## Polymarket Data API — Redesign Brief

### Research Summary
After studying Resend, Upstash, Neon, Turso, Replicate, and Algolia, the patterns that separate
professional API landing pages from amateur ones are:

1. **Code as hero** — Show working code immediately, with a language switcher
2. **Specific numbers** — "188,203 oracle ticks recorded" beats "millions of data points"
3. **Problem-first language** — Lead with the trader's problem, not our features
4. **Impact metrics** — What the data enables, not what it is
5. **Founder/expert credibility** — If you know the space, show it
6. **"What you won't pay for"** framing — Reframe pricing around what competitors charge
7. **Dark + accent glow** — Premium dark UI with ambient light effects
8. **Animated trust signals** — Live counters, pulsing status dots, real-time feel
9. **Multiple CTAs** — Start Free / View Docs / See Pricing serve different buyer stages
10. **Feature tabs** — Don't list features, let users navigate to what they care about

---

## Target Personas

### Primary: Algo Trader / Quant
- Wants: clean data, reliable uptime, known schema, fast endpoints
- Fears: data gaps, wrong timestamps, opaque pricing
- Language: "training-ready", "feature engineered", "millisecond timestamps"

### Secondary: ML Engineer
- Wants: bulk data access, export formats, backtesting capability
- Fears: rate limits too low, no history, can't afford Pro
- Language: "40+ pre-computed features", "CSV/SQLite export", "full history"

---

## Design Tokens (keeping existing)
- Background: `#0a0b10`
- Surface: `#17181c`
- Surface 2: `#1e2428`
- Border: `#1f2937`
- Border 2: `#2e3841`
- Brand blue: `#0093fd`
- Muted text: `#697d91`
- Body text: `#8a9ab0`
- Heading: `#e5e5e5` / `#ffffff`
- Green: `#26a05e`
- Font: Inter (already loaded)

## New Visual Techniques to Add
- Animated grid background (subtle, CSS only)
- Ambient glow blobs (CSS radial gradients, blurred)
- Floating code cards with colored syntax
- Live counter animation (Intersection Observer)
- Tab switching for code examples (Alpine.js)
- Testimonial cards with avatar initials
- Comparison table with icon checkmarks
- Sticky section headers on data sources
- FAQ accordion (already done, keep)

---

## Page Structure

### 1. NAV (enhance existing)
- Logo + wordmark left
- Docs / Pricing / Sign in right
- "Get API Key" CTA button (brand blue, small)
- Sticky on scroll with backdrop blur

### 2. HERO
**Headline:** "The data infrastructure for Polymarket traders"
**Subheadline:** "Millisecond oracle ticks, full CLOB order book, and 40+ ML features — all queryable via REST from day one. No scraping. No guessing. No data gaps."
**CTA:** [Start for free →] [View docs]
**Below CTAs:** Three inline trust chips: ✓ No credit card · ✓ API key in 60 seconds · ✓ Any language

**Visual:** Two-column. Right side = tabbed code preview with Python / cURL / JS tabs.
Code shows a real API call with colored JSON response. Floating status chip shows "● Live" + last oracle timestamp.

**Background:** Subtle dot-grid with blue glow centered behind headline.

### 3. LIVE STATS BAR
Full-width dark bar. Animated counters (count up on scroll into view).
- Oracle ticks recorded: **188,203**
- CLOB snapshots: **3.2M+**
- Resolved windows: **2,734**
- Uptime: **99.9%**

Each with a tiny label below and subtle separator lines.

### 4. MARQUEE / SCROLLING TRUST BAR
"Used by traders building on →" followed by scrolling tech logos (Python, pandas, NumPy, scikit-learn, Jupyter, Node.js, R) — shows the stack they're already using.

### 5. PROBLEM → SOLUTION SECTION
Three-column layout. Each column = a problem our users face.

**Column 1 — The Data Problem**
Icon: Warning triangle
Title: "Polymarket doesn't have an API for historical data"
Body: "The official API gives you live markets — not ticks, not snapshots, not features. Building your own recorder takes months."
→ Solution badge: "We record it for you. From day one."

**Column 2 — The Feature Engineering Problem**
Icon: Code brackets
Title: "Raw prices aren't enough to build a model"
Body: "You need TWAPs, momentum, CLOB imbalance, volatility — computed across the exact window each market was open."
→ Solution badge: "40+ features. Pre-computed. Per window."

**Column 3 — The Latency Problem**
Icon: Clock
Title: "By the time you scrape it, the signal is gone"
Body: "Oracle updates every ~3 seconds. A scraper that runs every minute misses 95% of the price action."
→ Solution badge: "Direct WebSocket. ~3s cadence."

### 6. FEATURE DEEP DIVE (tabbed)
Tab navigation: [Windows & Features] [Oracle Ticks] [CLOB Snapshots] [Candles]

Each tab has:
- Left: Description, field list as code-style tags, what it's useful for
- Right: A mini JSON preview of that endpoint's response

### 7. HOW IT WORKS
Three numbered steps with connecting line (already done, keep and refine).

### 8. LIVE DATA PREVIEW
A dark card that shows a mini "live feed" of oracle data updating. Uses realistic static data styled to look live. Small table with asset / price / timestamp / window. Shows what the data looks like in practice.

### 9. TESTIMONIAL / SOCIAL PROOF
Two or three quote cards. Since we don't have real testimonials yet, frame them as:
- An internal quote about the product's precision
- Or a placeholder styled block with "[Your quote here]" — but make the card design perfect so it looks intentional

Actually: use a "why we built this" block instead. First-person founder story explaining the pain point is more trustworthy than fake testimonials.

### 10. PRICING
Three-column (keep), but improve:
- Add "Most popular" on Builder with a gradient border
- Add feature comparison table below
- Add "Questions? Email us →" link

### 11. FAQ (keep existing, refine copy)

### 12. CTA SECTION
Big centered headline, dual buttons, ambient glow, disclaimer text.

---

## Copy Guidelines

### Voice
- Direct, technical, no fluff
- Speak to the trader who has been burned by bad data
- Use specific numbers everywhere
- Short sentences

### Headlines
- Use title case for section headers
- Use sentence case for body headlines
- Keep headlines under 8 words when possible

### Avoid
- "Powerful", "robust", "seamless", "next-generation"
- "We believe...", "Our mission is..."
- Vague metrics like "millions" or "thousands"
- Passive voice

### Use instead
- Specific numbers: "188,203 ticks", "~3s cadence", "40+ features"
- Active voice: "We record", "You get", "Query it"
- Problem language: "scraping", "data gaps", "missed signals"
- Trader language: "backtesting", "TWAP", "CLOB", "oracle"

---

## Implementation Notes

### Animations
- Counter animation: CSS + IntersectionObserver, count up from 0 on first viewport entry
- Glow: CSS `@keyframes` pulse on the hero glow element (very slow, 8s)
- Marquee: CSS `animation: marquee 30s linear infinite` on inner div
- Code tabs: Alpine.js `x-show` + `x-transition`
- FAQ: Alpine.js x-collapse (already in layout)

### Performance
- No images — everything SVG or CSS
- All animations CSS-only where possible
- Alpine.js already loaded
- No extra JS libraries needed

### Responsive
- Mobile: Stack hero columns, hide code preview or show below copy
- Tablet: 2-column feature cards
- Desktop: Full layout as described

---

## Files to Edit
- `resources/views/landing.blade.php` — full rewrite
- `resources/views/layouts/app.blade.php` — nav enhancements
