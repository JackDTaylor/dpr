<?php
namespace JackDTaylor\Dpr;

class Dpr {
	/**
	 * IP addresses allowed to see the debug info.
	 * For users not matching this filter dpr() will simply return first passed argument and do nothing.
	 *
	 * Supported formats:
	 *
	 *   **If value is FALSE, everyone will be considered a developer**
	 *	  define('DPR_DEVELOPER_IPS', false);
	 *
	 *   **Comma-separated list of IPs, spaces will be trimmed.**
	 *	  define('DPR_DEVELOPER_IPS', '12.34.56.78, 23.45.67.89, 23.56.78.90');
	 *
	 *   **NL-separated config with #-comments support:**
	 *	  define('DPR_DEVELOPER_IPS', "
	 *		12.34.56.78 # Alice
	 *		23.45.67.89 # Bob
	 *		34.56.78.90 # Bob from home
	 *	  ");
	 *
	 * @var string|bool List of IPs or false to disable this check
	 */
	public static $developerIps = false;

	/**
	 * Whether show memory info or not
	 * @var bool Info will be shown only if DPR_SHOW_MEMINFO === true
	 */
	public static $showMemoryInfo = true;

	/**
	 * Default encoding to send with header('Content-Type: text/plain; charset=<your encoding here>');
	 * @var string
	 */
	public static $encoding = 'utf-8';

	public static function init() {
		/**
		 * If there's no $_SERVER['REQUEST_TIME_FLOAT'], then let's measure it at least somehow.
		 */
		if(!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
			$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
		}

		if(defined('DPR_DEVELOPER_IPS')) {
			self::$developerIps = DPR_DEVELOPER_IPS;
		}

		if(defined('DPR_SHOW_MEMINFO')) {
			self::$showMemoryInfo = DPR_SHOW_MEMINFO;
		}

		if(defined('DPR_ENCODING')) {
			self::$encoding = DPR_ENCODING;
		}
	}

	public static function isDeveloper() {
		if(static::$developerIps === false) {
			return true;
		}

		$developer_ips = preg_replace('/#.*?([\r\n]+)/s', ",", static::$developerIps);
		$developer_ips = array_filter(array_map('trim', explode(',', $developer_ips)));

		return in_array($_SERVER['REMOTE_ADDR'], $developer_ips);
  }

	public static function hasBreakpoint() {

	}

	public static function dump(...$variables) {
		if(static::isDeveloper() === false) {
			return @pos($variables);
		}

		// Clear all existing buffers
		if(ob_get_level()) {
			while(ob_get_level()) {
				ob_end_clean();
			}
		}

		// Start our own output buffer.
		// This allows user to send headers in shutdown functions if needed
		ob_start();

		// Use default variable printer
		$printer = null;

		if(DPR_PRINTER != 'default') {
			$printer = DPR_PRINTER;

			if($printer == 'env' && isset($_ENV['DPR_PRINTER'])) {
				$printer = $_ENV['DPR_PRINTER'];
			}
		}

		$html_mode = headers_sent() || $printer;

		if($html_mode == false) {
			header('Content-type: text/plain; charset=' . DPR_ENCODING);
		} else {
			echo '
			  <style>
				* { overflow: hidden; position: static; }
				div#dpr { position: fixed; overflow: auto; top: 0; left: 0; margin: 0; padding: 5px; box-sizing: border-box; width: 100%; height: 100%; background-color: #FFFFFF; color: #000000; font-size: 12px; line-height: 125%; font-family: Courier New, monospace; z-index: 2147483647; }
				pre { outline: none; margin: 0; }
				pre.dpr { margin-top: 1em; }
			  </style>
			';
			echo '<div id="dpr"><pre class="dpr">';
		}

		$called_at = debug_backtrace(false);
		$called_at = $called_at[1];

		// header
		$exec_time = ((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000);
		$exec_time = number_format($exec_time, 2, '.', '');

		if(DPR_SHOW_MEMINFO === true) {
			// Hey don't look at me like that!
			// Oh, well, okay, whatever.
			/** TODO: Refactoring needed */

			$mem_limit = trim(ini_get('memory_limit'));
			$mem_limit_unit = is_string($mem_limit) ? strtolower($mem_limit[strlen($mem_limit)-1]) : '';
			$mem_limit = (int)$mem_limit;

			switch($mem_limit_unit) {
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
			echo "$s		  $s Current usage $s	Peak usage $s  Memory limit $s   Curr $s   Peak $s", PHP_EOL;
			echo "$s  emalloc $s{$curr_emal} $s{$peak_emal} $s{$mem_limit} $s{$cep} $s{$pep} $s", PHP_EOL;
			echo "$s	 real $s{$curr_real} $s{$peak_real} $s{$mem_limit} $s{$crp} $s{$prp} $s", PHP_EOL;
			echo ' ', str_repeat(chr(0xE2).chr(0x80).chr(0xBE), 76), ' ', PHP_EOL;

			mb_internal_encoding($internal_encoding);
		}

		echo str_pad(" {$exec_time}ms", 76, '#', STR_PAD_LEFT), ' #', PHP_EOL;
		echo '#', PHP_EOL;

		if(is_null($breakpoint) == false) {
			echo '#	Breakpoint at ', PHP_EOL;
			echo '#	  ', str_replace($_SERVER['DOCUMENT_ROOT'], '', $breakpoint), PHP_EOL;
			echo '#', PHP_EOL;
		}

		echo '#	Debug print at ', PHP_EOL;
		echo '#	  ', str_replace($_SERVER['DOCUMENT_ROOT'], '', $called_at['file']), ':', $called_at['line'], PHP_EOL;
		echo '#', PHP_EOL;
		echo str_repeat('#', 78);

		if($html_mode) {
			echo '</pre>';
		} else {
			echo PHP_EOL . PHP_EOL;
		}

		// prints
		foreach($variables as $index => $variable) {
			$index = is_int($index) ? "Index $index" : $index;
			$separator = "####### $index " . str_repeat('#', 69 - strlen($index));

			if($printer) {
				$printer($variable, $separator, $index);
				continue;
			}

			if($html_mode) {
				echo '<pre class="dpr">';
			}

			echo $separator, PHP_EOL;

			$function = is_bool($variable) ? '__echo_bool' : (is_null($variable) ? '__echo_null' : ($is_var_dump ? 'var_dump' : 'print_r'));
			$function($variable);

			if($html_mode) {
				echo '</pre>';
			}
		}

		die();
	}
}

Dpr::init();