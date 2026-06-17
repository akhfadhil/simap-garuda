<?php

namespace App\Support;

class PartyConfig
{
    public static function name(): string
    {
        return (string) config('party.name', 'Partai');
    }

    public static function shortName(): string
    {
        return (string) config('party.short_name', self::name());
    }

    public static function appName(): string
    {
        return (string) config('party.app_name', config('app.name', 'SIMAP Partai'));
    }

    public static function historicalNumbers(): array
    {
        return collect(config('party.historical_numbers', []))
            ->map(fn ($number) => (int) $number)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function matchesName(?string $name): bool
    {
        $name = mb_strtolower((string) $name);

        return str_contains($name, mb_strtolower(self::shortName()))
            || str_contains($name, mb_strtolower(self::name()));
    }

    public static function matchesHistoricalNumber($number): bool
    {
        return in_array((int) $number, self::historicalNumbers(), true);
    }

    public static function matchesParty($number, ?string $name): bool
    {
        return self::matchesHistoricalNumber($number) || self::matchesName($name);
    }

    public static function matchesSubmittedParty($number, ?string $name): bool
    {
        return self::matchesHistoricalNumber($number) && self::matchesName($name);
    }

    public static function applyPartyQuery($query, string $nameColumn = 'nama_partai', string $numberColumn = 'nomor_urut')
    {
        $numbers = self::historicalNumbers();

        return $query->where(function ($query) use ($nameColumn, $numberColumn, $numbers) {
            $query->where($nameColumn, 'like', '%'.self::shortName().'%')
                ->orWhere($nameColumn, 'like', '%'.self::name().'%');

            if ($numbers) {
                $query->orWhereIn($numberColumn, $numbers);
            }
        });
    }

    public static function totalVoiceLabel(): string
    {
        return 'Total Suara '.self::shortName();
    }

    public static function voteAcquisitionLabel(): string
    {
        return 'PEROLEHAN SUARA '.mb_strtoupper(self::shortName());
    }

    public static function recapTitlePrefix(): string
    {
        return 'REKAPITULASI SUARA '.mb_strtoupper(self::shortName());
    }
}
