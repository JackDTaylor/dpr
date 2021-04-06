<?php
namespace JackDTaylor\Dpr;

/**
 * Class Dpr
 *
 * @package JackDTaylor\Dpr
 */
class Dpr {
	public const VAR_DUMP = [__CLASS__, 'printAsVarDump'];
	public const PRINT_R = [__CLASS__, 'printAsPrintR'];

	public const DEFAULT_FILEPATH_FORMATTER = [__CLASS__, 'formatFilePath'];

	protected static $instance = null;

	public static function getInstance() {
		return static::$instance ?: static::$instance = new static();
	}

	/**
	 * IP addresses allowed to see the debug info.
	 * For users not matching this filter dpr() will simply return first passed argument and do nothing.
	 *
	 * Supported formats:
	 *   **If value is FALSE, everyone will be considered a developer**
	 *	  define('DPR_DEVELOPER_IPS', false);
	 *
	 *   **You can provide array directly using Dpr::setDeveloperIps**
	 *    Dpr::setDeveloperIps(['12.34.56.78', '23.45.67.89', '23.56.78.90'])
	 *
	 *   **Comma-separated list of IPs, spaces will be trimmed.**
	 *	  define('DPR_DEVELOPER_IPS', '12.34.56.78, 23.45.67.89, 23.56.78.90');
	 *
	 *   **NL-separated config with #-comments support:**
	 *   define('DPR_DEVELOPER_IPS', "
	 *     12.34.56.78 # Alice
	 *     23.45.67.89 # Bob
	 *     34.56.78.90 # Bob from home
	 *   ");
	 *
	 * @var array|string|bool List of IPs or false to disable this check
	 */
	public $developerIps = false;

	/**
	 * Whether show memory info or not
	 * @var bool Info will be shown only if DPR_SHOW_MEMINFO === true
	 */
	public $showMemoryInfo = true;

	/**
	 * Default encoding to send with header('Content-Type: text/plain; charset=<your encoding here>');
	 * @var string
	 */
	public $encoding = 'utf-8';

	protected $printer = self::PRINT_R;

	protected $filePathFormatter = self::DEFAULT_FILEPATH_FORMATTER;

	protected $breakpoint = null;

	protected $forceHtml = false;

	protected $collectedItems = [];

	public function init() {
		// If there's no $_SERVER['REQUEST_TIME_FLOAT'], then let's measure it at least somehow.
		if(!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
			$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
		}

		if(defined('DPR_DEVELOPER_IPS')) {
			$this->developerIps = DPR_DEVELOPER_IPS;
		}

		if(defined('DPR_SHOW_MEMINFO')) {
			$this->showMemoryInfo = DPR_SHOW_MEMINFO;
		}

		if(defined('DPR_ENCODING')) {
			$this->encoding = DPR_ENCODING;
		}

		if(defined('DPR_PRINTER')) {
			$this->printer = DPR_PRINTER;
		}

		return $this;
	}

	public function isDeveloper() {
		if($this->developerIps === false) {
			return true;
		}

		$developer_ips = $this->developerIps;

		if(!is_array($developer_ips)) {
			$developer_ips = preg_replace('/#.*?([\r\n]+)/s', ",", $developer_ips);
			$developer_ips = array_filter(array_map('trim', explode(',', $developer_ips)));
		}

		return in_array($_SERVER['REMOTE_ADDR'], $developer_ips);
	}

	public function setDeveloperIps($developer_ips) {
		$this->developerIps = $developer_ips;
		return $this;
	}

	public function setShowMemInfo($state) {
		$this->showMemoryInfo = !!$state;
		return $this;
	}

	public function setForceHtml($state) {
		$this->forceHtml = !!$state;
		return $this;
	}

	public function setEncoding($encoding) {
		$this->encoding = $encoding;
		return $this;
	}

	public function setFilePathFormatter($formatter) {
		$this->filePathFormatter = $formatter;
		return $this;
	}

	public function setPrinter($printer) {
		$this->printer = $printer;
		return $this;
	}

	public function setBreakpoint($file, $line = null) {
		$this->breakpoint = ['file' => $file, 'line' => $line];
		return $this;
	}

	public function hasBreakpoint() {
		return is_null($this->breakpoint) == false;
	}

	public function collect($item, $key = null) {
		if(func_num_args() == 1) {
			$this->collectedItems[] = $item;
		} else {
			$this->collectedItems[$key] = $item;
		}

		return $item;
	}

	protected function formatNumber($val) {
		$val = number_format($val / 1024 / 1024, 3, '.', '');
		return str_pad($val, 13, ' ', STR_PAD_LEFT);
	}

	protected function formatPercentage($val) {
		$val = number_format($val, 2, '.', '');
		return str_pad($val, 6, ' ', STR_PAD_LEFT);
	}

	protected static function printBasicValue($variable) {
		if(is_bool($variable)) {
			echo $variable ? '[TRUE]' : '[FALSE]';
			return true;
		}

		if(is_null($variable)) {
			echo '[NULL]';
			return true;
		}

		return false;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function printAsVarDump($variable, $separator, $index, $is_html_mode) {
		if($is_html_mode) {
			echo '<pre class="dpr">';
		}

		echo $separator, PHP_EOL;

		if(!static::printBasicValue($variable)) {
			var_dump($variable);
		}

		if($is_html_mode) {
			echo '</pre>';
		}
	}

	public static function formatFilePath($file, $line) {
		$file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);

		if(is_null($line)) {
			return $file;
		}

		return "{$file}:{$line}";
	}

	/** @noinspection PhpUnusedParameterInspection */
	public static function printAsPrintR($variable, $separator, $index, $is_html_mode) {
		if($is_html_mode) {
			echo '<pre class="dpr">';
		}

		echo $separator, PHP_EOL;

		if(!static::printBasicValue($variable)) {
			print_r($variable);
		}

		if($is_html_mode) {
			echo '</pre>';
		} else {
			echo PHP_EOL, PHP_EOL;
		}
	}

	protected function getCssRules() {
		return '
			<style>
				* { overflow: hidden; position: static; }
				div#dpr { position: fixed; overflow: auto; top: 0; left: 0; margin: 0; padding: 1em 8px 8px; box-sizing: border-box; width: 100%; height: 100%; background-color: #FFFFFF; color: #000000; font-size: 13px; line-height: normal; font-family: monospace; z-index: 2147483647; }
				pre { outline: none; margin: 0; }
				pre.dpr { margin-top: 1.15em; white-space: pre-wrap; }
				pre.dpr:first-child { margin-top: 0; }
			</style>
		';
	}

	/** @noinspection PhpMissingBreakStatementInspection */
	protected function printMemoryInfo() {
		// Refactor this mess?

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

		$cep = $this->formatPercentage($curr_emal / $mem_limit * 100) . '%';
		$crp = $this->formatPercentage($curr_real / $mem_limit * 100) . '%';
		$pep = $this->formatPercentage($peak_emal / $mem_limit * 100) . '%';
		$prp = $this->formatPercentage($peak_real / $mem_limit * 100) . '%';

		$mem_limit = $this->formatNumber($mem_limit) . 'M';

		$curr_emal = $this->formatNumber(memory_get_usage()) . 'M';
		$curr_real = $this->formatNumber(memory_get_usage(true)) . 'M';
		$peak_emal = $this->formatNumber(memory_get_peak_usage()) . 'M';
		$peak_real = $this->formatNumber(memory_get_peak_usage(true)) . 'M';

		$internal_encoding = mb_internal_encoding();
		mb_internal_encoding($this->encoding);

		$s = chr(226).chr(148).chr(130);
		echo " ____________________________________________________________________________ ", PHP_EOL;
		echo "$s          $s Current usage $s	Peak usage $s  Memory limit $s   Curr $s   Peak $s", PHP_EOL;
		echo "$s  emalloc $s{$curr_emal} $s{$peak_emal} $s{$mem_limit} $s{$cep} $s{$pep} $s", PHP_EOL;
		echo "$s     real $s{$curr_real} $s{$peak_real} $s{$mem_limit} $s{$crp} $s{$prp} $s", PHP_EOL;
		echo ' ', str_repeat(chr(0xE2).chr(0x80).chr(0xBE), 76), ' ', PHP_EOL;

		mb_internal_encoding($internal_encoding);
	}

	/**
	 * Dumps all provided variables
	 * TODO: Heavy refactoryng needed
	 *
	 * @param array $variables
	 * @param int $called_at_offset
	 */
	public function dump(array $variables, $called_at_offset = 0) {
		if($this->isDeveloper() === false) {
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
		$printer = $this->printer;
		$filepath_formatter = $this->filePathFormatter;

		$is_html_mode = headers_sent() || $this->forceHtml;

		if($is_html_mode) {
			echo $this->getCssRules();
			echo '<div id="dpr"><pre class="dpr">';
		} else {
			header('Content-type: text/plain; charset=' . $this->encoding);
		}

		$called_at = debug_backtrace(false);
		$called_at = $called_at[1 + $called_at_offset];

		// header
		$exec_time = ((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000);
		$exec_time = number_format($exec_time, 2, '.', '');

		if($this->showMemoryInfo) {
			$this->printMemoryInfo();
		}

		echo str_pad(" {$exec_time}ms", 76, '#', STR_PAD_LEFT), ' #', PHP_EOL;
		echo '#', PHP_EOL;

		if($this->hasBreakpoint()) {
			echo '#	Breakpoint at ', PHP_EOL;
			echo '#	  ', $filepath_formatter($this->breakpoint['file'], $this->breakpoint['line']), PHP_EOL;
			echo '#', PHP_EOL;
		}

		echo '#	Debug print at ', PHP_EOL;
		echo '#	  ', $filepath_formatter($called_at['file'], $called_at['line']), PHP_EOL;
		echo '#', PHP_EOL;
		echo str_repeat('#', 78);

		if($is_html_mode) {
			echo '</pre>';
		} else {
			echo PHP_EOL . PHP_EOL;
		}

		// prints
		foreach($variables as $index => $variable) {
			$index = is_int($index) ? "Index $index" : $index;
			$separator = "####### $index " . str_repeat('#', 69 - strlen($index));

			$printer($variable, $separator, $index, $is_html_mode);
		}

		die();
	}

	public function dumpCollected() {
		return $this->dump($this->collectedItems, 1);
	}
}