<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * FAQ in Bangla and English. Every answer restates a live rule, so the numbers
 * come from constants, the competition row, the prize table and the tiers
 * table, never typed here (CLAUDE.md: a duplicated rule drifts).
 *
 * @var string $lang 'bn' | 'en'  @var array|null $comp  @var int $winners  @var array $tiers  @var array $classes
 */
$bn = $lang === 'bn';
$n = function ($v) use ($bn) { return $bn ? bn_digits($v) : (string) $v; };      // a number in this language
$tk = function ($v) use ($n) { return '৳' . $n(rtrim(rtrim(number_format((float) $v, 2), '0'), '.')); };
$t = function ($bn_text, $en_text) use ($bn) { return $bn ? $bn_text : $en_text; };

$fee     = $comp ? $comp['entry_fee'] : COMPETITION_FEE_TAKA;
$minutes = QUIZ_QUESTION_COUNT * QUIZ_SECONDS_PER_QUESTION / 60;
$r1q     = $comp ? (int) $comp['round1_question_count'] : COMPETITION_ROUND1_EASY + COMPETITION_ROUND1_MEDIUM + COMPETITION_ROUND1_HARD;
$r1m     = $comp ? (int) $comp['round1_time_limit_minutes'] : 0;
$fq      = $comp ? (int) $comp['final_question_count'] : COMPETITION_FINAL_EASY + COMPETITION_FINAL_MEDIUM + COMPETITION_FINAL_HARD;
$fm      = $comp ? (int) $comp['final_time_limit_minutes'] : 0;
$qualify = $comp ? rtrim(rtrim($comp['qualify_percent'], '0'), '.') : '';

$tier_list = array();
foreach ($tiers as $tier) {
	$tier_list[] = (int) $tier['min_points'] === 0 ? $tier['title'] : $tier['title'] . ' (' . $n(number_format((int) $tier['min_points'])) . ')';
}
$class_list = implode(', ', array_map(function ($k) use ($bn, $n) {
	return $bn ? $n(str_replace('Class ', 'শ্রেণি ', $k['name'])) : $k['name'];
}, $classes));

$groups = array(
	'start' => array('icon' => 'play', 'title' => $t('শুরু করা', 'Getting started'), 'items' => array(
		array($t('প্র্যাকটিস করতে কি অ্যাকাউন্ট লাগবে?', 'Do I need an account to practise?'),
			$t('না। অ্যাকাউন্ট ছাড়াই ' . $n(GUEST_FREE_QUIZZES) . 'টি কুইজ দিয়ে দেখতে পারো। ফলাফল সংরক্ষণ আর র‍্যাংক গড়তে ফোন নম্বর দিয়ে ফ্রি অ্যাকাউন্ট খোলো; আগে দেওয়া কুইজগুলোও তাতে জমা থাকবে।',
			   'No. You can try ' . GUEST_FREE_QUIZZES . ' quizzes without an account. Create a free account with your phone number to save results and build your rank; the quizzes you already took are saved to it.')),
		array($t('খরচ কত?', 'How much does it cost?'),
			$t('প্রতিটি কুইজে ' . $tk(QUIZ_FEE_TAKA) . ' (' . $n(QUIZ_QUESTION_COUNT) . 'টি প্রশ্ন), টাকা কাটা হয় তোমার ওয়ালেট থেকে। কোনো সাবস্ক্রিপশন নেই। জাতীয় প্রতিযোগিতায় একবার ' . $tk($fee) . ' দিয়ে রেজিস্ট্রেশন করতে হয়।',
			   $tk(QUIZ_FEE_TAKA) . ' per quiz of ' . QUIZ_QUESTION_COUNT . ' questions, paid from your wallet. No subscription. The national competition is a one-time ' . $tk($fee) . '.')),
		array($t('একটি কুইজে কী থাকে?', 'What is in a quiz?'),
			$t('একটি অধ্যায় বা টপিক থেকে ' . $n(QUIZ_QUESTION_COUNT) . 'টি প্রশ্ন: ' . $n(QUIZ_MIX_EASY) . 'টি সহজ, ' . $n(QUIZ_MIX_MEDIUM) . 'টি মাঝারি আর ' . $n(QUIZ_MIX_HARD) . 'টি কঠিন; সময় ' . $n($minutes) . ' মিনিট। প্রতিটি উত্তরের ব্যাখ্যা দেওয়া থাকে।',
			   QUIZ_QUESTION_COUNT . ' questions from one chapter or topic: ' . QUIZ_MIX_EASY . ' easy, ' . QUIZ_MIX_MEDIUM . ' medium and ' . QUIZ_MIX_HARD . ' hard, with ' . $minutes . ' minutes to finish. Every answer has an explanation.')),
		array($t('কোন কোন শ্রেণি আছে?', 'Which classes are available?'),
			$t('এখন ' . $class_list . ' (বাংলা মাধ্যম, NCTB)। আরও শ্রেণি, English Version আর English Medium শিগগিরই আসছে।',
			   $class_list . ' (Bangla medium, NCTB) at launch. More classes, English Version and English Medium are coming.')),
	)),
	'points' => array('icon' => 'star', 'title' => $t('পয়েন্ট ও র‍্যাংক', 'Points and rank'), 'items' => array(
		array($t('Academic Points কীভাবে পাব?', 'How do I earn Academic Points?'),
			$t('প্রতিটিতে ' . $n(STREAK_PASS_PERCENTAGE) . '% বা তার বেশি পেয়ে ' . $n(STREAK_WINDOW_HOURS) . ' ঘণ্টার মধ্যে পরপর ' . $n(STREAK_REQUIRED_QUIZZES) . 'টি কুইজ শেষ করলে ' . $n(STREAK_POINTS) . ' পয়েন্ট; কোনো অধ্যায়ে পরপর ' . $n(MASTERY_RUN) . 'টি কুইজে ' . $n(MASTERY_PERCENTAGE) . '%+ পেলে ' . $n(MASTERY_POINTS) . ' পয়েন্ট; তোমার পাঠানো সংশোধন অনুমোদিত হলে ' . $n(CORRECTION_APPROVED_POINTS) . ' পয়েন্ট; আর জাতীয় প্রতিযোগিতায় পুরস্কার পেলে।',
			   'Complete a streak of ' . STREAK_REQUIRED_QUIZZES . ' quizzes, each scoring ' . STREAK_PASS_PERCENTAGE . '% or more, within ' . STREAK_WINDOW_HOURS . ' hours (' . STREAK_POINTS . ' point); master a chapter (' . MASTERY_RUN . ' quizzes in a row at ' . MASTERY_PERCENTAGE . '%+, ' . MASTERY_POINTS . ' points); get a correction approved (' . CORRECTION_APPROVED_POINTS . ' point); and by winning a place in the national competition.')),
		array($t('Academic Points কি খরচ করা যায়?', 'Can I spend my Academic Points?'),
			$t('না। Academic Points হলো তোমার একাডেমিক সুনাম। এগুলো কখনো কমে না, আর এগুলো টাকাও নয়। তোমার ওয়ালেটের টাকা (৳) সম্পূর্ণ আলাদা।',
			   'No. Academic Points are your academic reputation. They never decrease and are never money. Your wallet balance (৳) is completely separate.')),
		array($t('টিয়ারগুলো কী কী?', 'What are the tiers?'),
			$t(implode(', ', $tier_list) . ' পয়েন্টে।', implode(', ', $tier_list) . ' points.')),
	)),
	'competition' => array('icon' => 'trophy', 'title' => $t('জাতীয় প্রতিযোগিতা', 'National competition'), 'items' => array(
		array($t('জাতীয় প্রতিযোগিতা কীভাবে হয়?', 'How does the national competition work?'),
			$t('একবার ' . $tk($fee) . ' দিয়ে রেজিস্ট্রেশন। প্রথম রাউন্ডে ' . $n($r1m) . ' মিনিটে ' . $n($r1q) . 'টি প্রশ্ন (' . $n(COMPETITION_ROUND1_EASY) . 'টি সহজ, ' . $n(COMPETITION_ROUND1_MEDIUM) . 'টি মাঝারি, ' . $n(COMPETITION_ROUND1_HARD) . 'টি কঠিন; কঠিন প্রশ্নে বেশি নম্বর)। সেরা ' . $n($qualify) . '% যাবে ফাইনালে: ' . $n($fm) . ' মিনিটে ' . $n($fq) . 'টি প্রশ্ন, একটি একটি করে, আগের প্রশ্নে ফেরা যাবে না। নম্বর সমান হলে যে কম সময় নিয়েছে সে এগিয়ে। মোট ' . $n($winners) . ' জন বিজয়ী।',
			   'Register once for ' . $tk($fee) . '. Round 1 has ' . $r1q . ' questions in ' . $r1m . ' minutes (' . COMPETITION_ROUND1_EASY . ' easy, ' . COMPETITION_ROUND1_MEDIUM . ' medium, ' . COMPETITION_ROUND1_HARD . ' hard; harder questions are worth more). The top ' . $qualify . '% go to the final: ' . $fq . ' questions in ' . $fm . ' minutes, one at a time, with no going back. Ties are broken by time taken. There are ' . $winners . ' winners.')),
		array($t('সার্টিফিকেট আর পুরস্কার কীভাবে পাব?', 'How do certificates and prizes work?'),
			$t('প্রথম রাউন্ডে অংশ নেওয়া প্রত্যেকে সার্টিফিকেট পায়; ফাইনালিস্ট আর বিজয়ীরাও আলাদা সার্টিফিকেট পায়। প্রতিটি সার্টিফিকেটে একটি Verification ID থাকে, যা যে কেউ আমাদের সাইটে যাচাই করতে পারে। পুরস্কারের টাকা বিজয়ীদের সরাসরি পাঠানো হয়, AcademicMCQ ওয়ালেটে নয়; বিস্তারিত প্রতিযোগিতার নিয়মে থাকবে।',
			   'Everyone who sits Round 1 gets a participation certificate; finalists and winners get theirs too. Each certificate has a Verification ID that anyone can check on our site. Prize money is paid to winners directly, not into the AcademicMCQ wallet; the details are in the competition rules.')),
	)),
	'help' => array('icon' => 'shield', 'title' => $t('সাহায্য ও গোপনীয়তা', 'Help and privacy'), 'items' => array(
		array($t('“Correct Me” কী?', 'What is “Correct Me”?'),
			$t('কোনো প্রশ্ন বা উত্তর ভুল মনে হলে উত্তর রিভিউ থেকে রিপোর্ট করো। আমাদের টিম প্রতিটি রিপোর্ট দেখে; সংশোধন অনুমোদিত হলে ' . $n(CORRECTION_APPROVED_POINTS) . ' Academic Point পাবে।',
			   'If you think a question or its answer is wrong, report it from the answer review. Our team reviews every report; an approved correction earns ' . CORRECTION_APPROVED_POINTS . ' Academic Point.')),
		array($t('আমার ফোন নম্বর কি সবাই দেখতে পায়?', 'Is my phone number shown publicly?'),
			$t('না। লিডারবোর্ডে শুধু ছোট একটা নাম দেখায়, যেমন “Rahim A.”। প্রোফাইল থেকে নামটা বদলাতে পারো, চাইলে লিডারবোর্ড থেকে নিজেকে লুকাতেও পারো।',
			   'No. Leaderboards show a short display name like “Rahim A.”. You can change it, or hide yourself from the leaderboard, in your profile.')),
		array($t('বন্ধুদের আমন্ত্রণ জানাতে পারি?', 'Can I invite my friends?'),
			$t('হ্যাঁ। অ্যাকাউন্টের Invite Friends থেকে তোমার লিংক শেয়ার করো। বন্ধু সেই লিংকে অ্যাকাউন্ট খুললে তুমি সেখানে দেখতে পাবে।',
			   'Yes. Open Invite Friends in your account and share your link. When a friend creates an account with it, you will see them there.')),
	)),
);
$first = TRUE;
?>
<section class="page-band faq-band">
  <div class="amcq-container faq-band__inner">
    <div class="page-band__title">
      <span class="tile tile--blue"><?= icon('question') ?></span>
      <div lang="<?= $lang ?>">
        <h1><?= e($t('প্রায়ই জিজ্ঞাসিত প্রশ্ন', 'Frequently Asked Questions')) ?></h1>
        <p><?= e($t('AcademicMCQ নিয়ে সবচেয়ে বেশি যে প্রশ্নগুলো আসে, তার উত্তর।', 'Answers to the questions students and parents ask most.')) ?></p>
      </div>
    </div>
    <nav class="lang-toggle" aria-label="ভাষা / Language">
      <a href="<?= site_url('faq?lang=bn') ?>" lang="bn"<?= $bn ? ' aria-current="true" class="is-active"' : '' ?>>বাংলা</a>
      <a href="<?= site_url('faq?lang=en') ?>" lang="en"<?= $bn ? '' : ' aria-current="true" class="is-active"' ?>>English</a>
    </nav>
  </div>
</section>

<div class="amcq-container faq-layout" lang="<?= $lang ?>">
  <aside class="faq-nav" aria-label="<?= e($t('বিষয়', 'Topics')) ?>">
    <p class="faq-nav__label"><?= e($t('বিষয়', 'Topics')) ?></p>
    <?php foreach ($groups as $key => $g): ?>
      <a href="#faq-<?= $key ?>"><?= icon($g['icon']) ?><span><?= e($g['title']) ?></span><small><?= $n(count($g['items'])) ?></small></a>
    <?php endforeach; ?>
  </aside>

  <div class="faq-main">
    <label class="faq-search"><?= icon('search') ?><span class="sr-only"><?= e($t('প্রশ্ন খুঁজুন', 'Search the questions')) ?></span>
      <input type="search" data-faq-search placeholder="<?= e($t('প্রশ্ন খুঁজুন…', 'Search the questions…')) ?>" autocomplete="off"></label>
    <p class="faq-empty amcq-muted" data-faq-empty hidden><?= e($t('এমন কোনো প্রশ্ন পাওয়া যায়নি। নিচে আমাদের লিখে জানাও।', 'No question matches that. Write to us below.')) ?></p>

    <?php foreach ($groups as $key => $g): ?>
      <section class="faq-group" id="faq-<?= $key ?>" data-faq-group>
        <h2><span class="chip"><?= icon($g['icon']) ?></span><?= e($g['title']) ?></h2>
        <?php foreach ($g['items'] as $item): ?>
          <details class="faq-item" data-faq-item<?= $first ? ' open' : '' ?>><?php $first = FALSE; ?>
            <summary><span><?= e($item[0]) ?></span><span class="faq-item__sign" aria-hidden="true"><?= icon('plus') ?></span></summary>
            <div class="faq-item__body"><p><?= e($item[1]) ?></p></div>
          </details>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>

    <div class="faq-cta">
      <span class="round round--blue"><?= icon('chat') ?></span>
      <div>
        <h2><?= e($t('আরও প্রশ্ন আছে?', 'Still have a question?')) ?></h2>
        <p class="amcq-muted"><?= e($t('আমাদের লিখে জানাও, আমরা উত্তর দেব।', 'Write to us and we will get back to you.')) ?></p>
      </div>
      <div class="faq-cta__actions">
        <a class="amcq-btn amcq-btn--primary" href="<?= site_url('contact') ?>"><?= icon('mail') ?> <?= e($t('যোগাযোগ করো', 'Contact us')) ?></a>
        <a class="amcq-btn amcq-btn--secondary" href="<?= site_url('how-it-works') ?>"><?= e($t('কীভাবে কাজ করে', 'How it works')) ?></a>
      </div>
    </div>
  </div>
</div>
