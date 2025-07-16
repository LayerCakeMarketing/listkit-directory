<?php

namespace App\Helpers;

class StateHelper
{
    /**
     * Map of US state abbreviations to full names
     */
    private static $states = [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
        'DC' => 'District of Columbia'
    ];

    /**
     * Get full state name from abbreviation
     */
    public static function getFullName($abbreviation)
    {
        $abbreviation = strtoupper(trim($abbreviation));
        return self::$states[$abbreviation] ?? $abbreviation;
    }

    /**
     * Get state abbreviation from full name
     */
    public static function getAbbreviation($fullName)
    {
        $fullName = trim($fullName);
        $flipped = array_flip(self::$states);
        return $flipped[$fullName] ?? $fullName;
    }

    /**
     * Check if string is state abbreviation
     */
    public static function isAbbreviation($state)
    {
        return strlen($state) == 2 && isset(self::$states[strtoupper($state)]);
    }

    /**
     * Normalize state input to full name
     */
    public static function normalize($state)
    {
        if (self::isAbbreviation($state)) {
            return self::getFullName($state);
        }
        return $state;
    }
}