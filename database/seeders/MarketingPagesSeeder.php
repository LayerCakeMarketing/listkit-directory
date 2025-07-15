<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use App\Models\LoginPageSettings;
use App\Models\HomePageSettings;

class MarketingPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample pages
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => '<h2>Our Story</h2>
<p>Welcome to our directory platform! We are dedicated to helping people discover the best places in their local communities.</p>

<h3>Our Mission</h3>
<p>Our mission is to connect people with amazing local businesses, services, and experiences. We believe in supporting local communities by making it easier for people to find what they need close to home.</p>

<h3>What We Do</h3>
<ul>
<li>Curate comprehensive listings of local businesses</li>
<li>Provide detailed information and reviews</li>
<li>Enable easy search and discovery</li>
<li>Support local business growth</li>
</ul>

<h3>Our Values</h3>
<p>We are committed to:</p>
<ul>
<li><strong>Accuracy</strong> - Providing reliable, up-to-date information</li>
<li><strong>Community</strong> - Supporting local businesses and neighborhoods</li>
<li><strong>Innovation</strong> - Continuously improving our platform</li>
<li><strong>Trust</strong> - Building lasting relationships with users and businesses</li>
</ul>',
                'meta_title' => 'About Us - Learn More About Our Directory Platform',
                'meta_description' => 'Discover our mission to connect people with the best local businesses and services in their communities.',
                'status' => 'published',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => '<h2>Privacy Policy</h2>
<p>Last updated: ' . now()->format('F j, Y') . '</p>

<h3>Information We Collect</h3>
<p>We collect information you provide directly to us, such as when you create an account, submit a listing, or contact us.</p>

<h3>How We Use Your Information</h3>
<p>We use the information we collect to:</p>
<ul>
<li>Provide, maintain, and improve our services</li>
<li>Process transactions and send related information</li>
<li>Send you technical notices and support messages</li>
<li>Respond to your comments and questions</li>
</ul>

<h3>Information Sharing</h3>
<p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

<h3>Data Security</h3>
<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h3>Contact Us</h3>
<p>If you have questions about this Privacy Policy, please contact us at privacy@example.com.</p>',
                'meta_title' => 'Privacy Policy - How We Protect Your Information',
                'meta_description' => 'Learn about our commitment to protecting your privacy and how we handle your personal information.',
                'status' => 'published',
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => '<h2>Terms of Service</h2>
<p>By using our platform, you agree to these terms. Please read them carefully.</p>

<h3>Use of Our Services</h3>
<p>You must follow any policies made available to you within the services. You may use our services only as permitted by law.</p>

<h3>Your Account</h3>
<p>You may need an account to use some of our services. You are responsible for keeping your account secure.</p>

<h3>Content</h3>
<p>Our services display content that is not ours. This content is the sole responsibility of the entity that makes it available.</p>

<h3>Prohibited Uses</h3>
<p>You may not use our services to:</p>
<ul>
<li>Violate any laws or regulations</li>
<li>Infringe on intellectual property rights</li>
<li>Distribute spam or malicious content</li>
<li>Attempt to gain unauthorized access to our systems</li>
</ul>

<h3>Termination</h3>
<p>We may suspend or terminate your access to our services at any time for any reason.</p>',
                'meta_title' => 'Terms of Service - Rules for Using Our Platform',
                'meta_description' => 'Read our terms of service to understand the rules and guidelines for using our directory platform.',
                'status' => 'published',
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => '<h2>Get in Touch</h2>
<p>We\'re here to help! Whether you have questions, feedback, or need assistance, we\'d love to hear from you.</p>

<h3>Contact Information</h3>
<p><strong>Email:</strong> support@example.com<br>
<strong>Phone:</strong> (555) 123-4567<br>
<strong>Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM EST</p>

<h3>Office Location</h3>
<p>123 Main Street<br>
Suite 100<br>
Anytown, ST 12345</p>

<h3>For Businesses</h3>
<p>If you\'re a business owner looking to claim or update your listing, please email us at business@example.com.</p>

<h3>For Media Inquiries</h3>
<p>Members of the press can reach our media team at press@example.com.</p>',
                'meta_title' => 'Contact Us - Get in Touch with Our Team',
                'meta_description' => 'Contact our support team for help with listings, account questions, or general inquiries.',
                'status' => 'published',
            ],
            [
                'title' => 'How It Works',
                'slug' => 'how-it-works',
                'content' => '<h2>How Our Directory Works</h2>
<p>Finding the perfect place has never been easier. Here\'s how to make the most of our platform.</p>

<h3>For Visitors</h3>
<h4>1. Search or Browse</h4>
<p>Use our search bar to find specific businesses or browse by category and location.</p>

<h4>2. Explore Listings</h4>
<p>View detailed information including hours, contact details, photos, and reviews.</p>

<h4>3. Save Favorites</h4>
<p>Create lists of your favorite places to easily find them later.</p>

<h3>For Business Owners</h3>
<h4>1. Claim Your Listing</h4>
<p>Search for your business and claim your free listing.</p>

<h4>2. Add Details</h4>
<p>Complete your profile with photos, descriptions, and business information.</p>

<h4>3. Engage with Customers</h4>
<p>Respond to reviews and keep your information up to date.</p>',
                'meta_title' => 'How It Works - Using Our Directory Platform',
                'meta_description' => 'Learn how to search for places, save favorites, and make the most of our directory platform.',
                'status' => 'published',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        // Create login page settings
        LoginPageSettings::updateOrCreate(
            ['id' => 1],
            [
                'welcome_message' => 'Welcome Back! Sign in to your account to continue exploring and managing your favorite places.',
                'background_image_path' => null,
                'social_login_enabled' => true,
                'custom_css' => '',
            ]
        );

        // Create home page settings
        HomePageSettings::updateOrCreate(
            ['id' => 1],
            [
            'hero_title' => 'Discover Amazing Places Near You',
            'hero_subtitle' => 'Find the best restaurants, services, and experiences in your local community',
            'hero_image_path' => null,
            'cta_text' => 'Start Exploring',
            'cta_link' => '/places',
            'featured_places' => [],
            'testimonials' => [
                [
                    'quote' => 'This directory helped me find the perfect restaurant for our anniversary dinner. The reviews and photos were incredibly helpful!',
                    'author' => 'Sarah Johnson',
                    'company' => 'Happy Customer'
                ],
                [
                    'quote' => 'As a small business owner, this platform has been invaluable for connecting with new customers in our area.',
                    'author' => 'Mike Chen',
                    'company' => 'Chen\'s Bakery'
                ],
                [
                    'quote' => 'I love how easy it is to create lists of my favorite places and share them with friends.',
                    'author' => 'Emily Rodriguez',
                    'company' => 'Local Explorer'
                ],
            ],
            'custom_scripts' => '',
            ]
        );

        $this->command->info('Marketing pages seeded successfully!');
    }
}