# EXT:sanitizegp - Sanitize GET/POSt values

This extension makes it possible to configure global rules for GET/POST parameters to sanitize or convert them.
This way you can ensure that basic checks are applied and integer values are converted to integer and so on.

## How to configure

Configuration is done via site settings.

```
sanitizegp:
  L:
    -
      action: convert
      type: int
    -
      action: range
      lower: 0
      upper: 3
  parameter1:
    -
      action: htmlSpecialChars
```
