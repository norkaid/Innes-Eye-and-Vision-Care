<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\ArticleStatus;
use App\Enums\EnquiryStatus;
use App\Enums\ProductAvailability;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\ArticleCategory;
use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\HealthArticle;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DEMO DATA — REPLACE BEFORE PRODUCTION.
 *
 * Everything created here is sample content for local development and review only.
 * Branch addresses, phone numbers, hours, services, products and articles are NOT
 * verified business information.
 */
class DemoContentSeeder extends Seeder
{
    public const DEMO_PASSWORD_ENV = 'DEMO_USER_PASSWORD';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo content must never be seeded in production.');
        }

        $password = env(self::DEMO_PASSWORD_ENV, 'Demo-Password-2026');

        $this->staff($password);
        [$accra, $kumasi] = $this->branches();
        $services = $this->services([$accra, $kumasi]);
        $this->products();
        $this->articles();
        $this->sampleRequests($accra, $kumasi, $services, $password);
    }

    private function staff(string $password): void
    {
        $accounts = [
            ['super-admin', 'Demo', 'SuperAdmin', 'superadmin@innes.test'],
            ['administrator', 'Demo', 'Administrator', 'admin@innes.test'],
            ['receptionist', 'Demo', 'Receptionist', 'reception@innes.test'],
            ['content-manager', 'Demo', 'ContentManager', 'content@innes.test'],
        ];
        foreach ($accounts as [$role, $first, $last, $email]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill(['first_name' => $first, 'last_name' => $last, 'password' => $password]);
            $user->account_type = User::TYPE_STAFF;
            $user->is_active = true;
            $user->save();
            $user->roles()->sync([Role::where('slug', $role)->value('id')]);
        }
    }

    /** @return array{0: Branch, 1: Branch} */
    private function branches(): array
    {
        $make = function (string $name, string $city, string $region, int $order) {
            $branch = Branch::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'address' => 'DEMO ADDRESS – replace with verified address',
                'city' => $city,
                'region' => $region,
                'phone' => '+233 00 000 0000',
                'email' => null,
                'description' => 'DEMO DATA — REPLACE BEFORE PRODUCTION. Branch description, address, phone number and opening hours are placeholders awaiting confirmation from the centre.',
                'is_active' => true,
                'display_order' => $order,
            ]);
            foreach (Branch::DAYS as $day => $_) {
                $closed = $day === 7;
                $branch->openingHours()->updateOrCreate(['day_of_week' => $day], [
                    'is_closed' => $closed,
                    'opens_at' => $closed ? null : '08:30',
                    'closes_at' => $closed ? null : ($day === 6 ? '13:00' : '17:00'),
                ]);
            }

            return $branch;
        };

        return [
            $make('Accra Branch (Demo)', 'Accra', 'Greater Accra', 1),
            $make('Kumasi Branch (Demo)', 'Kumasi', 'Ashanti', 2),
        ];
    }

    private function services(array $branches): array
    {
        $note = "\n\nDEMO DATA — REPLACE BEFORE PRODUCTION. This description is sample text and has not been approved by the centre.";
        $items = [
            ['Comprehensive Eye Examination', 'A thorough check of your vision and the health of your eyes.', 'A comprehensive eye examination typically includes a discussion of your eye-health history, vision tests and an examination of the front and back of the eye. Your optometrist will explain the results and any next steps.', 'Bring your current glasses or contact lenses and a list of any questions.'],
            ['Vision Assessment', 'Checks how clearly you see at different distances.', 'A vision assessment measures how well you see near and far and whether glasses or lenses could help.', null],
            ['Glaucoma Screening', 'Screening tests that can help detect signs of glaucoma.', 'Glaucoma screening may include measuring eye pressure and examining the optic nerve. Your optometrist will advise whether further tests are needed.', 'Let the team know if anyone in your family has glaucoma.'],
            ['Contact Lens Fitting', 'Measurement and fitting for comfortable contact lenses.', 'A contact lens fitting measures your eyes to find suitable lenses and includes guidance on safe handling and care.', 'If you already wear lenses, please bring your lens details.'],
            ['Geriatric Eye Care', 'Eye care focused on the needs of older adults.', 'Eye-care appointments for older adults focus on age-related changes in vision and eye health.', null],
            ['Children\'s Eye Test', 'Friendly vision checks for children.', 'Children\'s eye tests are adapted to the child\'s age and help identify vision problems that may affect learning.', null],
        ];
        $services = [];
        foreach ($items as $i => [$name, $short, $desc, $prep]) {
            $service = Service::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'short_description' => $short,
                'description' => $desc.$note,
                'preparation' => $prep,
                'is_published' => true,
                'display_order' => $i + 1,
            ]);
            // Every demo service at Accra; the first four at Kumasi.
            $service->branches()->sync($i < 4 ? [$branches[0]->id, $branches[1]->id] : [$branches[0]->id]);
            $services[] = $service;
        }
        // One unpublished draft to demonstrate draft handling.
        Service::updateOrCreate(['slug' => 'low-vision-support'], [
            'name' => 'Low Vision Support (draft)',
            'short_description' => 'Draft service awaiting confirmation – not visible publicly.',
            'is_published' => false,
            'display_order' => 99,
        ]);

        return $services;
    }

    private function products(): void
    {
        $categories = [];
        foreach ([['Optical Frames', 1], ['Sunglasses', 2], ['Contact Lenses', 3], ['Lenses', 4]] as [$name, $order]) {
            $categories[$name] = ProductCategory::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'is_active' => true, 'display_order' => $order,
                'description' => 'DEMO DATA — REPLACE BEFORE PRODUCTION.',
            ]);
        }

        $items = [
            ['Optical Frames', 'Classic Round Frame (Demo)', 'Demo Brand A', 'Lightweight round frame in a timeless shape.'],
            ['Optical Frames', 'Rectangular Acetate Frame (Demo)', 'Demo Brand B', 'Bold rectangular frame in durable acetate.'],
            ['Optical Frames', 'Slim Metal Frame (Demo)', 'Demo Brand A', 'Minimal metal frame with adjustable nose pads.'],
            ['Optical Frames', 'Cat-Eye Frame (Demo)', 'Demo Brand C', 'Elegant cat-eye silhouette.'],
            ['Optical Frames', 'Children\'s Flexible Frame (Demo)', 'Demo Brand D', 'Flexible frame designed for active children.'],
            ['Optical Frames', 'Rimless Frame (Demo)', 'Demo Brand B', 'Discreet rimless design.'],
            ['Sunglasses', 'Aviator Sunglasses (Demo)', 'Demo Brand E', 'Classic aviator style sunglasses.'],
            ['Sunglasses', 'Wayfarer-Style Sunglasses (Demo)', 'Demo Brand E', 'Everyday sunglasses with a square shape.'],
            ['Contact Lenses', 'Daily Disposable Lenses (Demo)', 'Demo Brand F', 'Single-use lenses. Fitting required.'],
            ['Contact Lenses', 'Monthly Lenses (Demo)', 'Demo Brand F', 'Reusable monthly lenses. Fitting required.'],
            ['Lenses', 'Single Vision Lenses (Demo)', null, 'Lenses for one viewing distance.'],
            ['Lenses', 'Blue-Light Filtering Lenses (Demo)', null, 'Lens coating option. Ask in branch for details.'],
            ['Lenses', 'Progressive Lenses (Demo)', null, 'Lenses for near, intermediate and distance vision.'],
        ];
        foreach ($items as $i => [$cat, $name, $brand, $short]) {
            $product = Product::firstOrNew(['slug' => Str::slug($name)]);
            $product->fill([
                'product_category_id' => $categories[$cat]->id,
                'name' => $name,
                'brand' => $brand,
                'short_description' => $short,
                'description' => "DEMO DATA — REPLACE BEFORE PRODUCTION.\nThis is a sample catalogue item. No price or stock information is implied.",
                'price' => null,
                'availability_status' => $i % 5 === 4 ? ProductAvailability::Available : ProductAvailability::Enquire,
                'is_published' => true,
            ]);
            $product->save();
        }
    }

    private function articles(): void
    {
        $general = ArticleCategory::firstOrCreate(['slug' => 'eye-care-tips'], ['name' => 'Eye-care tips']);
        $conditions = ArticleCategory::firstOrCreate(['slug' => 'eye-conditions'], ['name' => 'Eye conditions']);

        $articles = [
            [$general, 'How often should you have an eye test?', 'Regular eye tests help keep track of your vision and eye health.', "Regular eye examinations help detect changes in vision and eye health early.\n\n## Why regular checks matter\n\nMany eye conditions develop gradually and may not cause noticeable symptoms at first.\n\n## How often?\n\nYour optometrist will recommend how often you should be seen based on your age and eye health.\n\n> DEMO DATA — REPLACE BEFORE PRODUCTION. This article is sample text and has not been clinically reviewed by the centre.", 10],
            [$general, 'Protecting your eyes from the sun', 'Simple ways to protect your eyes outdoors.', "Bright sunlight can be uncomfortable and ultraviolet (UV) light can affect the eyes.\n\n- Wear sunglasses that offer UV protection.\n- Use a wide-brimmed hat.\n- Never look directly at the sun.\n\n> DEMO DATA — REPLACE BEFORE PRODUCTION.", 20],
            [$conditions, 'Understanding glaucoma screening', 'What glaucoma screening involves and why it matters.', "Glaucoma is a group of conditions that can damage the optic nerve.\n\n## What happens during screening?\n\nScreening may include measuring eye pressure and examining the optic nerve.\n\n> DEMO DATA — REPLACE BEFORE PRODUCTION. Content requires clinical review.", 30],
            [$general, 'Screen time and tired eyes', 'Tips to reduce eye strain when using screens.', "Long periods of screen use can make eyes feel tired.\n\nTry the **20-20-20** habit: every 20 minutes, look at something 20 feet away for 20 seconds.\n\n> DEMO DATA — REPLACE BEFORE PRODUCTION.", 40],
        ];
        foreach ($articles as [$cat, $title, $excerpt, $body, $daysAgo]) {
            $a = HealthArticle::firstOrNew(['slug' => Str::slug($title)]);
            $a->fill(['title' => $title, 'excerpt' => $excerpt, 'body' => $body, 'article_category_id' => $cat->id, 'author_display_name' => 'Demo Author']);
            $a->status = ArticleStatus::Published;
            $a->published_at = now()->subDays($daysAgo);
            $a->save();
        }
        $draft = HealthArticle::firstOrNew(['slug' => 'draft-dry-eye-article']);
        $draft->fill(['title' => 'Dry eye: causes and relief (draft)', 'excerpt' => 'Draft awaiting clinical review.', 'body' => 'Draft content – not visible publicly.', 'article_category_id' => $conditions->id]);
        $draft->status = ArticleStatus::Draft;
        $draft->save();
    }

    private function sampleRequests(Branch $accra, Branch $kumasi, array $services, string $password): void
    {
        if (Appointment::exists()) {
            return;
        }
        $customer = User::firstOrNew(['email' => 'customer@innes.test']);
        $customer->fill(['first_name' => 'Demo', 'last_name' => 'Customer', 'phone' => '+233 00 000 0001', 'password' => $password]);
        $customer->account_type = User::TYPE_CUSTOMER;
        $customer->is_active = true;
        $customer->save();

        $refs = app(AppointmentService::class);
        $nextWeekday = fn (int $days) => tap(today()->addDays($days), function ($d) {
            while ($d->dayOfWeekIso >= 6) {
                $d->addDay();
            }
        });
        $make = function (array $attrs, AppointmentStatus $status, ?User $user = null) use ($refs) {
            $a = new Appointment($attrs);
            $a->reference_code = $refs->generateReference();
            $a->status = $status;
            $a->status_updated_at = now();
            $a->user_id = $user?->id;
            if ($status === AppointmentStatus::Confirmed) {
                $a->confirmed_start_at = $a->requestedStart();
                $a->confirmed_end_at = $a->requestedStart()->addMinutes(30);
            }
            $a->save();
            $a->histories()->save(new AppointmentStatusHistory(['new_status' => AppointmentStatus::Pending->value, 'internal_note' => 'Request submitted online (demo)']));
            if ($status !== AppointmentStatus::Pending) {
                $a->histories()->save(new AppointmentStatusHistory(['old_status' => 'pending', 'new_status' => $status->value, 'internal_note' => 'Demo status change']));
            }
        };

        $make(['branch_id' => $accra->id, 'service_id' => $services[0]->id, 'customer_name' => 'Demo Customer', 'customer_email' => 'customer@innes.test', 'customer_phone' => '+233 00 000 0001', 'requested_date' => $nextWeekday(3)->toDateString(), 'requested_time' => '10:00', 'customer_note' => 'Demo note.'], AppointmentStatus::Pending, $customer);
        $make(['branch_id' => $accra->id, 'service_id' => $services[3]->id, 'customer_name' => 'Demo Customer', 'customer_email' => 'customer@innes.test', 'customer_phone' => '+233 00 000 0001', 'requested_date' => $nextWeekday(6)->toDateString(), 'requested_time' => '11:30'], AppointmentStatus::Confirmed, $customer);
        $make(['branch_id' => $kumasi->id, 'service_id' => $services[1]->id, 'customer_name' => 'Guest Example (Demo)', 'customer_email' => 'guest@example.com', 'customer_phone' => '+233 00 000 0002', 'requested_date' => $nextWeekday(4)->toDateString(), 'requested_time' => '09:00'], AppointmentStatus::Pending);
        $make(['branch_id' => $kumasi->id, 'service_id' => $services[2]->id, 'customer_name' => 'Another Guest (Demo)', 'customer_email' => 'guest2@example.com', 'customer_phone' => '+233 00 000 0003', 'requested_date' => $nextWeekday(5)->toDateString(), 'requested_time' => '14:00'], AppointmentStatus::Declined);

        $e = new Enquiry(['name' => 'Guest Example (Demo)', 'email' => 'guest@example.com', 'subject' => 'Product enquiry: Classic Round Frame (Demo)', 'message' => 'Hello, is this frame available at the Accra branch? (Demo enquiry)']);
        $e->status = EnquiryStatus::New;
        $e->product_id = Product::where('slug', 'classic-round-frame-demo')->value('id');
        $e->save();
        $e2 = new Enquiry(['name' => 'Demo Customer', 'email' => 'customer@innes.test', 'subject' => 'Opening hours question', 'message' => 'Are you open on public holidays? (Demo enquiry)']);
        $e2->status = EnquiryStatus::InProgress;
        $e2->user_id = $customer->id;
        $e2->save();
    }
}
