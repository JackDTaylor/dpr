<?php
use JackDTaylor\Dpr\Dpr;

Dpr::getInstance()->init();

if(!function_exists('is_developer')) {
	/**
	 * Checks whether user is developer or not
	 *
	 * @return bool Returns TRUE if $_SERVER['REMOTE_ADDR'] is in DPR_DEVELOPER_IPS constant.
	 */
	function is_developer() {
		return Dpr::getInstance()->isDeveloper();
	}
}

if(!function_exists('dpr')) {
	/**
	 * Basic functionality. Prints variables provided as arguments and stops the script execution.
	 *
	 * @param mixed var1   Variable to print
	 * @param mixed _      [optional] Function supports any number of arguments
	 * @return mixed
	 */
	function dpr() {
		return Dpr::getInstance()->dump(func_get_args());
	}
}

if(!function_exists('dprv')) {
	/**
	 * Same as dpr(), but uses var_dump() instead of print_r()
	 *
	 * @param mixed var1   Variable to print
	 * @param mixed _      [optional] Function supports any number of arguments
	 * @return mixed
	 */
	function dprv() {
		// Remove filename since it makes no sense in this context
		if(ini_get("xdebug.overload_var_dump") == 2) {
			ini_set("xdebug.overload_var_dump", 1);
		}

		return Dpr::getInstance()
			->setPrinter(Dpr::VAR_DUMP)
			->setForceHtml(ini_get("xdebug.overload_var_dump") > 0 || ini_get('xdebug.mode') == 'develop')
			->dump(func_get_args());
	}
}

if(!function_exists('dprt')) {
	/**
	 * Prints backtrace and stops the script execution.
	 *
	 * @param mixed var1   Additional variable to print
	 * @param mixed _      [optional] Function supports any number of arguments
	 */
	function dprt() {
		$trace_result = [];

		foreach(debug_backtrace(false) as $trace_call) {
			$trace_result[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $trace_call['file'] ?? '<unknown>') . ':' . ($trace_call['line'] ?? '0');
		}

		$arguments = func_get_args();
		array_unshift($arguments, implode(PHP_EOL, $trace_result));

		Dpr::getInstance()->dump($arguments);
	}
}

if(!function_exists('dprb')) {
	/**
	 * Defines a breakpoint for dprd()
	 */
	function dprb() {
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$breakpoint_at = pos(debug_backtrace(false));

		Dpr::getInstance()->setBreakpoint($breakpoint_at['file'], $breakpoint_at['line']);
	}
}

if(!function_exists('dprd')) {
	/**
	 * Triggers dpr() if breakpoint was defined with dprb()
	 *
	 * @param mixed var1   Variable to print
	 * @param mixed _      [optional] Function supports any number of arguments
	 * @return mixed
	 */
	function dprd() {
		if(Dpr::getInstance()->hasBreakpoint()) {
			return Dpr::getInstance()->dump(func_get_args());
		}

		return func_get_arg(0);
	}
}

if(!function_exists('dprc')) {
	/**
	 * Collects items to print them all eventually
	 * If called without arguments, prints previously collected items
	 *
	 * @param mixed item Item to collect
	 * @param mixed key  [optional] Key for this item
	 * @return mixed
	 */
	function dprc($item = null, $key = null) {
		// Keep func_num_args() value in Dpr::collect()
		switch(func_num_args()) {
			case 1: return Dpr::getInstance()->collect($item);
			case 2: return Dpr::getInstance()->collect($item, $key);
		}

		return Dpr::getInstance()->dumpCollected();
	}
}