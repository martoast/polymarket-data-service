@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <h1 class="text-2xl font-bold text-[#e5e5e5] mb-2">Privacy Policy</h1>
    <p class="text-sm text-[#697d91] mb-10">Last updated: March 29, 2026</p>

    <div class="prose prose-invert max-w-none space-y-8 text-[#697d91] text-sm leading-relaxed">

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">1. What We Collect</h2>
            <p>When you create an account and use Polymarket Data API, we collect:</p>
            <ul class="list-disc list-inside space-y-1 mt-2 ml-2">
                <li><strong class="text-[#e5e5e5]">Account information</strong> — your name and email address</li>
                <li><strong class="text-[#e5e5e5]">API usage</strong> — request timestamps, endpoints called, HTTP status codes, and IP address</li>
                <li><strong class="text-[#e5e5e5]">Billing information</strong> — handled entirely by Stripe; we never see or store your card details</li>
                <li><strong class="text-[#e5e5e5]">Session data</strong> — standard browser session cookies for authentication</li>
            </ul>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">2. How We Use Your Data</h2>
            <p>We use collected data to:</p>
            <ul class="list-disc list-inside space-y-1 mt-2 ml-2">
                <li>Provide and operate the API service</li>
                <li>Enforce rate limits and tier-based access controls</li>
                <li>Send transactional emails (email verification, billing receipts)</li>
                <li>Monitor for abuse and ensure service stability</li>
                <li>Improve the Service based on usage patterns</li>
            </ul>
            <p class="mt-3">We do not sell, rent, or share your personal data with third parties for marketing purposes.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">3. Third-Party Services</h2>
            <p>We use the following third-party services to operate:</p>
            <ul class="list-disc list-inside space-y-1 mt-2 ml-2">
                <li><strong class="text-[#e5e5e5]">Stripe</strong> — payment processing. Your payment data is governed by <a href="https://stripe.com/privacy" target="_blank" class="text-[#0093fd] hover:underline">Stripe's Privacy Policy</a>.</li>
                <li><strong class="text-[#e5e5e5]">Mailgun</strong> — transactional email delivery</li>
                <li><strong class="text-[#e5e5e5]">Cloudflare</strong> — DNS and network security. Traffic passes through Cloudflare's network.</li>
            </ul>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">4. Data Storage & Security</h2>
            <p>Your data is stored on self-hosted infrastructure. We implement standard security measures including encrypted connections (HTTPS), hashed passwords, and access controls. No system is 100% secure — use the Service accordingly.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">5. Data Retention</h2>
            <p>We retain your account data for as long as your account is active. API request logs are retained for operational and abuse-prevention purposes. You may request deletion of your account and associated personal data by contacting us.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">6. Cookies</h2>
            <p>We use session cookies strictly necessary for authentication. We do not use tracking cookies, advertising cookies, or third-party analytics. You can disable cookies in your browser, but doing so will prevent you from logging in.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">7. Your Rights</h2>
            <p>You have the right to access, correct, or request deletion of your personal data. To exercise these rights, contact us directly. We will respond within a reasonable timeframe.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">8. No Affiliation</h2>
            <p>This service is not affiliated with Polymarket. Data sourced from third-party APIs is used in accordance with those services' terms. We are an independent data aggregator.</p>
        </section>

        <section>
            <h2 class="text-base font-semibold text-[#e5e5e5] mb-3">9. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will update the "Last updated" date when changes are made. Continued use of the Service constitutes acceptance of the updated policy.</p>
        </section>

    </div>

    <div class="mt-10 pt-6 border-t border-[#1f2937] flex gap-6 text-xs text-[#697d91]">
        <a href="{{ route('terms') }}" class="hover:text-[#e5e5e5] transition-colors">Terms of Service</a>
        <a href="{{ route('home') }}" class="hover:text-[#e5e5e5] transition-colors">← Back to home</a>
    </div>

</div>
@endsection
