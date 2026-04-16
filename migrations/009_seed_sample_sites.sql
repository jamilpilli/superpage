-- =============================================================
-- Seed: Sample Sites (5 niches)
-- All user passwords: adm123
-- =============================================================

-- -------------------------------------------------------
-- USERS
-- -------------------------------------------------------
INSERT INTO users (name, email, password_hash, role) VALUES
('Sarah Johnson',   'cleaning@superpage.co.uk',   '$2y$12$6QqJCR2vjpg6ZO8cdib8FeByLcJIlnj0Kxfp80xro4ZOt0ZrJU1Ie', 'client'),
('Amanda Torres',   'beauty@superpage.co.uk',     '$2y$12$6QqJCR2vjpg6ZO8cdib8FeByLcJIlnj0Kxfp80xro4ZOt0ZrJU1Ie', 'client'),
('Marcos Souza',    'restaurant@superpage.co.uk', '$2y$12$6QqJCR2vjpg6ZO8cdib8FeByLcJIlnj0Kxfp80xro4ZOt0ZrJU1Ie', 'client'),
('Patricia Lima',   'travel@superpage.co.uk',     '$2y$12$6QqJCR2vjpg6ZO8cdib8FeByLcJIlnj0Kxfp80xro4ZOt0ZrJU1Ie', 'client'),
('Diego Ferreira',  'delivery@superpage.co.uk',   '$2y$12$6QqJCR2vjpg6ZO8cdib8FeByLcJIlnj0Kxfp80xro4ZOt0ZrJU1Ie', 'client');


-- =====================================================================
-- SITE 1: Sparkle Clean — Residential Cleaning Services
-- =====================================================================
INSERT INTO sites (user_id, slug, status, design) VALUES (
    (SELECT id FROM users WHERE email = 'cleaning@superpage.co.uk'),
    'sparkle-clean',
    'active',
    '{"primary_color":"#0ea5e9","title_font":"Plus Jakarta Sans","text_font":"Inter","button_style":"rounded-full"}'
);
INSERT INTO pages (site_id, slug, title, status) VALUES (
    (SELECT id FROM sites WHERE slug = 'sparkle-clean'),
    'home', 'Sparkle Clean — Professional Home Cleaning', 'published'
);

INSERT INTO blocks (page_id, type, sort_order, config) VALUES
-- Header
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'sparkle-clean')),
 'header', 1, '{"title":"Sparkle Clean"}'),

-- Hero
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'sparkle-clean')),
 'hero', 2,
 '{"items":[{"title":"Your Home, Spotlessly Clean","description":"Professional residential cleaning services you can trust. Flexible scheduling, eco-friendly products, satisfaction guaranteed.","image":"https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=1920&q=80","button_text":"Book Now","button_link":"#contact"},{"title":"First Clean 20% Off","description":"New customers get 20% off their first cleaning session. No commitment required.","image":"https://images.unsplash.com/photo-1563453392212-326f5e854473?auto=format&fit=crop&w=1920&q=80","button_text":"Get Discount","button_link":"#contact"}]}'),

-- About
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'sparkle-clean')),
 'about', 3,
 '{"title":"About Sparkle Clean","text":"Founded in 2015, Sparkle Clean has been transforming homes across the city with our professional, reliable cleaning services. Our fully vetted team uses eco-friendly, non-toxic products that are safe for your family and pets.\n\nWith over 2,000 happy clients and a 4.9-star average rating, we are the most trusted residential cleaning service in town.","image":"https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=800&q=80","button_text":"Get a Free Quote","button_link":"#contact"}'),

-- Services
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'sparkle-clean')),
 'services', 4,
 '{"title":"Our Services","description":"Comprehensive cleaning solutions tailored to your home","items":[{"title":"Standard Cleaning","description":"Regular cleaning of all rooms, dusting, vacuuming, mopping, and bathroom sanitization. Perfect for weekly or bi-weekly maintenance.","image":"https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=800&q=80"},{"title":"Deep Cleaning","description":"Thorough top-to-bottom cleaning including inside appliances, baseboards, window sills, and hard-to-reach areas. Ideal for move-in/move-out.","image":"https://images.unsplash.com/photo-1563453392212-326f5e854473?auto=format&fit=crop&w=800&q=80"},{"title":"Post-Construction Cleaning","description":"Specialized cleaning after renovations or construction. We remove dust, debris, and leave your home ready to live in.","image":"https://images.unsplash.com/photo-1556909212-d5b604d0c90d?auto=format&fit=crop&w=800&q=80"}]}'),

-- Testimonials
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'sparkle-clean')),
 'testimonials', 5,
 '{"title":"What Our Clients Say","items":[{"title":"Jessica M.","description":"Sparkle Clean transformed my apartment! The team was punctual, thorough, and incredibly professional. I have been using them monthly for 2 years now.","image":"https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80"},{"title":"Thomas R.","description":"Hired them for a deep clean after moving in. They found dirt I did not even know existed. Worth every penny!","image":"https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80"},{"title":"Laura K.","description":"The eco-friendly products were a big selling point for me — safe for my kids and my dog. And the results are flawless.","image":"https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=150&q=80"}]}'),

-- Contact
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'sparkle-clean')),
 'contact', 6,
 '{"title":"Book Your Cleaning","phone":"11988001122","is_whatsapp":true,"button_text":"Send Message"}'),

-- Footer
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'sparkle-clean')),
 'footer', 7,
 '{"title":"Sparkle Clean"}');


-- =====================================================================
-- SITE 2: Glow Studio — Beauty Salon & Aesthetics
-- =====================================================================
INSERT INTO sites (user_id, slug, status, design) VALUES (
    (SELECT id FROM users WHERE email = 'beauty@superpage.co.uk'),
    'glow-studio',
    'active',
    '{"primary_color":"#db2777","title_font":"Playfair Display","text_font":"Inter","button_style":"rounded-full"}'
);
INSERT INTO pages (site_id, slug, title, status) VALUES (
    (SELECT id FROM sites WHERE slug = 'glow-studio'),
    'home', 'Glow Studio — Beauty Salon & Aesthetics', 'published'
);

INSERT INTO blocks (page_id, type, sort_order, config) VALUES
-- Header
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'header', 1, '{"title":"Glow Studio"}'),

-- Hero
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'hero', 2,
 '{"items":[{"title":"Where Beauty Meets Confidence","description":"Premium hair, skin, and nail services in a relaxing, luxurious environment. Walk out feeling like the best version of yourself.","image":"https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1920&q=80","button_text":"Book Appointment","button_link":"#contact"},{"title":"New: HydraFacial Treatment","description":"The most advanced facial treatment available. Cleanse, extract, and hydrate for visible results from the first session.","image":"https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=1920&q=80","button_text":"Learn More","button_link":"#services"}]}'),

-- About
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'about', 3,
 '{"title":"About Glow Studio","text":"Glow Studio was founded in 2018 by Amanda Torres, a certified aesthetician and hair stylist with over 15 years of experience. Our studio is a sanctuary where beauty and wellness meet.\n\nWe offer a curated menu of services using only professional-grade, cruelty-free products. Whether you are looking for a fresh haircut, radiant skin, or a complete makeover — our expert team is here to make it happen.","image":"https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80","button_text":"Meet Our Team","button_link":"#team"}'),

-- Services
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'services', 4,
 '{"title":"Our Services","description":"Expertly crafted treatments for hair, skin, and nails","items":[{"title":"Hair Services","description":"Haircuts, coloring, highlights, keratin treatments, and blowouts. Personalized consultations with every appointment.","image":"https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=80"},{"title":"Skin & Facials","description":"HydraFacial, microdermabrasion, chemical peels, and customized facials for all skin types and concerns.","image":"https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&q=80"},{"title":"Nail Care","description":"Manicures, pedicures, gel nails, nail art, and spa treatments. Relax while our nail techs work their magic.","image":"https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=800&q=80"},{"title":"Waxing & Threading","description":"Precise eyebrow shaping, full-body waxing, and threading. Long-lasting results with minimal discomfort.","image":"https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?auto=format&fit=crop&w=800&q=80"}]}'),

-- Testimonials
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'testimonials', 5,
 '{"title":"Client Love","items":[{"title":"Stephanie W.","description":"Amanda is an absolute genius with color! I came in with a vision and she delivered beyond my expectations. The salon atmosphere is so calming too.","image":"https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=150&q=80"},{"title":"Monica S.","description":"My HydraFacial results were incredible. My skin has never looked this good. I am officially a regular customer!","image":"https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=150&q=80"},{"title":"Rachel B.","description":"Best nail experience in the city. The attention to detail is unmatched and the products they use are top quality.","image":"https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80"}]}'),

-- Gallery
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'gallery', 6,
 '{"title":"Our Work","description":"A glimpse of the transformations we create every day","gallery_images":["https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80"]}'),

-- Contact
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'contact', 7,
 '{"title":"Book Your Appointment","phone":"11977334455","is_whatsapp":true,"button_text":"Schedule Now"}'),

-- Footer
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'glow-studio')),
 'footer', 8,
 '{"title":"Glow Studio"}');


-- =====================================================================
-- SITE 3: Casa do Brasil — Brazilian Food Restaurant
-- =====================================================================
INSERT INTO sites (user_id, slug, status, design) VALUES (
    (SELECT id FROM users WHERE email = 'restaurant@superpage.co.uk'),
    'casa-do-brasil',
    'active',
    '{"primary_color":"#16a34a","title_font":"Playfair Display","text_font":"Inter","button_style":"rounded"}'
);
INSERT INTO pages (site_id, slug, title, status) VALUES (
    (SELECT id FROM sites WHERE slug = 'casa-do-brasil'),
    'home', 'Casa do Brasil — Authentic Brazilian Cuisine', 'published'
);

INSERT INTO blocks (page_id, type, sort_order, config) VALUES
-- Header
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'header', 1, '{"title":"Casa do Brasil"}'),

-- Hero
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'hero', 2,
 '{"items":[{"title":"A Taste of Brazil in Your City","description":"Authentic Brazilian cuisine made from family recipes passed down through generations. Fresh ingredients, bold flavors, warm hospitality.","image":"https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1920&q=80","button_text":"See Our Menu","button_link":"#menu"},{"title":"Sunday Feijoada","description":"Every Sunday from 12pm to 4pm. Our legendary black bean stew with premium cuts — the real deal.","image":"https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1920&q=80","button_text":"Reserve a Table","button_link":"#contact"}]}'),

-- About
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'about', 3,
 '{"title":"Our Story","text":"Casa do Brasil was born from a simple idea: bring the warmth of a Brazilian family kitchen to the world. Chef Marcos Souza, originally from Minas Gerais, opened the restaurant in 2012 after years working in top kitchens across Europe and the US.\n\nEvery dish on our menu tells a story — from the slow-cooked feijoada to the creamy brigadeiro. We source our ingredients locally and import key Brazilian products directly to ensure authenticity in every bite.","image":"https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80","button_text":"Make a Reservation","button_link":"#contact"}'),

-- Products (Menu)
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'products', 4,
 '{"title":"Our Menu Highlights","items":[{"title":"Feijoada Completa","description":"Slow-cooked black bean stew with smoked pork ribs, sausage, and pork shoulder. Served with rice, collard greens, orange, and farofa.","image":"https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=800&q=80","button_text":"$28.90"},{"title":"Shrimp Moqueca","description":"Bahian-style coconut milk shrimp stew with dendê palm oil, bell peppers, tomato, and fresh herbs. Served with white rice and pirão.","image":"https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=800&q=80","button_text":"$26.90"},{"title":"Picanha Grelhada","description":"Brazil is most famous cut of beef, grilled to perfection and served with chimichurri, fries, and vinaigrette salsa.","image":"https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=800&q=80","button_text":"$32.90"},{"title":"Brigadeiro Trio","description":"Three handcrafted brigadeiros in classic chocolate, white chocolate with coconut, and passion fruit. A sweet taste of Brazil.","image":"https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?auto=format&fit=crop&w=800&q=80","button_text":"$8.90"}]}'),

-- Testimonials
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'testimonials', 5,
 '{"title":"What Our Guests Say","items":[{"title":"Andrew P.","description":"The feijoada took me straight back to my time living in Rio. This is the most authentic Brazilian food I have had outside of Brazil. Incredible!","image":"https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&q=80"},{"title":"Sofia G.","description":"Brought my family here for a birthday dinner. The food was spectacular and the staff made us feel like we were in someone is home. Will be back soon!","image":"https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=150&q=80"},{"title":"James T.","description":"The moqueca was out of this world. Rich, flavorful, and perfectly balanced. Best dish I have eaten all year — hands down.","image":"https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80"}]}'),

-- Gallery
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'gallery', 6,
 '{"title":"A Taste of What Awaits","description":"From our kitchen to your table","gallery_images":["https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?auto=format&fit=crop&w=600&q=80"]}'),

-- Contact
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'contact', 7,
 '{"title":"Reservations & Contact","phone":"11966223344","is_whatsapp":false,"button_text":"Send Message"}'),

-- Footer
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'casa-do-brasil')),
 'footer', 8,
 '{"title":"Casa do Brasil"}');


-- =====================================================================
-- SITE 4: Horizons Travel — Tourism & Travel Agency
-- =====================================================================
INSERT INTO sites (user_id, slug, status, design) VALUES (
    (SELECT id FROM users WHERE email = 'travel@superpage.co.uk'),
    'horizons-travel',
    'active',
    '{"primary_color":"#0369a1","title_font":"Plus Jakarta Sans","text_font":"Inter","button_style":"rounded-full"}'
);
INSERT INTO pages (site_id, slug, title, status) VALUES (
    (SELECT id FROM sites WHERE slug = 'horizons-travel'),
    'home', 'Horizons Travel — Your Dream Trip Starts Here', 'published'
);

INSERT INTO blocks (page_id, type, sort_order, config) VALUES
-- Header
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'header', 1, '{"title":"Horizons Travel"}'),

-- Hero
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'hero', 2,
 '{"items":[{"title":"Your Dream Trip Starts Here","description":"Tailor-made travel packages to the most breathtaking destinations in the world. Let us handle every detail.","image":"https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=1920&q=80","button_text":"Explore Packages","button_link":"#packages"},{"title":"Brazil: Land of Wonders","description":"From the Amazon jungle to the crystal beaches of Maldives. Discover the best of Brazil with our expert guides.","image":"https://images.unsplash.com/photo-1483729558449-99ef09a8c325?auto=format&fit=crop&w=1920&q=80","button_text":"See Brazil Tours","button_link":"#packages"}]}'),

-- About
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'about', 3,
 '{"title":"About Horizons Travel","text":"With over 20 years of experience in the travel industry, Horizons Travel has helped thousands of families, couples, and solo travelers create unforgettable memories around the world.\n\nWe believe that travel is more than just visiting places — it is about connecting with cultures, people, and yourself. Our team of passionate travel experts works tirelessly to craft personalized itineraries that match your budget, style, and dreams.","image":"https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80","button_text":"Start Planning","button_link":"#contact"}'),

-- Services
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'services', 4,
 '{"title":"Our Travel Packages","description":"Carefully curated experiences for every type of traveler","items":[{"title":"Honeymoon Packages","description":"Romantic getaways to the Maldives, Bali, Santorini, and Fernando de Noronha. All-inclusive options available with private villa upgrades.","image":"https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80","button_text":"Explore","button_link":"#contact"},{"title":"Brazil Adventure Tours","description":"Amazon expedition, Pantanal wildlife safari, Iguazu Falls, and Chapada Diamantina trekking. Off-the-beaten-path Brazil for adventurous souls.","image":"https://images.unsplash.com/photo-1483729558449-99ef09a8c325?auto=format&fit=crop&w=800&q=80","button_text":"Explore","button_link":"#contact"},{"title":"Family Vacations","description":"Kid-friendly itineraries to Orlando, Cancun, Porto de Galinhas, and Europe. Stress-free travel with everything organized for your family.","image":"https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=800&q=80","button_text":"Explore","button_link":"#contact"}]}'),

-- Testimonials
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'testimonials', 5,
 '{"title":"Happy Travelers","items":[{"title":"David & Emma W.","description":"Horizons planned our entire honeymoon in the Maldives and it was absolutely perfect. Every detail was taken care of. Pure magic!","image":"https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&q=80"},{"title":"The Rodrigues Family","description":"Our family trip to Orlando was seamless. The kids had the time of their lives and we did not have to worry about a single thing. Highly recommend!","image":"https://images.unsplash.com/photo-1511895426328-dc8714191011?auto=format&fit=crop&w=150&q=80"},{"title":"Carla M.","description":"The Amazon expedition was the most transformative experience of my life. The guides were knowledgeable and the logistics were flawless.","image":"https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=150&q=80"}]}'),

-- Gallery
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'gallery', 6,
 '{"title":"Destinations We Love","description":"A world of experiences waiting for you","gallery_images":["https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1483729558449-99ef09a8c325?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1548574505-5e239809ee19?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=600&q=80","https://images.unsplash.com/photo-1530521954074-e64f6810b32d?auto=format&fit=crop&w=600&q=80"]}'),

-- Contact
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'contact', 7,
 '{"title":"Plan Your Trip","phone":"11955112233","is_whatsapp":true,"button_text":"Get a Free Quote"}'),

-- Footer
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'horizons-travel')),
 'footer', 8,
 '{"title":"Horizons Travel"}');


-- =====================================================================
-- SITE 5: SwiftDrop — Delivery Services
-- =====================================================================
INSERT INTO sites (user_id, slug, status, design) VALUES (
    (SELECT id FROM users WHERE email = 'delivery@superpage.co.uk'),
    'swiftdrop',
    'active',
    '{"primary_color":"#ea580c","title_font":"Plus Jakarta Sans","text_font":"Inter","button_style":"rounded"}'
);
INSERT INTO pages (site_id, slug, title, status) VALUES (
    (SELECT id FROM sites WHERE slug = 'swiftdrop'),
    'home', 'SwiftDrop — Fast & Reliable Delivery Services', 'published'
);

INSERT INTO blocks (page_id, type, sort_order, config) VALUES
-- Header
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'swiftdrop')),
 'header', 1, '{"title":"SwiftDrop"}'),

-- Hero
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'swiftdrop')),
 'hero', 2,
 '{"items":[{"title":"Delivered in 60 Minutes or Less","description":"Same-day delivery for businesses and individuals. Fast, reliable, and trackable from pickup to doorstep.","image":"https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=1920&q=80","button_text":"Get a Quote","button_link":"#contact"},{"title":"For Businesses: Outsource Your Logistics","description":"Monthly contracts with dedicated drivers. Perfect for restaurants, e-commerce, pharmacies, and retail stores.","image":"https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?auto=format&fit=crop&w=1920&q=80","button_text":"Business Plans","button_link":"#services"}]}'),

-- About
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'swiftdrop')),
 'about', 3,
 '{"title":"About SwiftDrop","text":"SwiftDrop was founded in 2019 with a mission to make local delivery fast, affordable, and reliable. Starting with just 5 drivers in one city, we have grown to a fleet of 200+ couriers across 12 cities.\n\nWe serve individuals, small businesses, restaurants, pharmacies, and e-commerce stores. Our real-time tracking technology and dedicated support team ensure your package always arrives on time and in perfect condition.","image":"https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?auto=format&fit=crop&w=800&q=80","button_text":"Get Started","button_link":"#contact"}'),

-- Services
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'swiftdrop')),
 'services', 4,
 '{"title":"Our Services","description":"Delivery solutions for every need and budget","items":[{"title":"Express Delivery","description":"Urgent same-day delivery within the city. Pickup in 30 minutes, delivery in 60 minutes or less. Real-time GPS tracking included.","image":"https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=800&q=80"},{"title":"Scheduled Delivery","description":"Plan your deliveries up to 7 days in advance. Choose your preferred time window and we will be there. Perfect for e-commerce businesses.","image":"https://images.unsplash.com/photo-1580674285054-bed31e145f59?auto=format&fit=crop&w=800&q=80"},{"title":"Business Logistics","description":"Dedicated couriers for restaurants, pharmacies, and retail. Monthly or annual contracts with priority support and volume discounts.","image":"https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?auto=format&fit=crop&w=800&q=80"}]}'),

-- Testimonials
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'swiftdrop')),
 'testimonials', 5,
 '{"title":"Trusted by Hundreds of Businesses","items":[{"title":"Bruno A. — Restaurant Owner","description":"We switched our delivery to SwiftDrop 6 months ago. Zero lost orders, happier customers, and our drivers love the app. Best decision we made.","image":"https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80"},{"title":"Larissa P. — E-commerce Store","description":"SwiftDrop handles all our same-day orders now. The real-time tracking has eliminated customer complaints about delivery. Absolutely worth it.","image":"https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80"},{"title":"Carlos N. — Pharmacy Manager","description":"Fast, reliable, and professional. Our customers love getting their prescriptions delivered the same day. SwiftDrop is a game-changer.","image":"https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80"}]}'),

-- Contact
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'swiftdrop')),
 'contact', 6,
 '{"title":"Request a Quote","phone":"11944556677","is_whatsapp":true,"button_text":"Send Message"}'),

-- Footer
((SELECT id FROM pages WHERE site_id = (SELECT id FROM sites WHERE slug = 'swiftdrop')),
 'footer', 7,
 '{"title":"SwiftDrop"}');
