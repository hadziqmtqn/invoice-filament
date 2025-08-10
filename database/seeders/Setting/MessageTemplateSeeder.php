<?php

namespace Database\Seeders\Setting;

use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MessageTemplateSeeder extends Seeder
{
    /**
     * @throws FileNotFoundException
     */
    public function run(): void
    {
        $rows = json_decode(File::get(database_path('import/message-template.json')), true);

        if (is_array($rows)) {
            // Kumpulkan placeholder per kategori
            $categoryPlaceholders = [];

            foreach ($rows as $row) {
                $text = $row['message'] . ' ' . ($row['title'] ?? '');
                preg_match_all('/\[[^]]+]/', $text, $matches);

                $placeholders = array_unique($matches[0]);
                $category = $row['category'];

                if (!isset($categoryPlaceholders[$category])) {
                    $categoryPlaceholders[$category] = [];
                }
                $categoryPlaceholders[$category] = array_unique(array_merge(
                    $categoryPlaceholders[$category],
                    $placeholders
                ));
            }

            foreach ($rows as $row) {
                $category = $row['category'];
                $placeholders = $categoryPlaceholders[$category];

                // Format markdown list
                $markdownList = implode("\n", array_map(fn($p) => "- " . $p, $placeholders));

                $messageTemplateCategory = MessageTemplateCategory::firstOrCreate(
                    ['name' => Str::title(str_replace('-', ' ', $category))],
                    ['placeholder' => $markdownList]
                );

                // Jika sudah ada dan ingin update placeholder, gunakan ini:
                // $messageTemplateCategory->update(['placeholder' => $markdownList]);

                $messageTemplate = new MessageTemplate();
                $messageTemplate->message_template_category_id = $messageTemplateCategory->id;
                $messageTemplate->title = $row['title'];
                $messageTemplate->message = $row['message'];
                $messageTemplate->save();
            }
        }
    }
}
