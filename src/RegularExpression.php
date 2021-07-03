<?php namespace EugeneEgr\PeriodicNumber;

class RegularExpression extends StringObject
{
    public const PCRE_CASELESS = 1;
    public const PCRE_MULTILINE = 2;
    public const PCRE_DOTALL = 4;
    public const PCRE_EXTENDED = 8;
    public const PCRE_UNGREEDY = 16;
    public const PCRE_INFO_JCHANGED = 32;
    public const PCRE_UTF8 = 64;

    public function __construct(string $pattern, int $options = 0, ?array $bindings = null)
    {
        parent::__construct("({$this->bind($pattern, $bindings)}){$this->optionsToString($options)})");
    }

    public function match(string $subject, ?array &$matches, int $flags = 0): bool
    {
        $result = preg_match($this->__toString(), $subject, $subMatches, $flags);

        if ($result === false) {
            throw $this->createException();
        }

        $matches = $this->arrayStringToArrayStringObject($subMatches);

        return $result === 1;
    }

    public function matchAll(string $subject, ?array &$matches, int $flags = 0): int
    {
        $result = preg_match_all($this, $subject, $subMatches, $flags);

        if ($result === false) {
            throw $this->createException();
        }

        $matches = $this->arrayStringToArrayStringObject($subMatches);

        return $result;
    }

    public function grep(array $array, int $flags = 0): array
    {
        $result = preg_grep($this, $array, $flags);

        if ($result === false) {
            throw $this->createException();
        }

        return $result;
    }

    public function filter(string $replacement, string $subject, ?int $limit = null, ?int &$count = null): StringObject
    {
        $result = preg_filter($this, $replacement, $subject, $limit ?? -1, $count);

        if ($result === null) {
            throw $this->createException();
        }

        return new StringObject($result);
    }

    public function replace(string $replacement, string $subject, ?int $limit = null, ?int &$count = null): StringObject
    {
        $result = preg_replace($this, $replacement, $subject, $limit ?? -1, $count);

        if ($result === null) {
            throw $this->createException();
        }

        return new StringObject($result);
    }

    /**
     * @param string $subject
     * @param int|null $limit
     * @param int $flags
     * @return StringObject[]
     */
    public function split(string $subject, ?int $limit = null, int $flags = 0): array
    {
        $result = preg_split($this, $subject, $limit, $flags);

        if ($result === false) {
            throw $this->createException();
        }

        return array_map(static function (string $item): StringObject {
            return new StringObject($item);
        }, $result);
    }

    private function optionsToString(int $options): string
    {
        return $this->ifHasOptionThen($options, self::PCRE_CASELESS)
            . $this->ifHasOptionThen($options, self::PCRE_MULTILINE)
            . $this->ifHasOptionThen($options, self::PCRE_DOTALL)
            . $this->ifHasOptionThen($options, self::PCRE_EXTENDED)
            . $this->ifHasOptionThen($options, self::PCRE_UNGREEDY)
            . $this->ifHasOptionThen($options, self::PCRE_INFO_JCHANGED)
            . $this->ifHasOptionThen($options, self::PCRE_UTF8);
    }

    private function ifHasOptionThen(int $options, int $option): string
    {
        return ($options & $option) === $option ? [
            self::PCRE_CASELESS => 'i',
            self::PCRE_MULTILINE => 'm',
            self::PCRE_DOTALL => 's',
            self::PCRE_EXTENDED => 'X',
            self::PCRE_UNGREEDY => 'U',
            self::PCRE_INFO_JCHANGED => 'J',
            self::PCRE_UTF8 => 'u',
        ][$option] : '';
    }

    private function createException(): RegularExpressionException
    {
        $code = preg_last_error();
        $message = function_exists('preg_last_error_msg')
            ? preg_last_error_msg()
            : [
                PREG_NO_ERROR => 'No error',
                PREG_INTERNAL_ERROR => 'Internal error',
                PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit exhausted',
                PREG_RECURSION_LIMIT_ERROR => 'Recursion limit exhausted',
                PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 characters, possibly incorrectly encoded',
                PREG_BAD_UTF8_OFFSET_ERROR => 'The offset did not correspond to the beginning of a valid UTF-8 code point',
                PREG_JIT_STACKLIMIT_ERROR => 'JIT stack limit exhausted',
            ][$code] ?? 'Unknown error';

        return new RegularExpressionException($message, $code);
    }

    private function bind(string $pattern, ?array $bindings): string
    {
        if ($bindings === null) {
            return $pattern;
        }

        $result = [];

        foreach ($bindings as $binding) {
            $result[] = preg_quote($binding);
        }

        return vsprintf($pattern, $result);
    }

    private function arrayStringToArrayStringObject(array $subMatches): array
    {
        $result = [];

        foreach ($subMatches as $key => $item) {
            if (is_string($item)) {
                $result[$key] = new StringObject($item);
            } elseif (is_array($item)) {
                $result[$key] = $this->arrayStringToArrayStringObject($item);
            } else {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}