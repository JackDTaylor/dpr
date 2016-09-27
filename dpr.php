<?php

if(!function_exists('_dpr')) {
	/**
	 * Use it for IPs you use for testing
	 * @var string List of "|"-separated IPs
	 */
	if(!defined('DPR_DEVELOPER_IPS')) {
		define('DPR_DEVELOPER_IPS', '127.0.0.1|192.168.0.1');
	}

	/**
	 * You can disable check for IPs using this constant
	 */
	if(!defined('DPR_CHECK_DEVELOPER_IPS')) {
		define('DPR_CHECK_DEVELOPER_IPS', true);
	}
	
	/**
	 * Checks whether user is developer or not
	 * @return bool Returns TRUE if $_SERVER['REMOTE_ADDR'] is in DPR_DEVELOPER_IPS constant.
	 */
	function is_developer() {
		if(!defined('DPR_DEVELOPER_IPS')) {
			return false;
		}

		return in_array($_SERVER['REMOTE_ADDR'], explode('|', DPR_DEVELOPER_IPS));
	}

	/**
	 * Internal function for _dpr()
	 */
	function __echo_null() { echo '[NULL]'; }

	/**
	 * Internal function for _dpr()
	 * @param $variable
	 */
	function __echo_bool($variable) { echo $variable ? '[TRUE]' : '[FALSE]'; }

	/**
	 * Internal dpr() function.
	 *
	 * @param $variables   array       List of mixed values to print
	 * @param $is_var_dump boolean     If true, then var_dump() will be used instead of print_r()
	 * @param $breakpoint  string      Breakpoint location for dprb()/dprd() functions
	 * @return mixed
	 */
	function _dpr(array $variables = array(), $is_var_dump = false, $breakpoint = null) {
		if(DPR_CHECK_DEVELOPER_IPS && is_developer() == false) {
			return pos($variables);
		}

		if(ob_get_level()) {
			while(ob_get_level()) {
				ob_end_clean();
			}
		}


		$html_mode = headers_sent();

		if($html_mode === false) {
			header('Content-type: text/plain; charset=utf-8');
		} else {
			echo '
				<style>
				* {
					overflow: hidden; position: static;
				}

				#dprOutput {
					position: fixed;
					overflow: auto;
					top: 0;
					left: 0;
					margin: 0;
					padding: 5px;
					box-sizing: border-box;
					width: 100%;
					height: 100%;
					background-color: #FFFFFF;
					color: #000000;
					font-size: 14px;
					line-height: 125%;
					font-family: Courier New, monospace;
					z-index: 2147483647;
				}
				</style>
				<pre style="dprOutput">
			';
		}

		$called_at = debug_backtrace(false);
		$called_at = $called_at[1];

		// header
		echo str_repeat('#', 78), PHP_EOL;
		echo '#', PHP_EOL;

		if(is_null($breakpoint) == false) {
			echo '#    Breakpoint at ', PHP_EOL;
			echo '#      ', str_replace($_SERVER['DOCUMENT_ROOT'], '', $breakpoint), PHP_EOL;
			echo '#', PHP_EOL;
		}

		echo '#    Debug print at ', PHP_EOL;
		echo '#      ', str_replace($_SERVER['DOCUMENT_ROOT'], '', $called_at['file']), ':', $called_at['line'], PHP_EOL;
		echo '#', PHP_EOL;
		echo str_repeat('#', 78), PHP_EOL, PHP_EOL;

		// prints
		foreach($variables as $index => $variable) {
			$index = is_int($index) ? "Index $index" : $index;

			echo "####### $index ", str_repeat('#', 69 - strlen($index)), PHP_EOL;
			$function = is_bool($variable) ? '__echo_bool' : (is_null($variable) ? '__echo_null' : ($is_var_dump ? 'var_dump' : 'print_r'));
			$function($variable);
			echo PHP_EOL . PHP_EOL;
		}

		if($html_mode === true) {
			echo '</pre>';
		}

		die();
	}

	/**
	 * @deprecated
	 * -s for "silent"
	 * Executes dpr() only if is_developer() equals true.
	 */
	function dprs() {
		_dpr(func_get_args());
	}

	/**
	 * Basic functionality. Prints variables provided as arguments and stops the script execution.
	 *
	 * @internal param mixed $var1 Variable to print
	 * @internal param mixed $_ [optional] Function supports any number of arguments
	 */
	function dpr() {
		_dpr(func_get_args());
	}

	/**
	 * Same as dpr(), but uses var_dump() instead of print_r()
	 * @param var1 mixed   Variable to print
	 * @param _    mixed   [optional] Function supports any number of arguments
	 */
	function dprv() {
		_dpr(func_get_args(), true);
	}

	/**
	 * Prints backtrace and stops the script execution.
	 */
	function dprt() {
		$trace_result = array();

		foreach(debug_backtrace(false) as $trace_call) {
			$trace_result[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $trace_call['file']) . ':' . $trace_call['line'];
		}

		dpr($trace_result);
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
	 *
	 * @internal param mixed $var1 Variable to print
	 * @internal param mixed $_ [optional] Function supports any number of arguments
	 */
	function dprd() {
		if(defined('__DPR_BREAKPOINT_POSITION')) {
			_dpr(func_get_args(), false, __DPR_BREAKPOINT_POSITION);
		}
	}
}
