# Dpr
Set of utility/debugging PHP functions.

## Documentation
### Constants
#### `DPR_DEVELOPER_IPS`
``define('DPR_DEVELOPER_IPS', '127.0.0.1|192.168.0.1')``

List of IP-addresses separated with `'|'`, which should be considered as developer's IPs.

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

#### `dprs`
``dprs($var : any, ...); // -s for "silent"``

Executes [``dpr()``](#dpr) only if ``is_developer()`` equals ``true``.

#### `dprt`
``dprt(); //  -t for "trace"``

Prints backtrace and stops the script execution.

#### `dprb`
``dprb(); // -b for "breakpoint"``

Defines a breakpoint for [dprd()](#dprd) function.

#### `dprd`
``dprd($var : any, ...); // -d for something like "debug" or whatever``

Triggers dpr() if breakpoint was defined with [dprb()](#dprb)

