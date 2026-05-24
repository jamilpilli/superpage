<?php
// includes/prospect_templates.php
// Stock content por categoria — usado na criação de sites de prospecção

function get_prospect_template(string $category): array {
    $templates = [
        'construction' => [
            'hero'     => 'quality craftsmanship you can trust, built to last.',
            'about'    => 'With years of experience in the construction and trades industry, we deliver high-quality builds, renovations and repairs on time and within budget. Your vision, our expertise.',
            'services' => ['Renovation & Refurbishment', 'New Build', 'Repairs & Maintenance', 'Surveying & Planning'],
            'hero_img' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1920&q=80',
        ],
        'business' => [
            'hero'     => 'professional business solutions tailored to your needs.',
            'about'    => 'We provide reliable business and office services designed to help companies operate more efficiently. From administration to logistics, our team is here to support your growth.',
            'services' => ['Business Consultancy', 'Office Support', 'Administration', 'Virtual Assistant'],
            'hero_img' => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=1920&q=80',
        ],
        'property' => [
            'hero'     => 'keeping your property in perfect condition, all year round.',
            'about'    => 'We offer a comprehensive range of property and maintenance services for homeowners and landlords. From routine upkeep to emergency repairs, we have got you covered.',
            'services' => ['Property Management', 'Maintenance & Repairs', 'Cleaning Services', 'Garden & Outdoor'],
            'hero_img' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1920&q=80',
        ],
        'health' => [
            'hero'     => 'your health and beauty are our top priority.',
            'about'    => 'We provide compassionate, professional health and beauty services tailored to your individual needs. Our experienced team is dedicated to helping you look and feel your best.',
            'services' => ['Consultations', 'Treatments', 'Beauty Services', 'Wellness Plans'],
            'hero_img' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1920&q=80',
        ],
        'weddings' => [
            'hero'     => 'making your special day truly unforgettable.',
            'about'    => 'We specialise in creating beautiful, personalised wedding experiences. From planning to execution, our passionate team takes care of every detail so you can enjoy every moment.',
            'services' => ['Wedding Planning', 'Venue Styling', 'Catering & Cakes', 'Photography & Video'],
            'hero_img' => 'https://images.unsplash.com/photo-1519741497674-611481863552?w=1920&q=80',
        ],
        'education' => [
            'hero'     => 'expert tuition and classes to help you reach your potential.',
            'about'    => 'We offer high-quality tuition and training classes for learners of all ages and levels. Our experienced tutors are passionate about education and committed to your success.',
            'services' => ['One-to-One Tuition', 'Group Classes', 'Online Sessions', 'Exam Preparation'],
            'hero_img' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1920&q=80',
        ],
        'tech' => [
            'hero'     => 'reliable technology solutions for homes and businesses.',
            'about'    => 'We provide expert computer and telecoms support for individuals and businesses. Whether it is a quick repair or a full network setup, our team delivers fast and reliable results.',
            'services' => ['Computer Repair', 'Network Setup', 'Phone & Device Support', 'IT Consultancy'],
            'hero_img' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1920&q=80',
        ],
        'transport' => [
            'hero'     => 'safe, reliable transport whenever you need it.',
            'about'    => 'We offer professional transport services for individuals and businesses across the area. Our experienced drivers and well-maintained vehicles ensure every journey is smooth and on time.',
            'services' => ['Local Transfers', 'Airport Runs', 'Van Hire', 'Courier & Delivery'],
            'hero_img' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=1920&q=80',
        ],
        'entertainment' => [
            'hero'     => 'bringing fun and unforgettable experiences to every occasion.',
            'about'    => 'We provide top-quality entertainment services for events of all sizes. From private parties to corporate functions, our team delivers performances and experiences your guests will never forget.',
            'services' => ['Live Performances', 'Event DJ', 'Party Planning', 'Kids Entertainment'],
            'hero_img' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1920&q=80',
        ],
        'retail' => [
            'hero'     => 'discover products you will love, every single day.',
            'about'    => 'We offer a carefully selected range of products to suit every taste and budget. Whether you shop in-store or online, we are committed to making your experience effortless and enjoyable.',
            'services' => ['In-Store Shopping', 'Online Orders', 'Gift Cards', 'Easy Returns'],
            'hero_img' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1920&q=80',
        ],
        'finance' => [
            'hero'     => 'trusted financial and legal advice you can count on.',
            'about'    => 'We offer professional financial and legal services to individuals and businesses. Our experienced advisors provide clear, practical guidance to help you make the right decisions with confidence.',
            'services' => ['Financial Planning', 'Legal Advice', 'Tax & Accounting', 'Business Support'],
            'hero_img' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1920&q=80',
        ],
        'pets' => [
            'hero'     => 'caring for your pets as if they were our own.',
            'about'    => 'We are passionate animal lovers dedicated to providing the best care for your pets. Whether it is grooming, walking or boarding, your furry family members are in safe hands with us.',
            'services' => ['Dog Walking', 'Pet Grooming', 'Pet Sitting & Boarding', 'Veterinary Referrals'],
            'hero_img' => 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?w=1920&q=80',
        ],
        'motoring' => [
            'hero'     => 'keeping your vehicle running smoothly, always.',
            'about'    => 'We provide expert motoring services for all makes and models. Our qualified mechanics and technicians deliver honest, reliable work at competitive prices — keeping you safe on the road.',
            'services' => ['MOT & Servicing', 'Repairs & Diagnostics', 'Tyres & Brakes', 'Valeting'],
            'hero_img' => 'https://images.unsplash.com/photo-1487754180451-c456f719a1fc?w=1920&q=80',
        ],
        'clothing' => [
            'hero'     => 'style that speaks for itself.',
            'about'    => 'We offer a curated selection of clothing and fashion for every occasion. From everyday essentials to special occasion pieces, our collection is designed to make you look and feel great.',
            'services' => ['Womenswear', 'Menswear', 'Alterations & Tailoring', 'Personal Styling'],
            'hero_img' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=1920&q=80',
        ],
        'food' => [
            'hero'     => 'delicious food and drink, made with passion.',
            'about'    => 'We are passionate about bringing people together over great food and drink. Using fresh, quality ingredients, everything we make is crafted with care and served with a smile.',
            'services' => ['Dine In', 'Takeaway & Delivery', 'Catering', 'Private Events'],
            'hero_img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1920&q=80',
        ],
        'childcare' => [
            'hero'     => 'caring for your children with love, patience and dedication.',
            'about'    => 'We provide a safe, nurturing and stimulating environment for children of all ages. Our experienced and caring team treats every child as an individual, supporting their growth and happiness every day.',
            'services' => ['Childminding', 'After-School Care', 'Babysitting', 'Holiday Club'],
            'hero_img' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=1920&q=80',
        ],
        'travel' => [
            'hero'     => 'your next adventure starts here.',
            'about'    => 'We help individuals and families plan and book unforgettable travel experiences. From weekend breaks to long-haul adventures, our knowledgeable team is here to make every journey special.',
            'services' => ['Holiday Packages', 'Flight & Hotel Booking', 'Group Travel', 'Travel Insurance'],
            'hero_img' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1920&q=80',
        ],
        'marketing' => [
            'hero'     => 'award-winning marketing that helps your brand grow.',
            'about'    => 'We are a results-driven marketing agency specialising in digital growth. From social media to paid advertising, our team delivers strategies that generate real results for our clients.',
            'services' => ['Social Media Management', 'Brand Design', 'Paid Ads', 'SEO'],
            'hero_img' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=1920&q=80',
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
        'construction' => 'Tradesmen & Construction',
        'business'     => 'Business & Office',
        'property'     => 'Property & Maintenance',
        'health'       => 'Health & Beauty',
        'weddings'     => 'Weddings',
        'education'    => 'Tuition & Classes',
        'tech'         => 'Computers & Telecoms',
        'transport'    => 'Transport',
        'entertainment'=> 'Entertainment',
        'retail'       => 'Goods Suppliers & Retailers',
        'finance'      => 'Finance & Legal',
        'pets'         => 'Pets',
        'motoring'     => 'Motoring',
        'clothing'     => 'Clothing',
        'food'         => 'Food & Drink',
        'childcare'    => 'Childcare',
        'travel'       => 'Travel & Tourism',
        'marketing'    => 'Marketing & Agency',
        'professional' => 'Professional Services',
        'other'        => 'Other',
    ];
}
