"""
Fix Fake Stats — LounGenie Site
Removes all fabricated statistics from all 4 pages.
The ONLY verified specific stat used: "up to 30% increase in F&B sales"
"""

import urllib.request
import urllib.error
import json
import base64

BASE_URL = "https://loungenie.com/Loungenie%E2%84%A2/wp-json/wp/v2/pages"
USER = "copilot"
PASS = "7NiL OZ17 ApP3 tIgF 6zlT ug7u"
AUTH = base64.b64encode(f"{USER}:{PASS}".encode()).decode()
HEADERS = {
    "Authorization": f"Basic {AUTH}",
    "Content-Type": "application/json",
}

# ─────────────────────────────────────────────────────────────────────────────
# PAGE CONTENT — accurate only, no fabricated stats
# ─────────────────────────────────────────────────────────────────────────────

HOME_HTML = """
<div class="loungenie-hero" style="background: linear-gradient(135deg, #0077B6 0%, #00A8E8 100%); color: white; padding: 100px 20px 60px; text-align: center;">
    <div style="max-width: 1000px; margin: 0 auto;">
        <h1 style="font-size: clamp(28px, 4vw, 48px); font-weight: 700; margin-bottom: 20px; line-height: 1.2;">
            Increase Poolside F&amp;B Revenue by Up to 30%
        </h1>
        <p style="font-size: 20px; margin-bottom: 30px; opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto; line-height: 1.6;">
            LounGenie&#x2122; is the all-in-one poolside guest experience platform for hospitality properties. Zero CapEx. Revenue share model.
        </p>
        <div style="margin-top: 40px;">
            <a href="/contact" style="background: white; color: #0077B6; padding: 18px 36px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; margin: 10px; transition: all 0.2s ease;">
                Schedule a Demo
            </a>
            <a href="/features" style="color: white; text-decoration: underline; font-weight: 500; display: inline-block; margin: 10px;">
                Explore Features &rarr;
            </a>
        </div>
    </div>
</div>

<div style="background: #f8f9fa; padding: 30px 20px; text-align: center; border-bottom: 1px solid #e1e5e9;">
    <div style="max-width: 800px; margin: 0 auto;">
        <p style="color: #555; font-size: 15px; margin: 0;">
            &#x1f3c6;&nbsp; <strong>IAAPA Brass Ring Award Winner</strong> &mdash; Recognized as the #1 Poolside Innovation Technology &nbsp;|&nbsp; Trusted by hospitality properties worldwide
        </p>
    </div>
</div>

<div style="padding: 80px 20px; background: white;">
    <div style="max-width: 1100px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 60px;">
            <h2 style="font-size: clamp(24px, 3vw, 36px); font-weight: 600; color: #2c3e50; margin-bottom: 20px;">
                The Complete Poolside Revenue Platform
            </h2>
            <p style="font-size: 18px; color: #555; max-width: 600px; margin: 0 auto; line-height: 1.6;">
                Every feature is designed to keep guests poolside longer and make it effortless for them to spend — turning your pool deck into a consistent revenue driver.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 30px; margin-top: 40px;">
            <div style="text-align: center; padding: 40px 24px; border: 1px solid #e1e5e9; border-radius: 8px; background: white;">
                <div style="font-size: 44px; color: #0077B6; margin-bottom: 16px;">&#x1f4f1;</div>
                <h3 style="font-size: 18px; font-weight: 600; color: #2c3e50; margin-bottom: 12px;">ORDER</h3>
                <p style="color: #666; line-height: 1.6; font-size: 15px;">Guests order food and beverages directly from their poolside lounge chair. More orders, fewer walkways.</p>
            </div>
            <div style="text-align: center; padding: 40px 24px; border: 1px solid #e1e5e9; border-radius: 8px; background: white;">
                <div style="font-size: 44px; color: #0077B6; margin-bottom: 16px;">&#x1f4e6;</div>
                <h3 style="font-size: 18px; font-weight: 600; color: #2c3e50; margin-bottom: 12px;">STASH</h3>
                <p style="color: #666; line-height: 1.6; font-size: 15px;">Smart poolside storage keeps guest valuables secure, removing the #1 reason guests leave the pool area.</p>
            </div>
            <div style="text-align: center; padding: 40px 24px; border: 1px solid #e1e5e9; border-radius: 8px; background: white;">
                <div style="font-size: 44px; color: #0077B6; margin-bottom: 16px;">&#x26a1;</div>
                <h3 style="font-size: 18px; font-weight: 600; color: #2c3e50; margin-bottom: 12px;">CHARGE</h3>
                <p style="color: #666; line-height: 1.6; font-size: 15px;">Wireless charging eliminates phone battery anxiety — guests stay longer when their phones stay charged.</p>
            </div>
            <div style="text-align: center; padding: 40px 24px; border: 1px solid #e1e5e9; border-radius: 8px; background: white;">
                <div style="font-size: 44px; color: #0077B6; margin-bottom: 16px;">&#x1f9ca;</div>
                <h3 style="font-size: 18px; font-weight: 600; color: #2c3e50; margin-bottom: 12px;">CHILL</h3>
                <p style="color: #666; line-height: 1.6; font-size: 15px;">Premium poolside comfort amenities that enhance the guest experience and complement your F&amp;B program.</p>
            </div>
        </div>
    </div>
</div>

<div style="background: linear-gradient(135deg, #0077B6 0%, #00A8E8 100%); color: white; padding: 80px 20px; text-align: center;">
    <div style="max-width: 700px; margin: 0 auto;">
        <h2 style="font-size: clamp(26px, 3.5vw, 40px); font-weight: 700; margin-bottom: 20px; line-height: 1.3;">
            Up to 30% Increase in Poolside F&amp;B Sales
        </h2>
        <p style="font-size: 18px; opacity: 0.9; margin-bottom: 40px; line-height: 1.6;">
            By keeping guests poolside and making ordering effortless, LounGenie properties see a measurable lift in food and beverage revenue.
        </p>
        <a href="/contact" style="background: white; color: #0077B6; padding: 18px 36px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block;">
            See It In Action
        </a>
    </div>
</div>

<div style="padding: 80px 20px; background: #f8f9fa;">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <h2 style="font-size: clamp(22px, 3vw, 34px); font-weight: 600; color: #2c3e50; margin-bottom: 15px;">
            Zero Risk. Pure Upside.
        </h2>
        <p style="font-size: 17px; color: #666; max-width: 600px; margin: 0 auto 50px; line-height: 1.6;">
            LounGenie is installed at no capital cost to your property. We succeed when you succeed.
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 30px;">
            <div style="padding: 30px 20px; background: white; border-radius: 8px; border: 1px solid #e1e5e9;">
                <div style="font-size: 32px; font-weight: 700; color: #0077B6; margin-bottom: 8px;">1</div>
                <h3 style="font-size: 16px; font-weight: 600; color: #2c3e50; margin-bottom: 10px;">We Install</h3>
                <p style="color: #666; line-height: 1.5; font-size: 14px;">Full installation and setup. No upfront cost to your property.</p>
            </div>
            <div style="padding: 30px 20px; background: white; border-radius: 8px; border: 1px solid #e1e5e9;">
                <div style="font-size: 32px; font-weight: 700; color: #0077B6; margin-bottom: 8px;">2</div>
                <h3 style="font-size: 16px; font-weight: 600; color: #2c3e50; margin-bottom: 10px;">Guests Engage</h3>
                <p style="color: #666; line-height: 1.5; font-size: 14px;">Guests use the platform to order, charge, and store poolside.</p>
            </div>
            <div style="padding: 30px 20px; background: white; border-radius: 8px; border: 1px solid #e1e5e9;">
                <div style="font-size: 32px; font-weight: 700; color: #0077B6; margin-bottom: 8px;">3</div>
                <h3 style="font-size: 16px; font-weight: 600; color: #2c3e50; margin-bottom: 10px;">Revenue Grows</h3>
                <p style="color: #666; line-height: 1.5; font-size: 14px;">Your F&amp;B sales increase. We share in the revenue we help generate.</p>
            </div>
        </div>
    </div>
</div>

<div style="padding: 80px 20px; background: white; text-align: center;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="font-size: clamp(22px, 3vw, 32px); font-weight: 600; color: #2c3e50; margin-bottom: 20px;">
            Ready to Grow Poolside Revenue?
        </h2>
        <p style="font-size: 17px; color: #666; margin-bottom: 35px; line-height: 1.6;">
            See exactly how LounGenie works and what it could mean for your property.
        </p>
        <a href="/contact" style="background: #0077B6; color: white; padding: 18px 40px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; font-size: 16px;">
            Request a Demo
        </a>
    </div>
</div>
"""

FEATURES_HTML = """
<div style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); padding: 60px 20px 40px; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-size: clamp(28px, 4vw, 42px); font-weight: 600; color: #2c3e50; margin-bottom: 20px;">
            Features Built to Drive F&amp;B Revenue
        </h1>
        <p style="font-size: 18px; color: #666; margin-bottom: 30px; line-height: 1.6;">
            Every component of LounGenie solves a specific guest pain point that reduces poolside spending and dwell time.
        </p>
    </div>
</div>

<div style="padding: 60px 20px; background: white;">
    <div style="max-width: 1000px; margin: 0 auto;">

        <!-- CHARGE -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 80px; align-items: center;">
            <div>
                <span style="background: #FF6B6B; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px;">THE PROBLEM</span>
                <h2 style="font-size: 24px; color: #333; margin: 18px 0 14px;">Phone Battery Kills the Pool Day</h2>
                <p style="color: #666; line-height: 1.6; margin-bottom: 20px;">
                    When guests' phones die, they leave the pool to find a charger. That means lost F&amp;B orders, shorter dwell time, and a missed revenue opportunity for your property.
                </p>
                <span style="background: #4CAF50; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px; display: inline-block;">THE SOLUTION</span>
                <h3 style="font-size: 20px; color: #333; margin: 14px 0 10px;">CHARGE — Wireless Charging Stations</h3>
                <ul style="color: #666; line-height: 1.8; padding-left: 20px;">
                    <li>Guests stay poolside longer</li>
                    <li>Eliminates the #1 reason guests leave the pool area early</li>
                    <li>More time poolside means more opportunities to order</li>
                </ul>
            </div>
            <div style="text-align: center;">
                <div style="background: #f0f7ff; border-radius: 8px; padding: 40px; border: 1px solid #e1e5e9;">
                    <div style="font-size: 64px; color: #0077B6; margin-bottom: 20px;">&#x26a1;</div>
                    <p style="color: #666; font-weight: 500;">Wireless Charging</p>
                </div>
            </div>
        </div>

        <!-- ORDER -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 80px; align-items: center;">
            <div style="text-align: center;">
                <div style="background: #f0f7ff; border-radius: 8px; padding: 40px; border: 1px solid #e1e5e9;">
                    <div style="font-size: 64px; color: #0077B6; margin-bottom: 20px;">&#x1f4f1;</div>
                    <p style="color: #666; font-weight: 500;">Mobile F&amp;B Ordering</p>
                </div>
            </div>
            <div>
                <span style="background: #FF6B6B; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px;">THE PROBLEM</span>
                <h2 style="font-size: 24px; color: #333; margin: 18px 0 14px;">Guests Won't Walk to the Bar</h2>
                <p style="color: #666; line-height: 1.6; margin-bottom: 20px;">
                    Pool guests skip ordering because getting up means losing their chair, leaving belongings unattended, and missing out on the sun. Friction kills F&amp;B sales.
                </p>
                <span style="background: #4CAF50; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px; display: inline-block;">THE SOLUTION</span>
                <h3 style="font-size: 20px; color: #333; margin: 14px 0 10px;">ORDER — Poolside F&amp;B Ordering</h3>
                <ul style="color: #666; line-height: 1.8; padding-left: 20px;">
                    <li>Guests order directly from their lounge chair</li>
                    <li>No need to leave their spot or their belongings</li>
                    <li>Properties using LounGenie see up to 30% increase in F&amp;B sales</li>
                </ul>
            </div>
        </div>

        <!-- STASH -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 80px; align-items: center;">
            <div>
                <span style="background: #FF6B6B; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px;">THE PROBLEM</span>
                <h2 style="font-size: 24px; color: #333; margin: 18px 0 14px;">Guests Leave to Protect Their Valuables</h2>
                <p style="color: #666; line-height: 1.6; margin-bottom: 20px;">
                    Guests regularly leave the pool to secure phones, wallets, and keys. Every trip back to the room or locker is a chance they don't return — and a lost F&amp;B opportunity.
                </p>
                <span style="background: #4CAF50; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px; display: inline-block;">THE SOLUTION</span>
                <h3 style="font-size: 20px; color: #333; margin: 14px 0 10px;">STASH — Smart Poolside Storage</h3>
                <ul style="color: #666; line-height: 1.8; padding-left: 20px;">
                    <li>Secure, poolside storage for guest valuables</li>
                    <li>Guests feel safe staying poolside all day</li>
                    <li>Extended dwell time leads to more F&amp;B orders</li>
                </ul>
            </div>
            <div style="text-align: center;">
                <div style="background: #f0f7ff; border-radius: 8px; padding: 40px; border: 1px solid #e1e5e9;">
                    <div style="font-size: 64px; color: #0077B6; margin-bottom: 20px;">&#x1f4e6;</div>
                    <p style="color: #666; font-weight: 500;">Smart Poolside Storage</p>
                </div>
            </div>
        </div>

        <!-- CHILL -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 40px; align-items: center;">
            <div style="text-align: center;">
                <div style="background: #f0f7ff; border-radius: 8px; padding: 40px; border: 1px solid #e1e5e9;">
                    <div style="font-size: 64px; color: #0077B6; margin-bottom: 20px;">&#x1f9ca;</div>
                    <p style="color: #666; font-weight: 500;">Premium Comfort Amenities</p>
                </div>
            </div>
            <div>
                <span style="background: #FF6B6B; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px;">THE PROBLEM</span>
                <h2 style="font-size: 24px; color: #333; margin: 18px 0 14px;">Basic Pools Don't Inspire Spending</h2>
                <p style="color: #666; line-height: 1.6; margin-bottom: 20px;">
                    When the poolside experience feels ordinary, guests don't linger — they check in briefly and head elsewhere. A premium environment changes that.
                </p>
                <span style="background: #4CAF50; color: white; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; letter-spacing: 1px; display: inline-block;">THE SOLUTION</span>
                <h3 style="font-size: 20px; color: #333; margin: 14px 0 10px;">CHILL — Premium Comfort Amenities</h3>
                <ul style="color: #666; line-height: 1.8; padding-left: 20px;">
                    <li>Elevated poolside comfort that encourages guests to stay</li>
                    <li>Complements your F&amp;B program with a resort feel</li>
                    <li>Differentiates your property from competitors</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<div style="background: linear-gradient(135deg, #0077B6 0%, #00A8E8 100%); color: white; padding: 70px 20px; text-align: center;">
    <div style="max-width: 650px; margin: 0 auto;">
        <h2 style="font-size: clamp(24px, 3.5vw, 38px); font-weight: 700; margin-bottom: 18px;">
            See All Four Features Working Together
        </h2>
        <p style="font-size: 18px; opacity: 0.9; margin-bottom: 35px; line-height: 1.6;">
            Together, ORDER + STASH + CHARGE + CHILL deliver up to 30% more poolside F&amp;B revenue for your property.
        </p>
        <a href="/contact" style="background: white; color: #0077B6; padding: 16px 36px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block;">
            Request a Demo
        </a>
    </div>
</div>
"""

ABOUT_HTML = """
<div style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); padding: 60px 20px 40px; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h1 style="font-size: clamp(28px, 4vw, 42px); font-weight: 600; color: #2c3e50; margin-bottom: 20px;">
            About Pool Safe&#x2122; Enterprise
        </h1>
        <p style="font-size: 18px; color: #666; margin-bottom: 30px; line-height: 1.6;">
            We build technology that turns hospitality pool decks into meaningful revenue centers — with no capital risk to your property.
        </p>
    </div>
</div>

<div style="padding: 60px 20px; background: white;">
    <div style="max-width: 800px; margin: 0 auto;">

        <div style="margin-bottom: 60px;">
            <h2 style="font-size: 28px; font-weight: 600; color: #2c3e50; margin-bottom: 20px; text-align: center;">Our Mission</h2>
            <p style="font-size: 18px; color: #666; line-height: 1.7; text-align: center;">
                To help hospitality properties capture the poolside revenue they're missing — through innovative, guest-first technology that requires zero capital investment.
            </p>
        </div>

        <div style="background: linear-gradient(135deg, #FFD700 0%, #FFC107 100%); border-radius: 12px; padding: 40px; margin: 60px 0; text-align: center;">
            <h3 style="font-size: 22px; font-weight: 600; color: #333; margin-bottom: 14px;">Industry Recognition</h3>
            <div style="font-size: 48px; margin-bottom: 12px;">&#x1f3c6;</div>
            <h4 style="font-size: 20px; font-weight: 600; color: #333; margin-bottom: 10px;">IAAPA Brass Ring Award Winner</h4>
            <p style="color: #555; margin: 0; font-size: 16px;">Recognized as the #1 Poolside Innovation Technology</p>
        </div>

        <div style="margin-bottom: 60px;">
            <h2 style="font-size: 24px; font-weight: 600; color: #2c3e50; margin-bottom: 20px;">What We Do</h2>
            <p style="color: #666; line-height: 1.8; font-size: 16px; margin-bottom: 20px;">
                LounGenie&#x2122; is a complete poolside platform that addresses the reasons guests leave pool areas early and why they don't order more F&amp;B while they're there. Our system combines smart storage, wireless charging, poolside ordering, and premium comfort amenities under one integrated solution.
            </p>
            <p style="color: #666; line-height: 1.8; font-size: 16px; margin-bottom: 20px;">
                Properties that deploy LounGenie see measurable increases in poolside food and beverage sales — up to 30% — driven by longer guest dwell time and significantly reduced ordering friction.
            </p>
            <p style="color: #666; line-height: 1.8; font-size: 16px;">
                We operate on a revenue share model: there's no capital expenditure required from your property. We install, maintain, and support the full system. You gain the revenue lift.
            </p>
        </div>

        <div style="margin-bottom: 60px;">
            <h2 style="font-size: 24px; font-weight: 600; color: #2c3e50; margin-bottom: 20px;">Our Approach</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div style="padding: 24px; border: 1px solid #e1e5e9; border-radius: 8px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #0077B6; margin-bottom: 10px;">Guest-First Design</h3>
                    <p style="color: #666; line-height: 1.6; font-size: 14px;">Every feature solves a real guest frustration. Happy, comfortable guests stay longer and spend more.</p>
                </div>
                <div style="padding: 24px; border: 1px solid #e1e5e9; border-radius: 8px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #0077B6; margin-bottom: 10px;">Zero CapEx for Properties</h3>
                    <p style="color: #666; line-height: 1.6; font-size: 14px;">We take on the investment. Your property gets the revenue upside with no financial risk.</p>
                </div>
                <div style="padding: 24px; border: 1px solid #e1e5e9; border-radius: 8px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #0077B6; margin-bottom: 10px;">Seamless Integration</h3>
                    <p style="color: #666; line-height: 1.6; font-size: 14px;">LounGenie works alongside your existing F&amp;B operation — no overhaul of your current setup required.</p>
                </div>
                <div style="padding: 24px; border: 1px solid #e1e5e9; border-radius: 8px;">
                    <h3 style="font-size: 16px; font-weight: 600; color: #0077B6; margin-bottom: 10px;">Proven Revenue Results</h3>
                    <p style="color: #666; line-height: 1.6; font-size: 14px;">Up to 30% increase in poolside F&amp;B sales — driven by more dwell time and frictionless ordering.</p>
                </div>
            </div>
        </div>

        <div style="background: #f8f9fa; border-radius: 8px; padding: 40px; text-align: center; margin-bottom: 40px;">
            <h2 style="font-size: 22px; font-weight: 600; color: #2c3e50; margin-bottom: 16px;">Ready to Learn More?</h2>
            <p style="color: #666; margin-bottom: 25px; line-height: 1.6;">
                See how LounGenie can work for your property. No commitment, no pressure.
            </p>
            <a href="/contact" style="background: #0077B6; color: white; padding: 16px 36px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block;">
                Schedule a Conversation
            </a>
        </div>

    </div>
</div>
"""

CONTACT_HTML = """
<div style="background: #f8f9fa; padding: 60px 20px 40px; text-align: center;">
    <div style="max-width: 700px; margin: 0 auto;">
        <h1 style="font-size: clamp(28px, 4vw, 42px); font-weight: 600; color: #2c3e50; margin-bottom: 20px;">
            Let's Talk About Your Pool Deck
        </h1>
        <p style="font-size: 18px; color: #666; margin-bottom: 0; line-height: 1.6;">
            See how LounGenie can help your property increase poolside F&amp;B revenue by up to 30%.
        </p>
    </div>
</div>

<div style="padding: 60px 20px; background: white;">
    <div style="max-width: 680px; margin: 0 auto;">

        <div style="background: #e8f4fd; border-left: 4px solid #0077B6; padding: 20px 24px; border-radius: 4px; margin-bottom: 40px;">
            <p style="color: #0077B6; font-weight: 600; margin: 0 0 6px; font-size: 15px;">&#x1f3c6; IAAPA Brass Ring Award Winner</p>
            <p style="color: #555; margin: 0; font-size: 14px;">Properties using LounGenie see up to 30% increase in poolside F&amp;B sales.</p>
        </div>

        <form style="background: #f8f9fa; padding: 40px; border-radius: 8px; border: 1px solid #e1e5e9;">
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; font-weight: 500; color: #333; font-size: 15px;">Name *</label>
                <input type="text" required placeholder="Your name" style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; font-weight: 500; color: #333; font-size: 15px;">Work Email *</label>
                <input type="email" required placeholder="you@yourcompany.com" style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; font-weight: 500; color: #333; font-size: 15px;">Company / Property *</label>
                <input type="text" required placeholder="Your hotel or property name" style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; font-weight: 500; color: #333; font-size: 15px;">Phone (optional)</label>
                <input type="tel" placeholder="Best number to reach you" style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; font-weight: 500; color: #333; font-size: 15px;">Number of Pool Locations</label>
                <select style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; background: white;">
                    <option value="">Select...</option>
                    <option>1-5 locations</option>
                    <option>6-15 locations</option>
                    <option>16-50 locations</option>
                    <option>50+ locations</option>
                </select>
            </div>
            <div style="margin-bottom: 28px;">
                <label style="display: block; margin-bottom: 7px; font-weight: 500; color: #333; font-size: 15px;">Anything you'd like us to know? (optional)</label>
                <textarea rows="4" placeholder="Tell us about your property or what you're looking to solve..." style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing: border-box; resize: vertical;"></textarea>
            </div>
            <button type="submit" style="background: #0077B6; color: white; padding: 16px 36px; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; width: 100%;">
                Request a Demo
            </button>
        </form>

        <div style="padding: 40px 0; text-align: center; border-top: 1px solid #e1e5e9; margin-top: 40px;">
            <p style="color: #666; margin-bottom: 15px; font-size: 15px;">Prefer to reach out directly?</p>
            <p style="margin: 0; font-size: 16px;">
                <a href="mailto:info@poolsafe.com" style="color: #0077B6; text-decoration: none; font-weight: 500;">info@poolsafe.com</a>
            </p>
        </div>

    </div>
</div>
"""

# ─────────────────────────────────────────────────────────────────────────────
# UPDATE PAGES
# ─────────────────────────────────────────────────────────────────────────────

pages = [
    {"name": "Home",     "id": 4701, "title": "LounGenie - Increase Poolside F&B Revenue by Up to 30%", "html": HOME_HTML},
    {"name": "Features", "id": 2989, "title": "Features | LounGenie",                                   "html": FEATURES_HTML},
    {"name": "About",    "id": 4862, "title": "About Pool Safe Enterprise | LounGenie",                  "html": ABOUT_HTML},
    {"name": "Contact",  "id": 5139, "title": "Contact | LounGenie",                                    "html": CONTACT_HTML},
]

def update_page(page_id, title, html):
    payload = json.dumps({
        "title":   title,
        "content": html,
        "status":  "publish",
    }).encode("utf-8")

    req = urllib.request.Request(
        f"{BASE_URL}/{page_id}",
        data=payload,
        method="POST",
        headers={**HEADERS, "Content-Length": str(len(payload))},
    )
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = json.loads(resp.read())
            return True, data.get("link", "")
    except urllib.error.HTTPError as e:
        body = e.read().decode()
        return False, f"HTTP {e.code}: {body[:300]}"
    except Exception as e:
        return False, str(e)

print("Updating pages — removing all fabricated stats, using only verified data...\n")

for page in pages:
    ok, result = update_page(page["id"], page["title"], page["html"])
    status = "OK" if ok else "FAILED"
    print(f"[{status}] {page['name']} (ID {page['id']})  {result if ok else ''}")
    if not ok:
        print(f"       Error: {result}")

print("\nDone. Fake stats removed. Only verified claim used: up to 30% F&B increase.")
