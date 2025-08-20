<?php

namespace App\Enums;

enum Months: int
{
    case JAN = 1;
    case FEB = 2;
    case MAR = 3;
    case APR = 4;
    case MEI = 5;
    case JUN = 6;
    case JUL = 7;
    case AGU = 8;
    case SEP = 9;
    case OKT = 10;
    case NOV = 11;
    case DEC = 12;

    // Label panjang (Indonesia)
    public function label(): string
    {
        return match($this) {
            self::JAN => 'Januari',
            self::FEB => 'Februari',
            self::MAR => 'Maret',
            self::APR => 'April',
            self::MEI => 'Mei',
            self::JUN => 'Juni',
            self::JUL => 'Juli',
            self::AGU => 'Agustus',
            self::SEP => 'September',
            self::OKT => 'Oktober',
            self::NOV => 'November',
            self::DEC => 'Desember',
        };
    }

    // Label pendek (3 huruf)
    public function short(): string
    {
        return match($this) {
            self::JAN => 'Jan',
            self::FEB => 'Feb',
            self::MAR => 'Mar',
            self::APR => 'Apr',
            self::MEI => 'Mei',
            self::JUN => 'Jun',
            self::JUL => 'Jul',
            self::AGU => 'Agu',
            self::SEP => 'Sep',
            self::OKT => 'Okt',
            self::NOV => 'Nov',
            self::DEC => 'Des',
        };
    }

    // Untuk akses semua case (misal: untuk loop)
    public static function all(): array
    {
        return [
            self::JAN, self::FEB, self::MAR, self::APR, self::MEI, self::JUN,
            self::JUL, self::AGU, self::SEP, self::OKT, self::NOV, self::DEC
        ];
    }
}