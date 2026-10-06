-- ===========================================================================
-- TEST DATA — sample Contact Us messages, one per status, for building and
-- testing the staff inbox. NEVER on production. Idempotent (fixed ids).
-- ===========================================================================
SET NAMES utf8mb4;

INSERT IGNORE INTO contact_messages (id, user_id, name, phone, email, topic, message, ip_address, status, created_at) VALUES
(1, NULL, 'Test Parent',  '01711111111', NULL,                 'payment',     'আমার ছেলের ওয়ালেটে ৫ অক্টোবর ৫০ টাকা টপ-আপ করেছি, কিন্তু ব্যালেন্সে দেখাচ্ছে না।', '127.0.0.1', 'new',     NOW() - INTERVAL 2 HOUR),
(2, (SELECT id FROM users WHERE phone = '01700000001'), 'Rahim Ahmed',  '01700000001', NULL,                 'question',    'ভগ্নাংশ অধ্যায়ের একটি প্রশ্নে দুটি সঠিক উত্তর আছে মনে হচ্ছে।',                        '127.0.0.1', 'read',    NOW() - INTERVAL 1 DAY),
(3, NULL, 'Head Teacher', NULL,          'head@example.com',   'school',      'We would like to use AcademicMCQ for our Class 5 students. Is there school pricing?',   '127.0.0.1', 'replied', NOW() - INTERVAL 3 DAY),
(4, NULL, 'Test Student', '01822222222', 'student@example.com','technical',   'The quiz page did not load on my phone yesterday evening.',                              '127.0.0.1', 'closed',  NOW() - INTERVAL 6 DAY);
