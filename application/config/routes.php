<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI routing. Clean URLs map to controller/method; see the CodeIgniter 3
| user guide, "URI Routing". Specific routes must come before (:any) ones.
| -------------------------------------------------------------------------
*/
$route['default_controller'] = 'home';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Practice: class -> subject (chapter page, design 11)
$route['practice']                       = 'practice/index';
$route['practice/(:any)/(:any)']         = 'practice/subject/$1/$2';

// Quiz flow: start -> take -> result (design 10) -> review (design 09)
$route['quiz/start']                     = 'quiz/start';
$route['quiz/(:num)']                    = 'quiz/take/$1';
$route['quiz/(:num)/answer']             = 'quiz/answer/$1';
$route['quiz/(:num)/submit']             = 'quiz/submit/$1';
$route['quiz/(:num)/result']             = 'quiz/result/$1';
$route['quiz/(:num)/review']             = 'quiz/review/$1';

// Phone OTP sign-in
$route['login']                          = 'auth/login';
$route['login/verify']                   = 'auth/verify';
$route['register']                       = 'auth/register';
$route['logout']                         = 'auth/logout';

// Signed-in student
$route['dashboard']                      = 'dashboard/index';
$route['progress']                       = 'progress/index';
$route['rank']                           = 'rank/index';
$route['wallet']                         = 'wallet/index';
$route['wallet/topup']                   = 'wallet/topup';
$route['profile']                        = 'profile/index';
$route['correct-me']                     = 'correct_me/index';
$route['correct-me/(:num)']              = 'correct_me/question/$1';

// Pages linked from the designs' header and homepage
$route['signup']                         = 'auth/login/signup';
$route['search']                         = 'search/index';
$route['notifications']                  = 'notifications/index';
$route['notifications/read']             = 'notifications/read';
$route['certificates']                   = 'certificates/index';
$route['schools']                        = 'schools_board/index';
$route['testimonials']                   = 'testimonials/index';
$route['quiz/(:num)/pause']              = 'quiz/pause/$1';
$route['quiz/(:num)/resume']             = 'quiz/resume/$1';

// Public
$route['leaderboard']                    = 'leaderboard/index';
$route['competition']                    = 'competition/index';
$route['competition/register']           = 'competition/register';
$route['(how-it-works|pricing|faq|about|contact|terms|privacy|refund-policy|correct-me-policy)'] = 'pages/show/$1';
