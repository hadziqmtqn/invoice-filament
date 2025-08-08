<?php

namespace Database\Seeders\Setting;

use App\Models\MessageTemplate;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MessageTemplateSeeder extends Seeder
{
    /**
     * @throws FileNotFoundException
     */
    public function run(): void
    {
        $rows = json_decode(File::get(database_path('import/message-template.json')), true);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $messageTemplate = new MessageTemplate();
                $messageTemplate->category = $row['category'];
                $messageTemplate->title = $row['title'];
                $messageTemplate->message = $row['message'];
                $messageTemplate->save();
            }
        }
    }
}
