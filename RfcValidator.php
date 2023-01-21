<?php

/**
 * @author     Javier Cabanillas <jcabanillas@bitevolution.net>
 * @copyright  2017 Javier Cabanillas.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    0.0.1
 * @link       https://github.com/jcabanillas/yii2-mexvalidators
 */

namespace jcabanillas\mexvalidators;

use yii\validators\Validator;
use yii\base\ErrorException;

/**
 * Valida que la cadena sea un RFC válido según las leyes mexicanas.
 *
 * Validates that the string is a valid RFC according to Mexican law.
 */
class RfcValidator extends Validator {

    /**
     * Determina si se convierte el campo a mayúsculas automáticamente.
     * @var boolean determines if the field is automatically converted to uppercase.
     */
    public $toUpper = true;

    const VALIDCHARS = '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ&$%#@§';

    private $errors = array();

    /**
     * @inheritdoc
     */
    public function init() {
        if ($this->message === null) {
            $this->message = \Yii::t('validator', '{attribute} must be a string.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute) {
        //Uppercase the value (all RFC must be uppercase).
        if ($this->toUpper === true) {
            $model->{$attribute} = strtoupper($model->{$attribute});
        }
        $value = $model->{$attribute};
        if (!$this->_validateRfc($value)) {
            $this->addError($model, $attribute, \Yii::t('validator', 'RFC "{rfc}" is invalid.', array('rfc' => $value)));
            foreach ($this->errors as $error) {
                $this->addError($model, $attribute, $error);
            }
        }
    }

    private function _validateRfc($value) {
        // Check lenght
        // Mexico RFC must be 12 or 13 chars long.
        //  RFC for companies or businnesses is 12 chars long
        //  RFC for persons is 13 chars long.
        switch (strlen($value)) {
            case 12:
                $datePosition = 3;
                break;
            case 13:
                $datePosition = 4;
                break;
            default:
                $this->errors[] = \Yii::t('validator', 'Must be 12 or 13 characters long');
                return false;
                break;
        }

        // Check for valid characters
        for ($i = 0; $i < strlen($value); $i++) {
            if (strpos(self::VALIDCHARS, $value[$i]) === false) {
                $this->errors[] = \Yii::t('validator', 'Character "{char}" is not allowed.', array('char' => $value[$i]));
            }
        }

        // RFC consists of 3 parts:
        //  1) Character string formed from the name of the taxpayer.
        //    a) If the taxpayer is a company, is 3 chars long.
        //    b) If the taxpayer is a person, is 4 chars long.
        //  2) 6 numbers representing a date in the format YYMMDD. This is the registration date with the tax authority (SAT).
        //  3) 3 chars representing a checksum. There's no reliable algorithm to calculate this. 
        //     The only valid rule (AFAIK) is: Last char must be a digit or the letter 'A'
        // Split parts:
        $part1 = substr($value, 0, $datePosition);
        $part2 = substr($value, $datePosition, 6);
        $part3 = substr($value, -3);

        // Check part 2
        // Part 2 must be a valid date
        // Check if middle 6 is a valid date
        $year = (substr($part2, 0, 2) == '00') ? '2000' : substr($part2, 0, 2);
        $month = substr($part2, 2, 2);
        $day = substr($part2, 4, 2);

        try {
            checkdate($month, $day, $year);
        } catch (ErrorException $ex) {
            $this->errors[] = \Yii::t('validator', '"{part2}" must be a valid date in the format YYMMDD.', array('part2' => $part2));
        } catch (\TypeError $ext){
            $this->errors[] = \Yii::t('validator', '"{part2}" must be a valid date in the format YYMMDD.', array('part2' => $part2));
        }

        // Check part 3
        // Just check if last char is a digit or the letter 'A'
        if (!is_numeric(substr($value, -1)) && substr($value, -1) != 'A') {
            $this->errors[] = \Yii::t('validator', 'Last character must be a number or the letter "A".', array('cksum' => substr($value, -1)));
        }
        return (count($this->errors) == 0);
    }

}
