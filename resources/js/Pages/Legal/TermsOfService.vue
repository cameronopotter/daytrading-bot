<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <LegalLayout>
        <Head title="Terms of Service" />

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                    <Link href="/" class="inline-flex items-center text-sm text-emerald-400 hover:text-emerald-300 mb-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Home
                    </Link>
                    <h1 class="text-4xl font-bold text-white mb-2">Terms of Service</h1>
                    <p class="text-slate-400">Last Updated: November 2025</p>
                </div>

                <!-- Content -->
                <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-8 shadow-xl backdrop-blur-sm space-y-6">
                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">1. Acceptance of Terms</h2>
                        <p class="text-slate-300 leading-relaxed">
                            By accessing and using the Apex Trading platform ("Platform"), you accept and agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you may not access or use the Platform. These Terms constitute a legally binding agreement between you and Apex Trading.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">2. Eligibility</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            To use the Platform, you must:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Be at least 18 years of age or the age of majority in your jurisdiction</li>
                            <li>Have the legal capacity to enter into binding contracts</li>
                            <li>Not be prohibited from using the Platform under applicable laws</li>
                            <li>Comply with all applicable securities, commodities, and financial regulations</li>
                            <li>Maintain an account with a supported brokerage partner (e.g., Alpaca Markets)</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">3. Account Registration and Security</h2>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">3.1 Account Creation</h3>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            You must provide accurate, current, and complete information during registration. You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.
                        </p>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3">3.2 API Credentials</h3>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            You are solely responsible for the security and management of your brokerage API credentials. We encrypt and securely store your credentials but cannot guarantee absolute security. You should:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Never share your API credentials with others</li>
                            <li>Use strong, unique passwords</li>
                            <li>Monitor your account for unauthorized activity</li>
                            <li>Immediately notify us of any security breach</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">4. Platform Services</h2>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">4.1 Automated Trading</h3>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            The Platform provides algorithmic trading tools and automation. By using these services, you acknowledge that:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Trading involves substantial risk of loss</li>
                            <li>Past performance does not guarantee future results</li>
                            <li>You are responsible for all trading decisions and outcomes</li>
                            <li>Automated strategies may not perform as expected</li>
                            <li>Market conditions can change rapidly</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">4.2 Service Availability</h3>
                        <p class="text-slate-300 leading-relaxed">
                            We strive to provide continuous service but do not guarantee uninterrupted access. The Platform may be unavailable due to maintenance, updates, technical issues, or circumstances beyond our control. We are not liable for any losses resulting from service interruptions.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">5. User Responsibilities</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">You agree to:</p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Use the Platform in compliance with all applicable laws and regulations</li>
                            <li>Not engage in market manipulation or illegal trading practices</li>
                            <li>Not attempt to circumvent security measures or gain unauthorized access</li>
                            <li>Not use the Platform for any unlawful purpose</li>
                            <li>Monitor your trading activity and account balance</li>
                            <li>Maintain adequate capital and manage risk appropriately</li>
                            <li>Pay all applicable fees and charges</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">6. Fees and Payments</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            Use of the Platform may be subject to subscription fees, transaction fees, or other charges. You agree to pay all applicable fees as described in our pricing schedule. Fees are non-refundable except as required by law. We reserve the right to change fees with notice.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">7. Intellectual Property</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            All content, features, functionality, software, and code on the Platform are owned by Apex Trading or its licensors and are protected by copyright, trademark, and other intellectual property laws. You may not:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Copy, modify, or distribute Platform content without permission</li>
                            <li>Reverse engineer or decompile any Platform software</li>
                            <li>Remove or alter proprietary notices</li>
                            <li>Use our trademarks without authorization</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">8. Disclaimers and Limitations of Liability</h2>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">8.1 No Investment Advice</h3>
                        <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4 mb-4">
                            <p class="text-slate-300 leading-relaxed">
                                <strong class="text-amber-400">IMPORTANT:</strong> The Platform is a technology tool only. We do not provide investment advice, recommendations, or financial planning services. We are not registered as investment advisors, broker-dealers, or financial planners. You should consult with qualified financial professionals before making investment decisions.
                            </p>
                        </div>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3">8.2 "As Is" Service</h3>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            THE PLATFORM IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, OR NON-INFRINGEMENT.
                        </p>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3">8.3 Limitation of Liability</h3>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            TO THE MAXIMUM EXTENT PERMITTED BY LAW, APEX TRADING SHALL NOT BE LIABLE FOR:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Any trading losses or investment losses</li>
                            <li>Indirect, incidental, consequential, or punitive damages</li>
                            <li>Loss of profits, revenue, data, or business opportunities</li>
                            <li>Damages resulting from service interruptions or errors</li>
                            <li>Actions or omissions of third-party service providers</li>
                        </ul>
                        <p class="text-slate-300 leading-relaxed mt-4">
                            Our total liability shall not exceed the fees you paid in the 12 months preceding the claim.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">9. Indemnification</h2>
                        <p class="text-slate-300 leading-relaxed">
                            You agree to indemnify and hold harmless Apex Trading, its affiliates, officers, directors, employees, and agents from any claims, damages, losses, liabilities, and expenses (including legal fees) arising from your use of the Platform, violation of these Terms, or violation of any rights of third parties.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">10. Third-Party Services</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            The Platform integrates with third-party services (e.g., Alpaca Markets). Your use of these services is subject to their respective terms and conditions. We are not responsible for the performance, availability, or actions of third-party services.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">11. Termination</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            We may suspend or terminate your access to the Platform at any time for any reason, including violation of these Terms. You may terminate your account at any time by contacting us. Upon termination:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Your right to use the Platform immediately ceases</li>
                            <li>We may delete your account and data (subject to legal retention requirements)</li>
                            <li>All provisions that should survive termination remain in effect</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">12. Modifications to Terms</h2>
                        <p class="text-slate-300 leading-relaxed">
                            We reserve the right to modify these Terms at any time. We will notify you of material changes via email or Platform notification. Continued use of the Platform after changes constitutes acceptance of the modified Terms.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">13. Governing Law and Dispute Resolution</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            These Terms are governed by the laws of [Your Jurisdiction], without regard to conflict of law principles. Any disputes shall be resolved through binding arbitration in accordance with the rules of the American Arbitration Association, except where prohibited by law.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">14. Miscellaneous</h2>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Entire Agreement:</strong> These Terms constitute the entire agreement between you and Apex Trading</li>
                            <li><strong>Severability:</strong> If any provision is invalid, the remaining provisions remain in effect</li>
                            <li><strong>Waiver:</strong> Failure to enforce any right does not constitute a waiver</li>
                            <li><strong>Assignment:</strong> You may not assign these Terms; we may assign them without restriction</li>
                            <li><strong>Force Majeure:</strong> We are not liable for failures due to circumstances beyond our control</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">15. Contact Information</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            For questions about these Terms, contact us:
                        </p>
                        <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
                            <p class="text-slate-300"><strong>Email:</strong> legal@apextrading.com</p>
                            <p class="text-slate-300"><strong>Address:</strong> Apex Trading, Legal Department</p>
                        </div>
                    </section>

                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mt-8">
                        <p class="text-sm text-emerald-300">
                            By using the Apex Trading platform, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.
                        </p>
                </div>
            </div>
        </div>
    </LegalLayout>
</template>
