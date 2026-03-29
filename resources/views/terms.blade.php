@extends('layouts.app')

@section('title', 'Terms of Service')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <h1 class="text-2xl font-bold text-[#e5e5e5] mb-2">Terms of Service</h1>
    <p class="text-sm text-[#697d91] mb-10">Last updated: March 29, 2026</p>

    <div class="prose prose-invert max-w-none space-y-8 text-[#697d91] text-sm leading-relaxed">

        <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4 text-amber-400/80 text-xs">
            <strong class="text-amber-400">Independent Service Notice:</strong> Polymarket Data API is an independent data service and is not affiliated with, endorsed by, or associated with Polymarket or any of its affiliates. "Polymarket" is a trademark of its respective owner. We are a third-party data aggregator providing market data through publicly available APIs.
        </div>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">1. Acceptance of Terms</h2>
            <p>By accessing or using the Polymarket Data API service ("Service"), you agree to be bound by these Terms of Service. If you do not agree to these terms, do not use the Service.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">2. Description of Service</h2>
            <p>Polymarket Data API provides programmatic access to aggregated market data including oracle price ticks, CLOB order book snapshots, and pre-computed market features derived from publicly available data sources. The Service is intended for research, algorithmic trading, and data analysis purposes.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">3. No Affiliation with Polymarket</h2>
            <p>This Service is an independent, third-party data aggregator. We are not affiliated with, sponsored by, endorsed by, or in any way officially connected with Polymarket or its parent companies, subsidiaries, or affiliates. Data provided by this Service is sourced from publicly accessible APIs and is provided for informational purposes only.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">4. No Financial Advice</h2>
            <p>Nothing provided through this Service constitutes financial, investment, legal, or tax advice. All data is provided for informational and research purposes only. You are solely responsible for any decisions made based on data obtained through the Service. Past market data is not indicative of future results.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">5. Acceptable Use</h2>
            <p>You agree not to:</p>
            <ul class="list-disc list-inside space-y-1 mt-2 ml-2">
                <li>Resell, sublicense, or redistribute raw API data without prior written consent</li>
                <li>Use the Service to engage in market manipulation or any illegal activity</li>
                <li>Attempt to circumvent rate limits, access controls, or other technical restrictions</li>
                <li>Reverse engineer or attempt to extract proprietary logic from the Service</li>
                <li>Use the Service in a manner that violates applicable laws or regulations</li>
            </ul>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">6. Data Accuracy</h2>
            <p>We strive to provide accurate and timely data, but we make no representations or warranties regarding the completeness, accuracy, or timeliness of any data provided. Data may be delayed, incomplete, or contain errors. You should not rely solely on this Service for time-sensitive trading decisions.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">7. API Key & Account</h2>
            <p>Your API key is personal and must not be shared or exposed publicly. You are responsible for all activity that occurs under your account. We reserve the right to suspend or terminate accounts that violate these terms or abuse the Service.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">8. Service Availability</h2>
            <p>We do not guarantee uninterrupted or error-free access to the Service. We may modify, suspend, or discontinue the Service at any time without prior notice. We are not liable for any losses resulting from service interruptions.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">9. Limitation of Liability</h2>
            <p>To the maximum extent permitted by law, Polymarket Data API and its operators shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or business opportunities, arising from your use of or inability to use the Service.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">10. Changes to Terms</h2>
            <p>We reserve the right to update these Terms at any time. Continued use of the Service after changes constitutes acceptance of the revised Terms. We will update the "Last updated" date at the top of this page when changes are made.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">11. Governing Law</h2>
            <p>These Terms are governed by and construed in accordance with applicable law. Any disputes arising from these Terms or your use of the Service shall be resolved through good-faith negotiation where possible.</p>
        </section>

    </div>

    <div class="mt-10 pt-6 border-t border-[#1f2937] flex gap-6 text-xs text-[#697d91]">
        <a href="{{ route('privacy') }}" class="hover:text-[#e5e5e5] transition-colors">Privacy Policy</a>
        <a href="{{ route('home') }}" class="hover:text-[#e5e5e5] transition-colors">← Back to home</a>
    </div>

</div>
@endsection
