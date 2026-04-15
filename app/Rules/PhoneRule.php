<?php

/*
 * This file is part of the CLIENTXCMS project.
 * It is the property of the CLIENTXCMS association.
 *
 * Personal and non-commercial use of this source code is permitted.
 * However, any use in a project that generates profit (directly or indirectly),
 * or any reuse for commercial purposes, requires prior authorization from CLIENTXCMS.
 *
 * To request permission or for more information, please contact our support:
 * https://clientxcms.com/client/support
 *
 * Learn more about CLIENTXCMS License at:
 * https://clientxcms.com/eula
 *
 * Year: 2025
 */

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberType as libPhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Propaganistas\LaravelPhone\Concerns\PhoneNumberCountry;

/**
 * Custom phone validation rule that is more lenient than the default.
 * This rule specifically allows French mobile numbers starting with 07 (in addition to 06).
 */
class PhoneRule implements Rule, ValidatorAwareRule
{
    protected Validator $validator;

    protected array $countries = [];

    /**
     * Set the countries to validate against.
     */
    public function country($country): self
    {
        $countries = is_array($country) ? $country : func_get_args();
        $this->countries = array_merge($this->countries, $countries);

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true; // Nullable is handled separately
        }

        $countries = PhoneNumberCountry::sanitize($this->countries);
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // Try to parse with each country
            $parsedNumber = null;

            foreach ($countries as $country) {
                try {
                    $parsedNumber = $phoneUtil->parse($value, $country);
                    break;
                } catch (NumberParseException $e) {
                    continue;
                }
            }

            // If no country worked, try without country hint for international format
            if ($parsedNumber === null) {
                try {
                    $parsedNumber = $phoneUtil->parse($value, null);
                } catch (NumberParseException $e) {
                    return false;
                }
            }

            if ($parsedNumber === null) {
                return false;
            }

            // Get the number type
            $numberType = $phoneUtil->getNumberType($parsedNumber);

            // Accept mobile, fixed line, fixed line or mobile, and VOIP
            $validTypes = [
                libPhoneNumberType::MOBILE,
                libPhoneNumberType::FIXED_LINE,
                libPhoneNumberType::FIXED_LINE_OR_MOBILE,
                libPhoneNumberType::VOIP,
                libPhoneNumberType::PERSONAL_NUMBER,
            ];

            // Special handling for French numbers starting with 07
            $regionCode = $phoneUtil->getRegionCodeForNumber($parsedNumber);
            if ($regionCode === 'FR') {
                $nationalNumber = (string) $parsedNumber->getNationalNumber();
                // French mobile numbers: 06xxxxxxxx or 07xxxxxxxx
                if (preg_match('/^[67]\d{8}$/', $nationalNumber)) {
                    return true;
                }
                // Also accept French fixed line numbers
                if (preg_match('/^[1-5]\d{8}$/', $nationalNumber)) {
                    return true;
                }
            }

            // For other countries, use standard validation
            if (in_array($numberType, $validTypes)) {
                return $phoneUtil->isValidNumber($parsedNumber);
            }

            // Fallback: check if the number is possible (lenient)
            return $phoneUtil->isPossibleNumber($parsedNumber);
        } catch (NumberParseException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return trans('validation.phone');
    }

    /**
     * Set the validator instance.
     */
    public function setValidator($validator): self
    {
        $this->validator = $validator;

        return $this;
    }
}
