<?php

namespace Rfuehricht\Sanitizegp\Helper;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;


/**
 * Access array values using dot or pipe notation.
 *
 * Wildcards are possible.
 *
 * Based on https://github.com/Pharaonic/php-dot-array
 *
 * e.g. $array->get('key.subKey.*');
 */
class SeparatorArrayAccess implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * Array Items
     *
     * @var array
     */
    protected array $_ITEMS = [];

    /**
     * Create an new DoArray instance
     *
     * @param ?array $items
     */
    public function __construct(?array $items = [])
    {
        if ($items === null) {
            $items = [];
        }
        $this->setArray($items);
    }

    /**
     * Set Array Items
     *
     * @param array $items
     * @return  void
     **/
    public function setArray(array $items): void
    {
        $this->_ITEMS = $items;
    }

    /**
     * Clear all stored items
     *
     * @return void
     */
    public function clear(): void
    {
        $this->_ITEMS = [];
    }

    /**
     * Return the value of a given key as JSON
     *
     * @param int|string|null $key
     * @param int $options
     * @return  string
     */
    public function toJson(int|string $key = null, int $options = 0): string
    {
        return json_encode($key ? $this->get($key ?? '*') : $this->all(), $options);
    }

    /**
     * Return the value of a given key
     *
     * @param string|int $key
     * @param array|string|int|float|null $default
     * @param array|null $arr
     * @return  mixed
     */
    public function get(string|int $key, mixed $default = null, array $arr = null): mixed
    {
        $items = $arr ?? $this->_ITEMS;

        if (is_int($key)) {
            return isset($items[$key]) ? $items[$key] : $default;
        } else {
            $key = $this->prepareKey($key);
            $max = count($key) - 1;

            for ($index = 0; $index < count($key); $index++) {
                if ($key[$index] == '*') {
                    if (!is_array($items)) {
                        return $default;
                    }

                    $index++;
                    $next_key = implode('.', array_slice($key, $index));
                    $rs = null;

                    foreach ($items as $k => $item) {
                        $item = $this->get($next_key, $default, $item);
                        $rs[] = $item;
                    }

                    $items = $rs;
                    break;
                } else {
                    $items = is_array($items) && array_key_exists($key[$index], $items) ? $items[$key[$index]] : null;
                }
            }

            // if multidimensional
            if (is_array($items) && $this->isMultidimensional($items) && $this->isNumericKeys($items) && $index = $max) {
                if (isset($items[0][0]) && \is_array($items[0][0]))
                    foreach ($items as &$item) {
                        $item = array_merge_recursive(...$item);
                    }

                $items = array_merge_recursive(...$items);
            }

            if (is_array($items) && $this->isNulledValues($items)) {
                $items = null;
            }

            return is_null($items) ? $default : $items;
        }
    }

    /**
     * Prepare Key to Array of Keys
     *
     * @param string $key
     * @return array
     */
    private function prepareKey(string $key): array
    {
        $key = rtrim(
            trim($key, '. '),
            '.*'
        );

        return empty($key) ? [] : explode('.', $key);
    }

    /**
     * Check if the array is a multidimensional
     *
     * @param array $arr
     * @return boolean
     */
    private function isMultidimensional(array $arr): bool
    {
        return count($arr) !== count($arr, COUNT_RECURSIVE);
    }

    /**
     * Check if the given keys are integers from 0 to N
     *
     * @param array $arr
     * @return boolean
     */
    public function isNumericKeys(array $arr): bool
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Check if the array contains Null values only
     *
     * @param array $arr
     * @return boolean
     */
    public function isNulledValues(array $arr): bool
    {
        return empty(array_filter($arr, function ($v) {
            return $v !== null;
        }));
    }

    /**
     * Get all the stored items
     *
     * @return array
     */
    public function all(): array
    {
        return $this->_ITEMS;
    }

    /**
     * Check if a given key exists
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->has($key);
    }

    /**
     * Check if a given key exists
     *
     * @param string|int $key
     * @param array|null $arr
     * @return  bool
     */
    public function has(string|int $key, array $arr = null): bool
    {
        $items = $arr ?? $this->_ITEMS;

        if (is_int($key) && isset($items[$key])) {
            return true;
        } elseif (is_string($key)) {
            $key = $this->prepareKey($key);
            for ($index = 0; $index < count($key); $index++) {
                if (is_array($items)) {
                    if ($key[$index] == '*') {
                        $index++;
                        $next_key = implode('.', array_slice($key, $index));

                        foreach ($items as $item)
                            if (!$this->has($next_key, $item)) return false;

                        break;
                    } else {
                        if (!array_key_exists($key[$index], $items)) return false;
                        $items = $items[$key[$index]];
                    }
                } else {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Return the value of a given key
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Set a given value to the given key
     *
     * @param int|string $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $this->_ITEMS[] = $value;
            return;
        }

        $this->set($key, $value);
    }

    /**
     * Set a given value to the given key
     *
     * @param string $key
     * @param array|int|float|string|null $value
     * @return  void
     */
    public function set(string $key, mixed $value = null, array &$arr = null)
    {
        if (!$arr) {
            $items = &$this->_ITEMS;
        } else {
            $items = &$arr;
        }

        if (is_int($key)) {
            $items[$key] = $value;
            return;
        } elseif (is_string($key)) {
            $key = $this->prepareKey($key);
            $max = count($key) - 1;

            for ($index = 0; $index <= $max; $index++) {
                if ($index == $max) {
                    $items[$key[$index]] = $value;
                } else {
                    if ($key[$index] == '*') {
                        $index++;
                        $next_key = implode('.', array_slice($key, $index));

                        if (empty($items)) {
                            $items[][$key[$index]] = null;
                        }

                        foreach ($items as &$item) {
                            $this->set($next_key, $value, $item);
                        }

                        break;
                    } else {
                        if (!isset($items[$key[$index]])) {
                            $items[$key[$index]] = null;
                        }

                        $items = &$items[$key[$index]];
                    }
                }
            }
        }
    }

    /**
     * Delete the given key
     *
     * @param int|string $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->delete($key);
    }

    /**
     * Delete the given key
     *
     * @param string|int $key
     * @return  bool
     */
    public function delete(string|int $key, array &$arr = null): bool
    {
        if (!$arr) {
            $items = &$this->_ITEMS;
        } else {
            $items = &$arr;
        }

        if (is_int($key) && isset($items[$key])) {
            unset($items[$key]);
            return true;
        } elseif (is_string($key)) {
            $key = $this->prepareKey($key);
            $max = count($key) - 1;

            for ($index = 0; $index <= $max; $index++) {
                if ($index == $max) {
                    if (isset($items[$key[$index]])) {
                        unset($items[$key[$index]]);
                        return true;
                    }
                } else {
                    if ($key[$index] == '*') {
                        $index++;
                        $next_key = implode('.', array_slice($key, $index));
                        $rs = true;

                        foreach ($items as &$item) {
                            if (!$this->delete($next_key, $item)) {
                                $rs = false;
                            }
                        }

                        return $rs;
                    } elseif (isset($items[$key[$index]])) {
                        $items = &$items[$key[$index]];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Return the number of items in a given key
     *
     * @param string|null $key
     * @return  int
     */
    public function count($key = null): int
    {
        return count($this->get($key ?? '*'));
    }

    /**
     * Get an iterator for the stored items
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_ITEMS);
    }

    /**
     * Return items for JSON serialization
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->_ITEMS;
    }

}

;
