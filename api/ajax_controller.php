<?php

// use core_completion\progress;
// use core_course\external\course_summary_exporter;

error_reporting(E_ALL);
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/enrol/externallib.php');

try {
	global $USER, $PAGE;
	$details = $_POST;
	$returnArr = array();

	if (!isset($_REQUEST['request_type']) || strlen($_REQUEST['request_type']) == false) {
		throw new Exception();
	}

	switch ($_REQUEST['request_type']) {
		case 'getPreguntasEncuesta':
			$cursoid = $_REQUEST['cursoid'];
			$coursemoduleid = $_REQUEST['coursemoduleid'];
			$module = $_REQUEST['module'];
			$sesskey = $_REQUEST['sesskey'];
			$returnArr = getPreguntasEncuesta($cursoid, $coursemoduleid, $module, $sesskey);
			break;
		case 'encuestaRespByUser':
			$id = $_REQUEST['id'];
			$puntaje = $_REQUEST['puntaje'];
			$cursoid = $_REQUEST['cursoid'];
			$coursemoduleid = $_REQUEST['coursemoduleid'];
			$module = $_REQUEST['module'];
			$sesskey = $_REQUEST['sesskey'];
			$returnArr = encuestaRespByUser($id, $cursoid, $coursemoduleid, $module, $puntaje, $sesskey);
			break;
	}

} catch (Exception $e) {
	$returnArr['status'] = false;
	$returnArr['data'] = $e->getMessage();
}

header('Content-type: application/json');

echo json_encode($returnArr);
exit();

/** 
 * getPreguntasEncuesta
 * * obtengo las pregunta de la encuesta 
 * ? se deberia excluir las preguntas que ya fueron respondidas?
 * ? se deberia mostrar la puntuacion de la pregunta si ya fue marcada? -- POR DEFECTO
 */
function getPreguntasEncuesta($cursoid, $coursemoduleid, $module, $sesskey) {
	global $DB, $USER;
	require_sesskey();
	$not_in = [];
	$data = [];
	$if_exists = $DB->get_records('aq_encuesta_user_data', [
		'userid' => $USER->id,
		'course' => $cursoid,
		'moduleid' => $coursemoduleid,
		'module' => $module,
	]);
	if(count($if_exists)){
		foreach ($if_exists as $key => $value) {
			array_push($not_in, $value->preg_encuestaid);
		}
	}
	// TODO: si se decide excluir las preguntas entonces hacer un RAW SQL QUERY 
	$preguntas = $DB->get_records('aq_encuesta_data', [
		'active' => 1
	]);

	foreach ($preguntas as $key => $value) {
		$puntaje = $DB->get_field('aq_encuesta_user_data', 'puntaje', [
			'userid' => $USER->id,
			'course' => $cursoid,
			'moduleid' => $coursemoduleid,
			'module' => $module,
			'preg_encuestaid' => $value->id
		]);
		array_push($data, [
			'id' => $value->id,
			'pregunta' => $value->pregunta,
			'puntaje' => $puntaje == false ? 0 : intval($puntaje)
		]);
	}
	return $data;
}

/**
 * encuestaRespByUser
 * * guarda las respuestas del usuario
 * @param id es el id de la pregunta
 * @param puntaje es el puntaje de la pregunta
 * @param sesskey es la sesion del usuario
 */
function encuestaRespByUser($id, $cursoid, $coursemoduleid, $module, $puntaje, $sesskey){
	global $DB, $USER;
	require_sesskey();

	$if_exists = $DB->get_records('aq_encuesta_user_data', [
		'userid' => $USER->id,
		'course' => $cursoid,
		'moduleid' => $coursemoduleid,
		'module' => $module,
		'preg_encuestaid' => $id,
	]);

	if(count($if_exists)){
		foreach ($if_exists as $key => $value) {
			$data = array(
				'id' => $value->id,
				'puntaje' => $puntaje,
				'updated_at' => time()
			);
			$DB->update_record('aq_encuesta_user_data', $data);
		}
		return 'updated';
	}else{
		$data = array(
			'userid' => $USER->id,
			'preg_encuestaid' => $id,
			'course' => $cursoid,
			'moduleid' => $coursemoduleid,
			'module' => $module,
			'puntaje' => $puntaje,
			'created_at' => time()
		);
		$insert_id = $DB->insert_record('aq_encuesta_user_data', $data);
		return 'inserted';
	}
}