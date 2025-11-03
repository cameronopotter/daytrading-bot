<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <LegalLayout>
        <Head title="Risk Disclosure" />

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                    <Link href="/" class="inline-flex items-center text-sm text-emerald-400 hover:text-emerald-300 mb-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Home
                    </Link>
                    <h1 class="text-4xl font-bold text-white mb-2">Risk Disclosure Statement</h1>
                    <p class="text-slate-400">Last Updated: November 2025</p>
                </div>

                <!-- Important Warning Box -->
                <div class="bg-red-500/10 border-2 border-red-500/50 rounded-xl p-6 mb-8">
                    <div class="flex items-start gap-4">
                        <svg class="w-8 h-8 text-red-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h2 class="text-xl font-bold text-red-400 mb-2">IMPORTANT RISK WARNING</h2>
                            <p class="text-slate-300 leading-relaxed">
                                Trading in financial instruments involves substantial risk of loss and is not suitable for all investors.
                                The high degree of leverage that is often obtainable can work against you as well as for you.
                                Before deciding to trade, you should carefully consider your investment objectives, level of experience,
                                and risk appetite. <strong class="text-red-400">You could sustain a loss of some or all of your initial investment.</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-slate-900/50 border border-slate-800 rounded-xl p-8 shadow-xl backdrop-blur-sm space-y-6">
                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">1. General Risk Warning</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            This Risk Disclosure Statement provides important information about the risks associated with trading financial instruments
                            and using automated trading systems. You should read this document carefully before using the Apex Trading platform.
                        </p>
                        <p class="text-slate-300 leading-relaxed">
                            <strong class="text-emerald-400">You should not invest money that you cannot afford to lose.</strong>
                            Trading may not be suitable for all users of this platform and service. Please ensure that you fully understand
                            the risks involved.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">2. Market Risk</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            Financial markets are inherently volatile and unpredictable. Market prices can move rapidly and substantially.
                            Key market risks include:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Price Volatility:</strong> Asset prices can change rapidly in very short time periods</li>
                            <li><strong>Liquidity Risk:</strong> You may not be able to exit positions at desired prices</li>
                            <li><strong>Gap Risk:</strong> Markets can open significantly different from prior closing prices</li>
                            <li><strong>Systematic Risk:</strong> Broad market movements affect all securities</li>
                            <li><strong>Event Risk:</strong> Unexpected news or events can cause severe price movements</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">3. Leverage and Margin Risk</h2>
                        <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4 mb-4">
                            <p class="text-slate-300 leading-relaxed">
                                <strong class="text-amber-400">Leverage magnifies both gains and losses.</strong>
                                Even small adverse price movements can result in substantial losses that exceed your initial investment.
                            </p>
                        </div>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Leveraged positions require continuous margin maintenance</li>
                            <li>Margin calls may force you to liquidate positions at unfavorable prices</li>
                            <li>In extreme cases, you may lose more than your initial deposit</li>
                            <li>Leverage increases the risk of rapid and substantial losses</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">4. Automated Trading Risks</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            Automated trading systems and algorithmic strategies carry specific risks:
                        </p>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">4.1 System and Technology Risks</h3>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Technical Failures:</strong> Software bugs, server outages, or connectivity issues can prevent order execution</li>
                            <li><strong>Execution Risk:</strong> Orders may not execute at expected prices or times</li>
                            <li><strong>Slippage:</strong> Difference between expected and actual execution prices</li>
                            <li><strong>Latency:</strong> Delays in data transmission or order routing</li>
                            <li><strong>API Failures:</strong> Third-party API services may become unavailable</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">4.2 Strategy Performance Risks</h3>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Past Performance:</strong> Historical results do not guarantee future performance</li>
                            <li><strong>Backtesting Limitations:</strong> Backtested results may not reflect real trading conditions</li>
                            <li><strong>Curve Fitting:</strong> Strategies may be over-optimized for historical data</li>
                            <li><strong>Market Regime Changes:</strong> Strategies may fail when market conditions change</li>
                            <li><strong>Model Risk:</strong> Trading models may be based on incorrect assumptions</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">4.3 Monitoring Requirements</h3>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            Even with automated systems, you must actively monitor your:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Trading activity and open positions</li>
                            <li>Account balance and margin requirements</li>
                            <li>System operation and error logs</li>
                            <li>Market conditions and news events</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">5. Specific Trading Risks</h2>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">5.1 Equity Trading</h3>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Individual stock prices can decline to zero</li>
                            <li>Company-specific events can cause rapid price changes</li>
                            <li>Sector rotations affect related stocks</li>
                            <li>Earnings announcements create volatility</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">5.2 Options Trading</h3>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Options can expire worthless</li>
                            <li>Time decay erodes option value</li>
                            <li>Implied volatility changes affect prices</li>
                            <li>Complex strategies carry additional risks</li>
                        </ul>

                        <h3 class="text-xl font-semibold text-emerald-400 mb-3 mt-4">5.3 Futures and Derivatives</h3>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>High leverage increases risk</li>
                            <li>Futures contracts have expiration dates</li>
                            <li>Delivery obligations may apply</li>
                            <li>Counterparty risk exists</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">6. Regulatory and Legal Risks</h2>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Regulatory Changes:</strong> New regulations may affect trading strategies or market access</li>
                            <li><strong>Tax Implications:</strong> Trading profits may be subject to significant taxes</li>
                            <li><strong>Legal Restrictions:</strong> Certain trading activities may be prohibited in your jurisdiction</li>
                            <li><strong>Pattern Day Trading Rules:</strong> US regulations restrict certain trading patterns</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">7. Operational Risks</h2>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Internet Connectivity:</strong> Loss of connection can prevent managing positions</li>
                            <li><strong>Platform Availability:</strong> System maintenance or failures may limit access</li>
                            <li><strong>Data Accuracy:</strong> Market data may be delayed or incorrect</li>
                            <li><strong>Order Processing:</strong> Orders may be rejected or delayed</li>
                            <li><strong>Account Security:</strong> Unauthorized access could result in losses</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">8. Third-Party Risks</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            The platform relies on third-party services (e.g., Alpaca Markets):
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Third-party service interruptions affect platform functionality</li>
                            <li>Brokerage firms may experience financial difficulties</li>
                            <li>Broker policies and fees may change</li>
                            <li>Securities Investor Protection Corporation (SIPC) coverage has limits</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">9. No Guarantees</h2>
                        <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
                            <p class="text-slate-300 leading-relaxed mb-3">
                                <strong class="text-white">We make no guarantees regarding:</strong>
                            </p>
                            <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                                <li>Profitability of any trading strategy</li>
                                <li>Performance or availability of the platform</li>
                                <li>Accuracy of market data or analytics</li>
                                <li>Successful execution of orders</li>
                                <li>Protection against losses</li>
                            </ul>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">10. Your Responsibilities</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            Before trading, you should:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li>Educate yourself about trading and markets</li>
                            <li>Understand the specific instruments you trade</li>
                            <li>Develop and test your strategies thoroughly</li>
                            <li>Only invest money you can afford to lose</li>
                            <li>Maintain adequate risk management practices</li>
                            <li>Monitor positions and account balances regularly</li>
                            <li>Seek professional advice if unsure</li>
                            <li>Start with small position sizes</li>
                            <li>Use stop-loss orders where appropriate</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">11. Risk Management Recommendations</h2>
                        <p class="text-slate-300 leading-relaxed mb-4">
                            Consider implementing these risk management practices:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4">
                            <li><strong>Position Sizing:</strong> Never risk more than a small percentage of capital on any single trade</li>
                            <li><strong>Diversification:</strong> Don't concentrate holdings in single instruments</li>
                            <li><strong>Stop Losses:</strong> Use stop-loss orders to limit potential losses</li>
                            <li><strong>Maximum Drawdown:</strong> Set limits on total acceptable losses</li>
                            <li><strong>Regular Reviews:</strong> Periodically review strategy performance</li>
                            <li><strong>Emergency Procedures:</strong> Have a plan for market emergencies</li>
                        </ul>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold text-white mb-4">12. Seeking Advice</h2>
                        <p class="text-slate-300 leading-relaxed">
                            If you do not understand the risks involved in trading, you should seek independent advice from a qualified
                            financial advisor. Consider consulting with:
                        </p>
                        <ul class="list-disc list-inside text-slate-300 space-y-2 ml-4 mt-3">
                            <li>Licensed financial advisors</li>
                            <li>Tax professionals regarding tax implications</li>
                            <li>Legal counsel regarding regulatory compliance</li>
                            <li>Risk management specialists</li>
                        </ul>
                    </section>

                    <div class="bg-red-500/10 border-2 border-red-500/50 rounded-lg p-6 mt-8">
                        <h3 class="text-xl font-bold text-red-400 mb-3">Acknowledgment</h3>
                        <p class="text-slate-300 leading-relaxed">
                            By using the Apex Trading platform, you acknowledge that you have read and understood this Risk Disclosure Statement.
                            You accept full responsibility for your trading decisions and understand that trading involves the risk of loss.
                            You agree not to hold Apex Trading liable for any trading losses you may incur.
                        </p>
                    </div>

                    <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4 mt-6">
                        <p class="text-sm text-slate-400 italic">
                            This Risk Disclosure does not disclose all of the risks associated with trading.
                            Additional risks may exist that are not described here. You should carefully consider whether trading is appropriate for your circumstances.
                        </p>
                </div>
            </div>
        </div>
    </LegalLayout>
</template>
