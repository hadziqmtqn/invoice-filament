<?php

namespace Database\Seeders\Reference;

use App\Models\Item;
use Illuminate\Database\Seeder;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\UnavailableStream;

class ItemSeeder extends Seeder
{
    /**
     * @throws InvalidArgument
     * @throws UnavailableStream
     * @throws Exception
     */
    public function run(): void
    {
        $rows = Reader::createFromPath(database_path('import/items.csv'))
            ->setHeaderOffset(0)
            ->setDelimiter(';');

        foreach ($rows as $row) {
            $item = new Item();
            $item->product_type = $row['product_type'];
            $item->name = $row['name'];
            $item->item_name = $row['item_name'];
            $item->unit = !empty($row['unit']) ? $row['unit'] : null;
            $item->rate = $row['rate'];
            $item->description = !empty($row['description']) ? $row['description'] : null;
            $item->save();
        }
    }
}
