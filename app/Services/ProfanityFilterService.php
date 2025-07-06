<?php

namespace App\Services;

class ProfanityFilterService
{
    private static array $blockedWords = [
        'anal', 'anus', 'arse', 'ass', 'ballsack', 'balls', 'bastard', 'bitch', 'biatch', 'bloody',
        'blowjob', 'blow job', 'bollock', 'bollok', 'boner', 'boob', 'bugger', 'bum', 'butt',
        'buttplug', 'clitoris', 'cock', 'coon', 'crap', 'cunt', 'damn', 'dick', 'dildo', 'dyke',
        'fag', 'feck', 'fellate', 'fellatio', 'felching', 'fuck', 'f u c k', 'fudgepacker',
        'fudge packer', 'flange', 'homo', 'jerk', 'jizz', 'knobend', 'knob end', 'labia',
        'muff', 'nigger', 'nigga', 'penis', 'piss', 'poop', 'prick', 'pube', 'pussy', 'queer',
        'scrotum', 'sex', 'shit', 's hit', 'slut', 'smegma', 'spunk', 'tit', 'tosser', 'turd',
        'twat', 'vagina', 'wank', 'whore', 'wtf'
    ];

    public static function containsProfanity(string $text): bool
    {
        $normalizedText = strtolower(trim($text));
        
        foreach (self::$blockedWords as $blockedWord) {
            if (str_contains($normalizedText, strtolower($blockedWord))) {
                return true;
            }
        }
        
        return false;
    }

    public static function getBlockedWords(): array
    {
        return self::$blockedWords;
    }

    public static function validateTag(string $tagName): bool
    {
        return !self::containsProfanity($tagName);
    }

    public static function filterProfanity(string $text, string $replacement = '***'): string
    {
        $normalizedText = $text;
        
        foreach (self::$blockedWords as $blockedWord) {
            $pattern = '/\b' . preg_quote($blockedWord, '/') . '\b/i';
            $normalizedText = preg_replace($pattern, $replacement, $normalizedText);
        }
        
        return $normalizedText;
    }
}