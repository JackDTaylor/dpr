# Dpr
Set of utility/debugging PHP functions.

## Documentation
### Constants
#### `DPR_DEVELOPER_IPS`
``define('DPR_DEVELOPER_IPS', '127.0.0.1, 192.168.0.1')``  
List of comma-separated IPs or false to disable this check. Spaces are trimmed.
Declares IP addresses allowed to see the debug info. For users not matching this filter ``dpr()`` will simply return first passed argument and do nothing.

#### `DPR_SHOW_MEMINFO`
``define('DPR_SHOW_MEMINFO', true)``  
Whether show memory info or not

#### `DPR_ENCODING`
``define('DPR_ENCODING', 'utf-8')``  
Default encoding to send with ``header('Content-Type: text/plain; charset=<your encoding here>')``;
  
#### `$_SERVER['REQUEST_TIME_FLOAT']`
``$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);``  
Optionally you can put this line to the very start of your execution flow to measure request execution time.

If not present, ``dpr()`` will set it up as the time of ``include 'dpr.php';``.

### Functions
#### `is_developer()`
``is_developer(); // Returns boolean``  
Returns ``true`` if ``$_SERVER['REMOTE_ADDR']`` is in [``DPR_DEVELOPER_IPS``](#dpr_developer_ips) constant.

#### `dpr`
``dpr($var : any, ...);``  
Basic functionality. Prints variables provided as arguments and stops the script execution.

#### `dprv`
``dprv($var : any, ...); // -v for "var_dump"``  
Same as [``dpr()``](#dpr), but uses ``var_dump()`` instead of ``print_r()``

#### `dprt`
``dprt(); //  -t for "trace"``  
Prints backtrace and stops the script execution.

#### `dprb`
``dprb(); // -b for "breakpoint"``  
Defines a breakpoint for [dprd()](#dprd) function.

#### `dprd`
``dprd($var : any, ...); // -d for something like "debug" or whatever``  
Triggers [dpr()](#dpr) only if breakpoint was defined with [dprb()](#dprb)

#### `dprm`
``dprm($var : any, ...); // -m for "measure"``  
Prints all measures made with [dprmFrom()](#dprmFrom)/[dprmTo()](#dprmTo) and then executes [dpr()](#dpr) for passed arguments.

#### `dprmFrom`
``dprmFrom($key : string);``
Starts execution time measure named as ``$key``.

#### `dprmTo`
``dprmTo($key : string);``
Ends execution time measure named as ``$key``.



