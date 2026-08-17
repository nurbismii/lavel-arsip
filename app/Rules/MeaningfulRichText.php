<?php

namespace App\Rules;

use App\Support\RichText;
use Illuminate\Contracts\Validation\Rule;

class MeaningfulRichText implements Rule
{
    private $maxLength;
    private $required;

    public function __construct(int $maxLength = 1000, bool $required = true)
    {
        $this->maxLength = $maxLength;
        $this->required = $required;
    }

    public function passes($attribute, $value): bool
    {
        $text = RichText::plainText($value);

        if ($text === '') {
            return !$this->required;
        }

        return mb_strlen($text) <= $this->maxLength;
    }

    public function message(): string
    {
        return 'Keterangan penyelesaian wajib diisi dengan maksimal '
            . number_format($this->maxLength, 0, ',', '.')
            . ' karakter.';
    }
}
