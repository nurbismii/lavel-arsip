<?php

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\TestCase;

class RichTextTest extends TestCase
{
    public function test_it_preserves_supported_quill_formatting(): void
    {
        $html = '<h2>Ringkasan</h2><p><strong>Dokumen</strong> <em>sudah selesai</em>.</p>'
            . '<ul><li>Verifikasi data</li><li>Arsipkan berkas</li></ul>'
            . '<p><a href="https://example.com/dokumen">Referensi</a></p>';

        $sanitized = RichText::sanitizeDocument($html);

        $this->assertStringContainsString('<h2>Ringkasan</h2>', $sanitized);
        $this->assertStringContainsString('<strong>Dokumen</strong>', $sanitized);
        $this->assertStringContainsString('<em>sudah selesai</em>', $sanitized);
        $this->assertStringContainsString('<ul><li>Verifikasi data</li><li>Arsipkan berkas</li></ul>', $sanitized);
        $this->assertStringContainsString(
            '<a href="https://example.com/dokumen" target="_blank" rel="noopener noreferrer">Referensi</a>',
            $sanitized
        );
    }

    public function test_it_removes_active_content_unsafe_attributes_and_protocols(): void
    {
        $html = '<script>alert("xss")</script>'
            . '<p onclick="alert(1)">Aman<img src="x" onerror="alert(2)"></p>'
            . '<p><a href="javascript:alert(3)" style="color:red">Tautan</a></p>';

        $sanitized = RichText::sanitizeDocument($html);

        $this->assertStringNotContainsString('script', $sanitized);
        $this->assertStringNotContainsString('alert', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('onerror', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('style=', $sanitized);
        $this->assertStringNotContainsString('<img', $sanitized);
        $this->assertStringContainsString('<p>Aman</p>', $sanitized);
        $this->assertStringContainsString('<a>Tautan</a>', $sanitized);
    }

    public function test_it_converts_legacy_plain_text_to_safe_paragraphs(): void
    {
        $sanitized = RichText::sanitizeDocument("Catatan & tindak lanjut\nBaris kedua");

        $this->assertSame('<p>Catatan &amp; tindak lanjut<br>Baris kedua</p>', $sanitized);
    }

    public function test_it_treats_empty_quill_markup_as_empty(): void
    {
        $this->assertNull(RichText::sanitizeDocument('<p><br></p>'));
        $this->assertFalse(RichText::hasMeaningfulText('<p>&nbsp; </p>'));
        $this->assertSame('', RichText::plainText('<p><br></p>'));
    }

    public function test_it_extracts_normalized_text_for_character_validation(): void
    {
        $this->assertSame(
            'Dokumen selesai dan sudah diverifikasi.',
            RichText::plainText('<p>Dokumen&nbsp; selesai</p><p>dan sudah diverifikasi.</p>')
        );
    }
}
