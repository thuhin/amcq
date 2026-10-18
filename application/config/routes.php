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
$route['contact']                        = 'contact/index';

// Admin panel (docs/admin-panel.md). Controllers are prefixed Admin_ so their
// class names can never collide with the student ones (Testimonials, Competition).
$route['admin']                              = 'admin/admin_dashboard/index';
$route['admin/login']                        = 'admin/admin_auth/login';
$route['admin/logout']                       = 'admin/admin_auth/logout';
$route['admin/password']                     = 'admin/admin_auth/password';
$route['admin/questions']                    = 'admin/admin_questions/index';
$route['admin/questions/new']                = 'admin/admin_questions/edit';
$route['admin/questions/(:num)']             = 'admin/admin_questions/edit/$1';
$route['admin/questions/(:num)/status']      = 'admin/admin_questions/status/$1';
$route['admin/corrections']                  = 'admin/admin_corrections/index';
$route['admin/corrections/(:num)']           = 'admin/admin_corrections/review/$1';
$route['admin/corrections/(:num)/approve']   = 'admin/admin_corrections/approve/$1';
$route['admin/corrections/(:num)/reject']    = 'admin/admin_corrections/reject/$1';
$route['admin/messages']                     = 'admin/admin_messages/index';
$route['admin/messages/(:num)']              = 'admin/admin_messages/show/$1';
$route['admin/messages/(:num)/status']       = 'admin/admin_messages/status/$1';
$route['admin/testimonials']                 = 'admin/admin_testimonials/index';
$route['admin/testimonials/new']             = 'admin/admin_testimonials/edit';
$route['admin/testimonials/(:num)']          = 'admin/admin_testimonials/edit/$1';
$route['admin/testimonials/(:num)/publish']  = 'admin/admin_testimonials/publish/$1';
$route['admin/testimonials/(:num)/unpublish'] = 'admin/admin_testimonials/unpublish/$1';
$route['admin/testimonials/(:num)/delete']   = 'admin/admin_testimonials/delete/$1';
$route['admin/competition']                  = 'admin/admin_competition/index';
$route['(how-it-works|pricing|faq|about|terms|privacy|refund-policy|correct-me-policy)'] = 'pages/show/$1';
