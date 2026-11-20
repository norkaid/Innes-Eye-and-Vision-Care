<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Enums\ProductAvailability;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Moves website content from one installation to another (e.g. from a PC to the live
 * server) as a single gzip-compressed JSON file.
 *
 * Exported: business settings and logo, media library images, branches with opening
 * hours and services, services, product categories, products, article categories
 * and health articles.
 * Never exported: user accounts, appointments, enquiries, notes, activity logs.
 *
 * Import adds new items and updates existing ones with the same web address (slug);
 * nothing is deleted.
 */
class ContentTransfer
{
    public const FORMAT = 'innes-content';

    public const VERSION = 1;

    private const DEMO_MARKERS = ['DEMO DATA', 'DEMO ADDRESS', '(Demo)'];

    private const MEDIA_PATH = '#^media/[a-z]+/[A-Za-z0-9]{10,80}\.(jpg|png|webp)$#';

    private const MEDIA_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private const COLUMNS = [
        'branches' => ['name', 'slug', 'address', 'city', 'region', 'phone', 'email', 'map_url', 'description', 'image_path', 'is_active', 'display_order'],
        'services' => ['name', 'slug', 'short_description', 'description', 'preparation', 'image_path', 'is_published', 'display_order'],
        'product_categories' => ['name', 'slug', 'description', 'is_active', 'display_order'],
        'products' => ['name', 'slug', 'brand', 'short_description', 'description', 'image_path', 'price', 'availability_status', 'is_published'],
        'article_categories' => ['name', 'slug'],
        'health_articles' => ['title', 'slug', 'excerpt', 'body', 'featured_image_path', 'author_display_name', 'status', 'published_at'],
        'team_members' => ['name', 'slug', 'job_title', 'qualifications', 'bio', 'image_path', 'is_published', 'display_order'],
    ];

    /** Settings that are copied (business details, booking rules and website images). */
    private const SETTING_KEYS = ['logo_path', 'hero_image_path', 'about_image_path'];

    private const BOOLEANS = ['is_active', 'is_published', 'is_closed'];

    private const SOFT_DELETES = ['branches', 'services', 'products', 'health_articles'];

    public function __construct(private Settings $settings) {}

    /**
     * Collects all website content.
     *
     * @return array<string, mixed>
     */
    public function export(bool $includeDemo = false): array
    {
        $live = fn (string $table) => DB::table($table)
            ->when(in_array($table, self::SOFT_DELETES, true), fn ($q) => $q->whereNull('deleted_at'));
        $keep = fn (object $row) => $includeDemo || ! $this->isDemo($row);

        $services = $live('services')->orderBy('id')->get()->filter($keep);
        $branches = $live('branches')->orderBy('id')->get()->filter($keep);
        $products = $live('products')->orderBy('id')->get()->filter($keep);
        $articles = $live('health_articles')->orderBy('id')->get()->filter($keep);

        // Categories are kept when used by an exported item, even if they look like demo data.
        $productCategoryIds = $products->pluck('product_category_id')->unique()->all();
        $productCategories = DB::table('product_categories')->orderBy('id')->get()
            ->filter(fn ($c) => $keep($c) || in_array($c->id, $productCategoryIds));
        $articleCategoryIds = $articles->pluck('article_category_id')->filter()->unique()->all();
        $articleCategories = DB::table('article_categories')->orderBy('id')->get()
            ->filter(fn ($c) => $keep($c) || in_array($c->id, $articleCategoryIds));

        $serviceSlugs = $services->pluck('slug', 'id');
        $productCategorySlugs = $productCategories->pluck('slug', 'id');
        $articleCategorySlugs = $articleCategories->pluck('slug', 'id');

        $hours = DB::table('branch_opening_hours')->get()->groupBy('branch_id');
        $links = DB::table('branch_service')->get()->groupBy('branch_id');

        $settingKeys = [...Settings::GENERAL_KEYS, ...Settings::SYSTEM_KEYS, ...self::SETTING_KEYS];

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'includes_demo' => $includeDemo,
            'settings' => DB::table('business_settings')->whereIn('key', $settingKeys)->pluck('value', 'key')->all(),
            'media' => DB::table('media')->join('media_contents', 'media_contents.media_id', '=', 'media.id')
                ->orderBy('media.id')
                ->get(['media.folder', 'media.path', 'media.original_name', 'media.alt_text', 'media.mime_type', 'media.width', 'media.height', 'media_contents.data'])
                ->map(fn ($m) => [
                    'folder' => $m->folder, 'path' => $m->path, 'original_name' => $m->original_name, 'alt_text' => $m->alt_text,
                    'mime_type' => $m->mime_type, 'width' => $m->width, 'height' => $m->height,
                    'data' => base64_encode(is_resource($m->data) ? stream_get_contents($m->data) : (string) $m->data),
                ])->values()->all(),
            'services' => $services->map(fn ($s) => $this->row($s, 'services'))->values()->all(),
            'branches' => $branches->map(fn ($b) => $this->row($b, 'branches') + [
                'opening_hours' => collect($hours->get($b->id, []))->sortBy('day_of_week')->map(fn ($h) => [
                    'day_of_week' => (int) $h->day_of_week,
                    'opens_at' => $this->time($h->opens_at),
                    'closes_at' => $this->time($h->closes_at),
                    'is_closed' => (bool) $h->is_closed,
                ])->values()->all(),
                'services' => collect($links->get($b->id, []))->map(fn ($l) => $serviceSlugs[$l->service_id] ?? null)->filter()->values()->all(),
            ])->values()->all(),
            'product_categories' => $productCategories->map(fn ($c) => $this->row($c, 'product_categories'))->values()->all(),
            'products' => $products->map(fn ($p) => $this->row($p, 'products') + [
                'category' => $productCategorySlugs[$p->product_category_id] ?? null,
            ])->values()->all(),
            'article_categories' => $articleCategories->map(fn ($c) => $this->row($c, 'article_categories'))->values()->all(),
            'health_articles' => $articles->map(fn ($a) => $this->row($a, 'health_articles') + [
                'category' => $a->article_category_id ? ($articleCategorySlugs[$a->article_category_id] ?? null) : null,
            ])->values()->all(),
            'team_members' => DB::table('team_members')->orderBy('display_order')->orderBy('id')->get()
                ->map(fn ($t) => $this->row($t, 'team_members'))->values()->all(),
        ];
    }

    /** The export as a compressed file body. */
    public function exportFile(bool $includeDemo = false): string
    {
        return gzencode(json_encode($this->export($includeDemo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), 6);
    }

    /**
     * Counts of what an export would contain.
     *
     * @return array<string, array{all: int, demo: int}>
     */
    public function summary(): array
    {
        $count = function (string $table) {
            $rows = DB::table($table)->when(in_array($table, self::SOFT_DELETES, true), fn ($q) => $q->whereNull('deleted_at'))->get();

            return ['all' => $rows->count(), 'demo' => $rows->filter(fn ($r) => $this->isDemo($r))->count()];
        };

        return [
            'Branches' => $count('branches'),
            'Services' => $count('services'),
            'Product categories' => $count('product_categories'),
            'Products' => $count('products'),
            'Article categories' => $count('article_categories'),
            'Health articles' => $count('health_articles'),
            'Team profiles' => $count('team_members'),
            'Media library images' => ['all' => DB::table('media')->count(), 'demo' => 0],
        ];
    }

    /** Reads an export file (gzip-compressed or plain JSON). */
    public function readFile(string $contents): array
    {
        if (str_starts_with($contents, "\x1f\x8b")) {
            $contents = @gzdecode($contents);
            if ($contents === false) {
                throw new RuntimeException('The file is damaged and could not be unpacked.');
            }
        }
        $data = json_decode($contents, true);
        if (! is_array($data) || ($data['format'] ?? null) !== self::FORMAT) {
            throw new RuntimeException('This is not a content export from this website.');
        }
        if ((int) ($data['version'] ?? 0) > self::VERSION) {
            throw new RuntimeException('This export was made by a newer version of the website. Update this site first.');
        }

        return $data;
    }

    /**
     * Adds or updates content from an export. Runs in one transaction: either
     * everything is imported or nothing is.
     *
     * @return array<string, array{created: int, updated: int}>
     */
    public function import(array $data): array
    {
        $result = [];
        $tally = function (string $label, bool $created) use (&$result) {
            $result[$label] ??= ['created' => 0, 'updated' => 0];
            $result[$label][$created ? 'created' : 'updated']++;
        };

        DB::transaction(function () use ($data, $tally) {
            foreach ($this->list($data, 'media') as $item) {
                $this->importMedia($item, $tally);
            }

            $serviceIds = [];
            foreach ($this->list($data, 'services') as $item) {
                [$id, $created] = $this->upsert('services', $item);
                $serviceIds[$item['slug']] = $id;
                $tally('Services', $created);
            }

            foreach ($this->list($data, 'branches') as $item) {
                [$id, $created] = $this->upsert('branches', $item);
                $tally('Branches', $created);
                foreach ((array) ($item['opening_hours'] ?? []) as $h) {
                    $day = (int) ($h['day_of_week'] ?? 0);
                    if ($day < 1 || $day > 7) {
                        continue;
                    }
                    DB::table('branch_opening_hours')->updateOrInsert(
                        ['branch_id' => $id, 'day_of_week' => $day],
                        [
                            'opens_at' => $this->time($h['opens_at'] ?? null),
                            'closes_at' => $this->time($h['closes_at'] ?? null),
                            'is_closed' => (bool) ($h['is_closed'] ?? false),
                            'updated_at' => now(), 'created_at' => now(),
                        ],
                    );
                }
                $linked = collect((array) ($item['services'] ?? []))
                    ->map(fn ($slug) => $serviceIds[$slug] ?? DB::table('services')->where('slug', $slug)->value('id'))
                    ->filter()->unique();
                foreach ($linked as $serviceId) {
                    DB::table('branch_service')->updateOrInsert(['branch_id' => $id, 'service_id' => $serviceId], ['updated_at' => now(), 'created_at' => now()]);
                }
            }

            foreach ($this->list($data, 'product_categories') as $item) {
                $tally('Product categories', $this->upsert('product_categories', $item)[1]);
            }
            foreach ($this->list($data, 'products') as $item) {
                $categoryId = DB::table('product_categories')->where('slug', (string) ($item['category'] ?? ''))->value('id');
                if (! $categoryId) {
                    throw new RuntimeException("Product \"{$item['name']}\" has no category in the file.");
                }
                $tally('Products', $this->upsert('products', $item, ['product_category_id' => $categoryId])[1]);
            }

            foreach ($this->list($data, 'article_categories') as $item) {
                $tally('Article categories', $this->upsert('article_categories', $item)[1]);
            }
            foreach ($this->list($data, 'health_articles') as $item) {
                $categoryId = isset($item['category']) ? DB::table('article_categories')->where('slug', (string) $item['category'])->value('id') : null;
                $tally('Health articles', $this->upsert('health_articles', $item, ['article_category_id' => $categoryId])[1]);
            }

            foreach ($this->list($data, 'team_members') as $item) {
                $tally('Team profiles', $this->upsert('team_members', $item)[1]);
            }

            $settings = array_intersect_key((array) ($data['settings'] ?? []), array_flip([...Settings::GENERAL_KEYS, ...Settings::SYSTEM_KEYS, ...self::SETTING_KEYS]));
            $settings = array_map(fn ($v) => is_scalar($v) || $v === null ? $v : null, $settings);
            foreach (self::SETTING_KEYS as $imageKey) {
                if (array_key_exists($imageKey, $settings)) {
                    $settings[$imageKey] = $this->imagePath($settings[$imageKey]);
                }
            }
            if ($settings) {
                $this->settings->put($settings);
                $tally('Settings', false);
            }
        });

        $this->settings->flush();

        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function list(array $data, string $key): array
    {
        $items = $data[$key] ?? [];
        if (! is_array($items)) {
            throw new RuntimeException("The section \"{$key}\" in the file is not valid.");
        }

        return array_values(array_filter($items, 'is_array'));
    }

    private function importMedia(array $item, \Closure $tally): void
    {
        $path = (string) ($item['path'] ?? '');
        $mime = (string) ($item['mime_type'] ?? '');
        $folder = (string) ($item['folder'] ?? '');
        if (! preg_match(self::MEDIA_PATH, $path) || ! isset(self::MEDIA_TYPES[$mime]) || ! array_key_exists($folder, config('innes.media_folders'))) {
            throw new RuntimeException('An image in the file has an invalid name or type.');
        }
        $bytes = base64_decode((string) ($item['data'] ?? ''), true);
        $size = $bytes === false ? false : @getimagesizefromstring($bytes);
        if (! $size || ($size['mime'] ?? null) !== $mime) {
            throw new RuntimeException('An image in the file ('.Str::limit((string) ($item['original_name'] ?? $path), 60).') is damaged or is not a picture.');
        }

        $values = [
            'folder' => $folder,
            'original_name' => Str::limit((string) ($item['original_name'] ?? basename($path)), 250, ''),
            'alt_text' => isset($item['alt_text']) ? Str::limit((string) $item['alt_text'], 250, '') : null,
            'mime_type' => $mime,
            'size_bytes' => strlen($bytes),
            'width' => $size[0],
            'height' => $size[1],
            'updated_at' => now(),
        ];
        $existing = DB::table('media')->where('path', $path)->value('id');
        if ($existing) {
            DB::table('media')->where('id', $existing)->update($values);
            DB::table('media_contents')->where('media_id', $existing)->delete();
            $id = $existing;
        } else {
            $id = DB::table('media')->insertGetId($values + ['path' => $path, 'created_at' => now()]);
        }
        DB::table('media_contents')->insert(['media_id' => $id, 'data' => $bytes]);
        $tally('Media library images', ! $existing);
    }

    /**
     * Inserts or updates a row identified by its slug.
     *
     * @return array{0: int, 1: bool} [id, created]
     */
    private function upsert(string $table, array $item, array $extra = []): array
    {
        $slug = Str::slug((string) ($item['slug'] ?? ''));
        if ($slug === '') {
            throw new RuntimeException("An item in \"{$table}\" has no web address (slug).");
        }

        $values = $extra;
        foreach (self::COLUMNS[$table] as $column) {
            if ($column === 'slug') {
                continue;
            }
            $value = $item[$column] ?? null;
            $values[$column] = match (true) {
                in_array($column, self::BOOLEANS, true) => (bool) $value,
                $column === 'display_order' => max(0, (int) $value),
                $column === 'price' => is_numeric($value) ? round((float) $value, 2) : null,
                $column === 'availability_status' => ProductAvailability::tryFrom((string) $value)?->value ?? ProductAvailability::Enquire->value,
                $column === 'status' => ArticleStatus::tryFrom((string) $value)?->value ?? ArticleStatus::Draft->value,
                str_ends_with($column, 'image_path') => $this->imagePath($value),
                is_scalar($value) => (string) $value,
                default => null,
            };
        }
        foreach (['name', 'title', 'body'] as $required) {
            if (array_key_exists($required, $values) && blank($values[$required])) {
                throw new RuntimeException("The item \"{$slug}\" in \"{$table}\" is missing its {$required}.");
            }
        }
        $values['updated_at'] = now();
        if (in_array($table, self::SOFT_DELETES, true)) {
            $values['deleted_at'] = null;
        }

        $existing = DB::table($table)->where('slug', $slug)->value('id');
        if ($existing) {
            DB::table($table)->where('id', $existing)->update($values);

            return [(int) $existing, false];
        }

        return [(int) DB::table($table)->insertGetId($values + ['slug' => $slug, 'created_at' => now()]), true];
    }

    /** Only images from the media library or the bundled placeholders may be referenced. */
    private function imagePath(mixed $value): ?string
    {
        $value = is_string($value) ? $value : '';
        if (preg_match(self::MEDIA_PATH, $value) || preg_match('#^images/[A-Za-z0-9._-]+$#', $value)) {
            return $value;
        }

        return null;
    }

    private function row(object $row, string $table): array
    {
        $out = [];
        foreach (self::COLUMNS[$table] as $column) {
            $value = $row->{$column} ?? null;
            $out[$column] = match (true) {
                in_array($column, self::BOOLEANS, true) => (bool) $value,
                $column === 'display_order' => (int) $value,
                $column === 'price' => $value === null ? null : (string) $value,
                default => $value,
            };
        }

        return $out;
    }

    private function time(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^(\d{2}):(\d{2})/', $value, $m) ? "{$m[1]}:{$m[2]}:00" : null;
    }

    private function isDemo(object $row): bool
    {
        foreach (Arr::except((array) $row, ['id', 'created_at', 'updated_at']) as $value) {
            if (is_string($value) && Str::contains($value, self::DEMO_MARKERS)) {
                return true;
            }
        }

        return false;
    }
}
