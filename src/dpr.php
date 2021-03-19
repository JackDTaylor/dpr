<?php
use JackDTaylor\Dpr\Dpr;

Dpr::init();

/**
 * Checks whether user is developer or not
 * @return bool Returns TRUE if $_SERVER['REMOTE_ADDR'] is in DPR_DEVELOPER_IPS constant.
 */
function is_developer() {
	return Dpr::isDeveloper();
}

/**
 * Basic functionality. Prints variables provided as arguments and stops the script execution.
 * @param var1 mixed   Variable to print
 * @param _    mixed   [optional] Function supports any number of arguments
 * @return mixed
 */
function dpr() {
    return Dpr::dump(func_get_args());
}

/**
 * Same as dpr(), but uses var_dump() instead of print_r()
 * @param var1 mixed   Variable to print
 * @param _    mixed   [optional] Function supports any number of arguments
 * @return mixed
 */
function dprv() {
    return Dpr::dump(func_get_args(), true);
}

/**
 * Prints backtrace and stops the script execution.
 * @param var1 mixed   Additional variable to print
 * @param _    mixed   [optional] Function supports any number of arguments
 */
function dprt() {
    $trace_result = array();

    foreach(debug_backtrace(false) as $trace_call) {
        $trace_result[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $trace_call['file'] ?? '<unknown>') . ':' . ($trace_call['line'] ?? '0');
    }

    dpr($trace_result, ...func_get_args());
}

/**
 * Defines a breakpoint for dprd()
 */
function dprb() {
    $breakpoint_at = pos(debug_backtrace(false));

    define('__DPR_BREAKPOINT_POSITION', $breakpoint_at['file'] . ':' . $breakpoint_at['line']);
}

/**
 * Triggers dpr() if breakpoint was defined with dprb()
 * @param mixed var1   Variable to print
 * @param mixed _      [optional] Function supports any number of arguments
 * @return mixed
 */
function dprd() {
    if(Dpr::hasBreakpoint()) {
        return Dpr::dump(func_get_args(), false);
    }

    return func_get_arg(0);
}