<?php 

require_once(__DIR__ . '/../../config.php');
global $DB;

require_login();

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/mod/encuesta/encuesta.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title('Encuesta');
$PAGE->set_heading('Encuesta');

$iframe_link = $DB->get_field('aq_iframe_page', 'iframe_link', ['id'=>1]);

$templateContext = (object)[
    'sesskey' => sesskey(),
    'iframe_link' => $iframe_link
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_encuesta/encuesta', $templateContext);
echo $OUTPUT->footer();