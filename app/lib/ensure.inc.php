<?php
declare(strict_types=1);

/**
* Stellt sicher, dass Variablen definiert sind und den richtigen Datentyp haben
*
* @author Henrik Gebauer <mensa@henrik-gebauer.de>
*/

const ENSURE_SET = 1;
const ENSURE_STRING = 2;
const ENSURE_BOOL = 3;
const ENSURE_INT = 4;
const ENSURE_INT_GT = 5;
const ENSURE_INT_GTEQ = 6;
const ENSURE_INT_LT = 7;
const ENSURE_INT_LTEQ = 8;
const ENSURE_FLOAT = 9;
const ENSURE_FLOAT_GT = 10;
const ENSURE_FLOAT_GTEQ = 11;
const ENSURE_FLOAT_LT = 12;
const ENSURE_FLOAT_LTEQ = 13;
const ENSURE_ARRAY = 14;

function ensure(&$var, int $ensure = ENSURE_SET, $val = null, $default = null)
{
    // ggf. Default-Wert abhängig machen vom Datentyp
    if ($default === null) {
        switch ($ensure) {
            case ENSURE_ARRAY:
                $default = [];
                break;
        }
    }

    if ($ensure === ENSURE_BOOL) {
        if (isset($var) and ($var === 'false' or $var === '0')) { // String-Werte, die ==true sind, aber als ==false interpretiert werden sollen
            $var = false;
            return true;
        }
        $var = !empty($var);
        return true;
    }

    if (!isset($var)) {
        if ($default === null) {
            if ($ensure === ENSURE_STRING) {
                $default = '';
            } elseif ($ensure >= ENSURE_INT and $ensure < ENSURE_ARRAY) {
                $default = 0;
            } elseif ($ensure === ENSURE_ARRAY) {
                $default = [];
            }
        }
        $var = $default;
        return false;
    }

    switch ($ensure) {
        case ENSURE_SET:
            return true;
        case ENSURE_STRING:
            $var = trim((string)$var);
            return true;
        case ENSURE_INT:
            $var = (int)$var;
            return true;
        case ENSURE_INT_GT:
            $var = (int)$var;
            if ($var > $val) {
                return true;
            } else {
                $var = $default;
                return false;
            }
        case ENSURE_INT_GTEQ:
            $var = (int)$var;
            if ($var >= $val) {
                return true;
            } else {
                $var = $default;
                return false;
            }
        case ENSURE_INT_LT:
            $var = (int)$var;
            if ($var < $val) {
                return true;
            } else {
                $var = $default;
                return false;
            }
        case ENSURE_INT_LTEQ:
            $var = (int)$var;
            if ($var <= $val) {
                return true;
            } else {
                $var = $default;
                return false;
            }
        case ENSURE_FLOAT:
            $var = (float)$var;
            return true;
        case ENSURE_FLOAT_GT:
            $var = (float)$var;
            if ($var > $val) {
                return true;
            } else {
                $var = $default;
                return false;
            }
            return true;
        case ENSURE_ARRAY:
            if (!is_array($var)) {
                $var = $default;
                return false;
            } else {
                return true;
            }
    }
}
