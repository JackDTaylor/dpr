<?php
/**
 * dpr() is a debug function for extended and short `die(print_r($some_var, true));`
 * @see https://github.com/JackDTaylor/dpr
 */

if(!function_exists('_dpr') && !function_exists('is_developer')) {
	/**
	 * Declares IP addresses allowed to see the debug info.
	 * For users not matching this filter dpr() will simply return first passed argument and do nothing.
	 * @var string|boolean List of comma-separated IPs or false to disable this check
	 */
	if(!defined('DPR_DEVELOPER_IPS')) {
		define('DPR_DEVELOPER_IPS', '127.0.0.1'); // Don't forget to change this to your IP or set it to false
	}

	/**
	 * Whether show memory info or not
	 * @var boolean Info will be shown only if DPR_SHOW_MEMINFO === true
	 */
	if(!defined('DPR_SHOW_MEMINFO')) {
		define('DPR_SHOW_MEMINFO', true);
	}

	/**
	 * Default encoding to send with header('Content-Type: text/plain; charset=<your encoding here>');
	 * @var boolean Info will be shown only if DPR_SHOW_MEMINFO === true
	 */
	if(!defined('DPR_ENCODING')) {
		define('DPR_ENCODING', 'utf-8');
	}

	/**
	 * If there's no $_SERVER['REQUEST_TIME_FLOAT'], then let's measure it at least somehow.
	 */

	if(!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
		$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
	}

	/**
	 * Checks whether user is developer or not
	 * @return bool Returns TRUE if $_SERVER['REMOTE_ADDR'] is in DPR_DEVELOPER_IPS constant.
	 */
	function is_developer() {
		if(DPR_DEVELOPER_IPS === false) {
			return true;
		}

		return in_array($_SERVER['REMOTE_ADDR'], array_map('trim', explode(',', DPR_DEVELOPER_IPS)));
	}

	/**
	 * Internal functions for _dpr()
	 */
	function __echo_null() { echo '[NULL]'; }
	function __echo_bool($variable) { echo $variable ? '[TRUE]' : '[FALSE]'; }

	function __format_dpr_mem_number($val) {
		$val = number_format($val / 1024 / 1024, 3, '.', '');
		return str_pad($val, 13, ' ', STR_PAD_LEFT);
	}
	function __format_dpr_mem_percent($val) {
		$val = number_format($val, 2, '.', '');
		return str_pad($val, 6, ' ', STR_PAD_LEFT);
	}

	/**
	 * Internal dpr() function.
	 * @param $variables   array       List of mixed values to print
	 * @param $is_var_dump boolean     If true, then var_dump() will be used instead of print_r()
	 * @param $breakpoint  string      Breakpoint location for dprb()/dprd() functions
	 * @return
	 */
	function _dpr(array $variables = array(), $is_var_dump = false, $breakpoint = null) {
		if(is_developer() === false) {
			return @pos($variables);
		}

		if(ob_get_level()) {
			while(ob_get_level()) {
				ob_end_clean();
			}
		}

		if(!headers_sent()) {
			header('Content-type: text/plain; charset=' . DPR_ENCODING);
		} else {
			echo '<style> * { overflow: hidden; position: static; } </style>';
			echo '<pre style="position: fixed; overflow: auto; top: 0; left: 0; margin: 0; padding: 5px; box-sizing: border-box; width: 100%; height: 100%; background-color: #FFFFFF; color: #000000; font-size: 14px; line-height: 125%; font-family: Courier New; z-index: 2147483647">';
		}

		$called_at = debug_backtrace(false);
		$called_at = $called_at[1];

		// header
		$exec_time = ((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000);
		$exec_time = number_format($exec_time, 2, '.', '');

		if(DPR_SHOW_MEMINFO === true) {
			// Hey don't look at me like that!
			// Oh, well, okay, whatever.
			/** @FIXME Refactoring needed */

			$mem_limit = trim(ini_get('memory_limit'));

			switch(strtolower($mem_limit[strlen($mem_limit)-1])) {
				case 'g': $mem_limit *= 1024;
				case 'm': $mem_limit *= 1024;
				case 'k': $mem_limit *= 1024;
			}

			$curr_emal = memory_get_usage();
			$curr_real = memory_get_usage(true);
			$peak_emal = memory_get_peak_usage();
			$peak_real = memory_get_peak_usage(true);

			$cep = __format_dpr_mem_percent($curr_emal / $mem_limit * 100) . '%';
			$crp = __format_dpr_mem_percent($curr_real / $mem_limit * 100) . '%';
			$pep = __format_dpr_mem_percent($peak_emal / $mem_limit * 100) . '%';
			$prp = __format_dpr_mem_percent($peak_real / $mem_limit * 100) . '%';

			$mem_limit = __format_dpr_mem_number($mem_limit) . 'M';

			$curr_emal = __format_dpr_mem_number(memory_get_usage()) . 'M';
			$curr_real = __format_dpr_mem_number(memory_get_usage(true)) . 'M';
			$peak_emal = __format_dpr_mem_number(memory_get_peak_usage()) . 'M';
			$peak_real = __format_dpr_mem_number(memory_get_peak_usage(true)) . 'M';


			$internal_encoding = mb_internal_encoding();
			mb_internal_encoding(DPR_ENCODING);

			$s = chr(226).chr(148).chr(130);
			echo " ____________________________________________________________________________ ", PHP_EOL;
			echo "$s          $s Current usage $s    Peak usage $s  Memory limit $s   Curr $s   Peak $s", PHP_EOL;
			echo "$s  emalloc $s{$curr_emal} $s{$peak_emal} $s{$mem_limit} $s{$cep} $s{$pep} $s", PHP_EOL;
			echo "$s     real $s{$curr_real} $s{$peak_real} $s{$mem_limit} $s{$crp} $s{$prp} $s", PHP_EOL;
			echo ' ', str_repeat(chr(0xE2).chr(0x80).chr(0xBE), 76), ' ', PHP_EOL;

			mb_internal_encoding($internal_encoding);
		}

		echo str_pad(" {$exec_time}ms", 76, '#', STR_PAD_LEFT), ' #', PHP_EOL;
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
		die();
	}

	/**
	 * Basic functionality. Prints variables provided as arguments and stops the script execution.
	 * @param var1 mixed   Variable to print
	 * @param _    mixed   [optional] Function supports any number of arguments
	 * @return mixed
	 */
	function dpr() {
		return _dpr(func_get_args());
	}

	/**
	 * Same as dpr(), but uses var_dump() instead of print_r()
	 * @param var1 mixed   Variable to print
	 * @param _    mixed   [optional] Function supports any number of arguments
	 * @return mixed
	 */
	function dprv() {
		return _dpr(func_get_args(), true);
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
	 * @param var1 mixed   Variable to print
	 * @param _    mixed   [optional] Function supports any number of arguments
	 * @return mixed
	 */
	function dprd() {
		if(defined('__DPR_BREAKPOINT_POSITION')) {
			return _dpr(func_get_args(), false, __DPR_BREAKPOINT_POSITION);
		}

		return func_get_arg(0);
	}

	class dprmStorage {
		static $storage = [];
		static $locks = [];
	}

	function dprmFrom($key) {
		if(isset(dprmStorage::$locks[$key])) {
			return;
		}

		dprmStorage::$locks[$key] = microtime(true);
	}

	function dprmTo($key) {
		if(!isset(dprmStorage::$locks[$key])) {
			return;
		}

		dprmStorage::$storage[$key] = number_format((microtime(true) - dprmStorage::$locks[$key]) * 1000, 2, '.', '');

		unset(dprmStorage::$locks[$key]);
	}

	function dprm(...$args) {
		ksort(dprmStorage::$storage);

		_dpr(array_merge([ dprmStorage::$storage ], $args));
	}
}
