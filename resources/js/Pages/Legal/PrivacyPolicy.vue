<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <LegalLayout>
        <Head title="Privacy Policy" />

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                    <Link href="/" class="inline-flex items-center text-sm text-emerald-400 hover:text-emerald-300 mb-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Home
                    </Link>
                    <h1 class="text-4xl font-bold text-white mb-2">Privacy Policy</h1>
                    <p class="text-slate-400">Last Updated: November 2025</p>
                </div>

                <!-- Content -->
                <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-8 shadow-xl backdrop-blur-sm space-y-6">
                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">1. Introduction</h2>
                        <p class="text-slate-300 leading-relaxed">
                            Apex Trading ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our algorithmic trading platform and services.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">2. Information We Collect</h2>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">2.1 Personal Information</h3>
                        <p class="text-slate-300 leading-relaxed mb-2">We collect information that you provide directly to us, including:</p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Name and email address</li>
                            <li>Account credentials and authentication information</li>
                            <li>Trading API keys and credentials (encrypted)</li>
                            <li>Payment and billing information</li>
                            <li>Communication preferences</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">2.2 Trading Data</h3>
                        <p class="text-slate-300 leading-relaxed mb-2">We collect and process:</p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Trading activity and transaction history</li>
                            <li>Strategy configurations and parameters</li>
                            <li>Performance metrics and analytics</li>
                            <li>Market data and order information</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">2.3 Automatically Collected Information</h3>
                        <p class="text-slate-300 leading-relaxed mb-2">When you access our platform, we automatically collect:</p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>IP address and device information</li>
                            <li>Browser type and operating system</li>
                            <li>Usage data and session information</li>
                            <li>Log data and error reports</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">3. How We Use Your Information</h2>
                        <p class="text-slate-300 leading-relaxed mb-2">We use the information we collect to:</p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Provide, operate, and maintain our trading platform</li>
                            <li>Execute trading strategies on your behalf</li>
                            <li>Process transactions and send confirmations</li>
                            <li>Improve, personalize, and expand our services</li>
                            <li>Understand and analyze platform usage</li>
                            <li>Develop new features and functionality</li>
                            <li>Communicate with you about updates and support</li>
                            <li>Detect and prevent fraud or unauthorized activity</li>
                            <li>Comply with legal obligations and regulations</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">4. Data Security</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            We implement industry-standard security measures to protect your information:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>End-to-end encryption for sensitive data</li>
                            <li>Encrypted storage of API credentials</li>
                            <li>Secure HTTPS connections</li>
                            <li>Regular security audits and monitoring</li>
                            <li>Access controls and authentication measures</li>
                            <li>Data backup and disaster recovery procedures</li>
                        </ul>
                        <p class="text-slate-400 text-sm mt-4 italic">
                            While we use commercially reasonable efforts to protect your information, no method of transmission over the Internet or electronic storage is 100% secure.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">5. Information Sharing and Disclosure</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            We do not sell, trade, or rent your personal information. We may share your information only in the following circumstances:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>With your consent:</strong> When you explicitly authorize us to share information</li>
                            <li><strong>Service providers:</strong> With third-party vendors who assist in operating our platform (e.g., Alpaca Markets for trade execution)</li>
                            <li><strong>Legal requirements:</strong> When required by law, regulation, or legal process</li>
                            <li><strong>Business transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
                            <li><strong>Protection:</strong> To protect our rights, property, or safety, or that of our users</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">6. Third-Party Services</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            Our platform integrates with third-party services, including:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Alpaca Markets:</strong> For brokerage services and trade execution</li>
                            <li><strong>Payment processors:</strong> For billing and subscription management</li>
                            <li><strong>Analytics providers:</strong> For usage tracking and performance monitoring</li>
                        </ul>
                        <p class="text-slate-300 leading-relaxed mt-4">
                            These third parties have their own privacy policies. We encourage you to review their policies before providing any information.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">7. Data Retention</h2>
                        <p class="text-slate-300 leading-relaxed">
                            We retain your information for as long as your account is active or as needed to provide services. We may retain certain information as required by law, for legitimate business purposes, or to resolve disputes. Trading records and transaction data may be retained for up to 7 years to comply with financial regulations.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">8. Your Rights and Choices</h2>
                        <p class="text-slate-300 leading-relaxed mb-2">You have the right to:</p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Access and receive a copy of your personal information</li>
                            <li>Correct inaccurate or incomplete information</li>
                            <li>Request deletion of your information (subject to legal obligations)</li>
                            <li>Object to or restrict certain processing activities</li>
                            <li>Withdraw consent where processing is based on consent</li>
                            <li>Data portability for information you provided</li>
                        </ul>
                        <p class="text-slate-300 leading-relaxed mt-4">
                            To exercise these rights, please contact us at privacy@apextrading.com
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">9. Cookies and Tracking Technologies</h2>
                        <p class="text-slate-300 leading-relaxed">
                            We use cookies and similar tracking technologies to track activity on our platform and store certain information. Cookies are files with a small amount of data that may include an anonymous unique identifier. You can instruct your browser to refuse all cookies or indicate when a cookie is being sent.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">10. International Data Transfers</h2>
                        <p class="text-slate-300 leading-relaxed">
                            Your information may be transferred to and maintained on servers located outside of your country. By using our services, you consent to the transfer of information to countries outside your country of residence, which may have different data protection rules.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">11. Children's Privacy</h2>
                        <p class="text-slate-300 leading-relaxed">
                            Our services are not directed to individuals under the age of 18. We do not knowingly collect personal information from children. If you become aware that a child has provided us with personal information, please contact us.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">12. Changes to This Privacy Policy</h2>
                        <p class="text-slate-300 leading-relaxed">
                            We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date. You are advised to review this Privacy Policy periodically for any changes.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">13. Contact Us</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            If you have any questions about this Privacy Policy, please contact us:
                        </p>
                        <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
                            <p class="text-slate-300"><strong>Email:</strong> privacy@apextrading.com</p>
                            <p class="text-slate-300"><strong>Address:</strong> Apex Trading, Legal Department</p>
                        </div>
                </section>
            </div>
        </div>
    </LegalLayout>
</template>
