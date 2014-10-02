<?php

namespace IKTO\PgI\Converter;

use IKTO\PgI\Database\DatabaseAwareInterface;
use IKTO\PgI\Database\DatabaseInterface;
use IKTO\PgI\Exception\InvalidArgumentException;
use IKTO\PgI\Exception\MissingConverterException;

class PgArray implements
    ConverterInterface,
    DatabaseAwareInterface,
    EncoderGuesserInterface
{
    const ENCODING = 'UTF-8';

    /* @var DatabaseInterface */
    private $db;

    public function setDatabase(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function encode($value, $type = null)
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('The array must be passed as php array');
        }

        $result = array();
        try {
            $converter = $this->db->getConverterForType($type);
        }
        catch (MissingConverterException $ex) {
            if (null != $type) { throw $ex; }
        }
        foreach ($value as $element) {
            if (is_array($element)) {
                $result[] = $this->encode($element, $type);
            } else {
                /* Bypass php null as SQL NULL */
                if (null === $element) {
                    $result[] = 'NULL';
                    continue;
                }

                /* Perform element conversion if we specified types */
                if (isset($converter) && ($converter instanceof ConverterInterface)) {
                    $element = $converter->encode($element);
                }

                $element = str_replace('\\', '\\\\', $element); // Escape backslashes
                $element = str_replace('"', '\\"', $element); // Escape double-quotes.
                $result[] = '"' . $element . '"';
            }
        }

        return '{' . implode(',', $result) . '}'; // format
    }

    public function decode($value, $type = null)
    {
        if ((substr($value, 0, 1) != '{') || (substr($value, -1) != '}')) {
            throw new InvalidArgumentException(sprintf('Invalid array data: %s', $value));
        }

        $converter = null;
        try {
            $converter = $this->db->getConverterForType($type);
        }
        catch (MissingConverterException $ex) {
            if (null != $type) { throw $ex; }
        }

        $index = 0;

        return $this->parsePgArray($value, $index, null, $converter);
    }

    public function canEncode($value)
    {
        return is_array($value);
    }

    /**
     * @param string $src
     * @param integer $index
     * @param integer $length
     * @param ConverterInterface $subConverter
     * @return array
     */
    private function parsePgArray($src, &$index, $length = null, $subConverter = null)
    {
        if (null === $length) {
            $length = mb_strlen($src, static::ENCODING);
        }

        $result = null;

        $isQuoted = false;
        $wasQuoted = false;
        $isEscaping = false;
        $wasNested = false;
        $currentItem = '';
        for (; $index < $length; $index++) {
            $currentChar = mb_substr($src, $index, 1, static::ENCODING);

            // Checks if valid array string
            if (!is_array($result)) {
                if ('{' == $currentChar) {
                    $result = array();
                    continue;
                }

                throw new InvalidArgumentException(sprintf(
                    'Failed to parse array: expected "%s", but "%s" found',
                    '{', $currentChar
                ));
            }

            // If char is escaped, just bypass it
            if ($isEscaping) {
                $currentItem .= $currentChar;
                $isEscaping = false;
                continue;
            }

            // If string is quoted, parse its internal structure
            if ($isQuoted) {
                switch ($currentChar) {
                    case '\\':
                        $isEscaping = true;
                        break;
                    case '"':
                        $isQuoted = false;
                        $wasQuoted = true;
                        break;
                    default:
                        $currentItem .= $currentChar;
                        break;
                }

                continue;
            }

            switch ($currentChar) {
                case '{':
                    // Nested array
                    $currentItem = $this->parsePgArray($src, $index, $length, $subConverter);
                    $wasNested = true;
                    break;
                case '}':
                    // Array completed
                    $wasEmptyElement = (is_string($currentItem) && (strlen($currentItem) <= 0));
                    if (!$wasNested && !$wasQuoted && ($currentItem == 'NULL')) {
                        $currentItem = null;
                    }
                    if (!$wasNested && (null !== $currentItem) && ($subConverter instanceof ConverterInterface)) {
                        $currentItem = $subConverter->decode($currentItem);
                    }
                    if ($wasNested || (count($result) > 0) || !$wasEmptyElement) {
                        $result[] = $currentItem;
                    }

                    return $result;
                    break;
                case '"':
                    // We can have only one quotation
                    if ($wasQuoted) {
                        throw new InvalidArgumentException(
                            'Unable to parse array: multiple quotations detected'
                        );
                    }

                    $isQuoted = true;
                    break;
                case ',':
                    // Going to next item
                    if (!$wasNested && !$wasQuoted && ($currentItem == 'NULL')) {
                        $currentItem = null;
                    }
                    if (!$wasNested && (null !== $currentItem) && ($subConverter instanceof ConverterInterface)) {
                        $currentItem = $subConverter->decode($currentItem);
                    }
                    $result[] = $currentItem;
                    $currentItem = '';
                    $wasQuoted = false;
                    break;
                default:
                    $currentItem .= $currentChar;
                    break;
            }
        }

        throw new InvalidArgumentException('Unable to parse array: failed to complete sequence');
    }
}
