<?php

function get_dbcfg() {
    $cnf = function($get = false) {
	$file = 'dbcfg.cnf';
	if ($get)
	    return $file;
	if (file_exists($file))
	    return $file;
	elseif (isset($_GET['dbcfg'])) {
	    $file = "$_SERVER[DOCUMENT_ROOT]/$_GET[dbcfg]";
	    if (file_exists($file))
		return $file;
	}
	elseif (isset($_COOKIE['dbcfg'])) {
	    $file = "$_SERVER[DOCUMENT_ROOT]/$_COOKIE[dbcfg]";
	    if (file_exists($file))
		return $file;
	}
    };
    $msg = function($fmt,$arg) {
	$res = sprintf($fmt, $arg);
	return function_exists('lang') ? lang($res) : $res;
    };
    if (is_null($file = $cnf()))
	return $msg("cannot find file '%s'", $cnf(true));
    if (($cnf = file_get_contents($file)) === false)
        return $msg("cannot get '%s' contents", $file);
    if (is_null($ini = preg_replace('/\n;([a-z])/', "\n$1", $cnf)))
        return $msg("cannot process '%s' contents", $file);
    if (($ret = parse_ini_string($ini, false, INI_SCANNER_TYPED)) === false)
        return $msg("cannot parse '%s' contents", $file);

    return $ret;
}

call_user_func(function() {
    $log = basename(__FILE__, '.php').'.log';
    if (count($_GET) > 0 && is_writable($log))
	file_put_contents($log, sprintf('%s _GET=%s', date('Y-m-d H:i:s'), print_r($_GET, true)), FILE_APPEND);

    if (isset($_GET['file']))
	return;

    $vars = [ 'db'=>'', 'username'=>'', 'server'=>'' ];
    $ok = true;
    foreach ($vars as $key => $var)
	if (!isset($_GET[$key])) {
	    $ok = false;
	    break;
	}
    if ($ok || gettype($cfg = get_dbcfg()) == 'string')
	return;
    $vars['server'] = $cfg['host'];
    $vars['username'] = $cfg['user'];
    $vars['db'] = $cfg['base'];

    $get = $_GET;
    foreach ($vars as $key => $var)
    {
	if (!isset($get[$var]))
	    $get = [ $key => $var ] + $get;
    }
    $args = '';
    foreach ($get as $key => $var)
	$args .= ($args ? '&' : '?')."$key=$var";

    header("Location: $_SERVER[SCRIPT_NAME]$args");
    exit;
});
