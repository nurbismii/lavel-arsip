<?php

namespace Tests\Unit;

use App\Rules\MeaningfulRichText;
use PHPUnit\Framework\TestCase;

class MeaningfulRichTextTest extends TestCase
{
    public function test_it_rejects_semantically_empty_quill_content(): void
    {
        $rule = new MeaningfulRichText(1000);

        $this->assertFalse($rule->passes('keterangan_penyelesaian', '<p><br></p>'));
        $this->assertSame(
            'Keterangan penyelesaian wajib diisi dengan maksimal 1.000 karakter.',
            $rule->message()
        );
    }

    public function test_it_accepts_meaningful_content_within_the_limit(): void
    {
        $rule = new MeaningfulRichText(1000);

        $this->assertTrue($rule->passes(
            'keterangan_penyelesaian',
            '<p>Dokumen telah diverifikasi dan diselesaikan.</p>'
        ));
    }

    public function test_it_rejects_plain_text_beyond_the_limit(): void
    {
        $rule = new MeaningfulRichText(1000);

        $this->assertFalse($rule->passes(
            'keterangan_penyelesaian',
            '<p>' . str_repeat('a', 1001) . '</p>'
        ));
    }

    public function test_it_allows_empty_content_when_configured_as_optional(): void
    {
        $rule = new MeaningfulRichText(1000, false);

        $this->assertTrue($rule->passes('deskripsi', '<p><br></p>'));
    }
}
