# Polymarket Data Service — Pricing & Business Model Overview

---

## What We're Selling

Pre-recorded, cleaned, and labeled Polymarket binary market data served via REST API.

Every serious bot builder has to run their own 24/7 recorder for months before they have enough
data to train a model or backtest a strategy. We've already done that. Subscribers get immediate
access to historical data from day one — no waiting, no infrastructure, no gaps.

**The core value:** a single API query returns a fully labeled training dataset, ready for
pandas or sklearn. No joins, no feature engineering, no data cleaning.

---

## The Problem We Solve

To build a profitable Polymarket bot you need months of high-quality recorded data. Right now,
everyone who wants this has to:

1. Run their own 24/7 WebSocket recorder (server costs, uptime management, reconnect logic)
2. Wait weeks or months before they have enough data to even start backtesting
3. Deal with gaps, crashes, and corrupt session files
4. Write their own pipeline to fetch Gamma API resolutions and join everything together

This is a solved problem that every serious builder duplicates from scratch. It's also the
single biggest barrier to entry — most people give up before they have enough data to know if
their strategy even works.

**If this API existed when we started, we would have paid for it immediately.**

---

## Who Pays For This

**Primary — Algo traders and bot builders**
- They have real money on the line and understand the value of clean data
- A working strategy earns back the subscription cost in days
- They're already paying for servers, APIs, and data feeds — one more line item is easy

**Secondary — Researchers and quants**
- Tick-level CLOB + oracle data for Polymarket binary markets doesn't exist publicly
- Academically interesting for prediction market microstructure research

**Why they pay instead of DIY:**
- Historical data is available immediately on signup (retroactive from service launch)
- Uptime is someone else's problem
- Clean labeled data with Gamma API ground-truth outcomes is genuinely hard to maintain
- The `window_features` table alone replaces weeks of feature engineering work

---

## Pricing Tiers

| Tier | Price | History | Requests/day | Raw Export | Live Stream |
|------|-------|---------|--------------|------------|-------------|
| **Free** | $0/mo | 7 days | 100 | No | No |
| **Builder** | $29/mo | 90 days | 10,000 | No | No |
| **Pro** | $99/mo | Full history | 100,000 | Yes (CSV / SQLite / Parquet) | Yes |
| **Enterprise** | Custom | Full | Unlimited | Yes | Yes + dedicated support |

---

## Why These Numbers

**Free** drives signups and lets people evaluate data quality before committing. 7 days of
history and 100 requests per day is enough to explore the API and see what the data looks like —
but not enough to build or backtest anything serious.

**Builder at $29/mo** is priced below the cost of running your own VPS and managing a 24/7
recorder. 90 days of labeled windows is enough history to train a basic model and run
meaningful backtests. The upgrade pressure is natural: once your strategy is working, you want
more history.

**Pro at $99/mo** is for serious traders and teams. The unlocks are:
- **Full history** — everything we've recorded since launch, queryable in seconds
- **Raw exports** — download `window_features` as SQLite, CSV, or Parquet and work offline
- **Server-side backtesting** — send strategy params, get backtest results back without downloading data
- **Live stream** — real-time oracle ticks, CLOB snapshots, and signals via WebSocket for bot deployment

$99/mo is easily justified with a $1,000+ bankroll. Two good trading days pays for the month.

---

## The Crown Jewel: `window_features`

The single most valuable thing we serve is the `window_features` table — a pre-computed,
pre-joined feature vector for every resolved binary market window. No aggregations needed,
no joins required, resolution ground truth already included.

A Pro subscriber can run this and get a training-ready dataset:

```sql
SELECT
    oracle_dist_bp_at_2m,
    oracle_dist_bp_at_1m,
    oracle_range_5m_bp,
    oracle_crossings_last_5m,
    clob_yes_ask_final,
    hour_utc,
    outcome   -- YES or NO
FROM window_features
WHERE asset = 'BTC'
  AND has_full_oracle_coverage = TRUE
  AND recording_gap = FALSE
ORDER BY open_ts;
```

Zero joins. Zero aggregation. One query = training dataset.

The alternative is six months of self-recorded data plus a week of feature engineering work.

---

## Revenue Model

Freemium → monthly subscription. Marginal cost per additional subscriber is near zero —
the recording infrastructure runs regardless, the database just serves more queries.

**Conservative targets:**
- 100 free users, 10% convert to Builder = $290/mo
- 200 Builder users, 20% convert to Pro = $1,980/mo
- 200 Builder + 50 Pro = **$10,800/mo**

**12-month target:** 500 paid subscribers = $14,500–$49,500/mo ARR depending on tier mix.

---

## Competitive Moat

1. **Historical depth** — every day running adds to a dataset competitors can't instantly replicate
2. **Data quality** — Gamma API ground-truth outcomes, not oracle-inferred (~2.2% error rate eliminated)
3. **Market-specific** — purpose-built for Polymarket binary markets, not a generic price feed
4. **Signal layer** — not just raw data; strategy-ready derived features (oracle distance, vol guard values, break price crossings)
5. **Trust** — built by someone with real trading results proving the data quality is good enough to trade with

---

## Note on Free Tier Design

The free tier's 7-day history window means free users are mostly seeing recent unresolved markets.
Consider whether "last 50 resolved windows" is a better free tier than a strict 7-day cutoff —
resolved windows with labeled outcomes are the actual product, and letting free users see real
labeled data builds confidence faster than a time-window restriction.
