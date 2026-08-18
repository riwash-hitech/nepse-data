<?php

namespace App\Console\Commands;

use App\Models\Sector;
use App\Models\Stock;
use App\Services\NepseScraperService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Signature('app:repair-sector-names')]
#[Description('Fix sectors.name rows that hold a raw Chukul numeric ID instead of the real sector name (e.g. "1" instead of "Banking"), merging duplicates and reassigning stocks.sector_id safely.')]
class RepairSectorNames extends Command
{
    /**
     * Execute the console command.
     *
     * Historically `syncSectors()` stored the Chukul sector API's numeric
     * id in the `name` column for some rows. This repairs them in place —
     * or merges into an existing correctly-named row and moves affected
     * stocks — without ever touching `stocks.sector_id` incorrectly.
     * Safe to run repeatedly; it's a no-op once names are all clean.
     */
    public function handle(NepseScraperService $scraper): int
    {
        $chukulSectors = collect(Cache::remember('chukul_sector_list', 3600, fn() => $scraper->fetchSectorList()))
            ->keyBy('id');

        if ($chukulSectors->isEmpty()) {
            $this->error('Could not fetch the Chukul sector list — check connectivity and try again.');
            return self::FAILURE;
        }

        $numericSectors = Sector::whereRaw("name REGEXP '^[0-9]+$'")->get();

        if ($numericSectors->isEmpty()) {
            $this->info('No corrupted sector names found — nothing to do.');
            return self::SUCCESS;
        }

        $renamed = 0;
        $merged = 0;
        $skipped = 0;

        foreach ($numericSectors as $sector) {
            $chukulId = (int) $sector->name;
            $real = $chukulSectors[$chukulId]['name'] ?? null;

            if (!$real) {
                $this->warn("  Sector id={$sector->id} (\"{$sector->name}\") has no matching Chukul sector — skipped.");
                $skipped++;
                continue;
            }

            $existing = Sector::where('name', $real)->where('id', '!=', $sector->id)->first();

            if ($existing) {
                $moved = Stock::where('sector_id', $sector->id)->update(['sector_id' => $existing->id]);
                $sector->delete();
                $this->info("  Merged sector id={$sector->id} (\"{$chukulId}\") into id={$existing->id} (\"{$real}\") — moved {$moved} stocks.");
                $merged++;
            } else {
                $sector->name = $real;
                $sector->slug = Str::slug($real);
                $sector->save();
                $this->info("  Renamed sector id={$sector->id}: \"{$chukulId}\" -> \"{$real}\".");
                $renamed++;
            }
        }

        $this->info("Done — renamed {$renamed}, merged {$merged}, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
