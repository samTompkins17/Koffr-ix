<?php

namespace Tests\Unit;

use App\Support\DateEnLettres;
use PHPUnit\Framework\TestCase;

class DateEnLettresTest extends TestCase
{
    public function test_formate_une_date_de_novembre_deux_mille_vingt_six(): void
    {
        $this->assertSame('L’an deux mille vingt-six, le onze novembre', DateEnLettres::dateEnLettres('2026-11-11'));
    }

    public function test_formate_le_premier_jour_du_mois(): void
    {
        $this->assertSame('L’an deux mille vingt-six, le premier janvier', DateEnLettres::dateEnLettres('2026-01-01'));
    }

    public function test_formate_les_annees_autour_de_mille(): void
    {
        $this->assertSame('L’an mille, le neuf mai', DateEnLettres::dateEnLettres('1000-05-09'));
        $this->assertSame('L’an mille neuf cent quatre-vingt-dix-neuf, le vingt et un juin', DateEnLettres::dateEnLettres('1999-06-21'));
    }
}
