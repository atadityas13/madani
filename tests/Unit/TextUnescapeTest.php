<?php

namespace Tests\Unit;

use App\Support\TextUnescape;
use PHPUnit\Framework\TestCase;

class TextUnescapeTest extends TestCase
{
    public function test_membersihkan_backslash_sebelum_apostrof(): void
    {
        $this->assertSame("Endang Ma'sum", TextUnescape::clean("Endang Ma\\'sum"));
        $this->assertSame("Endang Ma'sum", TextUnescape::clean("Endang Ma\'sum"));
    }
}
