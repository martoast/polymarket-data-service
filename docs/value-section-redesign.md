# Value Proposition Section Redesign Plan

## Research Findings (Linear, Vercel, Resend, Supabase, Neon)

### What makes top API landing pages premium:
1. **Bento grid** — asymmetric card sizes communicate hierarchy. Most important feature = largest card.
2. **Show don't describe** — the visual inside the card IS the explanation (code block, sparkline, tag cloud). No paragraph copy.
3. **Single accent color** — one non-neutral color (#0093fd). No rainbow.
4. **Stats as display elements** — large numbers rendered at 36–48px, not buried in text.
5. **Gradient top accent line** on cards — thin 1px gradient from transparent → brand → transparent.
6. **Minimal copy per card** — one bold headline (4–6 words) + one subtitle (under 12 words).
7. **Hover: subtle border color shift** — `hover:border-[#0093fd]/20` only, no lift/shadow.
8. **mac-style traffic lights** on code blocks signal "real terminal."

## Layout: 2-Row Asymmetric Bento (3-col grid)

```
Desktop:
┌────────────────────────────────┬─────────────────┐
│  Code card (col-span-2)        │  Oracle sparkline│
│  Shows: curl + JSON response   │  Shows: SVG line │
│  "API key in 60 seconds"       │  "Real-time feed"│
├─────────────────┬──────────────┴──────────────────┤
│  ML Features    │  CLOB Order Book (col-span-2)   │
│  Shows: tag     │  Shows: bid/ask bar depth chart  │
│  cloud of names │  "Full order book at every tick" │
└─────────────────┴────────────────────────────────┘

Mobile: all cards full width, stacked
```

## Card Details

### Card 1: Code Preview (col-span-2, tall)
- Mac-style traffic light dots + "terminal" label
- Syntax-highlighted curl command (dark bg, green auth token)
- JSON response with colored fields
- Bottom: "API key in 60 seconds" + "Start free →" link
- Top accent: brand blue gradient line

### Card 2: Oracle Ticks (col-span-1)
- SVG sparkline showing BTC-like price movement trending up
- Filled gradient area below line (brand blue, fades to transparent)
- Pulsing dot at end of line ("Live")
- Bottom: "Real-time oracle ticks" + "Every Chainlink update"

### Card 3: ML Features (col-span-1)
- Tag cloud: actual feature names from our API (twap_1m, vol_3m, momentum, clob_imbalance, oracle_dist_bp, yes_bid_open...)
- Last tag: "+ 30 more" in brand blue
- Bottom: "40+ ML features" + "Pre-computed. Ready to train."

### Card 4: CLOB Order Book (col-span-2)
- Left: Mini order book visualization (horizontal bars, yes/no bid-ask)
  - Each bar = a colored fill showing depth, bar label on left, price on right
- Right: Text — "Full CLOB order book" + "Yes/No depth at every oracle tick"
- Top accent: green gradient line

## Visual Style Notes
- Card background: `bg-[#17181c]`
- Card border: `border-[#1f2937]` → hover `border-[#0093fd]/20`
- Code bg: `bg-[#0d0e13]`
- All card borders: `rounded-2xl`
- Transition: `transition-colors duration-300`
- Section bg: subtle centered radial glow `bg-[#0093fd]/[.03]` blurred 120px

## What we remove
- The "Building it yourself" / "With Oracle API" before-after timeline (too much text)
- The three proof chips at the bottom (stats are in the cards now)
- The red/orange ambient glows (too colorful)
- The red badge "The time problem" (reframe as positive)
