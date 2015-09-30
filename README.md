# dpr
Set of utility/debugging php functions

## Documentation
### Constants
#### `DPR_DEVELOPER_IPS : string`
List of IP-addresses, separated with `'|'`, which should be considered as developer's IPs.

### Functions
#### `dpr($var : any, ...)`
Basic functionality. Prints variables provided as arguments and stops the script execution.

#### `dprv($var : any, ...)`
`-v for "var_dump"`

Same as ``dpr()``, but uses ``var_dump()`` instead of ``print_r()``

#### `dprs($var : any, ...)`
`-s for "silent"`

Executes ``dpr()`` only if ``$_SERVER['REMOTE_ADDR']`` is in ``DPR_DEVELOPER_IPS`` constant.

#### `dprt()`
`-t for "trace"`

Prints backtrace and stops the script execution.
