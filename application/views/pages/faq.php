<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Every answer below restates the Master Blueprint v2.0 or the UI guideline.
$faq = array(
	array('Do I need an account to practise?', 'No. You can try ' . GUEST_FREE_QUIZZES . ' quizzes without an account. Create a free account with your phone number to save results and build your rank; the quizzes you already took are saved to it.'),
	array('How much does it cost?', 'Tk ' . number_format(QUIZ_FEE_TAKA) . ' per quiz of ' . QUIZ_QUESTION_COUNT . ' questions, paid from your wallet. No subscription. The national competition is a one-time Tk ' . number_format(COMPETITION_FEE_TAKA) . '.'),
	array('What is in a quiz?', QUIZ_QUESTION_COUNT . ' questions from one chapter or topic: ' . QUIZ_MIX_EASY . ' easy, ' . QUIZ_MIX_MEDIUM . ' medium and ' . QUIZ_MIX_HARD . ' hard, with ' . (QUIZ_QUESTION_COUNT * QUIZ_SECONDS_PER_QUESTION / 60) . ' minutes to finish. Every answer has an explanation.'),
	array('How do I earn Academic Points?', 'Complete a streak of ' . STREAK_REQUIRED_QUIZZES . ' quizzes, each scoring ' . STREAK_PASS_PERCENTAGE . '% or more, within ' . STREAK_WINDOW_HOURS . ' hours (1 point); master a chapter (' . MASTERY_RUN . ' quizzes in a row at ' . MASTERY_PERCENTAGE . '%+, ' . MASTERY_POINTS . ' points); get a correction approved (1 point); and through the competition.'),
	array('Can I spend my Academic Points?', 'No. Academic Points are your academic reputation. They never decrease and are never money. Your wallet balance (৳) is completely separate.'),
	array('What are the tiers?', 'Starter, Bronze Learner (100), Silver Scholar (500), Gold Master (1,000), Platinum Pro (5,000), Diamond Legend (10,000) and Grand Master (50,000 points).'),
	array('How does the national competition work?', 'Register once for Tk ' . number_format(COMPETITION_FEE_TAKA) . '. Round 1 has 30 questions in 45 minutes. The top 5% go to a proctored final of 50 questions. There are 20 winners and certificates for every participant.'),
	array('What is “Correct Me”?', 'If you think a question or answer is wrong, report it from the answer review. A teacher reviews every report; an approved correction earns 1 Academic Point.'),
	array('Is my phone number shown publicly?', 'No. Leaderboards show a short display name like “Rahim A.”. You can change it, or hide yourself from the leaderboard, in your profile.'),
	array('Which classes are available?', 'Class 5 (Bangla medium, NCTB) at launch. More classes, English Version and English Medium are coming.'),
);
?>
<section class="amcq-container page-pad narrow">
  <h1>Frequently Asked Questions</h1>
  <div class="faq">
    <?php foreach ($faq as $i => $f): ?>
      <details class="faq__item"<?= $i === 0 ? ' open' : '' ?>><summary><?= e($f[0]) ?><?= icon('chevron-down') ?></summary><p><?= e($f[1]) ?></p></details>
    <?php endforeach; ?>
  </div>
</section>
