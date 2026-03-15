<?php
get_header();

$sections = [
    'hero',
    'popular-guides',
    'rankings',
    'experts',
    'reviews',
    'latest',
    'seo-hub',
    'stats',
    'cta',
    'ads',
    'newsletter',
    'footer-seo',
];

foreach ($sections as $section) {
    get_template_part('template-parts/front-page/' . $section);
}

get_footer();
