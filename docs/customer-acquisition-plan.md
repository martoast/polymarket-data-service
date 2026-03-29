# Customer Acquisition Plan — Polymarket Data API

## The Angle That Wins

**Competitors (PolyTest, PolyBackTest, PolymarketData.co) all share the same blind spot:**
None of them capture Chainlink RTDS oracle tick data. None ship pre-computed ML features.
These are our two unfair advantages. Every piece of outreach should lead with one of these.

---

## Priority 1 — Communities (Highest ROI, Do First)

### Polymarket Discord
**Link:** `discord.com/invite/polymarket` — 95,000+ members, `#devs` channel
**What to post:**
> "Hey — built a REST API that solves the `{'history': []}` problem on resolved markets.
> We've been recording every Chainlink oracle tick and CLOB snapshot since early 2026.
> 40+ ML features pre-computed per window. Free tier, no card.
> [link] — happy to answer any questions here."

Keep it factual and builder-to-builder. No marketing speak.

---

### Reddit

| Subreddit | Members | Angle |
|---|---|---|
| r/polymarket | 50K+ | Direct audience — post about the oracle tick data problem |
| r/algotrading | Large | Frame Polymarket as a new frontier for systematic trading |
| r/quant | Professional | 40+ ML features, eliminates feature engineering grind |
| r/PredictionMarkets | Broad | Multi-platform prediction market data |
| r/MachineLearning | Huge | Binary classification dataset with real financial labels |

**Best post angle for r/algotrading:**
Title: *"I built a data API for Polymarket after spending weeks trying to scrape oracle ticks myself"*
Content: Tell the founder story — the pain of building the recorder, what it captures, how it solves the `py-clob-client` empty history bug, free tier CTA at the end.

**Best post angle for r/MachineLearning:**
Title: *"Polymarket UP/DOWN markets as a binary classification dataset — 188K+ labeled oracle tick sequences"*
Angle: Real financial labels, real stakes, millisecond timestamps. Novel dataset for prediction research.

---

## Priority 2 — Twitter/X Strategy

### Accounts to Engage / Tag

| Account | Why |
|---|---|
| @PolymarketBuild | Running $2.5M grants program, actively amplifies builders — tagging them in a launch post could get reshared to their entire builder audience |
| @Polymarket | Main account, 600M+ monthly X users via X partnership |
| @PolymarketTrade | Trader-facing, shares community tools |

**Join and post in the Polymarket X Community:**
`https://x.com/i/communities/1909347893179212063`

---

### X Post Templates (copy/paste ready)

**Post 1 — The Pain Point (lead with this one)**
```
The Polymarket official API returns {'history': []} for resolved markets.

We've been recording every Chainlink oracle tick and CLOB snapshot
since early 2026.

40+ ML features pre-computed per window. Free tier. No card.

→ polymarketdata.io
```

**Post 2 — The Bot Edge**
```
14 of the 20 most profitable Polymarket wallets are bots.

The ones losing are probably backtesting on vibes.

Here's how to backtest on actual oracle tick data from Chainlink RTDS:
[link to docs]
```

**Post 3 — The Feature Engineering Hook**
```
We pre-compute 40+ ML features per Polymarket window:

twap_1m, twap_5m, vol_1m, vol_3m, momentum,
clob_imbalance, oracle_dist_bp, yes_bid_open,
spread_open, price_velocity...

The same features quants spend weeks building — available via REST.

Free tier: polymarketdata.io
```

**Post 4 — The Research Angle**
```
Polymarket 5-min UP/DOWN markets are underrated as ML datasets:

- Binary labels (YES/NO) with real financial stakes
- Chainlink oracle ticks at millisecond precision
- Full CLOB order book at every tick
- 40+ pre-computed features per resolved window

188K+ records. Free tier available.
→ polymarketdata.io
```

**Post 5 — Competitor Gap (subtle)**
```
PolyTest and PolyBackTest cap history at 14–31 days on paid tiers.

If your backtest needs more than a month of Polymarket data,
you need something else.

→ polymarketdata.io (free tier, no limits on history for Pro)
```

**Posting cadence:** 3–4x per week to start. Engage in replies on any Polymarket-related thread. Retweet/comment on @PolymarketBuild posts to get on their radar before pitching.

---

## Priority 3 — GitHub

### Highest-ROI Repos to Engage

**1. warproxxx/poly_data (669 stars, 157 forks)**
`https://github.com/warproxxx/poly_data`
Their README warns users it takes 2+ days to collect initial data via Goldsky subgraph scraping.
**Action:** Open a GitHub issue titled *"Alternative data source for faster initial collection"* — show a before/after code snippet. 157 forks = 157 developers who hit this exact problem.

**2. Polymarket/agents (2,700 stars)**
`https://github.com/Polymarket/agents`
The repo is explicitly modular and extensible for data sourcing.
**Action:** Open a PR adding a `PolymarketDataAPIClient` data module. Getting merged here is a direct pipeline to 2,700 starred developers.

**3. Polymarket/py-clob-client**
Issues [#189](https://github.com/Polymarket/py-clob-client/issues/189) and [#216](https://github.com/Polymarket/py-clob-client/issues/216) are the exact bugs we solve (empty history for resolved markets).
**Action:** Comment on any new issues about historical data gaps with a factual pointer to the API.

**4. SII-WANGZJ/Polymarket_data**
Their repo is a static snapshot of 1.1B records.
**Action:** Comment offering the API as a continuously-updated live version.

---

## Priority 4 — Telegram

| Community | Angle |
|---|---|
| @PolyAlertHubBot community | Users are already building on top of Polymarket signals |
| @PolySpy_bot community | Market discovery users need historical data for backtesting |
| @YNSignals | Alpha signals channel — data-hungry audience |

Post a brief builder intro in these communities pointing to the free tier.

---

## Priority 5 — Direct Outreach

### Researchers / Builders to Email or DM

- **Navnoor Bawa** (built a quantitative prediction system for Polymarket, documented on Substack at `navnoorbawa.substack.com`) — DM on X or LinkedIn. Offer free Pro access in exchange for a mention or integration example.
- **warproxxx** (poly_data maintainer) — Open source contributor, likely receptive to a free tier offer and would mention it in the README.
- The **Polycule team** (`polycule.trade`) — They need historical performance data for their community features. Potential partnership or integration.

---

## Priority 6 — Content Marketing

### Blog Posts to Write (SEO + sharable on Reddit/X)

1. **"How to get Polymarket historical CLOB data (the official API won't help you)"**
   - Hit the py-clob-client bug, explain why, show our API as the solution
   - Target: r/polymarket, r/algotrading
   - Primary keywords: Polymarket historical data, Polymarket CLOB API

2. **"Building a Polymarket trading bot: what data you actually need"**
   - Cover oracle ticks, CLOB depth, feature engineering
   - Target: r/algotrading, X threads
   - Primary keywords: Polymarket trading bot data

3. **"Polymarket Chainlink oracle ticks explained: the signal most bots miss"**
   - Technical deep-dive on the RTDS feed
   - Target: r/quant, X
   - Primary keywords: Polymarket oracle data, Chainlink oracle tick data

4. **"Backtesting Polymarket strategies: a practical guide with real data"**
   - Step-by-step Python notebook using our API
   - Target: r/algotrading, r/MachineLearning, GitHub
   - Primary keywords: Polymarket backtesting API

---

## Competitor Weaknesses to Exploit

| Competitor | Weakness | Our Angle |
|---|---|---|
| PolymarketData.co | No oracle ticks, no ML features, $60/mo jump from free | We have Chainlink RTDS + 40+ features. Gentler pricing. |
| PolyTest.io | Only BTC/ETH/SOL, 14 days history on paid | Broader markets, deeper history |
| PolyBackTest.com | Only BTC/ETH/SOL, 31 days max, free tier is useless | History depth + oracle data |
| All three | None capture Chainlink RTDS oracle ticks | This is the signal that powers the most profitable bots |

---

## 30-Day Action Checklist

### Week 1 — Plant seeds
- [ ] Post in Polymarket Discord #devs
- [ ] Post Hook #1 on X, tag @PolymarketBuild
- [ ] Open issue on warproxxx/poly_data
- [ ] Comment on py-clob-client issues #189 and #216

### Week 2 — Reddit push
- [ ] Post in r/algotrading (founder story angle)
- [ ] Post in r/polymarket (pain point angle)
- [ ] Post Hook #2 and #3 on X

### Week 3 — GitHub contribution
- [ ] Open PR on Polymarket/agents with data module
- [ ] DM Navnoor Bawa on X (offer free Pro access)
- [ ] Post Hook #4 on X (ML research angle)

### Week 4 — Content
- [ ] Publish blog post #1 on the site
- [ ] Share blog post on Reddit + X
- [ ] Telegram community outreach
- [ ] Post Hook #5 on X (competitor gap)

---

## Notes
- Always be factual and builder-to-builder. Never sound like marketing.
- Lead with the pain point (empty history bug), not features.
- Free tier is the conversion hook — lead with it in every CTA.
- The oracle tick differentiator is the hardest to copy — hammer it everywhere.
