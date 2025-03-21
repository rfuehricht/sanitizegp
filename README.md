# EXT:sanitizegp - Sanitize GET/POST values

This extension makes it possible to configure global rules for GET/POST parameters to sanitize or convert them.
This way you can ensure that basic checks are applied and integer values are converted to integer and so on.

## How to configure

Configuration is done via site settings.

```yaml
sanitizegp:
  L:
    - action: convert
      type: int
    - action: range
      lower: 0
      upper: 3
  parameter1:
    - action: htmlSpecialChars
  tx_news|news:
    - action: int
```

Use pipe notation to access deep array parameters.
You can use wildcard `*` to access all deep values.
Use keyword `all` to perform actions on **all** parameters.

Examples:
```yaml
array|*:
- action: range
  lower: 1
  upper: 3
```
Parameters `array[sub]=27&array[sub2]=27&array[sub3][sub1]=27` will all be handled and result in `3` as configured as `upper` limit.


Perform actions on **all** parameters:
```yaml
all:
- action: htmlSpecialChars
```

## Global Settings

```yaml
sanitizegp:
  settings:
    separator: '.'
    replaceGlobals: 1
```

### Available settings

`separator` is the separator to use for accessing deep array values. Default is `|`.

`replaceGlobals` specifies if `$_GET` and `$_POST` should be replaced with the sanitized values or be left untouched. By default, only the values in the request object are sanitized.


## Available Actions

Each action has the option `scope` to define if only `get` or `post`should be processed.
Default is `get` **and** `post`.

```yaml
- action: convert
  type: int
  scope:
    - get
    - post
```

### Convert

Converts value to a specific data type.

#### Options

`type` Currently, can be `int` or `float`.

### HtmlSpecialChars

Calls `htmlspecialchars` on the value.

### Length

Makes sure a string value is in a length range.

If only one limit is set, the other one is ignored.

If value is shorter than `min`, the values is filled with content set in `padString` using PHP function `str_pad` (https://www.php.net/manual/function.str-pad.php).

#### Options

`min` The lower length limit.

`max` The upper length limit.

`padString` The content to fill up value shorter than `min`.

`padType` Where to apply `padString`. Can be: `left`, `right`, `both`. Default is `right`.

### Range

Makes sure a numeric value is in a certain range. If value is out of range, it will be set to the lower/upper as defined.

If only one limit is set, the other one is ignored.

#### Options

`lower` The lower range limit.

`upper` The upper range limit.

### Replace

Replaces values in the value.

#### Options

`search` Array or comma separated list of values to search.

`replace` Array or comma separated list of replacements.

`replaceFunction` Defaults to `str_ireplace`. Can be: `str_replace`, `str_ireplace`or `preg_replace`. When using `preg_replace`, the search and replacements are not exploded by the separator. Each line is treated as a regular expression.

`separator` Specify custom separator for values in `search` and `replace`. Default is `,`.

`fileSource` Path to file (absolute or relative from project root) containing the search/replacement infos.

**Examples**

Simple:
```yaml
- action: replace
  search: 'foo,bar,baz'
  replace: 'hello,world,!'
  fileSource: 'config/replacements.txt'
```

`replacements.txt` has this format:

```
foo,bar,baz => hello
bad => good
i love dogs => i love cats
```

Replacements from file are added to the list defined in `search` and `replace`.

Replacements as arrays:
```yaml
- action: replace
  search:
    - 'foo,bar,baz'
    - 'bad word'
  replace:
    - 'hello'
    - 'good word'
  fileSource: 'config/replacements.txt'
```

This replaces `foo`, `bar` and `baz` with `hello` and `good word` with `bad word`

Replacement with `preg_replace`:

```yaml
- action: replace
  replaceFunction: 'preg_replace'
  search:
    - '/foo.*bar/i'
    - '#bad word#i'
    - '/i am (.+)/i'
  replace:
    - 'foobar'
    - 'good word'
    - 'my name is $1'
```

### StripTags

Calls `strip_tags` on the value.

#### Options

`allowedTags` Array of tags to keep

Example:

```yaml
- action: StripTags
  options:
    allowedTags:
      - b
      - strong
      - i
      - em
```
