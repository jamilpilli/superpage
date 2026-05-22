<?php
// includes/prospect_templates.php
// Stock content por categoria — usado na criação de sites de prospecção

function get_prospect_template(string $category): array {
    $templates = [
        'marketing' => [
            'hero'     => 'award-winning marketing agency helping brands grow online.',
            'about'    => 'We are a results-driven marketing agency specialising in digital growth. From social media to paid advertising, our team delivers strategies that generate real results for our clients.',
            'services' => ['Social Media Management', 'Brand Design', 'Paid Ads', 'SEO'],
            'hero_img' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=1920&q=80',
        ],
        'restaurant' => [
            'hero'     => 'delicious food, warm atmosphere, unforgettable experience.',
            'about'    => 'We are passionate about bringing people together over great food. Using fresh, locally sourced ingredients, every dish is crafted with care and served with a smile.',
            'services' => ['Dine In', 'Takeaway', 'Catering', 'Private Events'],
            'hero_img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1920&q=80',
        ],
        'health' => [
            'hero'     => 'your health and wellbeing are our top priority.',
            'about'    => 'We provide compassionate, professional healthcare services tailored to your individual needs. Our experienced team is dedicated to helping you feel your best.',
            'services' => ['Consultations', 'Treatments', 'Wellness Plans', 'Nutrition Advice'],
            'hero_img' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1920&q=80',
        ],
        'construction' => [
            'hero'     => 'quality craftsmanship you can trust, built to last.',
            'about'    => 'With years of experience in the construction industry, we deliver high-quality builds and renovations on time and within budget. Your vision, our expertise.',
            'services' => ['Renovation', 'New Build', 'Repairs & Maintenance', 'Surveying'],
            'hero_img' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1920&q=80',
        ],
        'retail' => [
            'hero'     => 'discover products you will love, every single day.',
            'about'    => 'We curate a carefully selected range of products to suit every taste and budget. Whether you shop in-store or online, we are committed to making your experience effortless and enjoyable.',
            'services' => ['In-Store Shopping', 'Online Orders', 'Gift Cards', 'Easy Returns'],
            'hero_img' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1920&q=80',
        ],
        'professional' => [
            'hero'     => 'expert advice and professional services you can rely on.',
            'about'    => 'We are a team of experienced professionals committed to delivering practical solutions for our clients. We combine deep expertise with a personal approach to achieve outstanding outcomes.',
            'services' => ['Consultation', 'Advisory Services', 'Ongoing Support', 'Training'],
            'hero_img' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=1920&q=80',
        ],
        'other' => [
            'hero'     => 'welcome — we are here to help you succeed.',
            'about'    => 'We are a dedicated team passionate about what we do. Our goal is to provide outstanding service and create lasting value for every client we work with.',
            'services' => ['Our Services', 'What We Offer', 'How We Work', 'Get In Touch'],
            'hero_img' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=1920&q=80',
        ],
    ];

    return $templates[$category] ?? $templates['other'];
}

function get_prospect_categories(): array {
    return [
        'marketing'    => 'Marketing & Agency',
        'restaurant'   => 'Restaurant & Food',
        'health'       => 'Health & Beauty',
        'construction' => 'Construction & Trades',
        'retail'       => 'Retail & Shop',
        'professional' => 'Professional Services',
        'other'        => 'Other',
    ];
}
