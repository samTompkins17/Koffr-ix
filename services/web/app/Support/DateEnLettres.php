<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class DateEnLettres
{
    private const MOIS = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    /** Produit la formule de date en lettres utilisée dans les actes. */
    public static function dateEnLettres(DateTimeInterface|string $date): string
    {
        $date = $date instanceof DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);

        $jour = $date->day === 1 ? 'premier' : self::nombre($date->day);

        return 'L’an '.self::nombre($date->year).', le '.$jour.' '.self::MOIS[$date->month];
    }

    private static function nombre(int $nombre): string
    {
        if ($nombre < 0 || $nombre > 9999) {
            throw new \InvalidArgumentException('La date doit être comprise entre l’an 0 et 9999.');
        }

        if ($nombre < 100) {
            return self::moinsDeCent($nombre);
        }

        if ($nombre < 1000) {
            $centaines = intdiv($nombre, 100);
            $reste = $nombre % 100;
            $mot = $centaines === 1 ? 'cent' : self::moinsDeCent($centaines).' cent';
            if ($reste === 0 && $centaines > 1) {
                $mot .= 's';
            }

            return $mot.($reste ? ' '.self::moinsDeCent($reste) : '');
        }

        $milliers = intdiv($nombre, 1000);
        $reste = $nombre % 1000;
        $mot = $milliers === 1 ? 'mille' : self::moinsDeCent($milliers).' mille';

        return $mot.($reste ? ' '.self::nombre($reste) : '');
    }

    private static function moinsDeCent(int $nombre): string
    {
        $unites = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
        $dixASeize = [10 => 'dix', 11 => 'onze', 12 => 'douze', 13 => 'treize', 14 => 'quatorze', 15 => 'quinze', 16 => 'seize'];

        if ($nombre < 10) {
            return $unites[$nombre];
        }
        if (isset($dixASeize[$nombre])) {
            return $dixASeize[$nombre];
        }
        if ($nombre < 20) {
            return 'dix-'.self::moinsDeCent($nombre - 10);
        }

        $dizaines = intdiv($nombre, 10);
        $unite = $nombre % 10;
        if ($dizaines === 7 || $dizaines === 9) {
            $base = $dizaines === 7 ? 'soixante' : 'quatre-vingt';
            $reste = $dizaines === 7 ? 10 + $unite : 10 + $unite;

            return $base.'-'.self::moinsDeCent($reste);
        }
        if ($dizaines === 8) {
            return 'quatre-vingt'.($unite === 0 ? 's' : '-'.self::moinsDeCent($unite));
        }

        $base = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'][$dizaines];
        if ($unite === 0) {
            return $base;
        }
        if ($unite === 1) {
            return $base.' et un';
        }

        return $base.'-'.self::moinsDeCent($unite);
    }
}
